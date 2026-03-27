<?php

namespace App\Http\Requests;

use App\Models\SiteAudit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNcrReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $siteAudit = $this->route('siteAudit') ?? $this->route('site_audit');

        return (bool) ($siteAudit instanceof SiteAudit && $this->user()?->can('createNcr', $siteAudit));
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
            'status' => ['nullable', 'string', 'in:open,in_progress,pending_verification,closed'],
        ];
    }
}
