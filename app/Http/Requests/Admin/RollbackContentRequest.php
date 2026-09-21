<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ContentItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class RollbackContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $content = $this->route('content');

        return $content instanceof ContentItem && Gate::allows('rollback', $content);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'expected_current_version_id' => ['required', 'uuid'],
            'source_version_id' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
