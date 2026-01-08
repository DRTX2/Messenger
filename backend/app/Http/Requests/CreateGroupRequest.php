<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:users,id'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Group name is required.',
            'participant_ids.required' => 'At least 1 participant is required.',
            'participant_ids.min' => 'At least 1 participant is required.',
        ];
    }
}
