<?php
/**
 * Router API - Gestión de Usuarios
 * Enrutador de API REST para el módulo de gestión de usuarios
 */

namespace Modules\GestionUsuarios\Api;

use Modules\GestionUsuarios\Controllers\UsuarioController;
use Modules\GestionUsuarios\Controllers\PedidoController;
use Modules\GestionUsuarios\Controllers\RolController;
use Exception;

class Router {
    private $pdo;
    private $metodo;
    private $ruta;
    private $parametros;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->metodo = $_SERVER['REQUEST_METHOD'];
        $this->parseRuta();
    }
    
    /**
     * Parsear la ruta del request
     */
    private function parseRuta() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace('/modules/GestionUsuarios/Api/', '', $uri);
        $uri = trim($uri, '/');
        
        $this->ruta = explode('/', $uri);
        $this->parametros = array_filter($this->ruta, function($v) {
            return $v !== '';
        });
        $this->parametros = array_values($this->parametros);
    }
    
    /**
     * Ejecutar la ruta
     */
    public function ejecutar() {
        try {
            // Manejar query parameters especiales (ej: ?action=perfil)
            $queryAction = $_GET['action'] ?? null;
            
            // GET /api/index.php?action=perfil (obtener perfil del usuario autenticado)
            if ($queryAction === 'perfil') {
                $controller = new UsuarioController($this->pdo);
                return $controller->obtenerPerfilAutenticado();
            }
            
            // Obtener recurso y acción
            $recurso = $this->parametros[0] ?? null;
            $accion = $this->parametros[1] ?? null;
            $id = $this->parametros[2] ?? null;
            $subrecurso = $this->parametros[3] ?? null;
            $subid = $this->parametros[4] ?? null;
            
            // USUARIOS
            if ($recurso === 'usuarios') {
                $controller = new UsuarioController($this->pdo);
                
                // POST /api/usuarios/registro
                if ($this->metodo === 'POST' && $accion === 'registro') {
                    return $controller->registro();
                }
                
                // POST /api/usuarios/login
                if ($this->metodo === 'POST' && $accion === 'login') {
                    return $controller->login();
                }
                
                // POST /api/usuarios/logout
                if ($this->metodo === 'POST' && $accion === 'logout') {
                    return $controller->logout();
                }
                
                // POST /api/usuarios/recuperar-contrasena
                if ($this->metodo === 'POST' && $accion === 'recuperar-contrasena') {
                    return $controller->solicitarRecuperacion();
                }
                
                // GET /api/usuarios/validar-token-recuperacion?token=...
                if ($this->metodo === 'GET' && $accion === 'validar-token-recuperacion') {
                    return $controller->validarTokenRecuperacion();
                }
                
                // POST /api/usuarios/resetear-contrasena
                if ($this->metodo === 'POST' && $accion === 'resetear-contrasena') {
                    return $controller->resetearContrasena();
                }
                
                // GET /api/usuarios/validar-sesion?token=...
                if ($this->metodo === 'GET' && $accion === 'validar-sesion') {
                    return $controller->validarSesion();
                }
                
                // GET /api/usuarios (listar)
                if ($this->metodo === 'GET' && !$accion) {
                    return $controller->listar();
                }
                
                // GET /api/usuarios/:id
                if ($this->metodo === 'GET' && is_numeric($accion)) {
                    return $controller->obtener($accion);
                }
                
                // PUT /api/usuarios/:id/perfil
                if ($this->metodo === 'PUT' && is_numeric($accion) && $id === 'perfil') {
                    return $controller->actualizarPerfil($accion);
                }
                
                // POST /api/usuarios/:id/cambiar-contrasena
                if ($this->metodo === 'POST' && is_numeric($accion) && $id === 'cambiar-contrasena') {
                    return $controller->cambiarContrasena($accion);
                }
                
                // GET /api/usuarios/:id/permisos
                if ($this->metodo === 'GET' && is_numeric($accion) && $id === 'permisos') {
                    return $controller->obtenerPermisos($accion);
                }
                
                // GET /api/usuarios/:id/pedidos
                if ($this->metodo === 'GET' && is_numeric($accion) && $id === 'pedidos') {
                    $pedidoController = new PedidoController($this->pdo);
                    
                    if ($subrecurso === 'estadisticas') {
                        return $pedidoController->estadisticas($accion);
                    } elseif ($subrecurso === 'recientes') {
                        return $pedidoController->recientes($accion);
                    } else {
                        return $pedidoController->listar($accion);
                    }
                }
            }
            
            // PEDIDOS
            if ($recurso === 'pedidos') {
                $controller = new PedidoController($this->pdo);
                
                // GET /api/pedidos/:id
                if ($this->metodo === 'GET' && $accion) {
                    return $controller->obtener($accion);
                }
            }
            
            // ROLES
            if ($recurso === 'roles') {
                $controller = new RolController($this->pdo);
                
                // POST /api/roles
                if ($this->metodo === 'POST' && !$accion) {
                    return $controller->crear();
                }
                
                // GET /api/roles
                if ($this->metodo === 'GET' && !$accion) {
                    return $controller->listar();
                }
                
                // GET /api/roles/codigo/:codigo
                if ($this->metodo === 'GET' && $accion === 'codigo' && $id) {
                    return $controller->obtenerPorCodigo($id);
                }
                
                // GET /api/roles/:id
                if ($this->metodo === 'GET' && is_numeric($accion)) {
                    return $controller->obtener($accion);
                }
                
                // POST /api/roles/:id/permisos
                if ($this->metodo === 'POST' && is_numeric($accion) && $id === 'permisos') {
                    return $controller->asignarPermiso($accion);
                }
                
                // DELETE /api/roles/:id/permisos/:permiso_id
                if ($this->metodo === 'DELETE' && is_numeric($accion) && $id === 'permisos' && $subid) {
                    return $controller->removerPermiso($accion, $subid);
                }
            }
            
            // PERMISOS
            if ($recurso === 'permisos') {
                $controller = new RolController($this->pdo);
                
                // POST /api/permisos
                if ($this->metodo === 'POST') {
                    return $controller->crearPermiso();
                }
                
                // GET /api/permisos
                if ($this->metodo === 'GET') {
                    return $controller->listarPermisos();
                }
            }
            
            // No encontrado
            return $this->respuesta(404, 'Ruta no encontrada');
            
        } catch (Exception $e) {
            return $this->respuesta(500, 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Respuesta JSON
     */
    private function respuesta($codigo, $mensaje, $datos = null) {
        header('Content-Type: application/json');
        http_response_code($codigo);
        
        $respuesta = [
            'codigo' => $codigo,
            'mensaje' => $mensaje
        ];
        
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        
        echo json_encode($respuesta);
        exit;
    }
}
?>
