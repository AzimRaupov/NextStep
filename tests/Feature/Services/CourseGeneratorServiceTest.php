<?php

use App\Models\AiRequest;
use App\Models\User;
use App\Services\CourseGeneratorService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Responses\Responses\CreateResponse;

uses(LazilyRefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function fakeOpenAiTextAttributes(string $text): array
{
    return [
        'id' => 'resp_test',
        'object' => 'response',
        'created_at' => time(),
        'status' => 'completed',
        'error' => null,
        'incomplete_details' => null,
        'instructions' => null,
        'model' => 'gpt-5-nano',
        'output' => [
            [
                'type' => 'message',
                'id' => 'msg_test',
                'status' => 'completed',
                'role' => 'assistant',
                'content' => [
                    ['type' => 'output_text', 'text' => $text, 'annotations' => []],
                ],
            ],
        ],
        'parallel_tool_calls' => true,
        'previous_response_id' => null,
        'store' => true,
        'temperature' => 1.0,
        'tool_choice' => 'auto',
        'tools' => [],
        'top_p' => 1.0,
        'truncation' => 'disabled',
        'usage' => null,
        'user' => null,
        'metadata' => [],
    ];
}

it('calls OpenAI directly when AI_REQUEST_MODE is server', function () {
    config(['ai.request_mode' => 'server']);

    OpenAI::fake([
        CreateResponse::from(fakeOpenAiTextAttributes('Ответ от сервера.'), MetaInformation::from([])),
    ]);

    $answer = (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
        User::factory()->create()->id,
    );

    expect($answer)->toBe('Ответ от сервера.');
    expect(AiRequest::count())->toBe(0);
});

it('relays the request to the browser and uses its response when AI_REQUEST_MODE is client', function () {
    config(['ai.request_mode' => 'client']);

    $user = User::factory()->create();

    // Simulates the browser's poll-and-relay loop completing the request
    // the instant it is created, so the service's wait loop returns
    // immediately instead of actually polling for up to the real timeout.
    Event::listen('eloquent.created: '.AiRequest::class, function (AiRequest $aiRequest) {
        $aiRequest->update([
            'status' => 'completed',
            'response' => fakeOpenAiTextAttributes('Ответ из браузера.'),
        ]);
    });

    $answer = (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
        $user->id,
    );

    expect($answer)->toBe('Ответ из браузера.');

    $aiRequest = AiRequest::first();
    expect($aiRequest->user_id)->toBe($user->id);
    expect($aiRequest->status)->toBe('completed');
});

it('throws when the browser reports it could not reach OpenAI', function () {
    config(['ai.request_mode' => 'client']);

    $user = User::factory()->create();

    Event::listen('eloquent.created: '.AiRequest::class, function (AiRequest $aiRequest) {
        $aiRequest->update([
            'status' => 'failed',
            'error' => 'Сеть недоступна.',
        ]);
    });

    (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
        $user->id,
    );
})->throws(RuntimeException::class);

it('throws when the browser never relays a response before the timeout', function () {
    config(['ai.request_mode' => 'client', 'ai.client_timeout' => 0]);

    $user = User::factory()->create();

    (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
        $user->id,
    );
})->throws(RuntimeException::class);
