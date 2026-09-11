<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitPlacementTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:placement_questions,id'],
            'answers.*.selected_option' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['answers'])) {
                    return;
                }

                $course = $this->route('course');
                $placementTest = $course->placementTest;

                if ($placementTest === null) {
                    $validator->errors()->add('answers', 'Для этого курса ещё не создан входной тест.');

                    return;
                }

                $validQuestionIds = $placementTest->questions()->pluck('id')->all();
                $submittedIds = collect($this->input('answers'))->pluck('question_id')->all();

                if (count(array_diff($submittedIds, $validQuestionIds)) > 0) {
                    $validator->errors()->add('answers', 'Ответы содержат вопросы, не относящиеся к этому тесту.');
                }

                if (count(array_unique($submittedIds)) !== count($validQuestionIds)) {
                    $validator->errors()->add('answers', 'Нужно ответить на все вопросы теста.');
                }
            },
        ];
    }
}
