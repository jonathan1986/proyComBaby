<?php
namespace Modules\CatalogoProductos\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatalogoProductos\Models\Atributo;
use PDO;

class AtributoTest extends TestCase
{
    private Atributo $atributo;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE atributos (id_atributo INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, tipo TEXT)");
        $this->atributo = new Atributo($pdo);
    }

    public function testCrearYObtenerAtributo()
    {
        $id = $this->atributo->crear([
            'nombre' => 'Color',
            'tipo' => 'string'
        ]);
        $atributo = $this->atributo->obtenerPorId($id);
        $this->assertEquals('Color', $atributo['nombre']);
        $this->assertEquals('string', $atributo['tipo']);
    }

    public function testActualizarAtributo()
    {
        $id = $this->atributo->crear([
            'nombre' => 'Color',
            'tipo' => 'string'
        ]);
        $this->atributo->actualizar($id, [
            'nombre' => 'Tamaño',
            'tipo' => 'int'
        ]);
        $atributo = $this->atributo->obtenerPorId($id);
        $this->assertEquals('Tamaño', $atributo['nombre']);
        $this->assertEquals('int', $atributo['tipo']);
    }

    public function testEliminarAtributo()
    {
        $id = $this->atributo->crear([
            'nombre' => 'Color',
            'tipo' => 'string'
        ]);
        $this->assertTrue($this->atributo->eliminar($id));
        $this->assertNull($this->atributo->obtenerPorId($id));
    }
}
