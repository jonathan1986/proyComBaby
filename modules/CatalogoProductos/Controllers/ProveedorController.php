<?php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Proveedor;
use PDO;

class ProveedorController
{
    private Proveedor $proveedorModel;

    public function __construct(PDO $db)
    {
        $this->proveedorModel = new Proveedor($db);
    }

    public function crear(array $data): int
    {
        $this->validar($data);
        return $this->proveedorModel->crear($data);
    }

    public function obtener(int $id): ?array
    {
        return $this->proveedorModel->obtenerPorId($id);
    }

    public function actualizar(int $id, array $data): bool
    {
        $this->validar($data);
        return $this->proveedorModel->actualizar($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->proveedorModel->eliminar($id);
    }

    public function listar(): array
    {
        return $this->proveedorModel->listar();
    }

    public function buscar(string $term): array
    {
        return $this->proveedorModel->buscar($term);
    }

    private function validar(array $data): void
    {
        $data['nombre'] = trim(strip_tags($data['nombre']));
        if (empty($data['nombre']) || strlen($data['nombre']) > 100 || !preg_match('/^[\w\sáéíóúÁÉÍÓÚüÜñÑ.,-]{1,100}$/u', $data['nombre'])) {
            throw new \InvalidArgumentException('Nombre de proveedor inválido');
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email inválido');
        }
        if (!empty($data['telefono']) && !preg_match('/^[\d\s+()-]{1,30}$/', $data['telefono'])) {
            throw new \InvalidArgumentException('Teléfono inválido');
        }
        if (!empty($data['ruc']) && !preg_match('/^[\w\d-]{1,20}$/', $data['ruc'])) {
            throw new \InvalidArgumentException('RUC inválido');
        }
        if (!isset($data['estado']) || !in_array((int)$data['estado'], [0,1], true)) {
            throw new \InvalidArgumentException('Estado inválido');
        }
    }
}
