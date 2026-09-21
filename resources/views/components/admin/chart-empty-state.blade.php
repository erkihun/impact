@props(['title', 'description' => null])

<x-ui.empty-state :title="$title" :description="$description" {{ $attributes }} />
