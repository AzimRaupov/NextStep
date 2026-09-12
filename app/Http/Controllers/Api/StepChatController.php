<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendStepChatMessageRequest;
use App\Http\Resources\StepChatMessageResource;
use App\Models\Course;
use App\Models\CourseStep;
use App\Services\CourseGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StepChatController extends Controller
{
    public function __construct(private readonly CourseGeneratorService $generator) {}

    public function index(Request $request, Course $course, CourseStep $step)
    {
        Gate::authorize('view', $course);

        abort_if($step->status === 'locked', 403, 'Шаг ещё заблокирован.');

        return StepChatMessageResource::collection($step->chatMessages);
    }

    public function store(SendStepChatMessageRequest $request, Course $course, CourseStep $step)
    {
        abort_if($step->status === 'locked', 403, 'Шаг ещё заблокирован.');

        $history = $step->chatMessages()
            ->latest()
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])
            ->all();

        $userMessage = $step->chatMessages()->create([
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => $request->validated('message'),
        ]);

        $answer = $this->generator->answerStepQuestion(
            $course->topic,
            $step->title,
            $step->description,
            $history,
            $userMessage->content,
            $request->user()->id,
        );

        $assistantMessage = $step->chatMessages()->create([
            'user_id' => $request->user()->id,
            'role' => 'assistant',
            'content' => $answer,
        ]);

        return StepChatMessageResource::collection(collect([$userMessage, $assistantMessage]));
    }
}
