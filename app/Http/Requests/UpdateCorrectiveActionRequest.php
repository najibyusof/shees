<?php

namespace App\Http\Requests;

use App\Models\CorrectiveAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCorrectiveActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $correctiveAction = $this->route('correctiveAction');

        return (bool) ($correctiveAction instanceof CorrectiveAction && $this->user()?->can('updateCorrectiveAction', $correctiveAction->ncrReport->siteAudit));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:open,in_progress,completed,verified,rejected'],
            'completion_notes' => ['nullable', 'string'],
        ];
    }
}
