<?php
namespace Modules\CatalogoProductos\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatalogoProductos\Controllers\ProductoController;
use Modules\CatalogoProductos\Models\Producto;
use PDO;

class ProductoControllerTest extends TestCase
{
    private ProductoController $controller;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE productos (id_producto INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, descripcion TEXT, precio REAL, stock INTEGER, estado TEXT)");
        $this->controller = new ProductoController($pdo);
    }

    public function testValidacionNombreInvalido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->crear([
            'nombre' => '',
            'descripcion' => 'Desc',
            'precio' => 10,
            'stock' => 1,
            'estado' => 'activo'
        ]);
    }

    public function testValidacionPrecioNegativo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->crear([
            'nombre' => 'Test',
            'descripcion' => 'Desc',
            'precio' => -5,
            'stock' => 1,
            'estado' => 'activo'
        ]);
    }

    public function testValidacionEstadoInvalido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->crear([
            'nombre' => 'Test',
            'descripcion' => 'Desc',
            'precio' => 10,
            'stock' => 1,
            'estado' => 'otro'
        ]);
    }
}
