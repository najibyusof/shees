<?php

namespace App\Http\Requests;

use App\Models\SiteAudit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitSiteAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $siteAudit = $this->route('site_audit') ?? $this->route('siteAudit');

        return (bool) ($siteAudit instanceof SiteAudit && $this->user()?->can('submit', $siteAudit));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Intentionally empty: submit is a workflow trigger with no payload.
        ];
    }
}
