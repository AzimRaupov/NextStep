<?php

namespace App\Http\Resources;

use App\Support\PaceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'requires_test' => $this->requires_test,
            'estimated_days' => $this->estimated_days,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'pace' => PaceCalculator::forStep($this->resource),
            'resources' => $this->resources->map(fn ($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'url' => $resource->url,
                'type' => $resource->type,
            ]),
            'test' => $this->when($this->requires_test, fn () => $this->test ? [
                'id' => $this->test->id,
                'status' => $this->test->status,
            ] : null),
        ];
    }
}
