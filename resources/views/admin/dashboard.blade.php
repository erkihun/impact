<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Attention and recent work')"
            :title="__('Operations dashboard')"
            :description="__('Work waiting on you, and what changed recently. Everything shown is limited to the modules you are authorized to access.')"
        />
    </x-slot>

    <div class="admin-workspace">
        @if (count($metrics))
            <section aria-labelledby="attention-summary-title">
                <h2 id="attention-summary-title" class="sr-only">{{ __('Attention summary') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($metrics as $label => $value)
                        <x-admin.kpi-card
                            :label="__(str($label)->headline()->toString())"
                            :value="number_format($value)"
                            :context="__('Live count from authorized records')"
                        />
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="grid gap-6">
                {{-- Primary attention queue: content that is blocked on a reviewer. --}}
                @if ($awaitingReview instanceof \Illuminate\Support\Collection)
                    <section class="admin-attention-rail" aria-labelledby="awaiting-review-title">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="eyebrow">{{ __('Needs a decision') }}</p>
                                <h2 id="awaiting-review-title" class="mt-2 font-editorial text-xl font-bold text-brand-950">{{ __('Awaiting review') }}</h2>
                            </div>
                            <a class="button-tertiary" href="{{ route('admin.content.index', ['status' => 'in_review']) }}">{{ __('Open the review queue') }} <span aria-hidden="true">→</span></a>
                        </div>

                        @forelse ($awaitingReview as $item)
                            <article class="mt-4 border-t border-slate-200 pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.status-badge :status="$item->status" />
                                    <span class="text-xs font-bold uppercase tracking-[0.12em] text-muted">{{ __(str($item->type->value)->headline()->toString()) }}</span>
                                </div>
                                <h3 class="mt-2 text-base font-bold text-brand-950">
                                    <a class="hover:text-action-700" href="{{ route('admin.content.show', $item) }}">{{ $item->currentVersion?->title ?? __('Untitled content') }}</a>
                                </h3>
                                <p class="mt-1 text-sm text-muted">
                                    {{ __('Owner') }}: {{ $item->owner?->name ?? __('Unassigned') }}
                                    @if ($item->updated_at)
                                        · <time datetime="{{ $item->updated_at->toIso8601String() }}">{{ $item->updated_at->diffForHumans() }}</time>
                                    @endif
                                </p>
                            </article>
                        @empty
                            <p class="mt-4 border-t border-slate-200 pt-4 text-sm leading-6 text-muted">
                                {{ __('Nothing is waiting for review. Content submitted for approval will appear here.') }}
                            </p>
                        @endforelse
                    </section>
                @endif

                {{-- Secondary queue: engagement requests that are still open. --}}
                @if ($openEngagements instanceof \Illuminate\Support\Collection)
                    <section class="admin-panel" aria-labelledby="open-engagements-title">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <h2 id="open-engagements-title" class="font-editorial text-lg font-bold text-brand-950">{{ __('Open engagement requests') }}</h2>
                            <a class="button-tertiary" href="{{ route('admin.engagement.index') }}">{{ __('Open the queue') }} <span aria-hidden="true">→</span></a>
                        </div>

                        @forelse ($openEngagements as $submission)
                            <article class="mt-4 border-t border-slate-200 pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.status-badge :status="$submission->status" />
                                    <span class="font-mono text-xs font-bold text-muted">{{ $submission->reference_no }}</span>
                                </div>
                                <h3 class="mt-2 text-base font-bold text-brand-950">
                                    <a class="hover:text-action-700" href="{{ route('admin.engagement.show', $submission) }}">{{ $submission->organization_name ?: $submission->contact_name }}</a>
                                </h3>
                                <p class="mt-1 text-sm text-muted">
                                    {{ $submission->assignee?->name ? __('Assigned to :name', ['name' => $submission->assignee->name]) : __('Unassigned') }}
                                    @if ($submission->submitted_at)
                                        · <time datetime="{{ $submission->submitted_at->toIso8601String() }}">{{ $submission->submitted_at->diffForHumans() }}</time>
                                    @endif
                                </p>
                            </article>
                        @empty
                            <p class="mt-4 border-t border-slate-200 pt-4 text-sm leading-6 text-muted">
                                {{ __('No open requests. New consultation, proposal and contact requests appear here as they arrive.') }}
                            </p>
                        @endforelse
                    </section>
                @endif

                @if (! count($metrics))
                    <x-ui.empty-state
                        :title="__('No operational metrics are available for your permissions.')"
                        :description="__('Use the navigation to access the workspaces assigned to your role.')"
                    />
                @endif
            </div>

            <div class="grid gap-6">
                @if (count($quickActions))
                    <section class="admin-panel" aria-labelledby="quick-actions-title">
                        <h2 id="quick-actions-title" class="font-editorial text-lg font-bold text-brand-950">{{ __('Quick actions') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Only actions authorized by the backend are shown.') }}</p>
                        <div class="mt-5 grid gap-3">
                            @foreach ($quickActions as $action)
                                <x-admin.quick-action-card
                                    :href="$action['route']"
                                    :title="$action['label']"
                                    icon="external"
                                />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($recentWork instanceof \Illuminate\Support\Collection && $recentWork->isNotEmpty())
                    <section class="admin-panel" aria-labelledby="recent-work-title">
                        <h2 id="recent-work-title" class="font-editorial text-lg font-bold text-brand-950">{{ __('Recently edited') }}</h2>
                        <ul class="mt-4 grid divide-y divide-slate-200">
                            @foreach ($recentWork as $item)
                                <li class="py-3 first:pt-0">
                                    <a class="text-sm font-bold text-brand-950 hover:text-action-700" href="{{ route('admin.content.show', $item) }}">
                                        {{ $item->currentVersion?->title ?? __('Untitled content') }}
                                    </a>
                                    @if ($item->updated_at)
                                        <p class="mt-1 text-xs text-muted">
                                            <time datetime="{{ $item->updated_at->toIso8601String() }}">{{ $item->updated_at->diffForHumans() }}</time>
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <aside class="admin-panel">
                    <p class="eyebrow">{{ __('Security') }}</p>
                    <h2 class="mt-3 font-bold text-brand-950">{{ __('Protected session') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ __('Administrative work is permission-aware and important changes are audited. Sign out when using a shared device.') }}</p>
                    <a class="text-link mt-4" href="{{ route('profile.edit') }}">{{ __('Review profile and security') }} →</a>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
