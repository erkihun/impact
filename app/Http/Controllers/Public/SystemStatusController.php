<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\View\View;

final class SystemStatusController extends Controller
{
    public function __invoke(EffectiveSettings $settings): View
    {
        return view('public.system-status', [
            'bannerActive' => $settings->boolean('maintenance.maintenance_banner_enabled'),
            'message' => $settings->nullableString('maintenance.public_message'),
            'supportUrl' => $settings->nullableString('maintenance.support_url'),
            'title' => $settings->string('maintenance.public_title'),
        ]);
    }
}
