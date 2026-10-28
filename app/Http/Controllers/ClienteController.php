<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\SunatService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::withCount('ventas')
            ->orderBy('nombre')
            ->get();

        $canCreate = in_array(auth()->user()->role->nombre, ['Administrador', 'Vendedor', 'Tienda']);
        $canEdit = in_array(auth()->user()->role->nombre, ['Administrador', 'Vendedor', 'Tienda']);
        $canDelete = auth()->user()->role->nombre === 'Administrador';

        return view('clientes.index', compact('clientes', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC,CE',
            'numero_documento' => ['required', 'string', 'max:11', 'unique:clientes,numero_documento', $this->reglaNumeroDocumento($request->tipo_documento)],
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'ubigeo' => 'nullable|string|max:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'estado' => 'required|in:activo,inactivo',
        ], [
            'numero_documento.unique' => 'Este documento ya está registrado',
            'numero_documento.regex' => 'El número de documento no tiene el formato correcto para el tipo seleccionado.',
            'nombre.required' => 'El nombre es obligatorio',
        ]);

        $cliente = Cliente::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'id'     => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);
        }

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente registrado exitosamente');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC,CE',
            'numero_documento' => ['required', 'string', 'max:11', 'unique:clientes,numero_documento,' . $cliente->id, $this->reglaNumeroDocumento($request->tipo_documento)],
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'ubigeo' => 'nullable|string|max:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'estado' => 'required|in:activo,inactivo',
        ], [
            'numero_documento.regex' => 'El número de documento no tiene el formato correcto para el tipo seleccionado.',
        ]);

        $cliente->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'id'     => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);
        }

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy(Cliente $cliente)
    {
        try {
            $cliente->delete();
            return redirect()
                ->route('clientes.index')
                ->with('success', 'Cliente eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->route('clientes.index')
                ->with('error', 'No se puede eliminar el cliente porque tiene ventas asociadas');
        }
    }

    public function consultarDocumento(Request $request)
    {
        $tipo   = strtoupper($request->input('tipo', 'DNI'));
        $numero = trim($request->input('numero', ''));
        $sunat  = app(SunatService::class);

        if ($tipo === 'RUC') {
            $result = $sunat->consultarRuc($numero);
            if (!$result['success']) {
                return response()->json(['error' => $result['message']], 422);
            }
            return response()->json([
                'nombre'       => $result['data']['razon_social']     ?? '',
                'razon_social' => $result['data']['razon_social']     ?? '',
                'direccion'    => $result['data']['direccion_fiscal']  ?? '',
                'distrito'     => $result['data']['distrito']          ?? '',
                'provincia'    => $result['data']['provincia']         ?? '',
                'departamento' => $result['data']['departamento']      ?? '',
                'ubigeo'       => $result['data']['ubigeo']             ?? '',
            ]);
        }

        $result = $sunat->consultarDni($numero);
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }
        return response()->json([
            'nombre' => $result['data']['nombre'] ?? '',
        ]);
    }

    /**
     * Sin esto, numero_documento solo validaba "string, máx. 11 caracteres"
     * sin exigir dígitos ni la longitud exacta por tipo — un DNI de 2
     * caracteres o un RUC con letras pasaba la validación y recién fallaba
     * (o peor, se enviaba mal) al intentar facturar por SUNAT.
     */
    private function reglaNumeroDocumento(?string $tipoDocumento): string
    {
        return match (strtoupper($tipoDocumento ?? '')) {
            'DNI' => 'regex:/^\d{8}$/',
            'RUC' => 'regex:/^\d{11}$/',
            default => 'regex:/^[A-Za-z0-9]+$/', // CE: alfanumérico, longitud variable
        };
    }
}
