<?php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Producto;
use PDO;

class ProductoController
{
    private Producto $productoModel;

    public function __construct(PDO $db)
    {
        $this->productoModel = new Producto($db);
    }

    public function listar(): array
    {
        return $this->productoModel->listar();
    }

    public function buscar(string $term): array
    {
        $term = mb_substr(trim($term), 0, 100);
        return $this->productoModel->buscar($term);
    }

    public function obtener(int $id): ?array
    {
        return $this->productoModel->obtenerPorId($id);
    }

    public function crear(array $data): int
    {
        // Validación backend
        $this->validar($data);
        $data = $this->normalizar($data);
        return $this->productoModel->crear($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        $this->validar($data);
        $data = $this->normalizar($data);
        return $this->productoModel->actualizar($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->productoModel->eliminar($id);
    }

    private function validar(array $data): void
    {
        // Sanitizar nombre y descripción
        $data['nombre'] = trim(strip_tags($data['nombre']));
        $data['descripcion'] = trim(strip_tags($data['descripcion'] ?? ''));
        if (empty($data['nombre']) || mb_strlen($data['nombre']) > 100 || !preg_match('/^[\w\sáéíóúÁÉÍÓÚüÜñÑ.,\-&]{1,100}$/u', $data['nombre'])) {
            throw new \InvalidArgumentException('Nombre inválido');
        }
        if (!is_numeric($data['precio']) || $data['precio'] < 0) {
            throw new \InvalidArgumentException('Precio inválido');
        }
        if (!in_array($data['estado'], ['activo','inactivo','0','1',0,1], true)) {
            throw new \InvalidArgumentException('Estado inválido');
        }
        if (!is_numeric($data['stock']) || $data['stock'] < 0) {
            throw new \InvalidArgumentException('Stock inválido');
        }
        if (isset($data['stock_minimo']) && (!is_numeric($data['stock_minimo']) || $data['stock_minimo'] < 0)) {
            throw new \InvalidArgumentException('Stock mínimo inválido');
        }
    }

    private function normalizar(array $data): array
    {
        $data['estado'] = ($data['estado'] === 'activo' || $data['estado'] === '1' || $data['estado'] === 1) ? 1 : 0;
        $data['precio'] = (float)$data['precio'];
        $data['stock'] = (int)$data['stock'];
        $data['stock_minimo'] = isset($data['stock_minimo']) ? (int)$data['stock_minimo'] : 0;
        return $data;
    }
}
