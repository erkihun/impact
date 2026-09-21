<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'locale' => ['required', Rule::exists('locales', 'code')->where('enabled', true)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'uuid', 'distinct', Rule::exists('roles', 'id')],
        ];
    }
}
