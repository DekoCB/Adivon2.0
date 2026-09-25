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
use App\Models\Caja;

/**
 * Cobertura de las Policies agregadas en Fase 3 (ImeiPolicy / CajaPolicy).
 *
 * Antes de estas Policies:
 * - ImeiController no aplicaba ningún scoping por almacén: un Almacenero
 *   podía ver y editar IMEIs de cualquier almacén de la empresa, no solo
 *   del que tiene asignado (users.almacen_id).
 * - CajaController::registrarIngreso()/registrarGasto() no verificaban en
 *   absoluto que la caja recibida por parámetro perteneciera al usuario
 *   autenticado (a diferencia de show()/cerrar(), que sí lo hacían inline).
 */
class AutorizacionImeiCajaTest extends TestCase
{
    private array $createdUsers = [];
    private array $createdAlmacenes = [];
    private array $createdProductos = [];
    private array $createdImeis = [];
    private array $createdCajas = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Imei::whereIn('id', $this->createdImeis)->forceDelete();
        Caja::whereIn('id', $this->createdCajas)->delete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        Almacen::whereIn('id', $this->createdAlmacenes)->delete();
        User::whereIn('id', $this->createdUsers)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->createdUsers = [];
        $this->createdAlmacenes = [];
        $this->createdProductos = [];
        $this->createdImeis = [];
        $this->createdCajas = [];

