# 📡 API Endpoints - Módulo de Gestión de Usuarios

**Base URL:** `/modules/GestionUsuarios/Api`  
**Versión:** 1.0  
**Formato:** JSON

---

## 📑 Tabla de Contenidos

1. [Autenticación](#autenticación)
2. [Usuarios](#usuarios)
3. [Perfil](#perfil)
4. [Contraseña](#contraseña)
5. [Pedidos](#pedidos)
6. [Roles](#roles)
7. [Permisos](#permisos)
8. [Códigos de Error](#códigos-de-error)

---

## Autenticación

### POST `/usuarios/registro`

Registra un nuevo usuario en el sistema.

**Parámetros:**
```json
{
  "nombre_completo": "string (required)",
  "email": "string (required, unique)",
  "password": "string (required, min 8)",
  "confirmar_password": "string (required)",
  "apellido": "string (optional)"
}
```

**Respuesta exitosa (201):**
```json
{
  "codigo": 201,
  "mensaje": "Usuario registrado exitosamente",
  "datos": {
    "usuario_id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "usuario@example.com"
  }
}
```

**Errores posibles:**
- `400` - Email ya existe o datos inválidos
- `500` - Error interno del servidor

---

### POST `/usuarios/login`

Autentica un usuario y retorna un token de sesión.

**Parámetros:**
```json
{
  "email": "string (required)",
  "password": "string (required)",
  "recuerdarme": "boolean (optional)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Login exitoso",
  "datos": {
    "usuario_id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "usuario@example.com",
    "nombre": "Juan Pérez",
    "apellido": "Pérez",
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6...",
    "roles": ["CLIENTE"],
    "sesion_expiry": 7
  }
}
```

**Errores posibles:**
- `400` - Email o contraseña incorrectos
- `401` - Usuario bloqueado por múltiples intentos fallidos
- `500` - Error interno

---

### POST `/usuarios/logout`

Termina la sesión del usuario actual.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "token": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Logout exitoso",
  "datos": {
    "sesion_id": "xyz123"
  }
}
```

---

### POST `/usuarios/validar-sesion`

Valida que un token de sesión sea válido.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "token": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Sesión válida",
  "datos": {
    "usuario_id": 1,
    "email": "usuario@example.com",
    "valido": true,
    "expira_en": "2025-11-23 10:30:00"
  }
}
```

---

## Usuarios

### GET `/usuarios`

Lista todos los usuarios (requiere admin).

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `limit` (int): Resultados por página (default: 20)
- `offset` (int): Desplazamiento (default: 0)
- `buscar` (string): Búsqueda por nombre/email

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "usuarios": [
      {
        "usuario_id": 1,
        "email": "usuario@example.com",
        "nombre_completo": "Juan Pérez",
        "estado": "activo",
        "fecha_registro": "2025-11-16 10:30:00"
      }
    ],
    "total": 45,
    "pagina": 1,
    "por_pagina": 20
  }
}
```

---

### GET `/usuarios/:id`

Obtiene datos de un usuario específico.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "id": 1,
    "email": "usuario@example.com",
    "nombre_completo": "Juan Pérez",
    "apellido": "Pérez",
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "estado": "activo",
    "fecha_registro": "2025-11-16 10:30:00",
    "perfil": {
      "ciudad": "Bogotá",
      "pais": "Colombia",
      "telefono": "3001234567",
      "celular": "3001234567",
      "biografia": "Mi biografía"
    },
    "roles": ["CLIENTE"],
    "permisos": ["ver_pedidos", "crear_pedido"]
  }
}
```

---

## Perfil

### PUT `/usuarios/:id/perfil`

Actualiza la información del perfil de usuario.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "nombre_completo": "string (optional)",
  "apellido": "string (optional)",
  "ciudad": "string (optional)",
  "pais": "string (optional)",
  "documento": "string (optional)",
  "tipo_documento": "string (optional)",
  "telefono": "string (optional)",
  "celular": "string (optional)",
  "direccion": "string (optional)",
  "biografia": "string (optional)",
  "foto": "string (optional, base64)",
  "fecha_nacimiento": "string (optional, Y-m-d)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Perfil actualizado exitosamente",
  "datos": {
    "usuario_id": 1,
    "perfil_id": 1
  }
}
```

---

### GET `/usuarios/:id/perfil`

Obtiene información detallada del perfil.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "perfil_id": 1,
    "usuario_id": 1,
    "ciudad": "Bogotá",
    "pais": "Colombia",
    "documento": "1023456789",
    "tipo_documento": "CC",
    "telefono": "3001234567",
    "celular": "3001234567",
    "direccion": "Calle 123 #45-67",
    "biografia": "Mi biografía",
    "redes_sociales": {
      "facebook": "https://facebook.com/usuario",
      "instagram": "@usuario"
    }
  }
}
```

---

## Contraseña

### POST `/usuarios/recuperar-contrasena`

Solicita token para recuperar contraseña (por email).

**Parámetros:**
```json
{
  "email": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Revisa tu email para recuperar tu contraseña",
  "datos": {}
}
```

**Nota:** La respuesta es igual aunque el email no exista (por seguridad).

---

### GET `/usuarios/validar-token-recuperacion`

Valida que un token de recuperación sea válido.

**Query Parameters:**
- `token` (string, required): Token de recuperación

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Token válido",
  "datos": {
    "valido": true,
    "email": "usuario@example.com"
  }
}
```

**Error (400):**
```json
{
  "codigo": 400,
  "mensaje": "Token inválido o expirado",
  "datos": {}
}
```

---

### POST `/usuarios/resetear-contrasena`

Resetea la contraseña usando un token válido.

**Parámetros:**
```json
{
  "token": "string (required)",
  "password": "string (required, min 8)",
  "confirmar_password": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Contraseña actualizada exitosamente",
  "datos": {
    "usuario_id": 1
  }
}
```

---

### POST `/usuarios/:id/cambiar-contrasena`

Cambia la contraseña del usuario (requiere contraseña antigua).

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "password_antigua": "string (required)",
  "password_nueva": "string (required, min 8)",
  "confirmar_password": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Contraseña cambiada exitosamente",
  "datos": {
    "usuario_id": 1
  }
}
```

---

## Pedidos

### GET `/usuarios/:id/pedidos`

Lista los pedidos de un usuario.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `limit` (int): Resultados por página (default: 20)
- `offset` (int): Desplazamiento (default: 0)
- `estado` (string): Filtrar por estado (pendiente, procesando, entregado, etc)
- `desde` (date): Fecha inicio (Y-m-d)
- `hasta` (date): Fecha fin (Y-m-d)

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "pedidos": [
      {
        "pedido_id": 1,
        "numero_pedido": "PED-2025-00001",
        "estado": "entregado",
        "total": 150.50,
        "fecha": "2025-11-16 10:30:00",
        "cantidad_items": 3
      }
    ],
    "resumen": {
      "total_pedidos": 45,
      "gasto_total": 2500.00,
      "ticket_promedio": 55.56,
      "ultimo_pedido": "2025-11-16 10:30:00"
    },
    "paginacion": {
      "pagina": 1,
      "por_pagina": 20,
      "total": 45
    }
  }
}
```

---

### GET `/pedidos/:id`

Obtiene detalles de un pedido específico.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "pedido_id": 1,
    "numero_pedido": "PED-2025-00001",
    "usuario_id": 1,
    "estado": "entregado",
    "subtotal": 100.00,
    "impuestos": 19.00,
    "envio": 31.50,
    "total": 150.50,
    "fecha_pedido": "2025-11-16 10:30:00",
    "fecha_entrega": "2025-11-18 14:00:00",
    "direccion_entrega": "Calle 123 #45-67, Bogotá",
    "detalles": [
      {
        "detalle_id": 1,
        "producto_id": 10,
        "nombre_producto": "Producto A",
        "cantidad": 2,
        "precio_unitario": 50.00,
        "subtotal": 100.00
      }
    ]
  }
}
```

---

### GET `/usuarios/:id/pedidos/estadisticas`

Obtiene estadísticas de los pedidos de un usuario.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "total_pedidos": 45,
    "gasto_total": 2500.00,
    "gasto_promedio": 55.56,
    "pedido_mayor": 250.00,
    "pedido_menor": 15.50,
    "estado_distribucion": {
      "pendiente": 2,
      "procesando": 1,
      "enviado": 3,
      "entregado": 39
    },
    "mes_mayor_gasto": "octubre",
    "mes_mayor_gasto_monto": 450.00
  }
}
```

---

### GET `/usuarios/:id/pedidos/recientes`

Obtiene los últimos N pedidos.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `dias` (int): Últimos N días (default: 30)
- `limit` (int): Máximo de resultados (default: 10)

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "pedidos": [
      {
        "pedido_id": 1,
        "numero_pedido": "PED-2025-00001",
        "estado": "entregado",
        "total": 150.50,
        "fecha": "2025-11-16 10:30:00"
      }
    ],
    "total": 5
  }
}
```

