<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $source = '/'.ltrim($request->path(), '/');
        $redirect = Redirect::query()
            ->where('source_path', $source)
            ->where('enabled', true)
            ->first();
        abort_if($redirect === null, 404);

        $destination = (string) $redirect->destination_url;
        abort_if(! str_starts_with($destination, '/') || str_starts_with($destination, '//'), 404);
        abort_if(parse_url($destination, PHP_URL_PATH) === $source, 508);

        $visited = [$source];
        $probe = (string) parse_url($destination, PHP_URL_PATH);
        for ($depth = 0; $depth < 5; $depth++) {
            abort_if(in_array($probe, $visited, true), 508);
            $visited[] = $probe;
            $next = Redirect::query()
                ->where('source_path', $probe)
                ->where('enabled', true)
                ->first();
            if ($next === null) {
                break;
            }
            $destination = (string) $next->destination_url;
            abort_if(! str_starts_with($destination, '/') || str_starts_with($destination, '//'), 404);
            $probe = (string) parse_url($destination, PHP_URL_PATH);
        }
        abort_if(count($visited) > 5, 508);

        $redirect->increment('hit_count');
        $status = in_array($redirect->status_code, [301, 302, 307, 308], true)
            ? $redirect->status_code
            : 301;

        return redirect()->to($destination, $status);
    }
}
