<?php

use App\Jobs\GenerateRoadmapJob;
use App\Jobs\GenerateStepTestJob;
use App\Models\Course;
use App\Services\CourseGeneratorService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

function fakeRoadmap(): array
{
    return [
        'title' => 'Курс по теме',
        'summary' => 'Краткое описание курса',
        'modules' => [
            [
                'title' => 'Раздел 1',
                'description' => 'Описание раздела 1',
                'steps' => [
                    [
                        'title' => 'Шаг 1',
                        'description' => 'Описание шага 1',
                        'requires_test' => false,
                        'estimated_days' => 1,
                        'resources' => [
                            ['title' => 'Ресурс 1', 'url' => 'https://example.test/1', 'type' => 'article'],
                        ],
                    ],
                    [
                        'title' => 'Шаг 2',
                        'description' => 'Описание шага 2',
                        'requires_test' => false,
                        'estimated_days' => 2,
                        'resources' => [
                            ['title' => 'Ресурс 2', 'url' => 'https://example.test/2', 'type' => 'video'],
                        ],
                    ],
                    [
                        // The model didn't flag this one, but it's the last lesson
                        // of the section, so it must end up requiring a test anyway.
                        'title' => 'Шаг 3',
                        'description' => 'Описание шага 3',
                        'requires_test' => false,
                        'estimated_days' => 1,
                        'resources' => [],
                    ],
                ],
            ],
            [
                'title' => 'Раздел 2',
                'description' => 'Описание раздела 2',
                'steps' => [
                    [
                        'title' => 'Шаг 4',
                        'description' => 'Описание шага 4',
                        'requires_test' => true,
                        'estimated_days' => 2,
                        'resources' => [],
                    ],
                ],
            ],
        ],
    ];
}

it('creates a parent step per module and a child step per lesson', function () {
    Queue::fake([GenerateStepTestJob::class]);

    $course = Course::factory()->create(['status' => 'generating']);

    $generator = Mockery::mock(CourseGeneratorService::class);
    $generator->shouldReceive('generateRoadmap')
        ->once()
        ->with($course->topic, $course->level)
        ->andReturn(fakeRoadmap());

    (new GenerateRoadmapJob($course->id))->handle($generator);

    $course->refresh();
    $modules = $course->parentSteps()->with('children')->get();

    expect($modules)->toHaveCount(2);
    expect($modules[0]->title)->toBe('Раздел 1');
    expect($modules[0]->children)->toHaveCount(3);
    expect($modules[1]->title)->toBe('Раздел 2');
    expect($modules[1]->children)->toHaveCount(1);

    $childSteps = $course->flattenedChildSteps();

    expect($childSteps)->toHaveCount(4);
    expect($childSteps->first()->status)->toBe('available');
    expect($childSteps->skip(1)->pluck('status')->unique()->all())->toBe(['locked']);
});

it('dispatches a background test-generation job for every lesson that requires a test', function () {
    Queue::fake([GenerateStepTestJob::class]);

    $course = Course::factory()->create(['status' => 'generating']);

    $generator = Mockery::mock(CourseGeneratorService::class);
    $generator->shouldReceive('generateRoadmap')
        ->once()
        ->with($course->topic, $course->level)
        ->andReturn(fakeRoadmap());

    (new GenerateRoadmapJob($course->id))->handle($generator);

    $course->refresh();
    $childSteps = $course->flattenedChildSteps();

    $stepsRequiringTest = $childSteps->where('requires_test', true);
    $stepsNotRequiringTest = $childSteps->where('requires_test', false);

    expect($stepsRequiringTest)->toHaveCount(2);
    expect($stepsNotRequiringTest)->toHaveCount(2);

    Queue::assertPushed(GenerateStepTestJob::class, $stepsRequiringTest->count());

    foreach ($stepsRequiringTest as $step) {
        Queue::assertPushed(
            GenerateStepTestJob::class,
            fn (GenerateStepTestJob $job) => $job->stepId === $step->id
        );

        expect($step->test)->not->toBeNull();
        expect($step->test->status)->toBe('generating');
    }

    foreach ($stepsNotRequiringTest as $step) {
        expect($step->test)->toBeNull();
    }
});

it('always requires a test on the last lesson of a section, even when the model flags none', function () {
    Queue::fake([GenerateStepTestJob::class]);

    $course = Course::factory()->create(['status' => 'generating']);

    $roadmap = fakeRoadmap();
    $roadmap['modules'] = array_map(function (array $module) {
        $module['steps'] = array_map(function (array $step) {
            $step['requires_test'] = false;

            return $step;
        }, $module['steps']);

        return $module;
    }, $roadmap['modules']);

    $generator = Mockery::mock(CourseGeneratorService::class);
    $generator->shouldReceive('generateRoadmap')->once()->andReturn($roadmap);

    (new GenerateRoadmapJob($course->id))->handle($generator);

    $course->refresh();
    $modules = $course->parentSteps()->with('children')->get();

    // One module has 3 lessons, the other just 1 — only the last lesson of
    // each should have been forced to require a test either way.
    Queue::assertPushed(GenerateStepTestJob::class, $modules->count());

    foreach ($modules as $module) {
        $lastLesson = $module->children->last();

        expect($lastLesson->requires_test)->toBeTrue();
        expect($lastLesson->test)->not->toBeNull();

        foreach ($module->children->slice(0, -1) as $earlierLesson) {
            expect($earlierLesson->requires_test)->toBeFalse();
            expect($earlierLesson->test)->toBeNull();
        }
    }
});
