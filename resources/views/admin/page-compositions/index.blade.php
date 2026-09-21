<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Structured publishing')"
            :title="__('Page composer')"
            :description="__('Manage localized public-page structure through controlled, versioned sections.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <x-admin.result-summary :paginator="$compositions" :context="__('Page composition register')" />
        <div class="mt-5 grid gap-3">
            @forelse ($compositions as $composition)
                <a class="group grid gap-4 border border-edge bg-white p-5 shadow-sm transition hover:border-action-500 sm:grid-cols-[1fr_auto] sm:items-center" href="{{ route('admin.page-compositions.edit', $composition) }}">
                    <span>
                        <span class="block font-editorial text-xl font-bold text-brand-950">{{ str($composition->page_key)->headline() }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ strtoupper($composition->locale) }} · {{ str($composition->template_type->value)->headline() }} · {{ trans_choice(':count section|:count sections', $composition->sections_count, ['count' => $composition->sections_count]) }}</span>
                    </span>
                    <x-ui.status-badge :status="$composition->state" />
                </a>
            @empty
                <x-admin.empty-state :title="__('No page compositions exist.')" :description="__('Run the public-content migration seeder to create the approved localized page register.')" />
            @endforelse
        </div>
        <div class="mt-6">{{ $compositions->links() }}</div>
    </div>
</x-app-layout>
