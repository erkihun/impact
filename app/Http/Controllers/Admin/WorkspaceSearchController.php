<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EngagementSubmission;
use App\Models\ExpertVersion;
use App\Models\PageComposition;
use App\Models\User;
use App\Queries\Seo\PublicResourceQuery;
use App\Support\WebsiteCollections;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Finds records by name for the workspace command palette. Every source is
 * limited to what the signed-in person may open, and only labels and links
 * leave the server.
 */
final class WorkspaceSearchController extends Controller
{
    private const PER_SOURCE = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $query = Str::limit(trim((string) $request->query('q', '')), 80, '');
        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }
        $like = '%'.addcslashes($query, '%_\\').'%';

        $results = [
            ...$this->listings($user, $like),
            ...$this->experts($user, $like),
            ...$this->pages($user, $query),
            ...$this->requests($user, $like),
            ...$this->applications($user, $like),
            ...$this->people($user, $like),
        ];

        return response()->json(['results' => $results]);
    }

    /** @return list<array<string, mixed>> */
    private function listings(User $user, string $like): array
    {
        $results = [];
        foreach (WebsiteCollections::permittedFor($user) as $key => $definition) {
            $title = $definition['title'];
            $model = $definition['version'] ?? $definition['record'];
            $parentKey = $definition['resource']->parentKey();
            $rows = $model::query()
                ->where('locale', PublicResourceQuery::SITE_LOCALE)
                ->where($title, 'like', $like)
                ->when($definition['version'] !== null, fn ($query) => $query->orderByDesc('version_no'))
                ->limit(self::PER_SOURCE * 3)
                ->get([$parentKey, $title])
                ->unique($parentKey)
                ->take(self::PER_SOURCE);
            foreach ($rows as $row) {
                $results[] = $this->result(
                    (string) $row->getAttribute($title),
                    Str::ucfirst($definition['singular']),
                    route('admin.website.edit', ['collection' => $key, 'record' => $row->getAttribute($parentKey)]),
                    $definition['icon'],
                    'Listings',
                );
            }
        }

        return $results;
    }

    /** @return list<array<string, mixed>> */
    private function experts(User $user, string $like): array
    {
        if (! $user->hasPermission('experts.manage')) {
            return [];
        }

        return ExpertVersion::query()
            ->where('locale', PublicResourceQuery::SITE_LOCALE)
            ->where('display_name', 'like', $like)
            ->orderByDesc('version_no')
            ->limit(self::PER_SOURCE * 3)
            ->get(['expert_id', 'display_name', 'professional_title'])
            ->unique('expert_id')
            ->take(self::PER_SOURCE)
            ->map(fn (ExpertVersion $version): array => $this->result(
                (string) $version->display_name,
                (string) $version->professional_title,
                route('admin.experts.edit', $version->expert_id),
                'experts',
                'Listings',
            ))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function pages(User $user, string $query): array
    {
        if (! $user->hasPermission('pages.view')) {
            return [];
        }
        $key = str_replace(' ', '.', Str::lower($query));
        $key = ['homepage' => 'home', 'request for proposal' => 'rfp'][Str::lower($query)] ?? $key;

        return PageComposition::query()
            ->where('locale', PublicResourceQuery::SITE_LOCALE)
            ->where('page_key', 'like', '%'.addcslashes($key, '%_\\').'%')
            ->orderByDesc('version_no')
            ->limit(self::PER_SOURCE * 4)
            ->get(['id', 'page_key', 'state'])
            ->unique('page_key')
            ->take(self::PER_SOURCE)
            ->map(fn (PageComposition $page): array => $this->result(
                $page->page_key === 'home' ? 'Homepage' : Str::headline(str_replace('.', ' ', (string) $page->page_key)),
                'Website page · '.Str::headline((string) $page->getRawOriginal('state')),
                route('admin.page-compositions.edit', $page),
                'layout',
                'Pages',
            ))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function requests(User $user, string $like): array
    {
        if (! $user->hasPermission('engagement.view')) {
            return [];
        }

        return EngagementSubmission::query()
            ->where(fn ($query) => $query->where('organization_name', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('reference_no', 'like', $like))
            ->latest('submitted_at')
            ->limit(self::PER_SOURCE)
            ->get(['id', 'organization_name', 'contact_name', 'reference_no', 'type'])
            ->map(fn (EngagementSubmission $submission): array => $this->result(
                (string) ($submission->organization_name ?: $submission->contact_name),
                Str::headline((string) $submission->getRawOriginal('type')).' · '.$submission->reference_no,
                route('admin.engagement.show', $submission),
                'contact',
                'Requests',
            ))
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function applications(User $user, string $like): array
    {
        if (! $user->hasPermission('applications.view')) {
            return [];
        }

        return Application::query()
            ->with('vacancy:id,title')
            ->where('applicant_name', 'like', $like)
            ->latest('submitted_at')
            ->limit(self::PER_SOURCE)
            ->get(['id', 'vacancy_id', 'applicant_name'])
            ->map(fn (Application $application): array => $this->result(
                (string) $application->applicant_name,
                (string) ($application->vacancy->title ?? 'Application'),
                route('admin.applications.show', $application),
                'careers',
                'Applications',
            ))
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function people(User $user, string $like): array
    {
        if (! $user->hasPermission('users.view')) {
            return [];
        }
        $canManage = $user->hasPermission('users.manage');

        return User::query()
            ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->orderBy('name')
            ->limit(self::PER_SOURCE)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $person): array => $this->result(
                (string) $person->name,
                (string) $person->email,
                $canManage ? route('admin.users.edit', $person) : route('admin.users.index', ['q' => $person->email]),
                'users',
                'People',
            ))
            ->all();
    }

    /** @return array<string, mixed> */
    private function result(string $label, string $hint, string $href, string $icon, string $group): array
    {
        return [
            'label' => $label,
            'hint' => $hint,
            'href' => parse_url($href, PHP_URL_PATH).(($query = parse_url($href, PHP_URL_QUERY)) ? '?'.$query : ''),
            'icon' => $icon,
            'group' => $group,
            'record' => true,
        ];
    }
}
