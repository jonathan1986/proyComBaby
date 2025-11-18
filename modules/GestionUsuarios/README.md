# 📦 Módulo de Gestión de Usuarios - E-Commerce

**Versión:** 1.0  
**Compatibilidad:** PHP 7.4+, MySQL 5.7+  
**Última Actualización:** Noviembre 2025

---

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Instalación](#instalación)
4. [Configuración](#configuración)
5. [Endpoints de API](#endpoints-de-api)
6. [Uso del Frontend](#uso-del-frontend)
7. [Seguridad](#seguridad)
8. [Troubleshooting](#troubleshooting)

---

## Descripción General

El **Módulo de Gestión de Usuarios** es una solución completa para manejar:

- ✅ Registro y autenticación de usuarios
- ✅ Recuperación de contraseñas
- ✅ Perfiles de usuario con datos extendidos
- ✅ Historial de pedidos
- ✅ Sistema de roles y permisos
- ✅ Gestión de sesiones
- ✅ Auditoría de acceso

### Características Técnicas

- **Backend:** PHP 8+ sin dependencias externas (solo PDO)
- **Frontend:** HTML5 + Bootstrap 5 + JavaScript vanilla
- **Base de Datos:** MySQL 5.7+ con procedimientos almacenados
- **API:** REST JSON
- **Seguridad:** Hash bcrypt, tokens seguros, validación de entrada

---

## Estructura del Proyecto

```
modules/GestionUsuarios/
├── Models/                    # Modelos PHP
│   ├── Usuario.php           # CRUD y autenticación
│   ├── Perfil.php            # Datos extendidos
│   ├── Pedido.php            # Historial de compras
│   └── Rol.php               # Roles y permisos
├── Controllers/              # Controladores
│   ├── UsuarioController.php  # Lógica de usuarios
│   ├── PedidoController.php   # Lógica de pedidos
│   └── RolController.php      # Lógica de roles
├── Views/                     # HTML Frontend
│   ├── registro.html          # Formulario de registro
│   ├── login.html             # Formulario de login
│   ├── recuperar_contrasena.html  # Reset de contraseña
│   └── perfil.html            # Dashboard de usuario
├── Assets/
│   ├── css/
│   │   └── estilos.css        # Estilos personalizados
│   └── js/
│       ├── auth.js            # Funciones de autenticación
│       └── perfil.js          # Funciones de perfil
├── Api/
│   └── router.php             # Enrutador de API
├── README.md                  # Este archivo
└── GUIA_GESTION_USUARIOS.md  # Documentación técnica

```

---

## Instalación

### Paso 1: Importar Base de Datos

Asegúrate de que el archivo `modulo_gestion_usuarios_mysql.sql` esté importado:

```bash
# Docker
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql

# O directamente
mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

Verifica que se hayan creado 14 tablas:

```sql
SHOW TABLES LIKE '%usuario%';
SHOW TABLES LIKE '%rol%';
SHOW TABLES LIKE '%pedido%';
```

### Paso 2: Configurar Autoload

En tu archivo bootstrap o config principal, asegúrate que Composer autoload esté configurado para el namespace:

```php
// En bootstrap.php o config.php
require_once 'vendor/autoload.php';

// Si usas PSR-4 personalizado:
spl_autoload_register(function ($class) {
    $prefix = 'Modules\\';
    if (strpos($class, $prefix) === 0) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
```

### Paso 3: Crear Archivo de Entrada API

Crea `modules/GestionUsuarios/Api/index.php`:

```php
<?php
// Configurar headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Autoload
require_once '../../../bootstrap.php';

// Conexión a BD
$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Router
$router = new \Modules\GestionUsuarios\Api\Router($pdo);
$router->ejecutar();
?>
```

### Paso 4: Configurar URLs en Frontend

En `Assets/js/auth.js` y `Assets/js/perfil.js`, actualiza `API_URL` si es necesario:

```javascript
// Si instalaste en una ruta diferente
const API_URL = '/ruta/a/modules/GestionUsuarios/Api';
```

---

## Configuración

### Variable de Entorno (Opcional)

En `.env`:

```env
# Gestión de Usuarios
USERS_MODULE_API=/modules/GestionUsuarios/Api
USERS_RECOVERY_EXPIRY=30  # minutos
USERS_SESSION_EXPIRY=7    # días
USERS_PASSWORD_MIN_LENGTH=8
```

### Configurar Envío de Emails

En `Controllers/UsuarioController.php`, descomentar y configurar el envío de emails:

```php
// En solicitarRecuperacion()
// TODO: Implementar
// $this->enviarEmailRecuperacion($recuperacion['email'], $recuperacion['token']);

// Implementación sugerida:
private function enviarEmailRecuperacion($email, $token) {
    $link = "https://tusite.com/modules/GestionUsuarios/Views/recuperar_contrasena.html?token=$token";
    
    $subject = "Recupera tu contraseña";
    $body = "Haz click aquí para resetear tu contraseña: <a href='$link'>$link</a>";
    
    mail($email, $subject, $body);
}
```

---

## Endpoints de API

### Autenticación

#### Registro
```
POST /modules/GestionUsuarios/Api/usuarios/registro
Content-Type: application/json

{
    "nombre_completo": "Juan Pérez",
    "email": "juan@example.com",
    "password": "MiContraseña123!",
    "confirmar_password": "MiContraseña123!",
    "apellido": "Pérez"  // Opcional
}

Response: 201 Created
{
    "codigo": 201,
    "mensaje": "Usuario registrado exitosamente",
    "datos": {
        "usuario_id": 1,
        "email": "juan@example.com"
    }
}
```

#### Login
```
POST /modules/GestionUsuarios/Api/usuarios/login
Content-Type: application/json

{
    "email": "juan@example.com",
    "password": "MiContraseña123!"
}

Response: 200 OK
{
    "codigo": 200,
    "mensaje": "Login exitoso",
    "datos": {
        "usuario_id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "email": "juan@example.com",
        "nombre": "Juan Pérez",
        "token": "abc123...",
        "roles": ["CLIENTE"]
    }
}
```

#### Logout
```
POST /modules/GestionUsuarios/Api/usuarios/logout
Content-Type: application/json
Authorization: Bearer {token}

{
    "token": "abc123..."
}

Response: 200 OK
```

### Perfil de Usuario

#### Obtener Perfil
```
GET /modules/GestionUsuarios/Api/usuarios/:id
Authorization: Bearer {token}

Response: 200 OK
{
    "id": 1,
    "email": "juan@example.com",
    "nombre_completo": "Juan Pérez",
    "perfil": {...},
    "roles": [...]
}
```

#### Actualizar Perfil
```
PUT /modules/GestionUsuarios/Api/usuarios/:id/perfil
Content-Type: application/json
Authorization: Bearer {token}

{
    "nombre_completo": "Juan Carlos",
    "ciudad": "Bogotá",
    "pais": "Colombia",
    "biografia": "Mi biografía"
}

Response: 200 OK
```

### Contraseña

#### Solicitar Recuperación
```
POST /modules/GestionUsuarios/Api/usuarios/recuperar-contrasena
Content-Type: application/json

{
    "email": "juan@example.com"
}

Response: 200 OK
// Nota: No revela si el email existe por seguridad
```

#### Validar Token
```
GET /modules/GestionUsuarios/Api/usuarios/validar-token-recuperacion?token=abc123

Response: 200 OK si token válido
Response: 400 Bad Request si token inválido
```

#### Resetear Contraseña
```
POST /modules/GestionUsuarios/Api/usuarios/resetear-contrasena
Content-Type: application/json

{
    "token": "abc123...",
    "password": "NuevaContraseña123!",
    "confirmar_password": "NuevaContraseña123!"
}

Response: 200 OK
```

#### Cambiar Contraseña
```
POST /modules/GestionUsuarios/Api/usuarios/:id/cambiar-contrasena
Content-Type: application/json
Authorization: Bearer {token}

{
    "password_antigua": "MiContraseña123!",
    "password_nueva": "NuevaContraseña123!",
    "confirmar_password": "NuevaContraseña123!"
}

Response: 200 OK
```

### Pedidos

#### Listar Pedidos
```
GET /modules/GestionUsuarios/Api/usuarios/:id/pedidos?limit=20&offset=0
Authorization: Bearer {token}

Response: 200 OK
{
    "datos": {
        "pedidos": [...],
        "total": 45,
        "resumen": {
            "total_pedidos": 45,
            "gasto_total": 2500.00,
            "ticket_promedio": 55.56,
            "ultimo_pedido": "2025-11-16 10:30:00"
        }
    }
}
```

#### Obtener Pedido
```
GET /modules/GestionUsuarios/Api/pedidos/:id
Authorization: Bearer {token}

Response: 200 OK
{
    "datos": {
        "id": 123,
        "numero_pedido": "PED-2025-00123",
        "estado": "entregado",
        "total": 150.00,
        "detalles": [...]
    }
}
```

### Roles y Permisos

#### Listar Roles
```
GET /modules/GestionUsuarios/Api/roles
Authorization: Bearer {token}

Response: 200 OK
{
    "datos": {
        "roles": [...],
        "total": 5
    }
}
```

#### Obtener Permisos
```
GET /modules/GestionUsuarios/Api/permisos
Authorization: Bearer {token}

Response: 200 OK
{
    "datos": {
        "permisos": [...],
        "por_modulo": {...},
        "total": 21
    }
}
```

---

## Uso del Frontend

### Vistas Disponibles

#### 1. Registro (`registro.html`)
- Formulario de registro con validación cliente
- Campos: nombre, email, contraseña, confirmar contraseña, apellido
- Redirige a login tras registro exitoso

**Acceso:** `modules/GestionUsuarios/Views/registro.html`

#### 2. Login (`login.html`)
- Formulario de login simple
- Opción "Recuérdame"
- Link a recuperación de contraseña
- Almacena token en localStorage

**Acceso:** `modules/GestionUsuarios/Views/login.html`

#### 3. Recuperación (`recuperar_contrasena.html`)
- Paso 1: Ingresar email
- Paso 2: Ingresar nueva contraseña
- Soporta token en URL: `recuperar_contrasena.html?token=abc123`

**Acceso:** `modules/GestionUsuarios/Views/recuperar_contrasena.html`

#### 4. Perfil (`perfil.html`)
- Dashboard protegido (requiere login)
- Actualizar información personal
- Cambiar contraseña
- Ver historial de pedidos con estadísticas

**Acceso:** `modules/GestionUsuarios/Views/perfil.html`

### Almacenamiento Local

El frontend usa `localStorage` para mantener la sesión:

```javascript
localStorage.setItem('token', token);          // Token JWT
localStorage.setItem('usuario_id', id);        // ID del usuario
localStorage.setItem('usuario_email', email);  // Email
localStorage.setItem('usuario_nombre', nombre);// Nombre completo
localStorage.setItem('usuario_roles', roles);  // Array JSON de roles
```

### Validaciones del Cliente

Todas las vistas incluyen validaciones JavaScript:

- Email válido
- Contraseña mínimo 8 caracteres
- Coincidencia de contraseñas
- Campos requeridos
- Mensajes de error informativos

---

## Seguridad

### Implementadas

✅ **Hash de Contraseñas:** bcrypt (cost 12)  
✅ **Tokens de Sesión:** 64 caracteres aleatorios  
✅ **Tokens de Recuperación:** De un solo uso, con expiración 30 min  
✅ **Validación de Entrada:** Sanitización en Backend  
✅ **SQL Injection Prevention:** Prepared statements  
✅ **Rate Limiting:** En historial de acceso  
✅ **Auditoría:** Todos los cambios registrados  

### Recomendaciones Adicionales

⚠️ **CSRF Protection:**
```php
// En la aplicación principal
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

⚠️ **SSL/HTTPS:** Usar siempre en producción

⚠️ **Rate Limiting:** Implementar en router.php para login

```php
// Ejemplo básico
private function verificarRateLimit($email, $ip) {
    $stmt = $this->pdo->prepare("
        SELECT COUNT(*) as intentos
        FROM historial_acceso
        WHERE email_intento = ? AND direccion_ip = ? 
        AND exitoso = 0 
        AND fecha_intento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$email, $ip]);
    $intentos = $stmt->fetch()['intentos'];
    
    if ($intentos > 5) {
        throw new Exception('Demasiados intentos. Intenta en 15 minutos');
    }
}
```

⚠️ **CORS:** Configurar según tu dominio

```php
header('Access-Control-Allow-Origin: https://tudominio.com');
```

---

## Troubleshooting

### Error 404 - Ruta no encontrada

**Causa:** El router no está configurado correctamente

**Solución:**
```php
// Verifica que index.php está en Api/
// Y que la URL es correcta:
// POST /modules/GestionUsuarios/Api/usuarios/login
```

### Error 500 - Error interno del servidor

**Causa:** PDO no está inicializado correctamente

**Solución:**
```php
// En Api/index.php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=babylovec;charset=utf8mb4',
        'root',
        'contraseña'
    );
} catch (PDOException $e) {
    die('Conexión fallida: ' . $e->getMessage());
}
```

### Token inválido o expirado

**Causa:** LocalStorage fue limpiado

**Solución:** User debe volver a hacer login

### Contraseña no cambia

**Causa:** Hash anterior no coincide

**Solución:** Verificar que se está usando `password_verify()` correctamente

### Emails no se envían

**Causa:** Función `mail()` no configurada

**Solución:** Usar servicio SMTP (SwiftMailer, PHPMailer)

```php
// Instalar
composer require swiftmailer/swiftmailer

// Usar
$transport = new \Swift_SendmailTransport();
$mailer = new \Swift_Mailer($transport);
$message = (new \Swift_Message('Recupera tu contraseña'))
    ->setFrom('noreply@tusite.com')
    ->setTo($email)
    ->setBody($body);
$mailer->send($message);
```

---

## Próximos Pasos

- [ ] Implementar 2FA (TOTP)
- [ ] Integración con redes sociales (OAuth)
- [ ] Verificación de email
- [ ] Sistema de invitaciones
- [ ] Dashboard de admin
- [ ] Exportar pedidos a PDF
- [ ] Sistema de cupones/descuentos
- [ ] Integración con gateway de pago

---

## Contacto y Soporte

Para soporte, contacta al equipo de desarrollo.

---

**Última actualización:** Noviembre 16, 2025  
**Versión:** 1.0  
**Licencia:** Proyecto Interno
