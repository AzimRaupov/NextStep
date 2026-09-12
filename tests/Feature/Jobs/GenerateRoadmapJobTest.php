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
        'steps' => [
            [
                'title' => 'Шаг 1',
                'description' => 'Описание шага 1',
                'requires_test' => true,
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
                'title' => 'Шаг 3',
                'description' => 'Описание шага 3',
                'requires_test' => true,
                'estimated_days' => 2,
                'resources' => [],
            ],
        ],
    ];
}

it('dispatches a background test-generation job for every step that requires a test', function () {
    Queue::fake([GenerateStepTestJob::class]);

    $course = Course::factory()->create(['status' => 'generating']);

    $generator = Mockery::mock(CourseGeneratorService::class);
    $generator->shouldReceive('generateRoadmap')
        ->once()
        ->with($course->topic, $course->level, $course->user_id)
        ->andReturn(fakeRoadmap());

    (new GenerateRoadmapJob($course->id))->handle($generator);

    $course->refresh();
    $steps = $course->steps()->orderBy('order')->get();

    expect($steps)->toHaveCount(3);

    $stepsRequiringTest = $steps->where('requires_test', true);
    $stepsNotRequiringTest = $steps->where('requires_test', false);

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

it('does not dispatch any test-generation job when no step requires a test', function () {
    Queue::fake([GenerateStepTestJob::class]);

    $course = Course::factory()->create(['status' => 'generating']);

    $roadmap = fakeRoadmap();
    $roadmap['steps'] = array_map(function (array $step) {
        $step['requires_test'] = false;

        return $step;
    }, $roadmap['steps']);

    $generator = Mockery::mock(CourseGeneratorService::class);
    $generator->shouldReceive('generateRoadmap')->once()->andReturn($roadmap);

    (new GenerateRoadmapJob($course->id))->handle($generator);

    Queue::assertNothingPushed();
});
