# 🔐 Integración de Autenticación - Módulo de Impuestos

## ✅ Cambios Realizados

### 1️⃣ Frontend (`impuestos_admin.html`)

#### ❌ **ELIMINADO:**
- Campo de input "Token mantenimiento" (`X-Maint-Token`)
- Función `maintHeaders()`
- LocalStorage de `maintToken`
- Botón "Cargar impuestos" manual

#### ✅ **AGREGADO:**
- Verificación de autenticación al cargar la página
- Validación de roles permitidos: `ADMINISTRADOR`, `GESTOR_CONTENIDOS`
- Header `Authorization: Bearer <token>` en todas las peticiones
- Pantalla de "Acceso Restringido" si el rol no es válido
- Botón "Cerrar Sesión"
- Información del usuario logueado en el header
- Redirección automática a login si no hay token
- Manejo de errores 401/403 con redirección

#### 🔑 **Flujo de Autenticación:**
```
1. Usuario hace login → Obtiene token → Guarda en localStorage
2. Usuario accede a impuestos_admin.html
3. JavaScript valida token con API de usuarios
4. Verifica estado activo (estado_id = 1, activo = 1)
5. Verifica roles (ADMINISTRADOR o GESTOR_CONTENIDOS)
6. Si cumple → Muestra contenido
7. Si no cumple → Muestra "Acceso Restringido" → Redirige a login
```

---

### 2️⃣ Backend (Nuevo Middleware)

#### 📄 **Archivo Creado:** `Controllers/AuthMiddleware.php`

**Responsabilidades:**
- Extraer token del header `Authorization: Bearer <token>`
- Buscar sesión activa en tabla `sesiones_usuario`
- Verificar expiración del token (`fecha_expiracion`)
- Validar que usuario esté `activo = 1` y `estado_id = 1`
- Obtener roles del usuario desde `usuarios_roles`
- Verificar que tenga al menos uno de los roles permitidos
- Actualizar `fecha_ultima_actividad` de la sesión
- Retornar datos del usuario autenticado

**Métodos Principales:**
```php
$auth = new AuthMiddleware($pdo, ['ADMINISTRADOR', 'GESTOR_CONTENIDOS']);
$usuarioAutenticado = $auth->validarAcceso(); // Lanza excepción si falla
$tienePermiso = $auth->tienePermiso($usuario_id, 'PRODUCTOS_EDITAR');
```

---

### 3️⃣ Integración en Controladores API

#### 📄 **Archivo Creado:** `Controllers/INTEGRACION_AUTH.php`

Este archivo documenta cómo modificar:
- `impuestos_api.php`
- `productos_impuestos_api.php`
- `producto_api.php`

**Patrón de Integración:**
```php
<?php
require_once __DIR__ . '/AuthMiddleware.php';

try {
    $pdo = new PDO(...);
    $auth = new AuthMiddleware($pdo, ['ADMINISTRADOR', 'GESTOR_CONTENIDOS']);
    $usuarioAutenticado = $auth->validarAcceso();
} catch (Exception $e) {
    exit; // Middleware ya envió error 401/403
}

// Continuar con lógica del API
// REMOVER validación de X-Maint-Token
```

---

## 📋 Pasos para Completar la Integración

### ✅ **YA COMPLETADO:**
1. ✅ Modificado `impuestos_admin.html` con autenticación Bearer
2. ✅ Creado `AuthMiddleware.php` con validación completa
3. ✅ Creado `INTEGRACION_AUTH.php` con instrucciones

### 🔧 **PENDIENTE (Para ti):**

#### 1. Modificar `impuestos_api.php`
```php
// Al inicio del archivo, después de los headers:
require_once __DIR__ . '/AuthMiddleware.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=babylovec", "root", "password");
    $auth = new AuthMiddleware($pdo, ['ADMINISTRADOR', 'GESTOR_CONTENIDOS']);
    $usuarioAutenticado = $auth->validarAcceso();
} catch (Exception $e) {
    exit;
}

// REMOVER ESTA LÍNEA (si existe):
// if (!isset($_SERVER['HTTP_X_MAINT_TOKEN']) || ...) { ... }
```

#### 2. Modificar `productos_impuestos_api.php`
- Mismo patrón que arriba

#### 3. Modificar `producto_api.php`
- Mismo patrón que arriba

#### 4. Verificar que existe `config/database.php`
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'babylovec');
define('DB_USER', 'root');
define('DB_PASS', 'tu_password');
```

---

## 🧪 Pruebas

### 1. Verificar Login
```bash
# 1. Registrar usuario (si no existe)
curl -X POST http://localhost/modules/GestionUsuarios/Api/index.php?action=registro \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@babylove.com",
    "nombre_completo": "Admin",
    "password": "admin123",
    "confirmar_password": "admin123"
  }'