---

## Roles

### GET `/roles`

Lista todos los roles disponibles.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "roles": [
      {
        "rol_id": 1,
        "codigo": "CLIENTE",
        "nombre": "Cliente",
        "descripcion": "Cliente del sistema",
        "activo": true,
        "permisos_count": 5
      }
    ],
    "total": 3
  }
}
```

---

### GET `/roles/:id`

Obtiene detalles de un rol específico.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "rol_id": 1,
    "codigo": "CLIENTE",
    "nombre": "Cliente",
    "descripcion": "Cliente del sistema",
    "activo": true,
    "permisos": [
      {
        "permiso_id": 1,
        "codigo": "ver_pedidos",
        "nombre": "Ver Pedidos",
        "modulo": "pedidos"
      }
    ]
  }
}
```

---

### GET `/roles/codigo/:codigo`

Obtiene un rol por su código.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**URL Parámetros:**
- `codigo` (string): Código del rol (ej: CLIENTE, ADMIN)

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "rol_id": 1,
    "codigo": "CLIENTE"
  }
}
```

---

### POST `/roles`

Crea un nuevo rol (requiere admin).

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "codigo": "string (required, unique)",
  "nombre": "string (required)",
  "descripcion": "string (optional)"
}
```

**Respuesta exitosa (201):**
```json
{
  "codigo": 201,
  "mensaje": "Rol creado exitosamente",
  "datos": {
    "rol_id": 4,
    "codigo": "VENDEDOR"
  }
}
```

