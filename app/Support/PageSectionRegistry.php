<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CardVariant;
use App\Enums\ContainerWidth;
use App\Enums\ContentAlignment;
use App\Enums\MediaPosition;
use App\Enums\MobileStackingRule;
use App\Enums\PageSectionType;
use App\Enums\SectionSpacing;
use App\Enums\SurfaceTone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PageSectionRegistry
{
    /** @var list<string> */
    private const PRESENTATION_FIELDS = [
        'surface_tone', 'container_width', 'alignment', 'media_position',
        'spacing_top', 'spacing_bottom', 'card_variant', 'mobile_stacking',
    ];

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return [
            'homepage_hero' => $this->definition(
                'Homepage hero',
                'High-priority value proposition with controlled actions and approved media.',
                ['eyebrow', 'heading', 'summary', 'evidence_label', 'evidence_value'],
                ['heading', 'summary'],
                ['default', 'split_media'],
                'public.sections.homepage-hero',
                ['image'],
            ),
            'page_header' => $this->definition(
                'Page header',
                'Required semantic page introduction and H1.',
                ['eyebrow', 'heading', 'summary'],
                ['heading'],
                ['standard', 'paper', 'knowledge', 'media'],
                'public.sections.page-header',
                ['image'],
            ),
            'rich_text' => $this->definition(
                'Rich text',
                'Sanitized editorial content with a controlled heading hierarchy.',
                ['eyebrow', 'heading', 'body'],
                ['body'],
                ['reading', 'editorial'],
                'public.sections.rich-text',
            ),
            'image_text' => $this->definition(
                'Image and text',
                'Approved image paired with structured editorial copy.',
                ['eyebrow', 'heading', 'summary', 'caption'],
                ['heading'],
                ['split', 'stacked'],
                'public.sections.image-text',
                ['image'],
            ),
            'impact_metrics' => $this->definition(
                'Impact metrics',
                'Bounded evidence records with labels and context.',
                ['eyebrow', 'heading', 'summary', 'items'],
                ['items'],
                ['index', 'grid'],
                'public.sections.impact-metrics',
            ),
            'featured_collection' => $this->definition(
                'Featured collection',
                'Manual or automatic published-content selection.',
                ['eyebrow', 'heading', 'summary', 'empty_title', 'empty_summary'],
                ['heading'],
                ['editorial', 'grid'],
                'public.sections.featured-collection',
                [],
                ['service', 'industry', 'expert', 'case_study', 'insight', 'event', 'vacancy'],
            ),
            'related_content' => $this->definition(
                'Related content',
                'Controlled related published records with explicit empty behavior.',
                ['eyebrow', 'heading', 'summary', 'empty_title'],
                ['heading'],
                ['editorial', 'compact'],
                'public.sections.related-content',
                [],
                ['service', 'industry', 'expert', 'case_study', 'insight'],
            ),
            'quote' => $this->definition(
                'Quote',
                'Attributed testimonial or editorial quotation.',
                ['quote', 'attribution', 'role'],
                ['quote'],
                ['editorial', 'brand'],
                'public.sections.quote',
            ),
            'faq' => $this->definition(
                'FAQ',
                'Accessible question and answer disclosure list.',
                ['eyebrow', 'heading', 'items'],
                ['heading', 'items'],
                ['default'],
                'public.sections.faq',
            ),
            'cta_panel' => $this->definition(
                'Call to action',
                'Structured engagement prompt with approved actions.',
                ['eyebrow', 'heading', 'summary'],
                ['heading'],
                ['brand', 'quiet', 'dark'],
                'public.sections.cta-panel',
            ),
            'contact_panel' => $this->definition(
                'Contact panel',
                'Approved contact pathway and office context.',
                ['eyebrow', 'heading', 'summary'],
                ['heading'],
                ['default', 'split'],
                'public.sections.contact-panel',
                [],
                ['office'],
            ),
            'newsletter_panel' => $this->definition(
                'Newsletter panel',
                'Consent-aware newsletter entry surrounding the fixed secure form.',
                ['eyebrow', 'heading', 'summary'],
                ['heading'],
                ['default', 'compact'],
                'public.sections.newsletter-panel',
            ),
            'form_introduction' => $this->definition(
                'Form introduction',
                'Managed guidance surrounding a fixed secure form schema.',
                ['eyebrow', 'heading', 'summary', 'privacy_guidance'],
                ['heading', 'summary'],
                ['standard', 'compact'],
                'public.sections.form-introduction',
            ),
            'divider' => $this->definition(
                'Divider',
                'Design-system separator with no arbitrary size or styling.',
                [],
                [],
                ['line', 'space'],
                'public.sections.divider',
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function get(PageSectionType|string $type): array
    {
        $key = $type instanceof PageSectionType ? $type->value : $type;
        $definition = $this->all()[$key] ?? null;

        if ($definition === null) {
            throw ValidationException::withMessages(['type' => __('Unknown page section type.')]);
        }

        return $definition;
    }

    /** @param array<string, mixed> $content @param array<string, mixed> $presentation */
    public function validate(
        PageSectionType $type,
        string $variant,
        array $content,
        array $presentation,
    ): void {
        $definition = $this->get($type);
        if (! in_array($variant, $definition['variants'], true)) {
            throw ValidationException::withMessages(['variant' => __('This variant is not allowed.')]);
        }

        $unknownContent = array_diff(array_keys($content), $definition['fields']);
        $unknownPresentation = array_diff(array_keys($presentation), self::PRESENTATION_FIELDS);
        if ($unknownContent !== []) {
            throw ValidationException::withMessages([
                'content' => __('Unsupported section fields: :fields', ['fields' => implode(', ', $unknownContent)]),
            ]);
        }
        if ($unknownPresentation !== []) {
            throw ValidationException::withMessages([
                'presentation' => __('Unsupported presentation fields: :fields', ['fields' => implode(', ', $unknownPresentation)]),
            ]);
        }

        $rules = [];
        foreach ($definition['fields'] as $field) {
            $rules[$field] = in_array($field, $definition['required'], true)
                ? ['required']
                : ['nullable'];
            $rules[$field][] = $field === 'items' ? 'array' : 'string';
        }
        $rules += [
            'surface_tone' => ['sometimes', Rule::enum(SurfaceTone::class)],
            'container_width' => ['sometimes', Rule::enum(ContainerWidth::class)],
            'alignment' => ['sometimes', Rule::enum(ContentAlignment::class)],
            'media_position' => ['sometimes', Rule::enum(MediaPosition::class)],
            'spacing_top' => ['sometimes', Rule::enum(SectionSpacing::class)],
            'spacing_bottom' => ['sometimes', Rule::enum(SectionSpacing::class)],
            'card_variant' => ['sometimes', Rule::enum(CardVariant::class)],
            'mobile_stacking' => ['sometimes', Rule::enum(MobileStackingRule::class)],
        ];

        Validator::make([...$content, ...$presentation], $rules)->validate();
    }

    /**
     * @param  list<string>  $fields
     * @param  list<string>  $required
     * @param  list<string>  $variants
     * @param  list<string>  $media
     * @param  list<string>  $relations
     * @return array<string, mixed>
     */
    private function definition(
        string $name,
        string $description,
        array $fields,
        array $required,
        array $variants,
        string $renderer,
        array $media = [],
        array $relations = [],
    ): array {
        return compact(
            'name', 'description', 'fields', 'required', 'variants',
            'renderer', 'media', 'relations',
        ) + [
            'default_variant' => $variants[0],
            'minimum_items' => 0,
            'maximum_items' => 24,
            'cache_tags' => ['public-pages'],
            'accessibility' => ['semantic_order', 'heading_hierarchy', 'alt_text_decision'],
        ];
    }
}
