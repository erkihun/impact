<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('People directory')"
            :title="__('Create expert')"
            :description="__('Register a dedicated expert profile without using the generic content editor.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <form method="POST" action="{{ route('admin.experts.store') }}" enctype="multipart/form-data" class="admin-panel mx-auto max-w-5xl" data-prevent-duplicate>
            @csrf
            @include('admin.experts._form', ['submitLabel' => __('Create expert')])
        </form>
    </div>
</x-app-layout>
