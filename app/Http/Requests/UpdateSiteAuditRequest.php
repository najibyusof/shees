<?php

namespace App\Http\Requests;

use App\Models\SiteAudit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $siteAudit = $this->route('site_audit') ?? $this->route('siteAudit');

        return (bool) ($siteAudit instanceof SiteAudit && $this->user()?->can('update', $siteAudit));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'audit_type' => ['nullable', 'string', 'max:100'],
            'scheduled_for' => ['nullable', 'date'],
            'conducted_at' => ['nullable', 'date'],
            'scope' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,in_progress,rejected'],
        ];
    }
}
