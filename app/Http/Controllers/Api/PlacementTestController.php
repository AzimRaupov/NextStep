<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPlacementTestRequest;
use App\Http\Resources\CourseResource;
use App\Jobs\GenerateRoadmapJob;
use App\Models\Course;
use App\Support\LevelResolver;
use Illuminate\Support\Facades\DB;

class PlacementTestController extends Controller
{
    public function submit(SubmitPlacementTestRequest $request, Course $course)
    {
        $placementTest = $course->placementTest()->with('questions')->first();

        $correctCount = 0;

        DB::transaction(function () use ($request, $placementTest, &$correctCount) {
            foreach ($request->validated('answers') as $answer) {
                $question = $placementTest->questions->firstWhere('id', $answer['question_id']);
                $isCorrect = $question->correct_option === $answer['selected_option'];

                $question->answer()->updateOrCreate([], [
                    'selected_option' => $answer['selected_option'],
                    'is_correct' => $isCorrect,
                ]);

                if ($isCorrect) {
                    $correctCount++;
                }
            }
        });

        $total = $placementTest->questions->count();
        $scorePercent = (int) round($correctCount / $total * 100);
        $level = LevelResolver::fromScore($scorePercent, $course->declared_level);

        $placementTest->update([
            'status' => 'completed',
            'score' => $scorePercent,
            'level_result' => $level,
        ]);

        $course->update(['level' => $level, 'status' => 'generating']);

        GenerateRoadmapJob::dispatch($course->id);

        $course->load('placementTest.questions');

        return new CourseResource($course);
    }
}
