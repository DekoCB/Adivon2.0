<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $portafolio->nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }

        .header { background: #1e3a8a; color: #fff; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .header .logo { height: 34px; }
        .header .titulo h1 { font-size: 16px; font-weight: bold; }
        .header .titulo p { font-size: 9px; opacity: .8; margin-top: 2px; }
        .header .meta { font-size: 8px; text-align: right; opacity: .85; }

        .grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .card { width: 31.5%; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; margin-bottom: 10px; }
        .card .foto { height: 100px; background: #f9fafb; text-align: center; vertical-align: middle; }
        .card .foto img { max-height: 95px; max-width: 95%; margin-top: 3px; }
        .card .foto .sin-foto { color: #d1d5db; font-size: 8px; padding-top: 42px; display: block; }
        .card .info { padding: 6px 8px; }
        .card .marca { font-size: 7px; color: #9ca3af; text-transform: uppercase; }
        .card .nombre { font-size: 9px; font-weight: bold; color: #111827; margin-top: 1px; line-height: 1.25; }
        .card .precio { font-size: 11px; font-weight: bold; color: #047857; margin-top: 4px; }

        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 7px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="titulo">
            @if(is_file(public_path('images/logo-blanco.png')))
                <img class="logo" src="{{ public_path('images/logo-blanco.png') }}" style="margin-bottom: 6px;">
            @endif
            <h1>{{ $portafolio->nombre }}</h1>
            @if($portafolio->descripcion)
                <p>{{ $portafolio->descripcion }}</p>
            @endif
        </div>
        <div class="meta">
            @if($portafolio->fecha_inicio || $portafolio->fecha_fin)
                <p>Vigencia: {{ $portafolio->fecha_inicio?->format('d/m/Y') ?? '—' }} &ndash; {{ $portafolio->fecha_fin?->format('d/m/Y') ?? '—' }}</p>
            @endif
            <p>Generado el {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="grid">
        @foreach($portafolio->items as $item)
            <div class="card">
                <div class="foto">
                    @if($item->producto->imagen_local)
                        <img src="{{ $item->producto->imagen_local }}">
                    @else
                        <span class="sin-foto">Sin foto</span>
                    @endif
                </div>
                <div class="info">
                    <p class="marca">
                        {{ $item->producto->marca?->nombre }}
                        @if($item->producto->modelo) &middot; {{ $item->producto->modelo->nombre }} @endif
                    </p>
                    <p class="nombre">{{ $item->producto->nombre }}</p>
                    <p class="precio">S/ {{ number_format($item->precio_congelado, 2) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Corporación Adivon SAC &middot; Precios vigentes al momento de impresión de este catálogo, sujetos a cambio en próximas ediciones.
    </div>
</body>
</html>
