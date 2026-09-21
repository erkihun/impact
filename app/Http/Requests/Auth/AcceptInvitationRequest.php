<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\Settings\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

final class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(PasswordPolicy $passwordPolicy): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'password' => ['required', 'confirmed', ...$passwordPolicy->rules()],
        ];
    }
}
