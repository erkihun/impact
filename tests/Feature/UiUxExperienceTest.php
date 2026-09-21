<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('renders the public editorial shell with accessible desktop and mobile navigation', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('href="#main-content"', false)
        ->assertSee('aria-label="Primary navigation"', false)
        ->assertSee('aria-controls="mega-services"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('Request a consultation')
        ->assertSee('lang="am"', false);
});

it('renders one decorative icon system across public navigation, submenus, and footer links', function (): void {
    $response = $this->get('/en')
        ->assertOk()
        ->assertSee('nav-menu-icon', false)
        ->assertSee('mega-link-icon', false)
        ->assertSee('mobile-menu-icon', false)
        ->assertSee('footer-menu-icon', false)
        ->assertSee('footer-legal-icon', false)
        ->assertSee('data-nav-item="home"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('aria-hidden="true"', false);

    expect(substr_count($response->getContent(), 'data-nav-item="home"'))->toBe(2);
    expect(File::exists(resource_path('views/components/ui/icon.blade.php')))->toBeTrue();
});

it('renders the consultation and proposal task flows in the required sequence', function (): void {
    foreach (['/en/consultation', '/en/request-for-proposal'] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSeeInOrder(['Need', 'Organization', 'Project', 'Review'])
            ->assertSee('multiStepForm(4, 1)', false)
            ->assertSee('data-prevent-duplicate', false);
    }

    $this->get('/en/request-for-proposal')
        ->assertSee('does not mean it has been approved')
        ->assertSee('security processing is complete');
});

it('renders localized error states without exposing implementation details', function (string $view, string $code): void {
    $this->view($view)
        ->assertSee($code)
        ->assertSee('Return to the homepage')
        ->assertDontSee('Stack trace')
        ->assertDontSee('vendor/laravel');
})->with([
    ['errors.403', '403'],
    ['errors.404', '404'],
    ['errors.419', '419'],
    ['errors.429', '429'],
    ['errors.500', '500'],
    ['errors.503', '503'],
]);

it('publishes the legal, cookie and accessibility pages in both locales', function (string $path, string $heading): void {
    foreach (['en', 'am'] as $locale) {
        $this->get("/{$locale}/{$path}")->assertOk();
    }

    $this->get("/en/{$path}")
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('Contact us');
})->with([
    ['privacy', 'Privacy notice'],
    ['terms', 'Terms of use'],
    ['cookies', 'Cookie notice'],
    ['accessibility', 'Accessibility statement'],
]);

it('keeps privacy, cookie and accessibility routes reachable from the footer', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Privacy notice')
        ->assertSee('Cookie notice')
        ->assertSee('Terms of use')
        ->assertSee('Accessibility statement')
        ->assertSee('data-open-consent-preferences', false);
});

it('offers an accessibility barrier-reporting route', function (): void {
    $this->get('/en/accessibility')
        ->assertOk()
        ->assertSee('Report an accessibility issue')
        ->assertSee(route('contact.create', ['locale' => 'en']), false);
});

it('presents equivalent accept, reject and manage consent choices before optional storage', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('consentManager', false)
        ->assertSee('Accept optional')
        ->assertSee('Reject optional')
        ->assertSee('Manage choices')
        ->assertSee('aria-modal="true"', false);
});

it('records a consent decision only for the current policy version', function (): void {
    $policyVersion = (string) config('impact.privacy.policy_version');

    $this->postJson('/consent', [
        'decisions' => ['necessary' => true, 'analytics' => false],
        'policy_version' => $policyVersion,
    ])->assertOk()->assertJson(['recorded' => true]);

    $this->postJson('/consent', [
        'decisions' => ['necessary' => true],
        'policy_version' => 'not-the-current-version',
    ])->assertStatus(422);
});

it('offers search, services and insights recovery routes from the not-found page', function (): void {
    $this->get('/en/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('Where to go next')
        ->assertSee('Search the site')
        ->assertSee('Browse services')
        ->assertSee('Read insights')
        ->assertSee('Contact us');
});

it('uses one design system across every view, with no starter-kit gray palette', function (): void {
    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (preg_match('/\b(?:text|bg|border|divide)-gray-\d{2,3}\b/', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('does not reintroduce the retired Breeze navigation components', function (): void {
    expect(File::exists(resource_path('views/components/nav-link.blade.php')))->toBeFalse()
        ->and(File::exists(resource_path('views/components/responsive-nav-link.blade.php')))->toBeFalse();
});

it('keeps admin overlay bindings compatible with the CSP Alpine build', function (): void {
    // The CSP build resolves directives to registered properties and methods only, so an
    // inline expression such as `show && closeModal()` silently never runs.
    $inlineExpression = '/x-(?:on:[\w.:-]+|bind:[\w-]+|show|text)="[^"]*(?:&&|\|\||\(\)|\$event|\$dispatch)[^"]*"/';

    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (preg_match($inlineExpression, $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('defines the approved executive editorial core color tokens', function (): void {
    $tailwind = File::get(base_path('tailwind.config.js'));

    foreach (['#17324D', '#2D7A78', '#2D6C99', '#C99A2E', '#1F2933', '#5D6A74', '#F2F4F6', '#C0392B'] as $color) {
        expect(strtoupper($tailwind))->toContain($color);
    }

    expect($tailwind)
        ->toContain("runtimeScale('brand')")
        ->toContain("runtimeScale('action')")
        ->toContain("runtimeScale('knowledge')")
        ->toContain("runtimeScale('gold')")
        ->toContain('--palette-${token}');
});

it('has an Amharic translation for every literal Blade translation key', function (): void {
    /** @var array<string, string> $translations */
    $translations = json_decode(
        File::get(lang_path('am.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $missing = [];
    $pattern = '/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/sU';

    foreach (File::allFiles(resource_path('views')) as $file) {
        preg_match_all($pattern, $file->getContents(), $matches);

        foreach ($matches[2] ?? [] as $key) {
            $normalizedKey = str_replace("\\'", "'", (string) $key);

            if (! array_key_exists($normalizedKey, $translations)) {
                $missing[] = $normalizedKey;
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

it('has an Amharic translation for every literal public controller translation key', function (): void {
    /** @var array<string, string> $translations */
    $translations = json_decode(
        File::get(lang_path('am.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $missing = [];
    $pattern = '/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/sU';

    foreach (File::allFiles(app_path('Http/Controllers/Public')) as $file) {
        preg_match_all($pattern, $file->getContents(), $matches);

        foreach ($matches[2] ?? [] as $key) {
            $normalizedKey = str_replace("\\'", "'", (string) $key);

            if (! array_key_exists($normalizedKey, $translations)) {
                $missing[] = $normalizedKey;
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
