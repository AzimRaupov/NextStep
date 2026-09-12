<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Resources\CourseResource;
use App\Jobs\GeneratePlacementTestJob;
use App\Jobs\GenerateRoadmapJob;
use App\Models\Course;
use App\Support\LevelResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = $request->user()->courses()->with('steps.children')->latest()->get();

        return CourseResource::collection($courses);
    }

    public function store(StoreCourseRequest $request)
    {
        $topic = $request->validated('topic');
        $declaredLevel = $request->validated('declared_level');
        $skipsPlacementTest = LevelResolver::skipsPlacementTest($declaredLevel);

        $course = DB::transaction(function () use ($request, $topic, $declaredLevel, $skipsPlacementTest) {
            $course = $request->user()->courses()->create([
                'topic' => $topic,
                'declared_level' => $declaredLevel,
                'level' => $skipsPlacementTest ? 'beginner' : null,
                'status' => $skipsPlacementTest ? 'generating' : 'queued',
            ]);

            if (! $skipsPlacementTest) {
                $course->placementTest()->create(['status' => 'generating']);
            }

            return $course;
        });

        if ($skipsPlacementTest) {
            GenerateRoadmapJob::dispatch($course->id);
        } else {
            GeneratePlacementTestJob::dispatch($course->id);
        }

        $course->load('placementTest.questions');

        return new CourseResource($course);
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load(['placementTest.questions', 'steps.children.resources', 'steps.children.test']);

        return new CourseResource($course);
    }

    public function retry(Course $course)
    {
        Gate::authorize('update', $course);

        abort_unless($course->status === 'failed', 422, 'Курс не находится в состоянии ошибки.');

        if ($course->placementTest?->status === 'failed') {
            $course->placementTest->update(['status' => 'generating']);
            $course->update(['status' => 'queued']);
            GeneratePlacementTestJob::dispatch($course->id);
        } else {
            $course->update(['status' => 'generating']);
            GenerateRoadmapJob::dispatch($course->id);
        }

        return new CourseResource($course->fresh());
    }
}
