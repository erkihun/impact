<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Models\ApplicationFile;
use App\Models\ContentItem;
use App\Models\MediaAsset;
use App\Models\PageComposition;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspacePage
{
    public static function render(string $page, array $props = []): Response
    {
        Inertia::encryptHistory();
        $props['meta'] ??= ['robots' => 'noindex,nofollow,noarchive'];
        $component = implode('/', array_map(Str::studly(...), explode('.', $page)));

        if (($props['settings'] ?? null) instanceof EffectiveSettings) {
            $settings = $props['settings'];
            unset($props['settings']);
            if (isset($props['definitions'])) {
                $props['values'] = collect($props['definitions'])->mapWithKeys(function (array $definition, string $key) use ($settings): array {
                    $status = $settings->safeStatus($key);

                    return [SettingCatalog::inputName($key) => $status['editable'] && ($definition['sensitivity'] ?? null) !== 'secret_reference'
                        ? ($definition['type'] === 'media_reference' ? $settings->mediaReference($key) : $settings->get($key)) : null];
                })->all();
            }
        }
        if (($props['request'] ?? null) instanceof Request) {
            $props['token'] = $props['request']->route('token');
            $props['email'] = $props['request']->query('email', '');
            unset($props['request']);
        }

        return Inertia::render($component, self::normalize($props));
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof LengthAwarePaginator) {
            return [
                'data' => self::normalize($value->getCollection()),
                'total' => $value->total(), 'from' => $value->firstItem(), 'to' => $value->lastItem(),
                'current_page' => $value->currentPage(), 'last_page' => $value->lastPage(),
                'links' => $value->linkCollection()->all(),
            ];
        }
        if ($value instanceof Collection) {
            return $value->map(fn ($item) => self::normalize($item))->all();
        }
        if ($value instanceof Model) {
            $data = $value instanceof User
                ? $value->only(array_intersect(['id', 'name', 'email', 'locale', 'status', 'expires_at', 'last_login_at', 'email_verified_at'], array_keys($value->getAttributes())))
                : $value->attributesToArray();
            foreach (['token_hash', 'password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes', 'ip_hash', 'idempotency_key', 'disk', 'path', 'sha256'] as $key) {
                unset($data[$key]);
            }
            foreach ($value->getRelations() as $key => $relation) {
                $data[Str::snake($key)] = self::normalize($relation);
            }
            if ($value instanceof ContentItem) {
                $data['can'] = ['update' => Gate::allows('update', $value), 'rollback' => Gate::allows('rollback', $value)];
            }
            if ($value instanceof PageComposition) {
                $data['editable'] = $value->isEditable();
            }
            if ($value instanceof MediaAsset) {
                $data['url'] = $value->getRawOriginal('visibility') === 'public'
                    && $value->getRawOriginal('scan_status') === 'clean'
                    && $value->getRawOriginal('processing_status') === 'ready'
                    ? Storage::disk($value->disk)->url($value->path) : null;
            }
            if ($value instanceof ApplicationFile || $value instanceof SubmissionFile) {
                $asset = $value->getRelationValue('mediaAsset');
                $data['download_url'] = ($asset instanceof MediaAsset && $asset->getRawOriginal('scan_status') === 'clean' && $asset->getRawOriginal('processing_status') === 'ready' && Gate::allows('view', $value instanceof ApplicationFile ? $value->application : $value->submission))
                    ? URL::temporarySignedRoute($value instanceof ApplicationFile ? 'application-files.download' : 'submission-files.download', now()->addMinutes(5), ['file' => $value])
                    : null;
            }

            return self::normalize($data);
        }
        if (is_array($value)) {
            return array_map(self::normalize(...), $value);
        }

        return $value;
    }
}
