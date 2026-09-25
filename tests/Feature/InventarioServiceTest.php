<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Services\InventarioService;

/**
 * Cobertura de la Fase 6: InventarioService, extraído de
 * MovimientoInventario::registrarMovimiento() (antes un método estático de
 * ~230 líneas en el Model, con logging de depuración de desarrollo, sin
 * ningún test).
 */
class InventarioServiceTest extends TestCase
{
    private array $createdUsers = [];
    private array $createdAlmacenes = [];
    private array $createdProductos = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        \App\Models\MovimientoInventario::query()->delete();
        StockAlmacen::whereIn('almacen_id', $this->createdAlmacenes)->delete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        Almacen::whereIn('id', $this->createdAlmacenes)->delete();
        User::whereIn('id', $this->createdUsers)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createdUsers = [];
        $this->createdAlmacenes = [];
        $this->createdProductos = [];

        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['nombre' => 'Administrador']);
        $uid  = uniqid();
        $user = User::create([
            'name' => "Admin Inv {$uid}", 'email' => "admin_inv_{$uid}@test.com",
            'password' => bcrypt('password'), 'role_id' => $role->id,
        ]);
        $this->createdUsers[] = $user->id;
        return $user;
    }

    private function makeAlmacen(): Almacen
    {
        $almacen = Almacen::create([
            'nombre' => 'Almacen Inv', 'codigo' => 'AV' . substr(uniqid(), -10),
            'tipo' => 'principal', 'estado' => 'activo',
        ]);
        $this->createdAlmacenes[] = $almacen->id;
        return $almacen;
    }

    private function makeProductoCantidad(): Producto
    {
        $uid = uniqid();
        $categoria = Categoria::firstOrCreate(['codigo' => 'ACC-INVSVC'], ['nombre' => 'Accesorios InvSvc', 'estado' => 'activo']);
        $producto = Producto::create([
            'codigo' => "ACC-INVSVC-{$uid}", 'nombre' => "Test InventarioService {$uid}",
            'categoria_id' => $categoria->id, 'tipo_inventario' => 'cantidad',
            'stock_actual' => 0, 'stock_minimo' => 1, 'stock_maximo' => 100, 'estado' => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;
        return $producto;
    }

    public function test_ingreso_incrementa_stock_almacen_y_producto(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();

        app(InventarioService::class)->registrarMovimiento([
            'producto_id' => $producto->id, 'almacen_id' => $almacen->id,
            'tipo_movimiento' => 'ingreso', 'cantidad' => 10, 'motivo' => 'Test',
        ]);

        $this->assertSame(10, StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->first()->cantidad);
        $this->assertSame(10, $producto->fresh()->stock_actual);
    }

    public function test_salida_mayor_al_stock_disponible_falla_sin_dejar_negativo(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();

        app(InventarioService::class)->registrarMovimiento([
            'producto_id' => $producto->id, 'almacen_id' => $almacen->id,
            'tipo_movimiento' => 'ingreso', 'cantidad' => 5, 'motivo' => 'Test',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No hay suficiente stock');

        app(InventarioService::class)->registrarMovimiento([
            'producto_id' => $producto->id, 'almacen_id' => $almacen->id,
            'tipo_movimiento' => 'salida', 'cantidad' => 10, 'motivo' => 'Test',
        ]);
    }

    public function test_transferencia_directa_acredita_almacen_destino(): void
    {
        $this->actingAs($this->makeAdmin());
        $origen   = $this->makeAlmacen();
        $destino  = $this->makeAlmacen();
        $producto = $this->makeProductoCantidad();

        app(InventarioService::class)->registrarMovimiento([
            'producto_id' => $producto->id, 'almacen_id' => $origen->id,
            'tipo_movimiento' => 'ingreso', 'cantidad' => 20, 'motivo' => 'Test',
        ]);

        app(InventarioService::class)->registrarMovimiento([
            'producto_id' => $producto->id, 'almacen_id' => $origen->id,
            'almacen_destino_id' => $destino->id,
            'tipo_movimiento' => 'transferencia', 'cantidad' => 8, 'motivo' => 'Test',
        ]);

        $this->assertSame(12, StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $origen->id)->first()->cantidad);
        $this->assertSame(8, StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $destino->id)->first()->cantidad);
    }
}
