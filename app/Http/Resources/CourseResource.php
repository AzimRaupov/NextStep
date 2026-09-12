<?php

namespace App\Http\Resources;

use App\Support\LevelResolver;
use App\Support\PaceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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
            'steps' => $this->whenLoaded('steps', fn () => CourseModuleResource::collection(
                $this->steps->whereNull('parent_id')->sortBy('order')->values()
            )),
            'pace' => $this->whenLoaded('steps', fn () => PaceCalculator::forCourse($this->childSteps())),
            'progress' => $this->whenLoaded('steps', fn () => [
                'completed' => $this->childSteps()->where('status', 'completed')->count(),
                'total' => $this->childSteps()->count(),
            ]),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * The actionable lessons across every section — used for progress and
     * pace, which only make sense at the lesson level, not the section.
     */
    private function childSteps(): Collection
    {
        return $this->steps->whereNotNull('parent_id');
    }
}
