# Project Echo API Reference

This document covers all currently supported API endpoints in this project.

Base URL (local Herd):

```text
https://echo.test/api
```

## Authentication

- All routes are inside `AuthorizeTokenMiddleware` (`routes/api.php`).
- Current middleware behavior is pass-through (no token validation yet), so endpoints are publicly reachable unless middleware is updated.

## General Notes

- Content type for `POST` endpoints: `application/json`.
- Some endpoints cache responses for short periods.
- Error shapes are not globally unified yet; each endpoint may return a different structure.

---

## 1) Jambase Artist Lookup

### GET `/jambase/artists/spotify/{spotifyId}`

Fetches artist + upcoming events from Jambase using Spotify artist ID.

#### Path params

- `spotifyId` (string, required)

#### Success response (200)

```json
{
  "name": "Artist Name",
  "identifier": "source:artist-id",
  "events": [
    {
      "id": "event-id",
      "name": "Event Name",
      "type": "MusicEvent",
      "startDate": "2026-05-01T19:00:00Z",
      "endDate": "2026-05-01T23:00:00Z",
      "status": "EventScheduled",
      "address": {
        "name": "Venue Name",
        "streetAddress": "Street",
        "locality": "City",
        "postalCode": "12345",
        "country": "Germany",
        "countryCode": "DE",
        "lat": "52.52",
        "lng": "13.40"
      }
    }
  ]
}
```

#### Failure response

- Returns upstream Jambase body/status directly when Jambase fails.

---

## 2) Bandcamp Album Search

### GET `/bandcamp/albums/{search}`

Searches Bandcamp for album releases.

#### Path params

- `search` (string, required)

#### Success response (200)

```json
[
  {
    "name": "Requiem For Hell",
    "artist": "MONO",
    "url": "https://monoofjapan.bandcamp.com/album/requiem-for-hell"
  }
]
```

#### Notes

- Cached for 120 seconds per search term.

---

## 3) Bandcamp Artist Albums

### GET `/bandcamp/artists/{username}/albums`

Returns artist profile details + release list from `/{username}.bandcamp.com/music`.

#### Path params

- `username` (string, required)

#### Success response (200)

```json
{
  "artist": {
    "name": "MONO",
    "location": "Tokyo, Japan"
  },
  "bandcamp_url": "https://monoofjapan.bandcamp.com",
  "username": "monoofjapan",
  "photo": "https://f4.bcbits.com/img/...jpg",
  "albums": [
    {
      "name": "Requiem For Hell",
      "type": "album",
      "slug": "requiem-for-hell",
      "url": "https://monoofjapan.bandcamp.com/album/requiem-for-hell"
    },
    {
      "name": "Some Single",
      "type": "single",
      "slug": "some-single",
      "url": "https://monoofjapan.bandcamp.com/track/some-single"
    }
  ]
}
```

#### Notes

- `type` is normalized to `album` or `single` (`track` maps to `single`).
- Cached for 120 seconds per username.

---

## 4) Bandcamp Release Details (Auto Type)

### GET `/bandcamp/artists/{username}/albums/{albumSlug}`

Returns release details by slug; tries `/album/{slug}` then `/track/{slug}`.

#### Path params

- `username` (string, required)
- `albumSlug` (string, required)

#### Success response (200)

```json
{
  "album": "Requiem For Hell",
  "artist": "MONO",
  "bandcamp_url": "https://monoofjapan.bandcamp.com/album/requiem-for-hell",
  "username": "monoofjapan",
  "type": "album",
  "photo": "https://f4.bcbits.com/img/...jpg",
  "track_count": 5,
  "tracks": [
    {
      "number": 1,
      "title": "Death In Rebirth",
      "length": "08:05",
      "length_seconds": 485
    }
  ]
}
```

#### Failure response

- `404 Not Found` when neither album nor track slug resolves.

#### Notes

- Cached for 120 seconds.

---

## 5) Bandcamp Release Details (Explicit Type)

### GET `/bandcamp/artists/{username}/albums/{releaseType}/{albumSlug}`

Returns release details using explicit release type.

#### Path params

- `username` (string, required)
- `releaseType` (string, required): `album`, `track`, or `single`
- `albumSlug` (string, required)

