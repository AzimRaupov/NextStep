<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitStepTestRequest;
use App\Http\Resources\StepQuestionResource;
use App\Jobs\GenerateStepTestJob;
use App\Models\Course;
use App\Models\CourseStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StepTestController extends Controller
{
    public function show(Request $request, Course $course, CourseStep $step)
    {
        Gate::authorize('view', $course);

        abort_unless($step->requires_test, 404);

        $stepTest = $step->test;

        if (in_array($stepTest->status, ['not_started', 'failed'], true)) {
            $stepTest->update(['status' => 'generating']);
            GenerateStepTestJob::dispatch($step->id);
        }

        if ($stepTest->status !== 'pending') {
            return response()->json(['status' => $stepTest->status, 'questions' => []]);
        }

        return response()->json([
            'status' => 'pending',
            'questions' => StepQuestionResource::collection($stepTest->questions()->orderBy('order')->get()),
        ]);
    }

    public function submit(SubmitStepTestRequest $request, Course $course, CourseStep $step)
    {
        Gate::authorize('view', $course);

        $stepTest = $step->test()->with('questions')->first();

        $correctCount = 0;
        $answersSnapshot = [];

        foreach ($request->validated('answers') as $answer) {
            $question = $stepTest->questions->firstWhere('id', $answer['question_id']);
            $isCorrect = $question->correct_option === $answer['selected_option'];

            $answersSnapshot[] = [
                'question_id' => $question->id,
                'selected_option' => $answer['selected_option'],
                'is_correct' => $isCorrect,
            ];

            if ($isCorrect) {
                $correctCount++;
            }
        }

        $total = $stepTest->questions->count();
        $scorePercent = (int) round($correctCount / $total * 100);
        $passed = $scorePercent >= config('course.pass_score');

        DB::transaction(function () use ($request, $stepTest, $step, $scorePercent, $passed, $answersSnapshot) {
            $stepTest->attempts()->create([
                'user_id' => $request->user()->id,
                'score' => $scorePercent,
                'passed' => $passed,
                'answers' => $answersSnapshot,
            ]);

            if ($passed) {
                $stepTest->update(['status' => 'completed']);
                $step->markCompleted();
            }
        });

        return response()->json([
            'score' => $scorePercent,
            'passed' => $passed,
            'correct_count' => $correctCount,
            'total' => $total,
        ]);
    }
}
