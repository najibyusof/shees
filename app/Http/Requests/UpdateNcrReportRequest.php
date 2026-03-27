<?php

namespace App\Http\Requests;

use App\Models\NcrReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNcrReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ncrReport = $this->route('ncrReport');

        return (bool) ($ncrReport instanceof NcrReport && $this->user()?->can('updateNcr', $ncrReport->siteAudit));
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
            'severity' => ['required', 'string', 'in:minor,major,critical'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'root_cause' => ['nullable', 'string'],
            'containment_action' => ['nullable', 'string'],
            'corrective_action_plan' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:open,in_progress,pending_verification,closed'],
        ];
    }
}
