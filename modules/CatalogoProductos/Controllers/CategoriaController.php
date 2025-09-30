<?php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Categoria;
use PDO;

class CategoriaController
{
    private Categoria $categoriaModel;

    public function __construct(PDO $db)
    {
        $this->categoriaModel = new Categoria($db);
    }

    public function crear(array $data): int
    {
        $this->validar($data);
        return $this->categoriaModel->crear($data);
    }

    public function obtener(int $id): ?array
    {
        return $this->categoriaModel->obtenerPorId($id);
    }

    public function actualizar(int $id, array $data): bool
    {
        $this->validar($data);
        return $this->categoriaModel->actualizar($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->categoriaModel->eliminar($id);
    }

    public function jerarquia(): array
    {
        return $this->categoriaModel->obtenerJerarquia();
    }

    private function validar(array $data): void
    {
        // Sanitizar nombre y descripción
        $data['nombre'] = trim(strip_tags($data['nombre']));
        $data['descripcion'] = trim(strip_tags($data['descripcion'] ?? ''));
        if (empty($data['nombre']) || strlen($data['nombre']) > 100 || !preg_match('/^[\w\sáéíóúÁÉÍÓÚüÜñÑ.,-]{1,100}$/u', $data['nombre'])) {
            throw new \InvalidArgumentException('Nombre de categoría inválido');
        }
    }
}
