@props(['paginator' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm overflow-hidden']) }}>
    @isset($cardHeader)
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            {{ $cardHeader }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>{{ $head }}</tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
            {{ $footer }}
        </div>
    @endisset

    @if($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</div>