#### Success response

Same schema as endpoint #4.

#### Failure response

- `404 Not Found` for invalid `releaseType` or unresolved slug.

#### Notes

- `single` is treated as `track` internally.
- For track pages that do not contain a track table, the API falls back to Bandcamp JSON-LD and still returns:
  - `tracks[0].title`
  - `tracks[0].length`
  - `tracks[0].length_seconds`
- Cached for 120 seconds.

---

## 6) Capture Email

### POST `/captureEmail`

Stores a captured email with source URL/domain.

#### Request body

```json
{
  "email": "user@example.com",
  "source": "https://example.com/page"
}
```

#### Validation

- `email`: required, valid email
- `source`: required, string

#### Success response (200)

```json
{
  "status": "ok"
}
```

#### Notes

- Uses `firstOrCreate` on `(email, source, domain)` combination.

---

## 7) Upsert Artist

### POST `/artists/upsert`

Creates or updates an artist keyed by `prn_artist_id`.

#### Request body

```json
{
  "prn_artist_id": 123,
  "name": "MONO",
  "spotify": "2qS6gk2y1w...",
  "instagram": "monoofjapan",
  "twitter": "monoofjapan",
  "facebook": "monoofjapan",
  "youtube": "UCxxxx",
  "homepage": "https://monoofjapan.com",
  "apple": "artist-id"
}
```

#### Validation

- `prn_artist_id`: required, integer, min 1
- `name`: required, string, max 255
- optional nullable strings: `spotify`, `instagram`, `twitter`, `facebook`, `homepage`, `apple`, `youtube`

#### Success response (200)

```json
{
  "status": "ok",
  "artist": {
    "id": 1,
    "prn_artist_id": 123,
    "name": "MONO",
    "spotify": "...",
    "instagram": "...",
    "twitter": "...",
    "facebook": "...",
    "youtube": "...",
    "homepage": "...",
    "apple_music": "...",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

#### Field mapping

- Request `apple` maps to DB field `apple_music`.

---

## 8) List Mapped Concert Artists

### GET `/concerts/artists/mapped`

Returns PRN artist IDs that currently have at least one ticket provider mapping.

#### Success response (200)

```json
{
  "ok": true,
  "count": 2,
  "prn_artist_ids": [101, 202]
}
```

---

## 9) Search Concerts for Artist

### GET `/artists/{prnArtistId}/concerts`

Fetches concerts for a PRN artist across configured providers and returns consolidated performances.

#### Path params

- `prnArtistId` (integer, required)

#### Success response (200)

```json
{
  "ok": true,
  "prn_artist_id": 123,
  "synced_at": "2026-02-13T21:00:00Z",
  "performances": [
    {
      "identifier": "echo:perf:...",
      "name": "MONO Live",
      "type": "MusicEvent",
      "start_date": "2026-03-12T19:00:00Z",
      "end_date": "2026-03-12T23:00:00Z",
      "status": "scheduled",
      "address_name": "Columbia Theater",
      "address": "Tempelhofer Ufer 17",
      "city": "Berlin",
      "country": "Germany",
      "country_code": "DE",
      "postal_code": "10963",
      "latitude": 52.4937,
      "longitude": 13.3853,
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
```

#### Not found response (404)

```json
{
  "ok": false,
  "error": "artist_not_found",
  "message": "Artist not found."
}
```

#### Notes

- If artist exists but has no provider mappings, returns `ok: true` with empty `performances`.
- Successful provider responses are cached for 60 seconds per `prn_artist_id`.
- Responses are not cached if any provider call fails.

---

## Quick cURL Examples

```bash
# Bandcamp artist releases
curl "https://echo.test/api/bandcamp/artists/monoofjapan/albums"

# Bandcamp explicit single details
curl "https://echo.test/api/bandcamp/artists/primatatheband/albums/single/tebang"

# Upsert artist
curl -X POST "https://echo.test/api/artists/upsert" \
  -H "Content-Type: application/json" \
  -d '{"prn_artist_id":123,"name":"MONO"}'

# Concerts by PRN artist id
curl "https://echo.test/api/artists/123/concerts"
```
