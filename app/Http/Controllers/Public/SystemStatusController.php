<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Seo\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Services\Seo\SeoHeadRenderer;
use App\Services\Seo\SeoMetadataBuilder;
use App\Support\Settings\EffectiveSettings;
use Inertia\Inertia;
use Inertia\Response;

final class SystemStatusController extends Controller
{
    public function __invoke(EffectiveSettings $settings): Response
    {
        $bannerActive = $settings->boolean('maintenance.maintenance_banner_enabled');
        $title = $settings->string('maintenance.public_title');

        // A transient operational page: useful to visitors, not to search results.
        $seo = SeoMetadataBuilder::make()
            ->title($title)
            ->description($settings->nullableString('maintenance.public_message'))
            ->meaningfulQuery([])
            ->robots(RobotsDirective::NoindexFollow)
            ->withoutStructuredData()
            ->build();

        return Inertia::render('Public/SystemStatus', [
            'seo' => [...$seo->toArray(), 'head' => app(SeoHeadRenderer::class)->render($seo)],
            'header' => [
                'eyebrow' => __('Service information'),
                'title' => $title,
                'summary' => $settings->nullableString('maintenance.public_message'),
            ],
            'bannerActive' => $bannerActive,
            'statusMessage' => $bannerActive
                ? __('A scheduled maintenance notice is currently active.')
                : __('No scheduled maintenance notice is currently active.'),
            'supportUrl' => $settings->nullableString('maintenance.support_url'),
            'copy' => ['support' => __('Contact support')],
        ]);
    }
}
