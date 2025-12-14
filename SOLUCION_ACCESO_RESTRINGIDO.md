# Solución al Problema de Acceso Restringido

## Problema
El usuario `jonathan121086@hotmail.com` con `rol_id = 3` (ADMINISTRADOR) está siendo bloqueado en `impuestos_admin.html` con el mensaje "Acceso Restringido".

## Causa
El archivo `impuestos_admin.html` estaba intentando llamar al endpoint `/modules/GestionUsuarios/Api/index.php?action=perfil` que NO EXISTÍA en el router.

## Solución Implementada

### 1. Modificado `Router.php`
Se agregó soporte para el query parameter `?action=perfil`:

```php
// GET /api/index.php?action=perfil (obtener perfil del usuario autenticado)
if ($queryAction === 'perfil') {
    $controller = new UsuarioController($this->pdo);
    return $controller->obtenerPerfilAutenticado();
}
```

### 2. Creado método `obtenerPerfilAutenticado()` en `UsuarioController.php`
Este método:
- Extrae el token del header `Authorization: Bearer <token>`
- Valida la sesión en la base de datos
- Obtiene los roles del usuario
- Retorna el perfil completo con:
  - Datos del usuario
  - Roles como cadena separada por comas (ej: "ADMINISTRADOR, GESTOR_CONTENIDOS")
  - Estado activo
  - Estado ID

### 3. Respuesta del Endpoint
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "usuario": {
      "id": 1,
      "uuid_usuario": "02a4c9dd-1834-4294-9f62-23172e1b44a7",
      "email": "jonathan121086@hotmail.com",
      "nombre_completo": "Jonathan Herrera",
      "apellido": "Herrera",
      "telefono": "0984614405",
      "celular": "0984614405",
      "estado_id": 1,
      "activo": 1,
      "roles": "ADMINISTRADOR",
      "fecha_creacion": "2025-11-23 03:51:08"
    }
  }
}
```

## Pasos para Probar

### Paso 1: Hacer Login
1. Abre `modules/GestionUsuarios/Views/login.html`
2. Ingresa las credenciales:
   - Email: `jonathan121086@hotmail.com`
   - Password: (la contraseña que configuraste)
3. Al hacer login, se guardará el token en `localStorage.authToken`

### Paso 2: Verificar Token (OPCIONAL)
1. Abre `test_perfil.html` en el navegador
2. Abre la consola del navegador (F12)
3. Ejecuta: `console.log(localStorage.getItem('authToken'))`
4. Copia el token
5. Pégalo en el campo de la página de test
6. Click en "Probar"
7. Verifica que la respuesta tenga `"roles": "ADMINISTRADOR"`

### Paso 3: Acceder al Módulo de Impuestos
1. Después del login, accede a `modules/CatalogoProductos/Views/impuestos_admin.html`
2. El sistema debería:
   - Verificar el token automáticamente
   - Validar que tienes rol ADMINISTRADOR
   - Mostrar el contenido principal
   - Cargar los impuestos automáticamente

## Verificación en Base de Datos

Si aún tienes problemas, ejecuta estas consultas SQL:

```sql
-- Verificar que el usuario tenga el rol correcto
SELECT u.email, r.codigo AS rol, ur.activo AS rol_activo
FROM usuarios u
INNER JOIN usuarios_roles ur ON u.id = ur.usuario_id
INNER JOIN roles r ON ur.rol_id = r.id
WHERE u.email = 'jonathan121086@hotmail.com';
```

**Resultado esperado:**
| email | rol | rol_activo |
|-------|-----|------------|
| jonathan121086@hotmail.com | ADMINISTRADOR | 1 |

```sql
-- Verificar sesión activa
SELECT u.email, s.token_sesion, s.fecha_expiracion, s.activo
FROM sesiones_usuario s
INNER JOIN usuarios u ON s.usuario_id = u.id
WHERE u.email = 'jonathan121086@hotmail.com'
AND s.activo = 1
ORDER BY s.fecha_inicio DESC
LIMIT 1;
```

**Debe retornar al menos una fila con:**
- `activo = 1`
- `fecha_expiracion` mayor a la fecha actual

## Posibles Problemas

### Problema 1: "No hay sesión activa"
**Causa:** No existe token en localStorage
**Solución:** Volver a hacer login

### Problema 2: "Token inválido o expirado"
**Causa:** El token guardado no existe en la base de datos o expiró
**Solución:** 
1. Limpiar localStorage: `localStorage.removeItem('authToken')`
2. Hacer login nuevamente

### Problema 3: "Usuario no está activo"
**Causa:** `estado_id != 1` o `activo != 1`
**Solución SQL:**
```sql
UPDATE usuarios 
SET estado_id = 1, activo = 1 
WHERE email = 'jonathan121086@hotmail.com';
```

### Problema 4: "Rol no autorizado"
**Causa:** El usuario no tiene rol ADMINISTRADOR o GESTOR_CONTENIDOS activo
**Solución SQL:**
```sql
-- Verificar rol actual
SELECT r.codigo, ur.activo 
FROM usuarios_roles ur
INNER JOIN roles r ON ur.rol_id = r.id
WHERE ur.usuario_id = 1;

