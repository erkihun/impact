<?php

declare(strict_types=1);

use App\Models\ContentSlug;
use App\Models\Service;
use Illuminate\Database\Eloquent\MassAssignmentException;

it('protects generated model identities from mass assignment', function (): void {
    expect(fn (): Service => new Service([
        'id' => '0198a26f-6ad4-7a1f-b95e-e25ba2bd06c9',
        'featured' => true,
    ]))->toThrow(MassAssignmentException::class);
});

it('allow-lists the composite content slug attributes', function (): void {
    expect((new ContentSlug)->getFillable())->toBe([
        'content_item_id',
        'locale',
        'slug',
    ]);
});
