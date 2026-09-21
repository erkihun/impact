<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.manage') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('user')),
            ],
            'locale' => ['required', Rule::exists('locales', 'code')->where('enabled', true)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'uuid', 'distinct', Rule::exists('roles', 'id')],
        ];
    }
}
