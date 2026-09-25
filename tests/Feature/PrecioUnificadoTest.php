<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoPrecio;
use App\Services\PrecioRotativoService;

/**
 * Cobertura de la Fase 5: Producto::precio_venta/precio_mayorista ahora
 * delegan en PrecioRotativoService::obtenerPrecioVigente() en vez de
 * reimplementar su propio filtro (que ordenaba prioridad en sentido
 * contrario al del servicio). El caso más importante a cubrir es que un
 * producto SIN ningún precio configurado no entre en recursión infinita:
 * antes de este fix, el fallback de obtenerPrecioVigente() leía
 * $producto->precio_venta, que a su vez llama a obtenerPrecioVigente().
 */
class PrecioUnificadoTest extends TestCase
{
    private array $createdProductos = [];
    private array $createdPrecios = [];

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ProductoPrecio::whereIn('id', $this->createdPrecios)->delete();
        Producto::whereIn('id', $this->createdProductos)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->createdProductos = [];
        $this->createdPrecios = [];
        parent::tearDown();
    }

    private function makeProducto(): Producto
    {
        $uid = uniqid();
        $categoria = Categoria::firstOrCreate(['codigo' => 'CEL-PRECIO'], ['nombre' => 'Celulares Precio', 'estado' => 'activo']);
        $producto = Producto::create([
            'codigo' => "PROD-PRECIO-{$uid}", 'nombre' => "Producto Precio {$uid}",
            'categoria_id' => $categoria->id, 'tipo_inventario' => 'cantidad',
            'stock_actual' => 0, 'stock_minimo' => 1, 'stock_maximo' => 100, 'estado' => 'activo',
        ]);
        $this->createdProductos[] = $producto->id;
        return $producto;
    }

    public function test_precio_venta_sin_precio_configurado_devuelve_cero_sin_recursion(): void
    {
        $producto = $this->makeProducto();

        // Antes de este fix, esta línea entraba en recursión infinita
        // (RangeError / fatal por profundidad de pila).
        $this->assertSame(0.0, $producto->precio_venta);
        $this->assertNull($producto->precio_mayorista);
    }

    public function test_precio_venta_coincide_con_precio_rotativo_service(): void
    {
        $producto = $this->makeProducto();

        $precio = ProductoPrecio::create([
            'producto_id' => $producto->id,
            'tipo_precio' => 'venta_regular',
            'precio'      => 199.90,
            'moneda'      => 'PEN',
            'activo'      => true,
        ]);
        $this->createdPrecios[] = $precio->id;

        $viaAccessor = $producto->precio_venta;
        $viaService  = app(PrecioRotativoService::class)
            ->obtenerPrecioVigente($producto->fresh(), tipoPrecio: 'venta_regular')['precio'];

        $this->assertEquals(199.90, $viaAccessor);
        $this->assertEquals((float) $viaService, $viaAccessor);
    }

    public function test_precio_mayorista_solo_considera_tipo_precio_mayorista(): void
    {
        $producto = $this->makeProducto();

        $regular = ProductoPrecio::create([
            'producto_id' => $producto->id, 'tipo_precio' => 'venta_regular',
            'precio' => 100.00, 'moneda' => 'PEN', 'activo' => true,
        ]);
        $mayorista = ProductoPrecio::create([
            'producto_id' => $producto->id, 'tipo_precio' => 'venta_mayorista',
            'precio' => 80.00, 'moneda' => 'PEN', 'activo' => true,
        ]);
        $this->createdPrecios[] = $regular->id;
        $this->createdPrecios[] = $mayorista->id;

        $this->assertEquals(100.00, $producto->precio_venta);
        $this->assertEquals(80.00, $producto->precio_mayorista);
    }
}
