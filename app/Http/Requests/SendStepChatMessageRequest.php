<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendStepChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
        ];
    }
}
