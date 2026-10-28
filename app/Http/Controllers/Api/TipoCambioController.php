<?php
// app/Http/Controllers/Api/TipoCambioController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TipoCambioController extends Controller
{
    /**
     * Obtener tipo de cambio del día (SUNAT)
     */
    public function obtener()
    {
        $default = [
            'compra' => 3.70,
            'venta' => 3.75,
            'fecha' => now()->format('Y-m-d'),
        ];

        // Cachear por 6 horas solo si se obtuvo un valor real de la API.
        $tipoCambio = Cache::get('tipo_cambio');

        if (!$tipoCambio) {
            try {
                $response = Http::timeout(5)->get('https://api.apis.net.pe/v1/tipo-cambio-sunat');

                if ($response->successful()) {
                    $data = $response->json();
                    $tipoCambio = [
                        'compra' => $data['compra'] ?? $default['compra'],
                        'venta' => $data['venta'] ?? $default['venta'],
                        'fecha' => $data['fecha'] ?? $default['fecha'],
                    ];
                    Cache::put('tipo_cambio', $tipoCambio, 3600 * 6);
                } else {
                    $tipoCambio = $default;
                    Cache::put('tipo_cambio', $tipoCambio, 300); // reintentar pronto
                }
            } catch (\Exception $e) {
                $tipoCambio = $default;
                Cache::put('tipo_cambio', $tipoCambio, 300); // reintentar pronto
            }
        }

        return response()->json($tipoCambio);
    }
}