<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Support\LevelResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:2', 'max:120'],
            'declared_level' => ['required', 'string', Rule::in(LevelResolver::declaredLevels())],
        ];
    }
}
