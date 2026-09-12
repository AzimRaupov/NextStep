<?php

namespace App\Services;

use App\Support\LevelResolver;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class CourseGeneratorService
{
    public function generatePlacementQuestions(string $topic): array
    {
        $count = config('course.placement_question_count');

        $schema = [
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'minItems' => 4,
                                'maxItems' => 4,
                                'items' => ['type' => 'string'],
                            ],
                            'correct_option' => ['type' => 'string'],
                        ],
                        'required' => ['question', 'options', 'correct_option'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['questions'],
            'additionalProperties' => false,
        ];

        $instructions = <<<TEXT
Ты — эксперт по составлению входных тестов для образовательной платформы.
Составь {$count} вопросов с 4 вариантами ответа, чтобы определить текущий уровень
знаний ученика по теме "{$topic}" — от полного новичка до продвинутого специалиста.
Вопросы должны идти от простых к сложным. Поле correct_option должно дословно
совпадать с одним из вариантов в options. Пиши на русском языке.
TEXT;

        $data = $this->requestStructured($instructions, 'placement_test', $schema);

        return $data['questions'];
    }

    public function generateRoadmap(string $topic, string $level): array
    {
        ['min' => $minModules, 'max' => $maxModules] = $this->roadmapModuleBounds($level);
        ['min' => $minSteps, 'max' => $maxSteps] = config('course.module_steps');
        $levelLabel = LevelResolver::label($level);
        $startingPoint = $this->roadmapStartingPoint($level);

        $stepSchema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'requires_test' => ['type' => 'boolean'],
                'estimated_days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                'resources' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 4,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => ['article', 'video', 'docs', 'other'],
                            ],
                        ],
                        'required' => ['title', 'url', 'type'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['title', 'description', 'requires_test', 'estimated_days', 'resources'],
            'additionalProperties' => false,
        ];

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'modules' => [
                    'type' => 'array',
                    'minItems' => $minModules,
                    'maxItems' => $maxModules,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'steps' => [
                                'type' => 'array',
                                'minItems' => $minSteps,
                                'maxItems' => $maxSteps,
                                'items' => $stepSchema,
                            ],
                        ],
                        'required' => ['title', 'description', 'steps'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['title', 'summary', 'modules'],
            'additionalProperties' => false,
        ];

        $instructions = <<<TEXT
Ты — методист образовательной платформы. Составь подробную дорожную карту
обучения теме "{$topic}" для ученика с уровнем "{$levelLabel}".

{$startingPoint}

Дорожная карта — это полный маршрут от текущего уровня ученика до уверенного
практического владения темой, без пробелов: пройдя всё по порядку, ученик
должен уметь самостоятельно решать реальные задачи по этой теме.

Маршрут состоит из разделов (родительских шагов), а каждый раздел — из
последовательных уроков (дочерних шагов). Раздели маршрут на {$minModules}-{$maxModules}
разделов, каждый раздел раскрой {$minSteps}-{$maxSteps} уроками.

Как строить разделы:
- Раздел — это законченный тематический блок (подготовка и необходимые
  предварительные знания, база, ядро темы, продвинутые разделы, практика).
- Разделы идут строго от простого к сложному: каждый следующий опирается
  только на то, что уже разобрано в предыдущих.
- Не пропускай подготовительные разделы: установка и настройка инструментов,
  терминология и смежные знания, без которых дальше продвинуться нельзя.
- Последний раздел посвяти закреплению: самостоятельные практические задания
  и небольшие проекты, объединяющие изученное.

Как строить уроки внутри раздела:
- Один урок — одна конкретная узкая подтема, которую можно освоить за один
  подход. Названия вида «Изучить всё про X» или «Основы X» означают слишком
  крупный урок: разбивай такие уроки на отдельные по каждому ключевому
  понятию, инструменту или приёму.
- Если в названии урока приходится перечислять несколько понятий через «и»
  или запятую — это признак, что урок нужно разделить на несколько.
- Уроки внутри раздела и разделы между собой идут строго от простого к
  сложному. Ни один урок не должен требовать знаний, которые появляются
  дальше по маршруту.
- Не повторяй одну и ту же подтему в разных уроках.

Для каждого раздела укажи:
- title — короткое название раздела.
- description — 1-2 предложения о том, что охватывает раздел.

Для каждого урока укажи:
- title — короткое конкретное название подтемы.
- description — 2-4 предложения: что именно разбирается в уроке и что ученик
  сможет делать после него.
- resources — 1-4 ссылки на реальные, широко известные источники (официальная
  документация, известные бесплатные курсы, авторитетные статьи и видео).
  Не выдумывай ссылки, которых не существует.
- estimated_days — сколько календарных дней реально займёт у ученика с уровнем
  "{$levelLabel}" этот урок при обычном темпе занятий (немного времени в день,
  а не весь день подряд): обычно 1-3 дня, до 5 для особенно ёмких уроков.