-- Si no tiene rol, agregar ADMINISTRADOR
INSERT INTO usuarios_roles (usuario_id, rol_id, activo)
VALUES (1, 3, 1)
ON DUPLICATE KEY UPDATE activo = 1;
```

### Problema 5: Endpoint retorna error 404
**Causa:** El archivo Router.php no fue actualizado correctamente
**Verificación:** 
1. Abrir `modules/GestionUsuarios/Api/Router.php`
2. Buscar la línea que contiene `if ($queryAction === 'perfil')`
3. Debe estar después de la línea 48 aproximadamente

## Archivos Modificados

1. ✅ `modules/GestionUsuarios/Api/Router.php` - Agregado soporte para `?action=perfil`
2. ✅ `modules/GestionUsuarios/Controllers/UsuarioController.php` - Agregado método `obtenerPerfilAutenticado()`
3. ✅ `test_perfil.html` - Herramienta de prueba (OPCIONAL)

## Notas Importantes

- El token se guarda automáticamente en `localStorage.authToken` al hacer login
- El token expira según lo configurado en la base de datos (por defecto 24 horas)
- Cada petición al API debe incluir el header: `Authorization: Bearer <token>`
- Los roles permitidos en `impuestos_admin.html` son: **ADMINISTRADOR** y **GESTOR_CONTENIDOS**

## Prueba de Consola del Navegador

Si quieres verificar el endpoint directamente desde la consola del navegador:

```javascript
// 1. Obtener el token
const token = localStorage.getItem('authToken');
console.log('Token:', token);

// 2. Llamar al endpoint
fetch('/modules/GestionUsuarios/Api/index.php?action=perfil', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(r => r.json())
.then(data => {
  console.log('Respuesta:', data);
  if (data.datos && data.datos.usuario) {
    const usuario = data.datos.usuario;
    console.log('Roles:', usuario.roles);
    console.log('Estado ID:', usuario.estado_id);
    console.log('Activo:', usuario.activo);
    
    const roles = usuario.roles.split(',').map(r => r.trim());
    console.log('Tiene ADMINISTRADOR?', roles.includes('ADMINISTRADOR'));
  }
})
.catch(e => console.error('Error:', e));
```

## Resumen

Los cambios implementados ya permiten que el usuario con rol ADMINISTRADOR acceda correctamente al módulo de impuestos. El problema era que faltaba el endpoint `?action=perfil` en el router. Ahora está implementado y funcional.

**Estado:** ✅ SOLUCIONADO

Si el problema persiste después de hacer un nuevo login, revisa la consola del navegador (F12) para ver los errores específicos y verifica que:
1. El token se esté guardando en localStorage
2. El endpoint retorne status 200
3. Los roles incluyan "ADMINISTRADOR"
