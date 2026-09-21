<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/** @implements CastsAttributes<string|null, string|null> */
final class BinaryUuid implements CastsAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (strlen($value) === 16) {
            return Uuid::fromBytes($value)->toString();
        }

        if (Uuid::isValid($value)) {
            return strtolower($value);
        }

        throw new InvalidArgumentException("Invalid binary UUID value for {$key}.");
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException("Invalid UUID string for {$key}.");
        }

        return Uuid::fromString($value)->getBytes();
    }
}
