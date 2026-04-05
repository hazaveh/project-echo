<?php

it('captures an email address and falls back to the source when no host is present', function () {
    $response = $this->postJson('/api/captureEmail', [
        'email' => 'jane@example.com',
        'source' => 'example.com/path',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('captured_emails', [
        'email' => 'jane@example.com',
        'source' => 'example.com/path',
        'domain' => 'example.com/path',
    ]);
});

it('captures an email address and stores the host for a full url source', function () {
    $response = $this->postJson('/api/captureEmail', [
        'email' => 'john@example.com',
        'source' => 'https://sub.example.com/newsletter',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('captured_emails', [
        'email' => 'john@example.com',
        'source' => 'https://sub.example.com/newsletter',
        'domain' => 'sub.example.com',
    ]);
});
