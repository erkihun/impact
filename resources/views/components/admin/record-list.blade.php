@props([
    'columns' => [],
    'label' => null,
])

{{--
    Responsive record list.

    Desktop renders a real table with semantic column headers. Below the `lg` breakpoint
    every row becomes a stacked record card, with each cell labelled by its column name
    through a data attribute. This satisfies UIUX-04-05 (a usable mobile record pattern
    rather than a horizontally compressed table) without duplicating the markup.

    Rows are supplied via the default slot as <x-admin.record-row> elements.
--}}
<div class="admin-record-table" @if ($label) role="region" aria-label="{{ $label }}" @endif>
    <table class="admin-table">
        @if (count($columns) > 0)
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" @if (($column['srOnly'] ?? false)) class="sr-only-header" @endif>
                            {{ $column['label'] ?? $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
