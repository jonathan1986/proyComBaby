<?php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\ImagenesProducto;
use PDO;

class ImagenesProductoController
{
    private ImagenesProducto $imagenesModel;

    public function __construct(PDO $db)
    {
        $this->imagenesModel = new ImagenesProducto($db);
    }

    public function crear(array $data): int
    {
        // Validar y sanitizar datos
        return $this->imagenesModel->crear($data);
    }

    // ...otros métodos CRUD seguros...
}
