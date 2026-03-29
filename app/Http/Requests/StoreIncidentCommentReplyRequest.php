<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentCommentReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return (bool) ($incident instanceof Incident && $this->user()?->can('comment', $incident));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:2000'],
        ];
    }
}
