<?php

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('show', function () {
    it('returns 401 when no user is authenticated', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->getJson("/api/ai-requests/{$aiRequest->id}");

        $response->assertStatus(401);
    });

    it('forbids fetching a request that belongs to another user', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson("/api/ai-requests/{$aiRequest->id}");

        $response->assertStatus(403);
    });

    it('returns the payload for the owner', function () {
        $aiRequest = AiRequest::factory()->create([
            'payload' => ['model' => 'gpt-5-nano', 'instructions' => 'Составь дорожную карту.'],
        ]);

        $response = $this->actingAs($aiRequest->user)
            ->getJson("/api/ai-requests/{$aiRequest->id}");

        $response->assertOk();
        $response->assertJsonPath('payload.model', 'gpt-5-nano');
        $response->assertJsonPath('payload.instructions', 'Составь дорожную карту.');
    });
});

describe('complete', function () {
    it('returns 401 when no user is authenticated', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->postJson("/api/ai-requests/{$aiRequest->id}/complete", [
            'response' => ['output_text' => 'hi'],
        ]);

        $response->assertStatus(401);
    });

    it('forbids completing a request that belongs to another user', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->postJson("/api/ai-requests/{$aiRequest->id}/complete", [
                'response' => ['output_text' => 'hi'],
            ]);

        $response->assertStatus(403);
        expect($aiRequest->fresh()->status)->toBe('pending');
    });

    it('rejects a payload with neither a response nor an error', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->actingAs($aiRequest->user)
            ->postJson("/api/ai-requests/{$aiRequest->id}/complete", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['response', 'error']);
    });

    it('marks the request completed and stores the relayed response', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->actingAs($aiRequest->user)
            ->postJson("/api/ai-requests/{$aiRequest->id}/complete", [
                'response' => ['output_text' => 'Ответ из браузера.'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'completed');

        $aiRequest->refresh();
        expect($aiRequest->status)->toBe('completed');
        expect($aiRequest->response)->toBe(['output_text' => 'Ответ из браузера.']);
    });

    it('marks the request failed and stores the relayed error', function () {
        $aiRequest = AiRequest::factory()->create();

        $response = $this->actingAs($aiRequest->user)
            ->postJson("/api/ai-requests/{$aiRequest->id}/complete", [
                'error' => 'Сеть недоступна.',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'failed');

        $aiRequest->refresh();
        expect($aiRequest->status)->toBe('failed');
        expect($aiRequest->error)->toBe('Сеть недоступна.');
    });

    it('rejects completing a request that was already processed', function () {
        $aiRequest = AiRequest::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($aiRequest->user)
            ->postJson("/api/ai-requests/{$aiRequest->id}/complete", [
                'response' => ['output_text' => 'too late'],
            ]);

        $response->assertStatus(409);
    });
});
