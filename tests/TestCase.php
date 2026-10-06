<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\ParallelTesting;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Parallel processes must not write the same sitemap files.
        config(['impact.seo.sitemap_directory' => 'testing/seo-sitemaps-'.(ParallelTesting::token() ?: 'serial')]);
    }
}
