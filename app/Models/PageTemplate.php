<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageTemplateType;
use App\Models\Concerns\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property PageTemplateType $type */
final class PageTemplate extends Model
{
    use HasBinaryUuid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => PageTemplateType::class, 'definition' => 'array', 'active' => 'boolean'];
    }

    /** @return HasMany<PageComposition, $this> */
    public function compositions(): HasMany
    {
        return $this->hasMany(PageComposition::class, 'template_id');
    }
}
