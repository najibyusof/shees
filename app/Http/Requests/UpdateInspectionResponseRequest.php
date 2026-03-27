<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.response_value' => ['nullable', 'string'],
            'responses.*.comment' => ['nullable', 'string'],
            'responses.*.is_non_compliant' => ['nullable', 'boolean'],
            'mark_as_completed' => ['nullable', 'boolean'],
        ];
    }
}
