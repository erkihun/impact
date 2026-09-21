<?php

declare(strict_types=1);

use App\Enums\PageSectionType;
use App\Support\PageSectionRegistry;
use Illuminate\Validation\ValidationException;

it('registers every controlled section type with an existing renderer', function (): void {
    $registry = app(PageSectionRegistry::class);
    $definitions = collect($registry->all());

    expect($definitions->keys()->sort()->values()->all())
        ->toBe(collect(PageSectionType::cases())->map->value->sort()->values()->all());

    $definitions->each(function (array $definition): void {
        expect(view()->exists($definition['renderer']))->toBeTrue()
            ->and($definition['variants'])->not->toBeEmpty();
    });
});

it('rejects unknown fields and arbitrary variants', function (): void {
    $registry = app(PageSectionRegistry::class);

    expect(fn () => $registry->validate(
        PageSectionType::PageHeader,
        'custom-css',
        ['heading' => 'A governed page'],
        ['arbitrary_class' => 'fixed top-0'],
    ))->toThrow(ValidationException::class);
});
