<?php

declare(strict_types=1);

namespace App\Enums;

enum PageSectionType: string
{
    case HomepageHero = 'homepage_hero';
    case PageHeader = 'page_header';
    case RichText = 'rich_text';
    case ImageText = 'image_text';
    case ImpactMetrics = 'impact_metrics';
    case FeaturedCollection = 'featured_collection';
    case RelatedContent = 'related_content';
    case Quote = 'quote';
    case Faq = 'faq';
    case CtaPanel = 'cta_panel';
    case ContactPanel = 'contact_panel';
    case NewsletterPanel = 'newsletter_panel';
    case FormIntroduction = 'form_introduction';
    case Divider = 'divider';
}
