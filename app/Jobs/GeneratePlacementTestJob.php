<?php

namespace App\Jobs;

use App\Models\Course;
use App\Services\CourseGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GeneratePlacementTestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public int $courseId) {}

    public function handle(CourseGeneratorService $generator): void
    {
        $course = Course::with('placementTest')->find($this->courseId);

        if ($course === null || $course->placementTest === null) {
            return;
        }

        $questions = $generator->generatePlacementQuestions($course->topic, $course->user_id);

        DB::transaction(function () use ($course, $questions) {
            foreach ($questions as $index => $question) {
                $course->placementTest->questions()->create([
                    'order' => $index + 1,
                    'question' => $question['question'],
                    'options' => $question['options'],
                    'correct_option' => $question['correct_option'],
                ]);
            }

            $course->placementTest->update(['status' => 'pending']);
            $course->update(['status' => 'pending_test']);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $course = Course::with('placementTest')->find($this->courseId);

        $course?->placementTest?->update(['status' => 'failed']);
        $course?->update(['status' => 'failed']);
    }
}
