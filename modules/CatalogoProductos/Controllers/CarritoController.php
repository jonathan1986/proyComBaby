<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Carrito;
use PDO;

class CarritoController
{
    private Carrito $model;

    public function __construct(PDO $db)
    {
        $this->model = new Carrito($db);
    }

    public function crear(array $data): int
    {
        $payload = [
            'id_usuario' => $this->toNullableInt($data['id_usuario'] ?? null),
            'session_token' => $this->sanitizeToken($data['session_token'] ?? null),
            'moneda' => $this->sanitizeMoneda($data['moneda'] ?? 'USD'),
            'impuesto_pct' => $this->sanitizePct($data['impuesto_pct'] ?? 0),
            'descuento_pct' => $this->sanitizePct($data['descuento_pct'] ?? 0),
            'descuento_monto' => $this->sanitizeMonto($data['descuento_monto'] ?? 0),
        ];
        return $this->model->crear($payload);
    }

    public function obtener(int $id): ?array
    {
        return $this->model->obtenerPorId($id);
    }

    public function obtenerPorUsuarioOToken(?int $idUsuario, ?string $token): ?array
    {
        return $this->model->obtenerPorUsuarioOToken($idUsuario, $token);
    }

    public function actualizarCabecera(int $id, array $data): bool
    {
        $payload = [
            'impuesto_pct' => $this->sanitizePct($data['impuesto_pct'] ?? 0),
            'descuento_pct' => $this->sanitizePct($data['descuento_pct'] ?? 0),
            'descuento_monto' => $this->sanitizeMonto($data['descuento_monto'] ?? 0),
            'estado' => $this->sanitizeEstado($data['estado'] ?? 'abierto'),
        ];
        return $this->model->actualizarCabecera($id, $payload);
    }

    public function eliminar(int $id): bool
    {
        return $this->model->eliminar($id);
    }

    private function toNullableInt($v): ?int
    {
        if ($v === null || $v === '' || !is_numeric($v)) return null;
        $n = (int)$v;
        return $n > 0 ? $n : null;
    }

    private function sanitizeToken($t): ?string
    {
        if ($t === null) return null;
        $t = trim((string)$t);
        if ($t === '') return null;
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $t)) {
            throw new \InvalidArgumentException('Token inválido');
        }
        return $t;
    }

    private function sanitizeMoneda(string $m): string
    {
        $m = strtoupper(trim($m));
        if (!preg_match('/^[A-Z]{3}$/', $m)) throw new \InvalidArgumentException('Moneda inválida');
        return $m;
    }

    private function sanitizePct($p): float
    {
        if (!is_numeric($p)) throw new \InvalidArgumentException('Porcentaje inválido');
        $f = (float)$p;
        if ($f < 0 || $f > 100) throw new \InvalidArgumentException('Porcentaje fuera de rango');
        return round($f, 2);
    }

    private function sanitizeMonto($m): float
    {
        if (!is_numeric($m)) throw new \InvalidArgumentException('Monto inválido');
        $f = (float)$m;
        if ($f < 0) throw new \InvalidArgumentException('Monto negativo');
        return round($f, 2);
    }

    private function sanitizeEstado(string $e): string
    {
        $e = strtolower(trim($e));
        $allowed = ['abierto','confirmado','cancelado','expirado'];
        if (!in_array($e, $allowed, true)) throw new \InvalidArgumentException('Estado inválido');
        return $e;
    }

    public function perteneceA(int $idCarrito, ?int $idUsuario, ?string $token): bool
    {
        return $this->model->perteneceA($idCarrito, $idUsuario, $token);
    }
}
