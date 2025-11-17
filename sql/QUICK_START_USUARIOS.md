# QUICK START - Módulo Gestión de Usuarios

## ⚡ Implementación en 5 pasos

### Paso 1: Importar DDL (2 min)

```bash
# Windows - cmd/PowerShell
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
# Ingresa contraseña de root

# Linux/Mac
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

**Verificar:**
```bash
docker exec proycombaby-db-1 mysql -u root -p babylovec -e "SHOW TABLES;" | grep -E "usuarios|roles|permisos"
```

---

### Paso 2: Crear Modelo PHP (5 min)

Crea `modules/GestionUsuarios/Models/Usuario.php`:

```php
<?php
namespace Modules\GestionUsuarios\Models;

use PDO;
use Exception;

class Usuario {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($usuario_id) {
        $stmt = $this->pdo->prepare("CALL sp_obtener_usuario(?)");
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear($email, $nombre_completo, $password, $rol = 'CLIENTE') {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->pdo->prepare("CALL sp_crear_usuario_nuevo(?, ?, ?, ?, @id, @msg)");
        $stmt->execute([$email, $nombre_completo, $hash, $rol]);
        
        $resultado = $this->pdo->query("SELECT @id as id, @msg as msg")->fetch();
        
        if (!$resultado['id']) {
            throw new Exception($resultado['msg']);
        }
        
        return $resultado['id'];
    }
    
    /**
     * Autenticar usuario
     */
    public function autenticar($email, $password) {
        $usuario = $this->obtenerPorEmail($email);
        
        if (!$usuario) {
            // Registrar intento fallido
            $this->registrarIntento(null, $email, 0, 'Email no encontrado');
            return false;
        }
        
        if (!password_verify($password, $usuario['contrasena_hash'])) {
            // Registrar intento fallido
            $this->registrarIntento($usuario['id'], $email, 0, 'Contraseña incorrecta');
            return false;
        }
        
        // Registrar intento exitoso
        $this->registrarIntento($usuario['id'], $email, 1, null);
        
        // Crear sesión
        $this->crearSesion($usuario['id']);
        
        return $usuario;
    }
    
    /**
     * Registrar intento de login
     */
    public function registrarIntento($usuario_id, $email, $exitoso, $razon = null) {
        $stmt = $this->pdo->prepare(
            "CALL sp_registrar_intento_acceso(?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $usuario_id,
            $email,
            $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $exitoso,
            $razon
        ]);
    }
    
    /**
     * Crear sesión
     */
    public function crearSesion($usuario_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sesiones_usuario (usuario_id, token_sesion, fecha_expiracion, activo) VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([$usuario_id, $token, $expires]);
        
        return $token;
    }
    
    /**
     * Verificar si usuario tiene permiso
     */
    public function tienePermiso($usuario_id, $permiso_codigo) {
        $stmt = $this->pdo->prepare("SELECT fn_usuario_tiene_permiso(?, ?) as tiene");
        $stmt->execute([$usuario_id, $permiso_codigo]);
        return (bool)$stmt->fetch()['tiene'];
    }
    
    /**
     * Obtener permisos de usuario
     */
    public function obtenerPermisos($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT fn_obtener_permisos_usuario(?) as permisos");
        $stmt->execute([$usuario_id]);
        $resultado = $stmt->fetch()['permisos'];
        return explode(',', $resultado ?? '');
    }
    
