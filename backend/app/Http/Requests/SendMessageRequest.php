<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => ['required', 'string', 'uuid'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment_ids' => ['array', 'nullable'],
            'attachment_ids.*' => ['integer', 'exists:attachments,id'],
        ];
    }
}