- requires_test — true для уроков, которые завершают раздел или на которых
  важно проверить усвоение ключевого материала: как минимум последний урок
  каждого раздела.

В поле title укажи название маршрута, в summary — 2-3 предложения о том, для
кого этот маршрут и к какому результату он приводит.
Пиши на русском языке.
TEXT;

        return $this->requestStructured($instructions, 'course_roadmap', $schema);
    }

    /**
     * @return array{min: int, max: int}
     */
    private function roadmapModuleBounds(string $level): array
    {
        $bounds = config('course.roadmap_modules');

        return $bounds[$level] ?? $bounds['beginner'];
    }

    /**
     * Describes what the student already knows, so the model neither re-teaches
     * the basics nor skips over the foundation a beginner is missing.
     */
    private function roadmapStartingPoint(string $level): string
    {
        return match ($level) {
            'advanced' => 'Ученик уверенно владеет базой и имеет практический опыт. Пропусти вводные и базовые разделы и построй маршрут вокруг сложных тем, тонкостей, оптимизации, продвинутых инструментов и задач профессионального уровня.',
            'intermediate' => 'Ученик знаком с базовыми понятиями темы и имеет небольшой практический опыт. Не трать шаги на объяснение азов: ограничься кратким повторением базы в первых одном-двух шагах и сосредоточься на ядре темы, её более сложных разделах и практике.',
            default => 'Ученик начинает с полного нуля: он не знаком с темой, не знает её терминологии и не имеет никакого опыта. Начни маршрут с самых азов — что это за область, зачем она нужна и из чего состоит — и разбирай базовые понятия мелкими шагами, каждое понятие отдельно, прежде чем переходить к более сложным разделам. Обязательно включи подготовительные шаги по смежным знаниям и инструментам, без которых тему не освоить.',
        };
    }

    public function generateStepQuestions(string $topic, string $stepTitle, string $stepDescription): array
    {
        $count = config('course.step_question_count');

        $schema = [
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'minItems' => 4,
                                'maxItems' => 4,
                                'items' => ['type' => 'string'],
                            ],
                            'correct_option' => ['type' => 'string'],
                        ],
                        'required' => ['question', 'options', 'correct_option'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['questions'],
            'additionalProperties' => false,
        ];

        $instructions = <<<TEXT
Ты составляешь проверочный тест для шага "{$stepTitle}" курса по теме "{$topic}".
Описание шага: {$stepDescription}
Составь {$count} вопроса с 4 вариантами ответа, проверяющих понимание именно
материала этого шага. Поле correct_option должно дословно совпадать с одним
из вариантов в options. Пиши на русском языке.
TEXT;

        $data = $this->requestStructured($instructions, 'step_test', $schema);

        return $data['questions'];
    }

    public function answerStepQuestion(string $topic, string $stepTitle, string $stepDescription, array $history, string $question): string
    {
        $instructions = <<<TEXT
Ты — ИИ-репетитор образовательной платформы. Ученик проходит курс по теме
"{$topic}" и сейчас находится на шаге "{$stepTitle}". Описание шага: {$stepDescription}
Отвечай только на вопросы, связанные с этим шагом и темой курса. Объясняй понятно
и по существу, используй примеры кода при необходимости, не пиши лишнего.
Если вопрос не относится к теме шага, вежливо верни ученика к теме. Отвечай
на русском языке.
TEXT;

        $input = [];

        foreach ($history as $message) {
            $input[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $input[] = ['role' => 'user', 'content' => $question];

        $response = OpenAI::responses()->create([
            'model' => config('course.model'),
            'instructions' => $instructions,
            'input' => $input,
        ]);

        if ($response->outputText === null || trim($response->outputText) === '') {
            Log::error('CourseGeneratorService empty chat response', ['step' => $stepTitle]);

            throw new RuntimeException('Пустой ответ от модели.');
        }

        return trim($response->outputText);
    }

    private function requestStructured(string $instructions, string $schemaName, array $schema): array
    {
        $response = OpenAI::responses()->create([
            'model' => config('course.model'),
            'instructions' => $instructions,
            'input' => 'Сгенерируй результат строго в соответствии со схемой.',
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
        ]);

        if ($response->status === 'incomplete') {
            Log::error('CourseGeneratorService incomplete response', [
                'schema' => $schemaName,
                'reason' => $response->incompleteDetails?->reason,
            ]);

            throw new RuntimeException('Модель генерации не успела закончить ответ.');
        }

        if ($response->outputText === null || $response->outputText === '') {
            Log::error('CourseGeneratorService empty response', ['schema' => $schemaName]);

            throw new RuntimeException('Пустой ответ от модели генерации.');
        }

        $data = json_decode($response->outputText, true);

        if (! is_array($data)) {
            Log::error('CourseGeneratorService invalid json', ['schema' => $schemaName, 'raw' => $response->outputText]);

            throw new RuntimeException('Некорректный ответ от модели генерации.');
        }

        return $data;
    }
}
