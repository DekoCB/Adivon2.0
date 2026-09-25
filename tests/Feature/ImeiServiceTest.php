<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Imei;
use App\Models\MovimientoInventario;
use App\Services\ImeiService;

/**
 * Cobertura de la Fase 6: ImeiService, extraído de ImeiController (antes
 * esta lógica vivía como métodos privados del controller, sin ningún test
 * — era el único módulo de compra/venta/traslado/caja/imei sin su propio
 * *Service).
 */
class ImeiServiceTest extends TestCase
{
    private array $createdUsers = [];
    private array $createdAlmacenes = [];
    private array $createdProductos = [];
    private array $createdImeis = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        MovimientoInventario::query()->delete();
        Imei::whereIn('id', $this->createdImeis)->forceDelete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        Almacen::whereIn('id', $this->createdAlmacenes)->delete();
        User::whereIn('id', $this->createdUsers)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createdUsers = [];
        $this->createdAlmacenes = [];
        $this->createdProductos = [];
        $this->createdImeis = [];

        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['nombre' => 'Administrador']);
        $uid  = uniqid();
        $user = User::create([
            'name' => "Admin Imei {$uid}", 'email' => "admin_imei_{$uid}@test.com",
            'password' => bcrypt('password'), 'role_id' => $role->id,
        ]);
        $this->createdUsers[] = $user->id;
        return $user;
    }

    private function makeAlmacen(): Almacen
    {
        $almacen = Almacen::create([
            'nombre' => 'Almacen Imei', 'codigo' => 'AI' . substr(uniqid(), -10),
            'tipo' => 'principal', 'estado' => 'activo',
        ]);
        $this->createdAlmacenes[] = $almacen->id;
        return $almacen;
    }

    private function makeProductoSerie(): Producto
    {
        $uid = uniqid();
        $categoria = Categoria::firstOrCreate(['codigo' => 'CEL-IMEISVC'], ['nombre' => 'Celulares ImeiSvc', 'estado' => 'activo']);
        $producto = Producto::create([
            'codigo' => "CEL-IMEISVC-{$uid}", 'nombre' => "Test ImeiService {$uid}",
            'categoria_id' => $categoria->id, 'tipo_inventario' => 'serie',
            'stock_actual' => 0, 'stock_minimo' => 1, 'stock_maximo' => 10, 'estado' => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;
        return $producto;
    }

    public function test_registrar_en_stock_incrementa_stock_del_producto(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoSerie();

        $imei = app(ImeiService::class)->registrar([
            'codigo_imei' => (string) rand(100000000000000, 999999999999999),
            'producto_id' => $producto->id,
            'almacen_id'  => $almacen->id,
            'estado_imei' => 'en_stock',
        ]);
        $this->createdImeis[] = $imei->id;

        $producto->refresh();
        $this->assertSame(1, $producto->stock_actual);
        $this->assertNotNull($imei->qr_path, 'Debe generar y guardar la ruta del QR.');
    }

    public function test_registrar_en_garantia_no_incrementa_stock(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoSerie();

        $imei = app(ImeiService::class)->registrar([
            'codigo_imei' => (string) rand(100000000000000, 999999999999999),
            'producto_id' => $producto->id,
            'almacen_id'  => $almacen->id,
            'estado_imei' => 'garantia',
        ]);
        $this->createdImeis[] = $imei->id;

        $producto->refresh();
        $this->assertSame(0, $producto->stock_actual);
    }

    public function test_cambiar_estado_actualiza_stock_y_registra_movimiento(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoSerie();

        $imei = app(ImeiService::class)->registrar([
            'codigo_imei' => (string) rand(100000000000000, 999999999999999),
            'producto_id' => $producto->id,
            'almacen_id'  => $almacen->id,
            'estado_imei' => 'en_stock',
        ]);
        $this->createdImeis[] = $imei->id;
        $this->assertSame(1, $producto->fresh()->stock_actual);

        app(ImeiService::class)->cambiarEstado($imei->fresh(), 'vendido');

        $producto->refresh();
        $this->assertSame(0, $producto->stock_actual, 'Al vender, el IMEI deja de contar como stock disponible.');

        $movimiento = MovimientoInventario::where('imei_id', $imei->id)->latest()->first();
        $this->assertNotNull($movimiento);
        $this->assertSame('salida', $movimiento->tipo_movimiento);
        $this->assertSame($admin->id, $movimiento->user_id);
    }

    public function test_actualizar_sincroniza_color_desde_la_variante_seleccionada(): void
    {
        $this->actingAs($this->makeAdmin());
        $almacen  = $this->makeAlmacen();
        $producto = $this->makeProductoSerie();

        $imei = app(ImeiService::class)->registrar([
            'codigo_imei' => (string) rand(100000000000000, 999999999999999),
            'producto_id' => $producto->id,
            'almacen_id'  => $almacen->id,
            'estado_imei' => 'en_stock',
        ]);
        $this->createdImeis[] = $imei->id;

        app(ImeiService::class)->actualizar($imei->fresh(), [
            'almacen_id'  => $almacen->id,
            'estado_imei' => 'reservado',
        ]);

        $this->assertSame('reservado', $imei->fresh()->estado_imei);
    }
}
