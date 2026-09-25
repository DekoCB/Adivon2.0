<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Almacen;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\StockAlmacen;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Services\VentaStockService;
use App\Services\VentaCajaService;

/**
 * Cobertura de la Fase 6 (3/3): VentaStockService y VentaCajaService,
 * extraídos de VentaService (1282 → 945 líneas). El valor concreto de esta
 * extracción es que estos dos concerns ahora se pueden probar en aislado,
 * sin pasar por todo el flujo de crearVenta()/HTTP — exactamente lo que
 * hacen estos tests.
 */
class VentaStockYCajaServiceTest extends TestCase
{
    private array $createdUsers = [];
    private array $createdAlmacenes = [];
    private array $createdProductos = [];
    private array $createdVentas = [];
    private array $createdCajas = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        \App\Models\MovimientoInventario::query()->delete();
        MovimientoCaja::whereIn('caja_id', $this->createdCajas)->delete();
        Caja::whereIn('id', $this->createdCajas)->delete();
        DetalleVenta::whereIn('venta_id', $this->createdVentas)->delete();
        Venta::whereIn('id', $this->createdVentas)->delete();
        StockAlmacen::whereIn('almacen_id', $this->createdAlmacenes)->delete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        Almacen::whereIn('id', $this->createdAlmacenes)->delete();
        User::whereIn('id', $this->createdUsers)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createdUsers = [];
        $this->createdAlmacenes = [];
        $this->createdProductos = [];
        $this->createdVentas = [];
        $this->createdCajas = [];

        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['nombre' => 'Administrador']);
        $uid  = uniqid();
        $user = User::create([
            'name' => "Admin VSC {$uid}", 'email' => "admin_vsc_{$uid}@test.com",
            'password' => bcrypt('password'), 'role_id' => $role->id,
        ]);
        $this->createdUsers[] = $user->id;
        return $user;
    }

    private function makeAlmacen(): Almacen
    {
        $almacen = Almacen::create([
            'nombre' => 'Almacen VSC', 'codigo' => 'AS' . substr(uniqid(), -10),
            'tipo' => 'principal', 'estado' => 'activo',
        ]);
        $this->createdAlmacenes[] = $almacen->id;
        return $almacen;
    }

    private function makeProductoCantidad(): Producto
    {
        $uid = uniqid();
        $categoria = Categoria::firstOrCreate(['codigo' => 'ACC-VSC'], ['nombre' => 'Accesorios VSC', 'estado' => 'activo']);
        $producto = Producto::create([
            'codigo' => "ACC-VSC-{$uid}", 'nombre' => "Test VentaStock {$uid}",
            'categoria_id' => $categoria->id, 'tipo_inventario' => 'cantidad',
            'stock_actual' => 0, 'stock_minimo' => 1, 'stock_maximo' => 100, 'estado' => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;
        return $producto;
    }

    public function test_validar_stock_disponible_pasa_cuando_hay_stock_suficiente(): void
    {
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();
        StockAlmacen::create(['producto_id' => $producto->id, 'almacen_id' => $almacen->id, 'cantidad' => 10]);

        // No debe lanzar excepción.
        app(VentaStockService::class)->validarStockDisponible(
            [['producto_id' => $producto->id, 'cantidad' => 5]],
            $almacen->id
        );
        $this->assertTrue(true);
    }

    public function test_validar_stock_disponible_lanza_excepcion_si_no_alcanza(): void
    {
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();
        StockAlmacen::create(['producto_id' => $producto->id, 'almacen_id' => $almacen->id, 'cantidad' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        app(VentaStockService::class)->validarStockDisponible(
            [['producto_id' => $producto->id, 'cantidad' => 5]],
            $almacen->id
        );
    }

    public function test_descontar_stock_y_revertir_stock_dejan_la_cantidad_como_estaba(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();
        StockAlmacen::create(['producto_id' => $producto->id, 'almacen_id' => $almacen->id, 'cantidad' => 10]);

        $venta = Venta::create([
            'codigo' => 'V-VSC-' . uniqid(), 'user_id' => auth()->id(), 'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(), 'tipo_comprobante' => 'boleta',
            'subtotal' => 0, 'igv' => 0, 'total' => 0, 'estado_pago' => 'pagado', 'condicion_pago' => 'contado',
        ]);
        $this->createdVentas[] = $venta->id;
        $detalle = DetalleVenta::create([
            'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 4,
            'precio_unitario' => 10, 'precio_con_igv' => 11.8, 'subtotal' => 40, 'subtotal_con_igv' => 47.2,
        ]);

        app(VentaStockService::class)->descontarStock($producto->id, $almacen->id, 4);
        $this->assertSame(6, StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->first()->cantidad);

        app(VentaStockService::class)->revertirStock($venta, 'Test revertir');
        $this->assertSame(10, StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->first()->cantidad);
    }

    public function test_registrar_en_caja_crea_ingreso_para_venta_pagada(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);
        $almacen = $this->makeAlmacen();

        $caja = Caja::create([
            'user_id' => $admin->id, 'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(), 'fecha_apertura' => now(),
            'monto_inicial' => 0, 'estado' => 'abierta',
        ]);
        $this->createdCajas[] = $caja->id;

        $venta = Venta::create([
            'codigo' => 'V-VSC2-' . uniqid(), 'user_id' => $admin->id, 'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(), 'tipo_comprobante' => 'boleta', 'metodo_pago' => 'efectivo',
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'estado_pago' => 'pagado', 'condicion_pago' => 'contado',
        ]);
        $this->createdVentas[] = $venta->id;

        $resultado = app(VentaCajaService::class)->registrarEnCaja($venta, 'venta');

        $this->assertTrue($resultado);
        $this->assertSame(1, MovimientoCaja::where('caja_id', $caja->id)->where('venta_id', $venta->id)->where('tipo', 'ingreso')->count());
    }
}
