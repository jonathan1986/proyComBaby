<?php
namespace Modules\CatalogoProductos\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatalogoProductos\Models\Producto;
use PDO;

class ProductoTest extends TestCase
{
    private Producto $producto;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE productos (id_producto INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, descripcion TEXT, precio REAL, stock INTEGER, estado TEXT)");
        $this->producto = new Producto($pdo);
    }

    public function testCrearYObtenerProducto()
    {
        $id = $this->producto->crear([
            'nombre' => 'Test',
            'descripcion' => 'Desc',
            'precio' => 99.99,
            'stock' => 10,
            'estado' => 'activo'
        ]);
        $producto = $this->producto->obtenerPorId($id);
        $this->assertEquals('Test', $producto['nombre']);
        $this->assertEquals(99.99, $producto['precio']);
    }

    public function testActualizarProducto()
    {
        $id = $this->producto->crear([
            'nombre' => 'Test',
            'descripcion' => 'Desc',
            'precio' => 99.99,
            'stock' => 10,
            'estado' => 'activo'
        ]);
        $this->producto->actualizar($id, [
            'nombre' => 'Nuevo',
            'descripcion' => 'Desc',
            'precio' => 120.00,
            'stock' => 5,
            'estado' => 'inactivo'
        ]);
        $producto = $this->producto->obtenerPorId($id);
        $this->assertEquals('Nuevo', $producto['nombre']);
        $this->assertEquals(120.00, $producto['precio']);
        $this->assertEquals('inactivo', $producto['estado']);
    }

    public function testEliminarProducto()
    {
        $id = $this->producto->crear([
            'nombre' => 'Test',
            'descripcion' => 'Desc',
            'precio' => 99.99,
            'stock' => 10,
            'estado' => 'activo'
        ]);
        $this->assertTrue($this->producto->eliminar($id));
        $this->assertNull($this->producto->obtenerPorId($id));
    }
}