    /**
     * Obtener roles de usuario
     */
    public function obtenerRoles($usuario_id) {
        $stmt = $this->pdo->prepare(
            "SELECT r.* FROM usuarios_roles ur 
             JOIN roles r ON ur.rol_id = r.id 
             WHERE ur.usuario_id = ? AND ur.activo = 1"
        );
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena($usuario_id, $password_antigua, $password_nueva) {
        $usuario = $this->obtenerPorId($usuario_id);
        
        if (!password_verify($password_antigua, $usuario['contrasena_hash'])) {
            throw new Exception('Contraseña actual incorrecta');
        }
        
        $hash_anterior = $usuario['contrasena_hash'];
        $hash_nueva = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->pdo->prepare("CALL sp_cambiar_contrasena(?, ?, ?, @exito, @msg)");
        $stmt->execute([$usuario_id, $hash_anterior, $hash_nueva]);
        
        $resultado = $this->pdo->query("SELECT @exito as exito, @msg as msg")->fetch();
        
        if (!$resultado['exito']) {
            throw new Exception($resultado['msg']);
        }
        
        return true;
    }
    
    /**
     * Asignar rol a usuario
     */
    public function asignarRol($usuario_id, $rol_codigo) {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO usuarios_roles (usuario_id, rol_id, activo) 
             SELECT ?, id, 1 FROM roles WHERE codigo = ? AND activo = 1"
        );
        $stmt->execute([$usuario_id, $rol_codigo]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Listar usuarios activos
     */
    public function listarActivos($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM v_usuarios_activos LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
```

---

### Paso 3: Crear Controlador API (5 min)

Crea `modules/GestionUsuarios/Controllers/UsuarioController.php`:

```php
<?php
namespace Modules\GestionUsuarios\Controllers;

use Modules\GestionUsuarios\Models\Usuario;
use PDO;

class UsuarioController {
    private $usuario;
    
    public function __construct(PDO $pdo) {
        $this->usuario = new Usuario($pdo);
    }
    
    /**
     * GET /api/usuarios/:id
     */
    public function obtener($id) {
        try {
            $usuario = $this->usuario->obtenerPorId($id);
            if (!$usuario) {
                return $this->respuesta(404, 'Usuario no encontrado');
            }
            return $this->respuesta(200, 'OK', $usuario);
        } catch (\Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * POST /api/usuarios/registro
     */
    public function registro() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar
            if (empty($data['email']) || empty($data['nombre']) || empty($data['password'])) {
                return $this->respuesta(400, 'Campos requeridos: email, nombre, password');
            }
            
            $id = $this->usuario->crear(
                $data['email'],
                $data['nombre'],
                $data['password'],
                'CLIENTE'
            );
            
            return $this->respuesta(201, 'Usuario registrado', ['usuario_id' => $id]);
        } catch (\Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * POST /api/usuarios/login
     */
    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['email']) || empty($data['password'])) {
                return $this->respuesta(400, 'Email y password requeridos');
            }
            
            $usuario = $this->usuario->autenticar($data['email'], $data['password']);
            
            if (!$usuario) {
                return $this->respuesta(401, 'Credenciales inválidas');
            }
            
            $token = $this->usuario->crearSesion($usuario['id']);
            
            return $this->respuesta(200, 'Login exitoso', [
                'usuario_id' => $usuario['id'],
                'email' => $usuario['email'],
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * POST /api/usuarios/cambiar-contrasena
     */
    public function cambiarContrasena() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['usuario_id']) || empty($data['password_antigua']) || empty($data['password_nueva'])) {
                return $this->respuesta(400, 'Parámetros requeridos');
            }
            
            $this->usuario->cambiarContrasena(
                $data['usuario_id'],
                $data['password_antigua'],
                $data['password_nueva']
            );
            
            return $this->respuesta(200, 'Contraseña actualizada');
        } catch (\Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * GET /api/usuarios/:id/permisos
     */
    public function obtenerPermisos($id) {
        try {
            $permisos = $this->usuario->obtenerPermisos($id);
            return $this->respuesta(200, 'OK', ['permisos' => $permisos]);
        } catch (\Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * GET /api/usuarios/listar?limit=50&offset=0
     */
    public function listar() {
        try {
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            $usuarios = $this->usuario->listarActivos($limit, $offset);
            return $this->respuesta(200, 'OK', $usuarios);
        } catch (\Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    // Helper para respuestas JSON
    private function respuesta($code, $mensaje, $data = null) {
        header('Content-Type: application/json');
        http_response_code($code);
        
        $response = [
            'codigo' => $code,
            'mensaje' => $mensaje
        ];
        
        if ($data) {
            $response['datos'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
}
?>
```

---

### Paso 4: Crear rutas (5 min)

En tu archivo de rutas principal:

```php
<?php
// routes.php o similar

$controller = new \Modules\GestionUsuarios\Controllers\UsuarioController($pdo);

// Rutas públicas
$_POST['register'] ? $controller->registro() : null;
$_POST['login'] ? $controller->login() : null;

// Rutas protegidas (verificar token antes)
if (isset($_SESSION['user_id'])) {
    if ($_GET['action'] === 'obtener') {
        $controller->obtener($_GET['id']);
    } elseif ($_GET['action'] === 'cambiar-password') {
        $controller->cambiarContrasena();
    } elseif ($_GET['action'] === 'permisos') {
        $controller->obtenerPermisos($_SESSION['user_id']);
    } elseif ($_GET['action'] === 'listar') {
        $controller->listar();
    }
}
?>
```

---

### Paso 5: Probar (5 min)

```bash
# Test 1: Registrar usuario
curl -X POST http://localhost/api/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","nombre":"Test User","password":"Pass123!"}'

# Response: {"codigo":201,"mensaje":"Usuario registrado","datos":{"usuario_id":1}}

# Test 2: Login
curl -X POST http://localhost/api/usuarios/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Pass123!"}'

# Response: {"codigo":200,"mensaje":"Login exitoso","datos":{"usuario_id":1,"email":"test@example.com","token":"abc123..."}}

# Test 3: Obtener usuario
curl http://localhost/api/usuarios?action=obtener&id=1 \
  -H "Cookie: PHPSESSID=..."

# Test 4: Obtener permisos
curl http://localhost/api/usuarios?action=permisos \
  -H "Cookie: PHPSESSID=..."
```

---

## 📊 Próximos Pasos

- [ ] Implementar recuperación de contraseña
- [ ] Agregar verificación de email
- [ ] Configurar 2FA (TOTP)
- [ ] Crear dashboard de admin
- [ ] Implementar rate limiting
- [ ] Agregar webhooks

---

## 🔗 Documentación Completa

Ver `GUIA_GESTION_USUARIOS.md` para:
- Estructura completa de tablas
- Todas las funciones y procedimientos
- Vistas disponibles
- Ejemplos avanzados
- Recomendaciones de seguridad

---

**¿Preguntas?** Revisa el archivo de documentación o contacta al equipo.
