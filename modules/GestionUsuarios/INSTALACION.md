# 🔧 Guía de Instalación - Módulo de Gestión de Usuarios

**Versión:** 1.0  
**Compatibilidad:** PHP 7.4+, MySQL 5.7+  
**Tiempo estimado:** 15 minutos

---

## 📋 Contenido

1. [Requisitos Previos](#requisitos-previos)
2. [Descarga e Instalación](#descarga-e-instalación)
3. [Configuración de Base de Datos](#configuración-de-base-de-datos)
4. [Configuración de API](#configuración-de-api)
5. [Pruebas](#pruebas)
6. [Solución de Problemas](#solución-de-problemas)

---

## Requisitos Previos

### Software
- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o superior
- **Servidor Web:** Apache con mod_rewrite o Nginx
- **Navegador:** Moderno con soporte para ES6

### Extensiones PHP Requeridas
```bash
# Verificar extensiones instaladas
php -m

# Deberías ver:
# - PDO
# - pdo_mysql
# - json
# - openssl
```

### Dependencias Composer
```json
{
    "require": {
        "php": ">=7.4.0"
    }
}
```

---

## Descarga e Instalación

### Opción 1: Instalación Manual

1. **Descargar archivos**
   ```bash
   cd tu_proyecto/modules/
   git clone https://repo.com/modulo-gestion-usuarios.git GestionUsuarios
   # O descargar ZIP y extraer
   ```

2. **Verificar estructura**
   ```bash
   ls -la GestionUsuarios/
   # Deberías ver:
   # Models/       - Clases PHP
   # Controllers/  - Controladores
   # Views/        - Archivos HTML
   # Assets/       - CSS y JS
   # Api/          - Enrutador
   # Utils/        - Utilidades
   ```

3. **Crear carpeta de logs**
   ```bash
   mkdir -p GestionUsuarios/logs
   chmod 755 GestionUsuarios/logs
   ```

### Opción 2: Instalación con Composer (Futuro)

```bash
composer require proycomba/modulo-gestion-usuarios:^1.0
```

---

## Configuración de Base de Datos

### Paso 1: Importar Esquema

Localiza el archivo `sql/modulo_gestion_usuarios_mysql.sql` en el repositorio principal.

**Con Docker:**
```bash
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

**Con MySQL Local:**
```bash
mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

**Con phpMyAdmin:**
1. Abre phpMyAdmin
2. Selecciona base de datos `babylovec`
3. Ve a "Importar"
4. Sube `modulo_gestion_usuarios_mysql.sql`
5. Click en "Ejecutar"

### Paso 2: Verificar Importación

```sql
-- Conectarse a la BD
mysql -u root -p babylovec

-- Verificar tablas
SHOW TABLES;

-- Deberías ver 12-14 tablas:
-- usuarios
-- perfiles_usuario
-- sesiones_usuario
-- recuperacion_contrasena
-- historial_acceso
-- historial_contrasenas
-- roles
-- permisos
-- roles_permisos
-- usuarios_roles
-- pedidos
-- detalles_pedido
```

### Paso 3: Verificar Procedimientos Almacenados

```sql
-- Ver procedimientos
SHOW PROCEDURES;

-- O
SELECT ROUTINE_NAME FROM information_schema.ROUTINES 
WHERE ROUTINE_SCHEMA = 'babylovec' 
AND ROUTINE_TYPE = 'PROCEDURE';
```

---

## Configuración de API

### Paso 1: Crear Archivo Api/index.php

Si no existe, crea `modules/GestionUsuarios/Api/index.php`:

```php
<?php
/**
 * API Entry Point - Gestión de Usuarios
 * Acceso: /modules/GestionUsuarios/Api/*
 */

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejo OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir bootstrap/configuración
require_once '../../../bootstrap.php';

// Crear conexión PDO
try {
    $pdo = new PDO(
        'mysql:host=' . (defined('DB_HOST') ? DB_HOST : 'localhost') .
        ';dbname=' . (defined('DB_NAME') ? DB_NAME : 'babylovec') .
        ';charset=utf8mb4',
        defined('DB_USER') ? DB_USER : 'root',
        defined('DB_PASS') ? DB_PASS : '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'Error de conexión a BD',
        'error' => $e->getMessage()
    ]));
}

// Iniciar router
try {
    $router = new \Modules\GestionUsuarios\Api\Router($pdo);
    $router->ejecutar();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'codigo' => 500,
        'mensaje' => 'Error procesando solicitud',
        'error' => $e->getMessage()
    ]);
}
?>
```

### Paso 2: Configurar Variables de Entorno

**Opción A: Usar archivo .env**

Crea `.env` en la raíz del proyecto:

```env
# Database
DB_HOST=localhost
DB_NAME=babylovec
DB_USER=root
DB_PASS=

# Security
PASSWORD_MIN_LENGTH=8
SESSION_EXPIRY_DAYS=7
RECOVERY_TOKEN_EXPIRY=30

# Email (opcional)
MAIL_FROM=noreply@tudominio.com
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu@gmail.com
MAIL_PASSWORD=tu_contraseña
```

**Opción B: Modificar bootstrap.php**

En `bootstrap.php` asegúrate que esté definido:

```php
<?php
// Configuración de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'babylovec');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12);
define('TOKEN_LENGTH', 64);
define('SESSION_EXPIRY_DAYS', 7);
?>
```

### Paso 3: Actualizar URLs del Frontend

En `Assets/js/auth.js` y `Assets/js/perfil.js`:

Busca esta línea (normalmente al principio):
```javascript
const API_URL = '/modules/GestionUsuarios/Api';
```

Ajusta según tu estructura:
- **Local:** `/modules/GestionUsuarios/Api`
- **Subdirectorio:** `/subdir/modules/GestionUsuarios/Api`
- **HTTPS:** `https://tudominio.com/modules/GestionUsuarios/Api`

### Paso 4: Configurar Rutas en .htaccess

Si usas Apache, crea o actualiza `.htaccess` en `Api/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /modules/GestionUsuarios/Api/
    
    # No reescribir archivos existentes
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Toda solicitud va a index.php
    RewriteRule ^(.*)$ index.php [L,QSA]
</IfModule>
```

---

## Pruebas

### Prueba 1: Verificar Instalación

Accede a `check_install.php` en tu navegador:

```
http://localhost/modules/GestionUsuarios/check_install.php
```

Deberías ver un reporte con:
- ✓ Directorios creados
- ✓ Archivos en su lugar
- ✓ Conexión a BD
- ✓ Tablas importadas

### Prueba 2: Test Simple con cURL

```bash
# Endpoint para obtener usuarios (requiere admin)
curl -X GET http://localhost/modules/GestionUsuarios/Api/usuarios \
  -H "Content-Type: application/json"
```

Esperado: `404` (porque la tabla existe pero no hay usuarios, o `200` con lista vacía)

### Prueba 3: Registrar Usuario

```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_completo": "Test User",
    "email": "test@example.com",
    "password": "TestPassword123!",
    "confirmar_password": "TestPassword123!"
  }'
```

Esperado: `201` con datos de usuario creado

### Prueba 4: Login

```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPassword123!"
  }'
```

Esperado: `200` con token

### Prueba 5: Test Completo

```bash
# O ejecutar script bash
bash modules/GestionUsuarios/test_api.sh
```

---

## Solución de Problemas

### Error 404: Ruta no encontrada

**Causa:** API no es accesible

**Soluciones:**
1. Verifica que `Api/index.php` existe
2. Verifica la URL base correcta
3. Verifica que .htaccess está configurado
4. Verifica que mod_rewrite está habilitado: `a2enmod rewrite`

### Error 500: Error de servidor

**Causa:** Problema en PHP

**Soluciones:**
1. Revisa logs PHP: `/var/log/php/error.log`
2. Activa modo debug temporalmente en `Api/index.php`
3. Verifica que las rutas de include son correctas

### Error: "Conexión a BD fallida"

**Causa:** Credenciales incorrectas

**Soluciones:**
1. Verifica `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
2. Verifica que MySQL está corriendo
3. Verifica permisos del usuario: `GRANT ALL PRIVILEGES ON babylovec.* TO 'root'@'localhost';`

### Error: "Tabla no existe"

**Causa:** DDL no fue importado

**Soluciones:**
1. Verifica que `modulo_gestion_usuarios_mysql.sql` fue importado
2. Re-importa el archivo
3. Verifica que estás en la BD correcta: `USE babylovec;`

### CORS Policy Error

**Causa:** Headers CORS no configurados correctamente

**Soluciones:**
1. Verifica que `Api/index.php` tiene headers CORS
2. Actualiza `Access-Control-Allow-Origin` con tu dominio
3. En desarrollo puedes usar: `Access-Control-Allow-Origin: *`

### Token inválido

**Causa:** Token expiró (7 días por defecto)

**Soluciones:**
1. User debe hacer login nuevamente
2. Verifica que el reloj del servidor es correcto
3. Aumenta `SESSION_EXPIRY_DAYS` si es necesario

### No se envían emails

**Causa:** `mail()` no configurado

**Soluciones:**
1. Configura un servidor SMTP
2. Usa una librería como SwiftMailer: `composer require swiftmailer/swiftmailer`
3. Por ahora, desactiva en `config.example.php`: `'email' => ['enabled' => false]`

---

## Integración con Aplicación Principal

### Paso 1: Vincular Navegación

En tu menú principal, agrega:

```html
<!-- Si usuario no está logueado -->
<a href="/modules/GestionUsuarios/Views/login.html" class="btn btn-primary">Iniciar Sesión</a>
<a href="/modules/GestionUsuarios/Views/registro.html" class="btn btn-outline-primary">Registrarse</a>

<!-- Si usuario está logueado -->
<a href="/modules/GestionUsuarios/Views/perfil.html" class="btn btn-success">Mi Perfil</a>
<a href="#" onclick="logout()" class="btn btn-danger">Cerrar Sesión</a>
```

### Paso 2: Compartir Estilos (Opcional)

Importa Bootstrap en tu app:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
```

### Paso 3: Sincronizar Usuarios

Si tienes tabla de usuarios propia:
1. Crea migraciones para sincronizar datos
2. O usa esta tabla como fuente única

### Paso 4: Middleware de Autenticación

En tu aplicación principal, verifica token:

```php
<?php
// En tu middleware o bootstrap
function verificarAutenticacion() {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? $_COOKIE['auth_token'] ?? null;
    
    if (!$token) {
        header('Location: /modules/GestionUsuarios/Views/login.html');
        exit;
    }
    
    // Validar token contra BD
    // ...
}
?>
```

---

## Siguientes Pasos

1. ✅ Instalación completada
2. 📖 Lee `README.md` para documentación completa
3. 🚀 Accede a `Views/login.html` para probar
4. 📡 Documenta tus endpoints en Postman
5. 🔐 Configura HTTPS en producción
6. 📊 Configura emails para recuperación
7. 📈 Implementa logs/monitoring

---

## Soporte

Para problemas o preguntas:

1. **Documentación:** Lee `README.md` y `API_ENDPOINTS.md`
2. **Verificación:** Ejecuta `check_install.php`
3. **Testing:** Ejecuta `test_api.sh` para validar endpoints
4. **Logs:** Revisa `logs/` para errores
5. **Contacto:** Comunícate con el equipo de desarrollo

---

**Versión:** 1.0  
**Última actualización:** Noviembre 16, 2025  
**Licencia:** Proyecto Interno