        parent::tearDown();
    }

    private function makeUser(string $roleName, ?int $almacenId = null): User
    {
        $role = Role::firstOrCreate(['nombre' => $roleName]);
        $uid  = uniqid();
        $user = User::create([
            'name'       => "{$roleName} Test {$uid}",
            'email'      => strtolower($roleName) . "_{$uid}@test.com",
            'password'   => bcrypt('password'),
            'role_id'    => $role->id,
            'almacen_id' => $almacenId,
        ]);
        $this->createdUsers[] = $user->id;
        return $user;
    }

    private function makeAlmacen(): Almacen
    {
        $uid = uniqid();
        $almacen = Almacen::create([
            'nombre' => "Almacen Auth {$uid}",
            'codigo' => "ALMA-{$uid}",
            'tipo'   => 'principal',
            'estado' => 'activo',
        ]);
        $this->createdAlmacenes[] = $almacen->id;
        return $almacen;
    }

    private function makeImei(int $almacenId): Imei
    {
        $uid = uniqid();
        $categoria = Categoria::firstOrCreate(
            ['codigo' => 'CEL-AUTH'],
            ['nombre' => 'Celulares Auth', 'estado' => 'activo']
        );
        $producto = Producto::create([
            'codigo'          => "CEL-AUTH-{$uid}",
            'nombre'          => "Test Auth {$uid}",
            'categoria_id'    => $categoria->id,
            'tipo_inventario' => 'serie',
            'stock_actual'    => 1,
            'stock_minimo'    => 1,
            'stock_maximo'    => 10,
            'estado'          => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;

        $imei = Imei::create([
            'codigo_imei' => (string) rand(100000000000000, 999999999999999),
            'producto_id' => $producto->id,
            'almacen_id'  => $almacenId,
            'estado_imei' => 'en_stock',
        ]);
        $this->createdImeis[] = $imei->id;
        return $imei;
    }

    private function makeCaja(User $user, int $almacenId): Caja
    {
        $caja = Caja::create([
            'user_id'        => $user->id,
            'almacen_id'     => $almacenId,
            'fecha'          => now()->toDateString(),
            'fecha_apertura' => now(),
            'monto_inicial'  => 100,
            'estado'         => 'abierta',
        ]);
        $this->createdCajas[] = $caja->id;
        return $caja;
    }

    // ── IMEI: scoping por almacén ────────────────────────────────────────

    public function test_almacenero_puede_ver_y_editar_imei_de_su_propio_almacen(): void
    {
        $almacen = $this->makeAlmacen();
        $almacenero = $this->makeUser('Almacenero', $almacen->id);
        $imei = $this->makeImei($almacen->id);

        $this->actingAs($almacenero)->get(route('inventario.imeis.show', $imei))->assertOk();
        $this->actingAs($almacenero)->get(route('inventario.imeis.edit', $imei))->assertOk();
    }

    public function test_almacenero_no_puede_ver_ni_editar_ni_cambiar_estado_de_imei_de_otro_almacen(): void
    {
        $almacenPropio = $this->makeAlmacen();
        $almacenAjeno  = $this->makeAlmacen();
        $almacenero = $this->makeUser('Almacenero', $almacenPropio->id);
        $imei = $this->makeImei($almacenAjeno->id);

        $this->actingAs($almacenero)->get(route('inventario.imeis.show', $imei))->assertForbidden();
        $this->actingAs($almacenero)->get(route('inventario.imeis.edit', $imei))->assertForbidden();
        $this->actingAs($almacenero)
            ->post(route('inventario.imeis.cambiar-estado', $imei), ['estado' => 'reservado'])
            ->assertForbidden();
    }

    public function test_administrador_puede_ver_imei_de_cualquier_almacen(): void
    {
        $almacen = $this->makeAlmacen();
        $admin = $this->makeUser('Administrador');
        $imei = $this->makeImei($almacen->id);

        $this->actingAs($admin)->get(route('inventario.imeis.show', $imei))->assertOk();
    }

    public function test_listado_de_imeis_de_almacenero_solo_muestra_su_propio_almacen(): void
    {
        $almacenPropio = $this->makeAlmacen();
        $almacenAjeno  = $this->makeAlmacen();
        $almacenero = $this->makeUser('Almacenero', $almacenPropio->id);
        $imeiPropio = $this->makeImei($almacenPropio->id);
        $imeiAjeno  = $this->makeImei($almacenAjeno->id);

        $response = $this->actingAs($almacenero)->get(route('inventario.imeis.index'));

        $response->assertOk();
        $response->assertSee($imeiPropio->codigo_imei);
        $response->assertDontSee($imeiAjeno->codigo_imei);
    }

    // ── Caja: dueño o Administrador ──────────────────────────────────────

    public function test_usuario_puede_ver_su_propia_caja(): void
    {
        $almacen = $this->makeAlmacen();
        $tienda = $this->makeUser('Tienda', $almacen->id);
        $caja = $this->makeCaja($tienda, $almacen->id);

        $this->actingAs($tienda)->get(route('caja.show', $caja))->assertOk();
    }

    public function test_usuario_no_puede_ver_ni_cerrar_caja_de_otro_usuario(): void
    {
        $almacen = $this->makeAlmacen();
        $dueno = $this->makeUser('Tienda', $almacen->id);
        $otro  = $this->makeUser('Tienda', $almacen->id);
        $caja  = $this->makeCaja($dueno, $almacen->id);

        $this->actingAs($otro)->get(route('caja.show', $caja))->assertForbidden();

        $this->actingAs($otro)
            ->post(route('caja.cerrar'), ['caja_id' => $caja->id, 'monto_real_cierre' => 100])
            ->assertForbidden();
    }

    public function test_usuario_no_puede_registrar_ingreso_ni_gasto_en_caja_de_otro_usuario(): void
    {
        $almacen = $this->makeAlmacen();
        $dueno = $this->makeUser('Tienda', $almacen->id);
        $otro  = $this->makeUser('Tienda', $almacen->id);
        $caja  = $this->makeCaja($dueno, $almacen->id);

        $this->actingAs($otro)
            ->post(route('caja.ingreso'), [
                'caja_id'     => $caja->id,
                'monto'       => 50,
                'concepto'    => 'Test',
                'metodo_pago' => 'efectivo',
            ])
            ->assertForbidden();

        $this->actingAs($otro)
            ->post(route('caja.gasto'), [
                'caja_id'         => $caja->id,
                'monto'           => 50,
                'concepto'        => 'Test',
                'categoria_gasto' => 'otros',
            ])
            ->assertForbidden();
    }

    public function test_administrador_puede_ver_y_cerrar_caja_de_cualquier_usuario(): void
    {
        $almacen = $this->makeAlmacen();
        $tienda = $this->makeUser('Tienda', $almacen->id);
        $admin  = $this->makeUser('Administrador');
        $caja   = $this->makeCaja($tienda, $almacen->id);

        $this->actingAs($admin)->get(route('caja.show', $caja))->assertOk();
    }
}
