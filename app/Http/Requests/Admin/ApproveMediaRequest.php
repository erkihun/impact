<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ApproveMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $asset = $this->route('media');

        return $asset instanceof MediaAsset
            && $this->user()?->can('approve', $asset) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
