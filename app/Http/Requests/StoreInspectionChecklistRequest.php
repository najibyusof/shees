<?php

namespace App\Http\Requests;

use App\Models\InspectionChecklistItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionChecklistRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.en' => ['nullable', 'string', 'max:255'],
            'title_translations.id' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.id' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.label_translations' => ['nullable', 'array'],
            'items.*.label_translations.en' => ['nullable', 'string', 'max:255'],
            'items.*.label_translations.id' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['required', 'string', 'in:'.implode(',', InspectionChecklistItem::ITEM_TYPES)],
            'items.*.options' => ['nullable', 'string'],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
