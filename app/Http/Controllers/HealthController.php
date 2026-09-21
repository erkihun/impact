<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class HealthController extends Controller
{
    public function ready(Request $request): JsonResponse
    {
        $checks = ['database' => false, 'cache' => false, 'storage' => false];

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable) {
            // The response intentionally contains no provider or SQL details.
        }

        try {
            $key = 'readiness:'.Str::random(20);
            Cache::put($key, 'ok', 10);
            $checks['cache'] = Cache::get($key) === 'ok';
            Cache::forget($key);
        } catch (Throwable) {
            // The response intentionally contains no provider details.
        }

        try {
            Storage::disk(config('filesystems.default'))->exists('.');
            $checks['storage'] = true;
        } catch (Throwable) {
            // The response intentionally contains no internal path details.
        }

        $ready = ! in_array(false, $checks, true);

        return response()->json(
            ['status' => $ready ? 'ready' : 'not_ready', 'checks' => $checks],
            $ready ? 200 : 503,
        );
    }
}
