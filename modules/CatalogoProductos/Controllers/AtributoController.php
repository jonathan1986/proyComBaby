<?php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Atributo;
use PDO;

class AtributoController
{
    private Atributo $atributoModel;

    public function __construct(PDO $db)
    {
        $this->atributoModel = new Atributo($db);
    }

    public function crear(array $data): int
    {
        $this->validar($data);
        return $this->atributoModel->crear($data);
    }

    public function obtener(int $id): ?array
    {
        return $this->atributoModel->obtenerPorId($id);
    }

    public function actualizar(int $id, array $data): bool
    {
        $this->validar($data);
        return $this->atributoModel->actualizar($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->atributoModel->eliminar($id);
    }

    public function listar(): array
    {
        return $this->atributoModel->listar();
    }

    private function validar(array $data): void
    {
        // Sanitizar nombre: eliminar etiquetas HTML y espacios extra
        $data['nombre'] = trim(strip_tags($data['nombre']));
        if (empty($data['nombre']) || strlen($data['nombre']) > 100 || !preg_match('/^[\w\sáéíóúÁÉÍÓÚüÜñÑ.,-]{1,100}$/u', $data['nombre'])) {
            throw new \InvalidArgumentException('Nombre de atributo inválido');
        }
        // Validar tipo
        if (!in_array($data['tipo'], ['string','int','float','bool','date'])) {
            throw new \InvalidArgumentException('Tipo de atributo inválido');
        }
        // Validar estado
        if (!isset($data['estado']) || !in_array((int)$data['estado'], [0,1], true)) {
            throw new \InvalidArgumentException('Estado inválido');
        }
    }
}
