<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\CourseStep;
use App\Services\CourseGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateRoadmapJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $courseId) {}

    public function handle(CourseGeneratorService $generator): void
    {
        $course = Course::find($this->courseId);

        if ($course === null) {
            return;
        }

        $roadmap = $generator->generateRoadmap($course->topic, $course->level);

        $stepIdsRequiringTest = DB::transaction(function () use ($course, $roadmap) {
            $course->update([
                'title' => $roadmap['title'],
                'summary' => $roadmap['summary'],
                'status' => 'ready',
            ]);

            $stepIdsRequiringTest = [];

            foreach ($roadmap['steps'] as $index => $step) {
                $courseStep = $course->steps()->create([
                    'order' => $index + 1,
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'requires_test' => $step['requires_test'],
                    'estimated_days' => $step['estimated_days'],
                    'status' => $index === 0 ? 'available' : 'locked',
                    'started_at' => $index === 0 ? now() : null,
                ]);

                foreach ($step['resources'] as $resource) {
                    $courseStep->resources()->create([
                        'title' => $resource['title'],
                        'url' => $resource['url'],
                        'type' => $resource['type'],
                    ]);
                }

                if ($step['requires_test']) {
                    $courseStep->test()->create(['status' => 'not_started']);
                    $stepIdsRequiringTest[] = $courseStep->id;
                }
            }

            return $stepIdsRequiringTest;
        });

        // Generate the mandatory tests for checkpoint steps in the background right away,
        // instead of waiting for a student to open the step and trigger lazy generation.
        foreach ($stepIdsRequiringTest as $stepId) {
            CourseStep::find($stepId)?->test?->update(['status' => 'generating']);

            GenerateStepTestJob::dispatch($stepId);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Course::find($this->courseId)?->update(['status' => 'failed']);
    }
}
