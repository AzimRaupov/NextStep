<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitStepTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:step_questions,id'],
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

                $step = $this->route('step');
                $stepTest = $step->test;

                if ($stepTest === null) {
                    $validator->errors()->add('answers', 'Для этого шага нет теста.');

                    return;
                }

                $validQuestionIds = $stepTest->questions()->pluck('id')->all();
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
