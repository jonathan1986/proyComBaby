# 🚀 Guía Rápida - Módulo de Gestión de Usuarios

**Tiempo estimado de setup:** 5 minutos

---

## ✅ Checklist de Instalación

### 1️⃣ Importar Base de Datos

```bash
# Si usas Docker
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql

# O con MySQL local
mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

**Verifica:** 14 tablas creadas (sin errores de UUID)
```sql
SHOW TABLES;  -- Debe haber: usuarios, perfiles_usuario, pedidos, roles, permisos, etc.
```

**Nota:** El script está corregido para MySQL 5.7. Los UUIDs se generan automáticamente desde PHP.

---

### 2️⃣ Crear Archivo Entry Point

**Ruta:** `modules/GestionUsuarios/Api/index.php`

```php
<?php
/**
 * API Entry Point - Gestión de Usuarios
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Autoload y configuración
require_once '../../../bootstrap.php';

// Crear conexión PDO
try {
    $pdo = new PDO(
        'mysql:host=' . (defined('DB_HOST') ? DB_HOST : 'localhost') .
        ';dbname=' . (defined('DB_NAME') ? DB_NAME : 'babylovec') .
        ';charset=utf8mb4',
        defined('DB_USER') ? DB_USER : 'root',
        defined('DB_PASS') ? DB_PASS : 'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'Error de conexión a base de datos',
        'error' => $e->getMessage()
    ]));
}

// Instanciar y ejecutar router
try {
    $router = new \Modules\GestionUsuarios\Api\Router($pdo);
    $router->ejecutar();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'codigo' => 500,
        'mensaje' => 'Error en la API',
        'error' => $e->getMessage()
    ]);
}
?>
```

---

### 3️⃣ Configurar URLs en Frontend

**Archivo:** `modules/GestionUsuarios/Assets/js/auth.js`

Busca esta línea (línea ~1):
```javascript
const API_URL = '/modules/GestionUsuarios/Api';
```

**Ajusta según tu estructura:**
- Si está en raíz: `/modules/GestionUsuarios/Api`
- Si está en subcarpeta: `/proyecto/modules/GestionUsuarios/Api`
- Si es HTTPS: `https://tudominio.com/modules/GestionUsuarios/Api`

---

### 4️⃣ Prueba Rápida con cURL

**Registro:**
```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_completo": "Test User",
    "email": "test@example.com",
    "password": "Test1234!",
    "confirmar_password": "Test1234!"
  }'
```

**Login:**
```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test1234!"
  }'
```

**Respuesta exitosa:**
```json
{
  "codigo": 200,
  "mensaje": "Login exitoso",
  "datos": {
    "usuario_id": 1,
    "email": "test@example.com",
    "token": "abc123..."
  }
}
```

---

## 📌 URLs de Acceso

Una vez instalado:

| Función | URL |
|---------|-----|
| 📝 Registro | `/modules/GestionUsuarios/Views/registro.html` |
| 🔐 Login | `/modules/GestionUsuarios/Views/login.html` |
| 🔑 Recuperar | `/modules/GestionUsuarios/Views/recuperar_contrasena.html` |
| 👤 Perfil | `/modules/GestionUsuarios/Views/perfil.html` |
| 📡 API | `/modules/GestionUsuarios/Api/` |

---

## 🔧 Configuración (Opcional)

### Si usas variables de entorno

**Archivo:** `modules/GestionUsuarios/config.php`

```php
<?php
return [
    'api_base_url' => getenv('USERS_API_URL') ?? '/modules/GestionUsuarios/Api',
    'password_min_length' => getenv('PASSWORD_MIN_LENGTH') ?? 8,
    'session_expiry_days' => getenv('SESSION_EXPIRY_DAYS') ?? 7,
    'recovery_token_expiry_minutes' => getenv('RECOVERY_EXPIRY_MIN') ?? 30,
];
?>
```

Luego en tu `.env`:
```env
USERS_API_URL=/modules/GestionUsuarios/Api
PASSWORD_MIN_LENGTH=8
SESSION_EXPIRY_DAYS=7
RECOVERY_EXPIRY_MIN=30
```

---

## 🧪 Testing Completo

### 1. Crear Usuario

```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_completo": "Juan Pérez",
    "email": "juan@test.com",
    "password": "Seguro123!",
    "confirmar_password": "Seguro123!"
  }'
```

### 2. Login

```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@test.com",
    "password": "Seguro123!"
  }' | jq .
```

**Copiar el `token` del response**

### 3. Obtener Perfil

```bash
curl -X GET http://localhost/modules/GestionUsuarios/Api/usuarios/1 \
  -H "Authorization: Bearer TOKEN_AQUI"
```

### 4. Actualizar Perfil

```bash
curl -X PUT http://localhost/modules/GestionUsuarios/Api/usuarios/1/perfil \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_AQUI" \
  -d '{
    "ciudad": "Bogotá",
    "pais": "Colombia",
    "telefono": "3001234567"
  }'
```

### 5. Ver Pedidos

```bash
curl -X GET http://localhost/modules/GestionUsuarios/Api/usuarios/1/pedidos \
  -H "Authorization: Bearer TOKEN_AQUI"
```

### 6. Logout

```bash
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/logout \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_AQUI" \
  -d '{"token": "TOKEN_AQUI"}'
```

---

## 🆘 Problemas Comunes

### Error: "404 Not Found"
**Causa:** Ruta incorrecta o archivo index.php no existe  
**Solución:** Verifica que `Api/index.php` exista y la URL sea correcta

### Error: "Conexión a base de datos fallida"
**Causa:** Credenciales de BD incorrectas  
**Solución:** Revisa `bootstrap.php` o variables de entorno

### Error: "CORS policy"
**Causa:** Frontend en diferente dominio  
**Solución:** Actualiza `Access-Control-Allow-Origin` en `Api/index.php`

### Token inválido
**Causa:** Sesión expirada (7 días por defecto)  
**Solución:** Usuario debe hacer login nuevamente

### Email no se envía en recuperación
**Causa:** `mail()` no configurado en servidor  
**Solución:** Usa SwiftMailer o similar (ver README.md)

---

## 📊 Estructura de Respuestas

Todos los endpoints retornan este formato:

```json
{
  "codigo": 200,
  "mensaje": "Descripción de lo que pasó",
  "datos": {
    // Aquí va la información
  }
}
```

**Códigos HTTP:**
- `200` - OK
- `201` - Creado
- `400` - Bad Request (validación)
- `401` - Unauthorized (token inválido)
- `404` - Not Found
- `500` - Server Error

---

## 🔐 Seguridad en Producción

Antes de ir a producción:

- [ ] Cambiar `Access-Control-Allow-Origin` a tu dominio
- [ ] Usar HTTPS siempre
- [ ] Configurar CORS correctamente
- [ ] Implementar Rate Limiting
- [ ] Usar variables de entorno para credenciales
- [ ] Activar HTTPS en .htaccess
- [ ] Configurar email service real (no mock)
- [ ] Revisar logs de error
- [ ] Hacer backup de BD regularmente

---

## 📝 Notas

- El módulo es **independiente** del resto del e-commerce
- Puedes integrarlo en cualquier página
- Los tokens expiran automáticamente en 7 días
- Las contraseñas se hashean con bcrypt
- Todos los datos se validan en servidor

---

## 📞 Soporte

Para problemas o preguntas, revisa:
1. `README.md` - Documentación completa
2. `GUIA_GESTION_USUARIOS.md` - Documentación técnica
3. `Api/router.php` - Ver rutas disponibles

---

**Versión:** 1.0 | **Fecha:** Noviembre 2025
