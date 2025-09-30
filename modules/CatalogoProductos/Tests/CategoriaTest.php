<?php
namespace Modules\CatalogoProductos\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatalogoProductos\Models\Categoria;
use PDO;

class CategoriaTest extends TestCase
{
    private Categoria $categoria;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE categorias (id_categoria INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, descripcion TEXT, id_categoria_padre INTEGER)");
        $this->categoria = new Categoria($pdo);
    }

    public function testCrearYObtenerCategoria()
    {
        $id = $this->categoria->crear([
            'nombre' => 'Electrónica',
            'descripcion' => 'Productos electrónicos',
            'id_categoria_padre' => null
        ]);
        $cat = $this->categoria->obtenerPorId($id);
        $this->assertEquals('Electrónica', $cat['nombre']);
    }

    public function testActualizarCategoria()
    {
        $id = $this->categoria->crear([
            'nombre' => 'Electrónica',
            'descripcion' => 'Productos electrónicos',
            'id_categoria_padre' => null
        ]);
        $this->categoria->actualizar($id, [
            'nombre' => 'Audio',
            'descripcion' => 'Productos de audio',
            'id_categoria_padre' => null
        ]);
        $cat = $this->categoria->obtenerPorId($id);
        $this->assertEquals('Audio', $cat['nombre']);
    }

    public function testEliminarCategoria()
    {
        $id = $this->categoria->crear([
            'nombre' => 'Electrónica',
            'descripcion' => 'Productos electrónicos',
            'id_categoria_padre' => null
        ]);
        $this->assertTrue($this->categoria->eliminar($id));
        $this->assertNull($this->categoria->obtenerPorId($id));
    }
}
