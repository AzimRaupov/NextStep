<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('aiRequest'));
    }

    public function rules(): array
    {
        return [
            'response' => ['required_without:error', 'array'],
            'error' => ['required_without:response', 'string'],
        ];
    }
}
