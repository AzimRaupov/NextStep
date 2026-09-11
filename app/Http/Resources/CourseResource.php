<?php

namespace App\Http\Resources;

use App\Support\LevelResolver;
use App\Support\PaceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'title' => $this->title,
            'summary' => $this->summary,
            'declared_level' => $this->declared_level,
            'declared_level_label' => LevelResolver::declaredLevelLabel($this->declared_level),
            'level' => $this->level,
            'level_label' => $this->level ? LevelResolver::label($this->level) : null,
            'status' => $this->status,
            'placement_test' => $this->whenLoaded('placementTest', fn () => $this->placementTest ? [
                'id' => $this->placementTest->id,
                'status' => $this->placementTest->status,
                'score' => $this->placementTest->score,
                'questions' => PlacementQuestionResource::collection($this->placementTest->questions),
            ] : null),
            'steps' => $this->whenLoaded('steps', fn () => CourseStepResource::collection($this->steps)),
            'pace' => $this->whenLoaded('steps', fn () => PaceCalculator::forCourse($this->steps)),
            'progress' => $this->whenLoaded('steps', fn () => [
                'completed' => $this->steps->where('status', 'completed')->count(),
                'total' => $this->steps->count(),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
