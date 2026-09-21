<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MediaVisibility;
use App\Support\Settings\MediaSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('media.create') === true;
    }

    /** @return array<string, mixed> */
    public function rules(MediaSettings $media): array
    {
        return [
            'file' => ['required', 'file', 'max:'.max(20480, $media->maximumImageKilobytes())],
            'visibility' => ['required', Rule::enum(MediaVisibility::class)],
            'title' => ['nullable', 'string', 'max:220'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'locale' => ['nullable', Rule::in(config('impact.locales.supported', ['en', 'am']))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');
            if (! $file instanceof UploadedFile || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
                return;
            }

            $media = app(MediaSettings::class);
            if (($file->getSize() ?: 0) > $media->maximumImageKilobytes() * 1024) {
                $validator->errors()->add('file', __('The image exceeds the configured maximum size.'));
            }

            if ($media->requiresAltText() && blank($this->input('alt_text'))) {
                $validator->errors()->add('alt_text', __('Alternative text is required for images.'));
            }
        });
    }
}
