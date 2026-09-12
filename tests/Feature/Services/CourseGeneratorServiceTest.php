<?php

use App\Services\CourseGeneratorService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Responses\Responses\CreateResponse;

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

it('returns the model output text for a step chat answer', function () {
    OpenAI::fake([
        CreateResponse::from(fakeOpenAiTextAttributes('Ответ от модели.'), MetaInformation::from([])),
    ]);

    $answer = (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
    );

    expect($answer)->toBe('Ответ от модели.');
});

it('throws when the model returns an empty chat answer', function () {
    OpenAI::fake([
        CreateResponse::from(fakeOpenAiTextAttributes(''), MetaInformation::from([])),
    ]);

    (new CourseGeneratorService)->answerStepQuestion(
        'PHP',
        'Переменные',
        'Основы переменных',
        [],
        'Что такое переменная?',
    );
})->throws(RuntimeException::class);
