<?php

namespace App\Http\Requests;

use App\Models\SiteAudit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteAuditKpiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $siteAudit = $this->route('siteAudit') ?? $this->route('site_audit');

        return (bool) ($siteAudit instanceof SiteAudit && $this->user()?->can('manageKpi', $siteAudit));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'actual_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['nullable', 'string', 'in:pending,on_track,off_track'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