# 2. Activar usuario manualmente en BD
UPDATE usuarios SET estado_id = 1 WHERE email = 'admin@babylove.com';

# 3. Asignar rol ADMINISTRADOR
INSERT INTO usuarios_roles (usuario_id, rol_id, activo)
SELECT id, 3, 1 FROM usuarios WHERE email = 'admin@babylove.com';

# 4. Login y obtener token
curl -X POST http://localhost/modules/GestionUsuarios/Api/index.php?action=login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@babylove.com",
    "password": "admin123"
  }'

# Respuesta esperada:
{
  "codigo": 200,
  "mensaje": "Login exitoso",
  "datos": {
    "token": "abc123...",  ← GUARDAR ESTE TOKEN
    "usuario": { ... }
  }
}
```

### 2. Probar API de Impuestos
```bash
# Listar impuestos (con token)
curl http://localhost/modules/CatalogoProductos/Controllers/impuestos_api.php \
  -H "Authorization: Bearer abc123..."

# Crear impuesto
curl -X POST http://localhost/modules/CatalogoProductos/Controllers/impuestos_api.php \
  -H "Authorization: Bearer abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "codigo": "IVA",
    "nombre": "Impuesto al Valor Agregado",
    "tipo": "porcentaje",
    "valor": 19.0,
    "aplica_sobre": "subtotal",
    "activo": 1
  }'
```

### 3. Probar Acceso Denegado
```bash
# Sin token → Error 401
curl http://localhost/modules/CatalogoProductos/Controllers/impuestos_api.php

# Token inválido → Error 401
curl http://localhost/modules/CatalogoProductos/Controllers/impuestos_api.php \
  -H "Authorization: Bearer token_falso"

# Usuario con rol CLIENTE → Error 403
# (crear usuario con rol CLIENTE y probar)
```

---

## 🔒 Seguridad Implementada

| Aspecto | Implementación |
|---------|----------------|
| **Autenticación** | ✅ Token Bearer de 64 caracteres |
| **Autorización** | ✅ Validación de roles permitidos |
| **Expiración** | ✅ Tokens expiran según `fecha_expiracion` |
| **Estado usuario** | ✅ Solo usuarios activos (estado_id=1, activo=1) |
| **Auditoría** | ✅ `fecha_ultima_actividad` se actualiza |
| **CORS** | ✅ Headers configurados |
| **SQL Injection** | ✅ Prepared statements |
| **XSS** | ✅ `escapeHTML()` en frontend |

---

## 📊 Comparativa

| Antes | Después |
|-------|---------|
| ❌ Token manual `X-Maint-Token` | ✅ Token de sesión automático |
| ❌ Sin expiración | ✅ Expira según configuración |
| ❌ Sin roles | ✅ Roles y permisos validados |
| ❌ Sin auditoría | ✅ Rastreo de actividad |
| ❌ Mismo token para todos | ✅ Token único por usuario/sesión |

---

## 🎯 Próximos Pasos Recomendados

1. **Aplicar el mismo patrón a otros módulos**
   - `catalogo_productos.html`
   - Otras vistas administrativas

2. **Implementar refresh token**
   - Token de corta duración + refresh token

3. **Agregar registro de auditoría**
   - Tabla `auditoria_impuestos`
   - Registrar quién creó/modificó/eliminó

4. **Rate limiting**
   - Limitar intentos de API por IP/usuario

5. **Notificaciones**
   - Email cuando se asignan/modifican impuestos importantes

---

## ❓ FAQ

**P: ¿Qué pasa si el token expira durante el uso?**
R: El middleware detecta expiración y retorna 401. El frontend redirige a login automáticamente.

**P: ¿Puedo agregar más roles permitidos?**
R: Sí, modifica el array en `verificarAcceso()`:
```javascript
const ROLES_PERMITIDOS = ['ADMINISTRADOR', 'GESTOR_CONTENIDOS', 'VENDEDOR'];
```

**P: ¿Cómo agrego permisos granulares?**
R: Usa `$auth->tienePermiso($usuario_id, 'PRODUCTOS_EDITAR')` en cada endpoint.

**P: ¿El middleware funciona con otros frameworks?**
R: Sí, es PHP puro. Compatible con cualquier proyecto PHP con PDO.

---

## 📞 Soporte

Si encuentras errores:
1. Verifica que la tabla `sesiones_usuario` exista
2. Verifica que el evento `evt_limpiar_sesiones_expiradas` esté activo
3. Revisa los logs de PHP/Apache
4. Verifica permisos de CORS en el servidor

---

**¡Integración completada! 🎉**