---

### POST `/roles/:id/permisos`

Asigna un permiso a un rol.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "permiso_id": "integer (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Permiso asignado exitosamente",
  "datos": {}
}
```

---

### DELETE `/roles/:id/permisos/:permiso_id`

Remueve un permiso de un rol.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "Permiso removido exitosamente",
  "datos": {}
}
```

---

## Permisos

### GET `/permisos`

Lista todos los permisos disponibles.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "permisos": [
      {
        "permiso_id": 1,
        "codigo": "ver_usuarios",
        "nombre": "Ver Usuarios",
        "modulo": "usuarios"
      }
    ],
    "por_modulo": {
      "usuarios": [
        {
          "permiso_id": 1,
          "codigo": "ver_usuarios",
          "nombre": "Ver Usuarios"
        }
      ],
      "pedidos": [
        {
          "permiso_id": 5,
          "codigo": "ver_pedidos",
          "nombre": "Ver Pedidos"
        }
      ]
    },
    "total": 15
  }
}
```

---

### GET `/usuarios/:id/permisos`

Obtiene los permisos de un usuario específico.

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "codigo": 200,
  "mensaje": "OK",
  "datos": {
    "usuario_id": 1,
    "permisos": [
      "ver_pedidos",
      "crear_pedido",
      "editar_pedido"
    ],
    "total": 3,
    "roles": ["CLIENTE"]
  }
}
```

---

### POST `/permisos`

Crea un nuevo permiso (requiere admin).

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Parámetros:**
```json
{
  "codigo": "string (required, unique)",
  "nombre": "string (required)",
  "modulo": "string (required)",
  "descripcion": "string (optional)"
}
```

**Respuesta exitosa (201):**
```json
{
  "codigo": 201,
  "mensaje": "Permiso creado exitosamente",
  "datos": {
    "permiso_id": 16,
    "codigo": "nuevo_permiso"
  }
}
```

---

## Códigos de Error

### 200 - OK
Solicitud exitosa.

### 201 - Created
Recurso creado exitosamente.

### 400 - Bad Request
- Datos inválidos
- Email ya existe
- Contraseña débil
- Token inválido

```json
{
  "codigo": 400,
  "mensaje": "Email ya está registrado",
  "datos": {}
}
```

### 401 - Unauthorized
- Token ausente o inválido
- Sesión expirada
- Permisos insuficientes

```json
{
  "codigo": 401,
  "mensaje": "Acceso no autorizado",
  "datos": {}
}
```

### 404 - Not Found
- Usuario no existe
- Endpoint no existe

```json
{
  "codigo": 404,
  "mensaje": "Recurso no encontrado",
  "datos": {}
}
```

### 500 - Internal Server Error
- Error de base de datos
- Error no manejado

```json
{
  "codigo": 500,
  "mensaje": "Error interno del servidor",
  "datos": {}
}
```

---

## Notas Generales

- Todos los timestamps están en formato `Y-m-d H:i:s` (UTC-5)
- Los tokens expiran en 7 días
- Las contraseñas se hashean con bcrypt (cost 12)
- Todos los IDs son números enteros
- Los UUIDs son válidos RFC 4122

---

**Última actualización:** Noviembre 16, 2025  
**Versión:** 1.0
