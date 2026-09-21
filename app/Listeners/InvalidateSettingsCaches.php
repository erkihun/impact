<?php

namespace App\Listeners;

use App\Events\SettingsGroupUpdated;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Support\Facades\Cache;

final class InvalidateSettingsCaches
{
    public function handle(SettingsGroupUpdated $event): void
    {
        EffectiveSettings::flushCaches();

        foreach ($this->dependentCacheKeys($event) as $key) {
            Cache::forget($key);
        }
    }

    /** @return list<string> */
    private function dependentCacheKeys(SettingsGroupUpdated $event): array
    {
        $keys = ["settings.category.{$event->category}"];

        if (in_array($event->category, ['general', 'branding', 'appearance', 'homepage', 'localization', 'seo', 'maintenance'], true)) {
            $keys[] = 'public.navigation';
            $keys[] = 'public.layout';
        }

        if ($event->category === 'search') {
            $keys[] = 'search.configuration';
        }

        return $keys;
    }
}
