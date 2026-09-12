<?php

namespace App\Jobs;

use App\Models\CourseStep;
use App\Services\CourseGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateStepTestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public int $stepId) {}

    public function handle(CourseGeneratorService $generator): void
    {
        $step = CourseStep::with(['course', 'test'])->find($this->stepId);

        if ($step === null || $step->test === null) {
            return;
        }

        $questions = $generator->generateStepQuestions($step->course->topic, $step->title, $step->description);

        DB::transaction(function () use ($step, $questions) {
            foreach ($questions as $index => $question) {
                $options = $question['options'];
                shuffle($options);

                $step->test->questions()->create([
                    'order' => $index + 1,
                    'question' => $question['question'],
                    'options' => $options,
                    'correct_option' => $question['correct_option'],
                ]);
            }

            $step->test->update(['status' => 'pending']);
        });
    }

    public function failed(?Throwable $exception): void
    {
        CourseStep::find($this->stepId)?->test?->update(['status' => 'failed']);
    }
}
