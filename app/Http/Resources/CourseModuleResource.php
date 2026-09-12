<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->computedStatus(),
            'estimated_days' => $this->children->sum('estimated_days'),
            'steps' => CourseStepResource::collection($this->children),
        ];
    }
}
