<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Enums\Seo\SeoIssueSeverity;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\ContentSeoValidator;
use App\Support\Settings\EffectiveSettings;
use App\Support\WebsiteCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;

final class SaveWebsiteContentRequest extends FormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $definition = null;

    private ?Model $record = null;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission($this->definition()['permission']) === true;
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return $this->definition ??= WebsiteCollections::find((string) $this->route('collection')) ?? abort(404);
    }

    /** The record being edited, or null when creating. */
    public function record(): ?Model
    {
        $id = $this->route('record');
        if (! is_string($id)) {
            return null;
        }

        return $this->record ??= $this->definition()['record']::query()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $definition = $this->definition();
        $row = WebsiteCollections::editableRow($definition, $this->record());
        $table = (new ($definition['version'] ?? $definition['record']))->getTable();

        $rules = [
            'status' => ['required', Rule::in(WebsiteCollections::statusValues($definition))],
            'slug' => [
                'required',
                'string',
                'max:200',
                // Lowercase words joined by single hyphens; never a reserved path.
                'regex:'.ContentSeoValidator::SLUG_PATTERN,
                Rule::notIn(ContentSeoValidator::RESERVED_SLUGS),
                Rule::unique($table, 'slug')->where('locale', PublicResourceQuery::SITE_LOCALE)->ignore($row?->getKey()),
            ],
        ];
        foreach ($definition['fields'] as $field) {
            $rules[$field['name']] = $this->fieldRules($field);
        }
        foreach (isset($definition['rules']) ? $definition['rules']($this->all()) : [] as $name => $extra) {
            $rules[$name] = [...$rules[$name], ...$extra];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->definition()['messages'] ?? [];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'slug' => 'web address',
            ...collect($this->definition()['fields'])->mapWithKeys(fn (array $field): array => [$field['name'] => Str::lower($field['label'])])->all(),
        ];
    }

    /**
     * Publication gate: an item cannot go live with SEO blocking issues
     * (missing title, invalid or reserved slug).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $definition = $this->definition();
            if ($validator->errors()->isNotEmpty()
                || ! in_array($this->input('status'), WebsiteCollections::liveStatusValues($definition), true)
                || ! app(EffectiveSettings::class)->boolean('content.seo_checks_required')) {
                return;
            }

            $result = app(ContentSeoValidator::class)->validate([
                'title' => $this->input($definition['title']),
                'description' => $this->input($definition['summary']),
                'slug' => (string) $this->input('slug'),
                'h1' => $this->input($definition['title']),
            ]);
            foreach ($result->issues(SeoIssueSeverity::Blocking) as $issue) {
                $validator->errors()->add(str_starts_with($issue->code, 'slug') ? 'slug' : $definition['title'], __($issue->message));
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        // A blank web address is created from the title.
        $slug = filled($this->input('slug')) ? $this->input('slug') : $this->input($this->definition()['title']);
        $this->merge(['slug' => ContentSeoValidator::normalizeSlug((string) $slug)]);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private function fieldRules(array $field): array
    {
        $presence = ($field['required'] ?? false) ? 'required' : 'nullable';

        return match ($field['type']) {
            'text', 'textarea' => [$presence, 'string', 'max:'.($field['max'] ?? 65000)],
            'lines' => [$presence, 'string', 'max:20000'],
            'number' => [$presence, 'integer', 'min:0', 'max:1000000'],
            'select' => [$presence, Rule::in(array_column($field['options'], 'value'))],
            'datetime' => [$presence, 'date'],
            'media' => [$presence, 'uuid', Rule::exists('media_assets', 'id')
                ->where('visibility', MediaVisibility::Public->value)
                ->where('scan_status', MediaStatus::Clean->value)
                ->where('processing_status', MediaStatus::Ready->value)],
            default => throw new LogicException("Unsupported website field type [{$field['type']}]."),
        };
    }
}
