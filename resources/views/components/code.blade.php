@props([])

{{-- Firma visual para códigos del negocio: producto, IMEI, serie de comprobante.
     Distinto de <x-badge> (que es para estado) — este es solo para mostrar un
     código tal cual, en monoespaciada. --}}
<span {{ $attributes->merge(['class' => 'inline-block font-mono text-xs px-2 py-0.5 rounded-md bg-coral-50 text-coral-700 dark:bg-coral-950 dark:text-coral-300 whitespace-nowrap']) }}>{{ $slot }}</span>
