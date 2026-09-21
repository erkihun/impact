<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cv' => ['required', 'file', 'max:10240', 'mimes:pdf,docx'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'privacy_acknowledged' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }
}
