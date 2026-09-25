@props(['action' => null, 'filters' => []])

<form method="GET" action="{{ $action ?? url()->current() }}" {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm p-4 mb-6']) }}>
    {{ $slot }}

    <div class="flex items-center gap-2 mt-3">
        <button type="submit" class="bg-blue-900 hover:bg-blue-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-search mr-1"></i>Filtrar
        </button>
        @if(request()->hasAny($filters))
            <a href="{{ $action ?? url()->current() }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                <i class="fas fa-times mr-1"></i>Limpiar
            </a>
            @isset($resultCount)
                <span class="text-xs text-blue-600 font-medium">{{ $resultCount }} resultado(s) encontrado(s)</span>
            @endisset
        @endif
    </div>
</form>
