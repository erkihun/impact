<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentWorkflowState;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpertRequest;
use App\Http\Requests\Admin\UpdateExpertRequest;
use App\Models\Expert;
use App\Models\ExpertVersion;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ExpertController extends Controller
{
    /** @var list<string> */
    private const PROFILE_STATES = ['draft', 'published', 'unpublished', 'archived'];

    public function index(EffectiveSettings $settings): View
    {
        $experts = Expert::query()
            ->with([
                'profileMedia',
                'user',
                'versions' => fn ($query) => $query->orderBy('locale')->orderByDesc('version_no'),
            ])
            ->latest()
            ->paginate(min(
                $settings->integer('content.default_items_per_page'),
                $settings->integer('performance.maximum_pagination_size'),
            ));

        return view('admin.experts.index', compact('experts'));
    }

    public function create(EffectiveSettings $settings): View
    {
        return view('admin.experts.create', $this->formData(
            expert: new Expert([
                'status' => 'draft',
                'public_email_enabled' => false,
            ]),
            version: new ExpertVersion([
                'locale' => $settings->string('localization.default_locale'),
                'workflow_state' => ContentWorkflowState::Draft,
            ]),
            settings: $settings,
        ));
    }

    public function store(StoreExpertRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $profileMediaId = $this->profileMediaIdFromRequest($request, $validated);

        $expert = DB::transaction(function () use ($validated, $profileMediaId): Expert {
            $expert = Expert::query()->create([
                'user_id' => $validated['user_id'] ?? null,
                'status' => $validated['status'],
                'public_email_enabled' => (bool) $validated['public_email_enabled'],
                'years_experience' => $validated['years_experience'] ?? null,
                'profile_media_id' => $profileMediaId,
                'publication_authorized_at' => $this->publicationAuthorizedAt($validated),
                'authorization_reference' => $validated['authorization_reference'] ?? null,
            ]);

            ExpertVersion::query()->create([
                'expert_id' => $expert->id,
                'locale' => $validated['locale'],
                'version_no' => 1,
                'slug' => $validated['slug'],
                'display_name' => $validated['display_name'],
                'professional_title' => $validated['professional_title'],
                'biography' => $validated['biography'],
                'qualifications' => $this->lineList($validated['qualification_lines'] ?? null),
                'languages' => $this->languageList($validated['language_lines'] ?? null),
                'workflow_state' => $this->versionWorkflowState($validated),
            ]);

            return $expert;
        });

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('status', __('Expert profile created.'));
    }

    public function edit(Expert $expert, EffectiveSettings $settings): View
    {
        $expert->load(['profileMedia', 'user']);

        return view('admin.experts.edit', $this->formData(
            expert: $expert,
            version: $this->editableVersion($expert, $settings->string('localization.default_locale')),
            settings: $settings,
        ));
    }

    public function update(
        UpdateExpertRequest $request,
        Expert $expert,
    ): RedirectResponse {
        $validated = $request->validated();
        $profileMediaId = $this->profileMediaIdFromRequest($request, $validated);

        DB::transaction(function () use ($validated, $expert, $profileMediaId): void {
            $expert->update([
                'user_id' => $validated['user_id'] ?? null,
                'status' => $validated['status'],
                'public_email_enabled' => (bool) $validated['public_email_enabled'],
                'years_experience' => $validated['years_experience'] ?? null,
                'profile_media_id' => $profileMediaId,
                'publication_authorized_at' => $this->publicationAuthorizedAt($validated),
                'authorization_reference' => $validated['authorization_reference'] ?? null,
            ]);

            $version = isset($validated['version_id'])
                ? $expert->versions()->whereKey($validated['version_id'])->first()
                : null;

            $version ??= new ExpertVersion([
                'expert_id' => $expert->id,
                'version_no' => $this->nextVersionNumber($expert, $validated['locale']),
            ]);

            $version->fill([
                'locale' => $validated['locale'],
                'slug' => $validated['slug'],
                'display_name' => $validated['display_name'],
                'professional_title' => $validated['professional_title'],
                'biography' => $validated['biography'],
                'qualifications' => $this->lineList($validated['qualification_lines'] ?? null),
                'languages' => $this->languageList($validated['language_lines'] ?? null),
                'workflow_state' => $this->versionWorkflowState($validated),
            ]);
            $version->save();
        });

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('status', __('Expert profile updated.'));
    }

    public function destroy(Expert $expert): RedirectResponse
    {
        DB::transaction(function () use ($expert): void {
            $expert->versions()->delete();
            $expert->delete();
        });

        return redirect()
            ->route('admin.experts.index')
            ->with('status', __('Expert profile deleted.'));
    }

    /** @return array<string, mixed> */
    private function formData(Expert $expert, ExpertVersion $version, EffectiveSettings $settings): array
    {
        return [
            'expert' => $expert,
            'version' => $version,
            'locales' => $settings->array('localization.enabled_locales'),
            'profileStates' => self::PROFILE_STATES,
            'workflowStates' => ContentWorkflowState::cases(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'profileMedia' => $this->eligibleProfileMedia(),
        ];
    }

    private function editableVersion(Expert $expert, string $preferredLocale): ExpertVersion
    {
        return $expert->versions()
            ->where('locale', $preferredLocale)
            ->orderByDesc('version_no')
            ->first()
            ?? $expert->versions()->orderBy('locale')->orderByDesc('version_no')->first()
            ?? new ExpertVersion([
                'expert_id' => $expert->id,
                'locale' => $preferredLocale,
                'version_no' => 1,
                'workflow_state' => ContentWorkflowState::Draft,
            ]);
    }

    /** @return Collection<int, MediaAsset> */
    private function eligibleProfileMedia(): Collection
    {
        return MediaAsset::query()
            ->where('visibility', MediaVisibility::Public)
            ->where('scan_status', MediaStatus::Clean)
            ->where('processing_status', MediaStatus::Ready)
            ->orderBy('title')
            ->orderBy('original_name')
            ->get(['id', 'disk', 'path', 'title', 'original_name', 'alt_text', 'visibility', 'scan_status', 'processing_status']);
    }

    /** @param array<string, mixed> $validated */
    private function publicationAuthorizedAt(array $validated): ?Carbon
    {
        if (filled($validated['publication_authorized_at'] ?? null)) {
            return Carbon::parse((string) $validated['publication_authorized_at']);
        }

        return $validated['status'] === 'published' ? now() : null;
    }

    /** @return list<string> */
    private function lineList(?string $value): array
    {
        return collect(preg_split('/\R/', (string) $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function languageList(?string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function nextVersionNumber(Expert $expert, string $locale): int
    {
        return ((int) $expert->versions()->where('locale', $locale)->max('version_no')) + 1;
    }

    /** @param array<string, mixed> $validated */
    private function versionWorkflowState(array $validated): ContentWorkflowState
    {
        if ($validated['status'] === 'published') {
            return ContentWorkflowState::Published;
        }

        return ContentWorkflowState::from($validated['workflow_state']);
    }

    /** @param array<string, mixed> $validated */
    private function profileMediaIdFromRequest(
        StoreExpertRequest|UpdateExpertRequest $request,
        array $validated,
    ): ?string {
        $photo = $request->file('profile_photo');
        if (!$photo instanceof UploadedFile) {
            return $validated['profile_media_id'] ?? null;
        }

        return (string) $this->storePublicProfilePhoto($photo, $request, $validated)->id;
    }

    /** @param array<string, mixed> $validated */
    private function storePublicProfilePhoto(
        UploadedFile $photo,
        StoreExpertRequest|UpdateExpertRequest $request,
        array $validated,
    ): MediaAsset {
        $disk = (string) config('impact.files.public_disk', 'public');
        $extension = strtolower($photo->guessExtension() ?: $photo->getClientOriginalExtension() ?: 'jpg');
        $objectId = (string) Str::uuid7();
        $path = 'media/expert-profiles/'.now('UTC')->format('Y/m')."/{$objectId}.{$extension}";
        $temporaryPath = $photo->getRealPath();

        if ($temporaryPath === false) {
            abort(422, __('The uploaded profile photo is unavailable.'));
        }

        $storedPath = Storage::disk($disk)->putFileAs(
            dirname($path),
            $photo,
            basename($path),
            ['visibility' => 'public'],
        );
        abort_if($storedPath === false, 422, __('The profile photo could not be stored.'));

        return MediaAsset::query()->create([
            'original_name' => Str::limit($photo->getClientOriginalName(), 255, ''),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => (string) $photo->getMimeType(),
            'size_bytes' => (int) $photo->getSize(),
            'sha256' => hash_file('sha256', $temporaryPath),
            'visibility' => MediaVisibility::Public,
            'scan_status' => MediaStatus::Clean,
            'processing_status' => MediaStatus::Ready,
            'title' => __(':name profile photo', ['name' => $validated['display_name']]),
            'alt_text' => __('Profile photo of :name', ['name' => $validated['display_name']]),
            'locale' => $validated['locale'] ?? null,
            'uploaded_by' => $request->user()?->getKey(),
        ]);
    }
}
