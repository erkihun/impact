<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Seo\PublicResourceType;

/**
 * Curated contextual link from one public resource to another. Both ends are
 * stable resource identities (service id, insight id, event id, ...), so the
 * link survives new content versions and slug changes.
 *
 * @property string $source_type
 * @property string $source_id
 * @property string $target_type
 * @property string $target_id
 */
final class ContentRelation extends BaseModel
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function sourceType(): ?PublicResourceType
    {
        return PublicResourceType::tryFrom($this->source_type);
    }

    public function targetType(): ?PublicResourceType
    {
        return PublicResourceType::tryFrom($this->target_type);
    }
}
