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

    public int $timeout = 600;

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
            $isFirstChildOverall = true;

            foreach ($roadmap['modules'] as $moduleIndex => $module) {
                $parentStep = $course->steps()->create([
                    'order' => $moduleIndex + 1,
                    'title' => $module['title'],
                    'description' => $module['description'],
                ]);

                $lastStepIndex = array_key_last($module['steps']);

                foreach ($module['steps'] as $stepIndex => $step) {
                    // Every section must end in a checkpoint: force a test on its
                    // last lesson even if the model didn't flag one, so a student
                    // can never finish a whole section without being tested on it.
                    $requiresTest = $step['requires_test'] || $stepIndex === $lastStepIndex;

                    $childStep = $parentStep->children()->create([
                        'course_id' => $course->id,
                        'order' => $stepIndex + 1,
                        'title' => $step['title'],
                        'description' => $step['description'],
                        'requires_test' => $requiresTest,
                        'estimated_days' => $step['estimated_days'],
                        'status' => $isFirstChildOverall ? 'available' : 'locked',
                        'started_at' => $isFirstChildOverall ? now() : null,
                    ]);

                    $isFirstChildOverall = false;

                    foreach ($step['resources'] as $resource) {
                        $childStep->resources()->create([
                            'title' => $resource['title'],
                            'url' => $resource['url'],
                            'type' => $resource['type'],
                        ]);
                    }

                    if ($requiresTest) {
                        $childStep->test()->create(['status' => 'not_started']);
                        $stepIdsRequiringTest[] = $childStep->id;
                    }
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
