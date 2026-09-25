<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\Almacen;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Compra;
use App\Models\Caja;
use App\Models\Auditoria;
use App\Services\CompraService;

/**
 * Cobertura de la Fase 4: bitácora genérica (auditorias) para operaciones
 * críticas de Compras/Caja que antes no dejaban ningún rastro estructurado
 * (eliminarCompra era un hard delete sin auditoría; forzarCierre solo
 * dejaba una nota de texto dentro de un MovimientoCaja).
 */
class AuditoriaOperacionesTest extends TestCase
{
    private array $createdUsers = [];
    private array $createdAlmacenes = [];
    private array $createdProveedores = [];
    private array $createdProductos = [];
    private array $createdCajas = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Auditoria::query()->delete();
        Caja::whereIn('id', $this->createdCajas)->delete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        Proveedor::whereIn('id', $this->createdProveedores)->delete();
        Almacen::whereIn('id', $this->createdAlmacenes)->delete();
        User::whereIn('id', $this->createdUsers)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createdUsers = [];
        $this->createdAlmacenes = [];
        $this->createdProveedores = [];
        $this->createdProductos = [];
        $this->createdCajas = [];

        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['nombre' => 'Administrador']);
        $uid  = uniqid();
        $user = User::create([
            'name' => "Admin Auditoria {$uid}", 'email' => "admin_aud_{$uid}@test.com",
            'password' => bcrypt('password'), 'role_id' => $role->id,
        ]);
        $this->createdUsers[] = $user->id;
        return $user;
    }

    public function test_eliminar_compra_registra_auditoria_con_snapshot(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $almacen = Almacen::create(['nombre' => 'Almacen Aud', 'codigo' => 'AA' . substr(uniqid(), -10), 'tipo' => 'principal', 'estado' => 'activo']);
        $this->createdAlmacenes[] = $almacen->id;

        $proveedor = Proveedor::create(['ruc' => substr('20' . uniqid(), 0, 11), 'razon_social' => 'Prov Aud', 'estado' => 'activo']);
        $this->createdProveedores[] = $proveedor->id;

        $categoria = Categoria::firstOrCreate(['codigo' => 'CEL-AUD'], ['nombre' => 'Celulares Aud', 'estado' => 'activo']);
        $producto = Producto::create([
            'codigo' => 'PROD-AUD-' . uniqid(), 'nombre' => 'Producto Aud',
            'categoria_id' => $categoria->id, 'tipo_inventario' => 'cantidad',
            'stock_actual' => 0, 'stock_minimo' => 1, 'stock_maximo' => 100, 'estado' => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;

        $compra = app(CompraService::class)->registrarCompra([
            'codigo' => 'C-AUD-' . uniqid(), 'proveedor_id' => $proveedor->id, 'almacen_id' => $almacen->id,
            'user_id' => $admin->id, 'numero_factura' => 'F-AUD-' . uniqid(), 'fecha' => now()->toDateString(),
            'forma_pago' => 'contado', 'tipo_moneda' => 'PEN', 'tipo_cambio' => 1.0, 'tipo_compra' => 'local',
            'subtotal' => 0, 'igv' => 0, 'total' => 0, 'total_pen' => 0, 'estado' => 'registrado',
        ], [[
            'producto_id' => $producto->id, 'cantidad' => 5, 'precio_unitario' => 10.00, 'descuento' => 0,
        ]]);
        $compraId = $compra->id;

        $this->assertSame(0, Auditoria::where('auditable_type', Compra::class)->where('auditable_id', $compraId)->count());

        app(CompraService::class)->eliminarCompra($compra->fresh());

        $this->assertDatabaseMissing('compras', ['id' => $compraId]);

        $registro = Auditoria::where('auditable_type', Compra::class)
            ->where('auditable_id', $compraId)
            ->where('accion', 'eliminar')
            ->first();

        $this->assertNotNull($registro, 'Debe quedar una entrada de auditoría para la compra eliminada.');
        $this->assertSame($admin->id, $registro->usuario_id);
        $this->assertSame($compraId, $registro->datos_anteriores['id']);
        $this->assertNull($registro->datos_nuevos);
    }

    public function test_forzar_cierre_de_caja_registra_auditoria(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $almacen = Almacen::create(['nombre' => 'Almacen Aud2', 'codigo' => 'AB' . substr(uniqid(), -10), 'tipo' => 'principal', 'estado' => 'activo']);
        $this->createdAlmacenes[] = $almacen->id;

        $caja = Caja::create([
            'user_id' => $admin->id, 'almacen_id' => $almacen->id,
            'fecha' => now()->toDateString(), 'fecha_apertura' => now(),
            'monto_inicial' => 100, 'monto_final' => 100, 'estado' => 'abierta',
        ]);
        $this->createdCajas[] = $caja->id;

        $this->post(route('admin.cajas.forzar-cierre', $caja), [
            'observaciones' => 'Cierre forzado de prueba automatizada.',
        ])->assertRedirect();

        $registro = Auditoria::where('auditable_type', Caja::class)
            ->where('auditable_id', $caja->id)
            ->where('accion', 'forzar_cierre')
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('abierta', $registro->datos_anteriores['estado']);
        $this->assertSame('cerrada', $registro->datos_nuevos['estado']);
    }

    public function test_registro_de_auditoria_no_se_puede_modificar_ni_eliminar(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $almacen = Almacen::create(['nombre' => 'Almacen Aud3', 'codigo' => 'AC' . substr(uniqid(), -10), 'tipo' => 'principal', 'estado' => 'activo']);
        $this->createdAlmacenes[] = $almacen->id;

        $registro = Auditoria::registrar($almacen, 'editar', ['x' => 1], ['x' => 2]);

        $this->expectException(\RuntimeException::class);
        $registro->update(['accion' => 'otra']);
    }
}
