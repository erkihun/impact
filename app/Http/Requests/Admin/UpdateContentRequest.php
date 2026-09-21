<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ContentItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $content = $this->route('content');

        return $content instanceof ContentItem && Gate::allows('update', $content);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'expected_current_version_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:220'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['required', 'string', 'max:100000'],
        ];
    }
}
