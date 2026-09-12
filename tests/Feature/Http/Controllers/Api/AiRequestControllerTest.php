<?php

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('pending', function () {
    it('returns 401 when no user is authenticated', function () {
        $response = $this->getJson('/api/ai-requests/pending');

        $response->assertStatus(401);
    });

    it('returns only the pending requests belonging to the current user', function () {
        $user = User::factory()->create();

        $mine = AiRequest::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        AiRequest::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        AiRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->getJson('/api/ai-requests/pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
        $response->assertJsonPath('data.0.payload', $mine->payload);
    });

    it('claims returned requests so a second poll does not pick them up again', function () {
        $user = User::factory()->create();
        $aiRequest = AiRequest::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)->getJson('/api/ai-requests/pending')->assertJsonCount(1, 'data');

        expect($aiRequest->fresh()->status)->toBe('processing');

        $this->actingAs($user)->getJson('/api/ai-requests/pending')->assertJsonCount(0, 'data');
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
        $aiRequest = AiRequest::factory()->create(['status' => 'processing']);

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
        $aiRequest = AiRequest::factory()->create(['status' => 'processing']);

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
