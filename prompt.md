# Design Specification — Provider-Agnostic Concert Search API (Echo)

This file is a **complete design specification and Codex prompt**.  
Everything in this document is intentional and must be implemented exactly as described.  
There is no text outside this Markdown file.

---

## 1. GOAL

Refactor the existing concert search system so that:

-   The API is provider-agnostic
-   The controller is thin
-   All logic lives in Action classes
-   Multiple ticket providers can be queried and consolidated
-   The API always returns JSON, never HTML
-   Ticket offers from multiple providers are merged cleanly
-   The system is robust enough to add Eventim later without refactors
-   All provider calls (success and failure) are logged with full raw payloads
-   The implementation is covered by tests

---

## 2. API ROUTE DESIGN

### Remove provider from URL

Old route (remove or deprecate):

/api/artists/concerts/{provider}/{prn_artist_id}

New route (provider-agnostic):

GET /api/artists/{prn_artist_id}/concerts

This endpoint:

-   Looks up the artist
-   Determines which ticket providers are available
-   Queries all providers
-   Consolidates results
-   Returns a unified response

---

## 3. JSON-ONLY API RESPONSES (CRITICAL)

All responses must be JSON.  
Laravel HTML error pages must never be returned.

### Artist not found

HTTP status: 404

```json
{
  "ok": false,
  "error": "artist_not_found",
  "message": "Artist not found."
}

Rules:
	•	Do NOT use abort(404)
	•	Do NOT allow ModelNotFoundException to bubble
	•	Always return response()->json(…)

⸻

Artist exists but has no provider mappings

This is NOT an error.

HTTP status: 200

{
  "ok": true,
  "prn_artist_id": 25,
  "synced_at": "ISO8601",
  "performances": []
}


⸻

Provider failures
	•	Never throw
	•	Never return HTML
	•	Log the failure
	•	Continue processing other providers
	•	Final response must still be valid JSON

⸻

4. CONTROLLER RESPONSIBILITY

SearchConcertController must be orchestration only.

The controller may:
	•	Call Action classes
	•	Shape the final response
	•	Return JSON

The controller must NOT:
	•	Contain provider logic
	•	Contain merging logic
	•	Perform HTTP requests

⸻

5. ACTION-BASED ARCHITECTURE (REQUIRED)

All business logic must live in Action classes under app/Actions.

5.1 FindArtistByPrnIdAction

Path:

app/Actions/Artists/FindArtistByPrnIdAction.php

Method:

execute(int $prnArtistId): ?Artist

Returns null if artist does not exist.

⸻

5.2 ListArtistProviderMappingsAction

Path:

app/Actions/Concerts/ListArtistProviderMappingsAction.php

Method:

execute(Artist $artist): Collection

Returns all TicketProviderMapping entries for supported providers.

⸻

5.3 FetchConcertsFromProvidersAction

Path:

app/Actions/Concerts/FetchConcertsFromProvidersAction.php

Method:

execute(Artist $artist, Collection $mappings): array

Responsibilities:
	•	Iterate provider mappings
	•	Dispatch calls via provider registry
	•	Log every provider call (success or error)
	•	Never throw
	•	Return array of ProviderResult DTOs

⸻

5.4 ConsolidateConcertPerformancesAction

Path:

app/Actions/Concerts/ConsolidateConcertPerformancesAction.php

Method:

execute(int $prnArtistId, array $providerResults): array

Responsibilities:
	•	Generate deterministic canonical identifiers
	•	Merge performances across providers
	•	Merge ticket offers
	•	Prefer non-null fields
	•	Apply status priority
	•	Sort by start_date ascending

⸻

5.5 LogProviderConcertSyncAction

Path:

app/Actions/Concerts/LogProviderConcertSyncAction.php

Writes rows into the provider sync log table for both success and error cases.

⸻

6. PROVIDER ABSTRACTION

6.1 Provider Interface

Path:

app/Services/ConcertProviders/ConcertProviderInterface.php

Methods:

providerKey(): string
providerName(): string
supports(TicketProviderMapping $mapping): bool
fetchConcerts(Artist $artist, TicketProviderMapping $mapping): ProviderResult


⸻

6.2 ProviderResult DTO

Contains:
	•	ok (bool)
	•	status_code (int|null)
	•	payload (array|null) — raw provider JSON
	•	error_message (string|null)
	•	performances (array) — normalized performances with exactly one offer

⸻

6.3 Ticketmaster Provider

Path:

app/Services/ConcertProviders/TicketmasterConcertProvider.php

Rules:
	•	Use Ticketmaster Discovery API events endpoint
	•	Always include locale=*
	•	Convert events to normalized performances
	•	Each performance includes exactly one offer:
	•	provider
	•	provider_name
	•	url (affiliate-wrapped)
	•	Do NOT generate canonical identifiers here

⸻

6.4 Eventim Provider (Stub)

Path:

app/Services/ConcertProviders/EventimConcertProvider.php

Behavior:
	•	Return ProviderResult with ok=false
	•	error_message = “Not implemented”
	•	Must be registered in provider registry

⸻

6.5 Provider Registry

Path:

app/Services/ConcertProviders/ConcertProviderRegistry.php

Responsibilities:
	•	Register all providers
	•	Resolve provider by key
	•	Return all providers

⸻

7. PERFORMANCE CONSOLIDATION RULES

Canonical Identifier

Do NOT use provider event IDs.

Generate identifier using deterministic fingerprint:

echo:perf:sha1(
  prn_artist_id +
  start_date +
  country_code +
  normalized_city +
  normalized_venue_name
)


⸻

Merging Rules
	•	Merge by canonical identifier
	•	Merge offers (dedupe by provider)
	•	Prefer non-null values
	•	Status priority:
	1.	cancelled / postponed
	2.	soldout
	3.	onsale
	4.	scheduled

⸻

8. FINAL API RESPONSE STRUCTURE

{
  "ok": true,
  "prn_artist_id": 25,
  "synced_at": "ISO8601",
  "performances": [
    {
      "identifier": "echo:perf:abc123",
      "name": "Artist Name",
      "type": "concert",
      "start_date": "YYYY-MM-DD",
      "end_date": null,
      "status": "onsale",
      "address_name": "...",
      "address": "...",
      "city": "...",
      "country": "...",
      "country_code": "...",
      "postal_code": "...",
      "latitude": 0.0,
      "longitude": 0.0,
      "offers": [
        {
          "provider": "ticketmaster",
          "provider_name": "Ticketmaster",
          "url": "https://..."
        }
      ]
    }
  ]
}


⸻

9. PROVIDER SYNC LOG TABLE

Migration name:

concert_provider_sync_logs

Fields:
	•	id
	•	prn_artist_id (indexed)
	•	artist_id (nullable, indexed)
	•	provider (indexed)
	•	provider_artist_id (nullable)
	•	ok (boolean)
	•	status_code (int nullable)
	•	result_count (int nullable)
	•	duration_ms (int nullable)
	•	error_message (text nullable)
	•	response_payload (longText nullable, raw JSON)
	•	timestamps

Rules:
	•	Log every provider call
	•	Log success and failure
	•	Store raw JSON payload
	•	Disk usage is acceptable

⸻

10. TESTING REQUIREMENTS (MANDATORY)

Feature Tests
	•	JSON 404 when artist not found
	•	Empty performances when no provider mappings
	•	Successful Ticketmaster response
	•	Provider error still returns JSON
	•	Sync log written for success and error

Unit Tests
	•	Performance consolidation
	•	Deterministic identifier generation
	•	Offer merging
	•	Status priority

Use Http::fake() for provider calls.

⸻

11. CONSTRAINTS
	•	No HTML responses
	•	No provider logic in controller
	•	All logic via Action classes
	•	Eventim not implemented yet but must be pluggable
	•	Keep response minimal and stable

⸻

END OF DESIGN SPECIFICATION

```
