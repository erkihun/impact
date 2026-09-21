@props(['paginator'])

<div {{ $attributes->class(['mt-6']) }}>
    {{ $paginator->links() }}
</div>
