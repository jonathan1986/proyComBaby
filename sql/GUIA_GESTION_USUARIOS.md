# Módulo de Gestión de Usuarios - E-Commerce
## Guía Técnica y de Implementación

**Versión:** 1.0  
**Compatible con:** MySQL 5.7+  
**Última actualización:** Noviembre 2025

---

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Estructura de Base de Datos](#estructura-de-base-de-datos)
3. [Instalación](#instalación)
4. [Características Principales](#características-principales)
5. [Tablas Detalladas](#tablas-detalladas)
6. [Vistas (Views)](#vistas-views)
7. [Procedimientos Almacenados](#procedimientos-almacenados)
8. [Funciones](#funciones)
9. [Triggers](#triggers)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Recomendaciones de Seguridad](#recomendaciones-de-seguridad)
12. [FAQ](#faq)

---

## Descripción General

Este módulo proporciona una solución completa de gestión de usuarios para sistemas de e-commerce con:

- ✅ **Autenticación segura** con manejo de contraseñas hasheadas
- ✅ **Sistema de Roles y Permisos** flexible (RBAC)
- ✅ **Recuperación de contraseñas** con tokens temporales
- ✅ **Perfiles de usuario** extendidos (datos personales, preferencias, redes sociales)
- ✅ **Historial de acceso** y auditoría completa
- ✅ **Gestión de sesiones** con expiración automática
- ✅ **Soporte para 2FA** (base de datos lista)
- ✅ **Relación Usuarios-Pedidos** para e-commerce
- ✅ **Vistas optimizadas** para reportes

---

## Estructura de Base de Datos

### Diagrama ER (Entity-Relationship)

```
┌─────────────────────────────────────────────────────────────────┐
│                       MÓDULO DE USUARIOS                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────┐        ┌─────────────────┐               │
│  │    USUARIOS      │◄───────┤ ESTADOS_USUARIO │               │
│  │  (1 a muchos)    │        │   (Estados)     │               │
│  └──────────────────┘        └─────────────────┘               │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ PERFILES_USUARIO     │ (1:1)      │
│          │                  │  (Datos extendidos)  │            │
│          │                  └──────────────────────┘            │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ SESIONES_USUARIO     │ (1:N)      │
│          │                  │ (Sesiones activas)   │            │
│          │                  └──────────────────────┘            │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ RECUPERACION_CONTRASENA│ (1:N)     │
│          │                  │ (Tokens de reset)    │            │
│          │                  └──────────────────────┘            │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ HISTORIAL_CONTRASENAS│ (1:N)      │
│          │                  │ (Cambios pasados)    │            │
│          │                  └──────────────────────┘            │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ HISTORIAL_ACCESO     │ (1:N)      │
│          │                  │ (Login auditoría)    │            │
│          │                  └──────────────────────┘            │
│          │                                                      │
│          ├─────────────────►┌──────────────────────┐            │
│          │                  │ USUARIOS_ROLES       │ (N:M)      │
│          │                  │ (Asignación de roles)│            │
│          │                  └──────────────────────┘            │
│          │                         │                           │
│          │                         └──────────────────────┐    │
│          │                                                │    │
│          ├─────────────────►┌──────────────────────┐    │    │
│          │                  │ PEDIDOS              │    │    │
│          │                  │ (Historial compras)  │    │    │
│          │                  └──────────────────────┘    │    │
│          │                         │                    │    │
│          │                         └────────────────┐   │    │
│          │                                         │   │    │
│          └─►┌──────────────────────┐   ┌─────────────────┐    │
│             │ DETALLES_PEDIDO      │   │ ROLES           │    │
│             │ (Items en pedidos)   │   │ (Rol del sistema)   │
│             └──────────────────────┘   └─────────────────┘    │
│                                                │               │
│                                                ▼               │
│                                     ┌─────────────────┐        │
│                                     │ ROLES_PERMISOS  │ (N:M)  │
│                                     │ (Asignación     │        │
│                                     │  permisos)      │        │
│                                     └─────────────────┘        │
│                                                │               │
│                                                ▼               │
│                                     ┌─────────────────┐        │
│                                     │ PERMISOS        │        │
│                                     │ (Permisos)      │        │
│                                     └─────────────────┘        │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ AUDITORIA_USUARIOS                                     │    │
│  │ (Log de cambios y operaciones sensibles)               │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Instalación

### Paso 1: Requisitos Previos

```bash
# Verificar versión de MySQL
mysql --version
# Salida esperada: mysql Ver 14.14 Distrib 5.7.x

# En Docker (si lo usas)
docker exec proycombaby-db-1 mysql --version
```

### Paso 2: Importar el DDL

#### Opción A: Línea de comandos (Recomendado)

```bash
# Via Docker
docker exec -i proycombaby-db-1 mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql

# O directamente si MySQL está instalado localmente
mysql -u root -p babylovec < sql/modulo_gestion_usuarios_mysql.sql
```

#### Opción B: PhpMyAdmin

1. Abre PhpMyAdmin: `http://localhost:8080` (o tu URL)
2. Selecciona la base de datos `babylovec`
3. Haz clic en la pestaña **"Importar"**
4. Selecciona el archivo `modulo_gestion_usuarios_mysql.sql`
5. Haz clic en **"Ejecutar"**

#### Opción C: Cliente MySQL GUI

- Abre MySQL Workbench o similar
- Conecta a tu servidor
- Abre el archivo SQL
- Ejecuta (Ctrl+Shift+Enter)

### Paso 3: Verificar Instalación

```sql
-- Ver todas las tablas creadas
SHOW TABLES IN babylovec;

-- Verificar datos iniciales
SELECT * FROM estados_usuario;
SELECT * FROM roles;
SELECT COUNT(*) as total_permisos FROM permisos;

-- Ver vistas creadas
SHOW FULL TABLES WHERE table_type = 'VIEW';
```

**Salida esperada:**

```
Tabla: auditoria_usuarios
Tabla: detalles_pedido
Tabla: estados_usuario (5 filas)
Tabla: historial_acceso
Tabla: historial_contrasenas
Tabla: pedidos
Tabla: perfiles_usuario
Tabla: permisos (21 filas)
Tabla: recuperacion_contrasena
Tabla: roles (5 filas)
Tabla: roles_permisos (31 filas)
Tabla: sesiones_usuario
Tabla: usuarios
Tabla: usuarios_roles
View: v_usuarios_activos
View: v_pedidos_recientes
View: v_permisos_usuario
View: v_resumen_usuario
```

---

## Características Principales

### 1. Autenticación y Seguridad

- **UUID único** por usuario (CHAR(36))
- **Contraseña hasheada** (se genera desde PHP, no almacena texto plano)
- **Tokens de sesión** (64 caracteres, únicos, con expiración)
- **Historial de intentos de login** (incluyendo fallos)
- **Protección contra reutilización** de contraseñas antiguas
- **Recuperación de contraseña** con token de un solo uso

### 2. Gestión de Roles y Permisos (RBAC)

Roles predefinidos:
- **CLIENTE** (nivel 10): Acceso básico, solo sus pedidos
- **VENDEDOR** (nivel 20): Gestión de productos y pedidos propios
- **MODERADOR** (nivel 30): Supervisión de usuarios y pedidos
- **GESTOR_CONTENIDOS** (nivel 25): Gestión de catálogo
- **ADMINISTRADOR** (nivel 50): Acceso total al sistema

Permisos granulares (21 permisos):
- USUARIOS: ver, crear, editar, eliminar, bloquear
- PEDIDOS: ver propios, ver todos, editar, cancelar
- PRODUCTOS: ver, crear, editar, eliminar
- ROLES: ver, editar, asignar
- REPORTES: ver, exportar
- CONFIG: ver, editar
- AUDITORIA: ver

### 3. Perfiles de Usuario Extendidos

```sql
-- Cada usuario tiene un perfil con:
- Foto de perfil
- Biografía
- Ubicación (país, estado, ciudad, dirección)
- Redes sociales (JSON)
- Preferencias de notificación (JSON)
- Configuración de 2FA
- Idioma y zona horaria
- Estado de verificación (email, SMS)
```

### 4. Auditoría Completa

```sql
-- Tabla auditoria_usuarios registra:
- Qué operación se realizó (INSERT, UPDATE, DELETE)
- Quién la realizó (usuario admin)
- Cuándo se realizó (timestamp)
- Qué cambió (datos anteriores vs nuevos en JSON)
- Por qué cambió (razón del cambio)
- Desde dónde (IP address)
```

### 5. Gestión de Sesiones

```sql
-- sesiones_usuario mantiene:
- Token único de sesión
- IP del cliente
- User Agent (navegador/dispositivo)
- Fecha de inicio y expiración
- Última actividad
- Estado (activa/inactiva)
```

---

## Tablas Detalladas

### 1. `usuarios`

Tabla principal que almacena información de usuario.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | ID único |
| `uuid_usuario` | CHAR(36) | UNIQUE, DEFAULT UUID() | UUID para APIs |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email único |
| `contrasena_hash` | VARCHAR(255) | NOT NULL | Hash de contraseña (bcrypt) |
| `nombre_completo` | VARCHAR(255) | NOT NULL | Nombre completo |
| `apellido` | VARCHAR(255) | NULL | Apellido |
| `numero_documento` | VARCHAR(50) | UNIQUE, NULL | Cédula/DNI/Pasaporte |
| `tipo_documento` | ENUM | CC, CE, PAS, NIT | Tipo de documento |
| `fecha_nacimiento` | DATE | NULL | Cumpleaños |
| `genero` | ENUM | M, F, O, ND | Género |
| `telefono` | VARCHAR(20) | NULL | Teléfono fijo |
| `celular` | VARCHAR(20) | NULL | Teléfono móvil |
| `estado_id` | INT | FK estados_usuario | Estado actual |
| `estado_verificacion_email` | TINYINT(1) | DEFAULT 0 | ¿Email verificado? |
| `estado_verificacion_celular` | TINYINT(1) | DEFAULT 0 | ¿Celular verificado? |
| `fecha_ultima_conexion` | DATETIME | NULL | Último login |
| `fecha_ultima_modificacion` | DATETIME | ON UPDATE | Última edición |
| `fecha_creacion` | DATETIME | DEFAULT NOW() | Cuando se registró |
| `activo` | TINYINT(1) | DEFAULT 1 | ¿Está activo? |

**Índices:**
- `email` (búsquedas por email)
- `uuid_usuario` (API lookups)
- `numero_documento` (búsqueda por documento)
- `estado_id` (filtrar por estado)

---

### 2. `perfiles_usuario`

Relación 1:1 con usuarios, almacena datos extendidos y preferencias.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | FK a usuarios (UNIQUE) |
| `foto_perfil_url` | VARCHAR(500) | URL de avatar |
| `biografia` | TEXT | Descripción personal |
| `pais` | VARCHAR(100) | País de residencia |
| `departamento` | VARCHAR(100) | Región/Estado |
| `ciudad` | VARCHAR(100) | Ciudad |
| `direccion_principal` | TEXT | Dirección de envío |
| `redes_sociales` | JSON | `{"facebook":"...", "twitter":"..."}` |
| `preferencias_notificacion` | JSON | `{"email":true, "sms":false, ...}` |
| `verificacion_2fa_activa` | TINYINT(1) | ¿2FA habilitado? |
| `codigo_2fa_secreto` | VARCHAR(255) | Secret para TOTP |
| `telefono_2fa` | VARCHAR(20) | Teléfono para SMS |
| `notificaciones_email` | TINYINT(1) | Recibir emails |

---

### 3. `sesiones_usuario`

Maneja sesiones activas del usuario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | FK a usuarios |
| `token_sesion` | CHAR(64) | Token único (hash SHA-256) |
| `direccion_ip` | VARCHAR(45) | IPv4/IPv6 del cliente |
| `user_agent` | TEXT | Navegador/dispositivo |
| `fecha_inicio` | DATETIME | Cuando inició sesión |
| `fecha_expiracion` | DATETIME | Cuando caduca |
| `fecha_ultima_actividad` | DATETIME | Último movimiento |
| `activo` | TINYINT(1) | ¿Sesión vigente? |

---

### 4. `recuperacion_contrasena`

Tokens para reset de contraseña.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | Usuario solicitante |
| `token_recuperacion` | CHAR(64) | Token único |
| `email_destino` | VARCHAR(255) | Email donde se envió |
| `fecha_creacion` | DATETIME | Cuándo se generó |
| `fecha_expiracion` | DATETIME | Válido hasta (30 min típicamente) |
| `fecha_utilizacion` | DATETIME | Cuándo se usó |
| `ip_utilizacion` | VARCHAR(45) | IP desde donde se usó |
| `usado` | TINYINT(1) | ¿Ya se consumió? |

---

### 5. `historial_contrasenas`

Previene reutilización de contraseñas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | Usuario |
| `contrasena_hash_anterior` | VARCHAR(255) | Hash anterior |
| `contrasena_hash_nueva` | VARCHAR(255) | Nuevo hash |
| `fecha_cambio` | DATETIME | Cuándo cambió |
| `cambio_requerido` | TINYINT(1) | ¿Admin lo forzó? |
| `razon` | VARCHAR(255) | Motivo del cambio |

---

### 6. `historial_acceso`

Auditoría de intentos de login.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | Usuario (NULL si falló) |
| `email_intento` | VARCHAR(255) | Email usado en intento |
| `direccion_ip` | VARCHAR(45) | IP del cliente |
| `user_agent` | TEXT | Navegador |
| `exitoso` | TINYINT(1) | ¿Login exitoso? |
| `razon_fallo` | VARCHAR(255) | "Contraseña incorrecta", "Usuario no existe" |
| `fecha_intento` | DATETIME | Cuándo ocurrió |

---

### 7. `roles`

Define roles en el sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | VARCHAR(50) | UNIQUE - "CLIENTE", "ADMIN", etc. |
| `nombre` | VARCHAR(100) | "Administrador" |
| `descripcion` | TEXT | Descripción del rol |
| `nivel_acceso` | INT | 10=CLIENTE, 50=ADMIN (para jerarquía) |
| `activo` | TINYINT(1) | ¿Rol disponible? |

---

### 8. `permisos`

Define permisos granulares.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | VARCHAR(100) | UNIQUE - "USUARIOS_VER", "PEDIDOS_CREAR" |
| `nombre` | VARCHAR(150) | Descripción legible |
| `modulo` | VARCHAR(50) | "usuarios", "pedidos", "productos" |
| `accion` | VARCHAR(50) | "ver", "crear", "editar", "eliminar" |
| `activo` | TINYINT(1) | ¿Permiso habilitado? |

---

### 9. `usuarios_roles` (N:M)

Asignación de roles a usuarios (muchos-a-muchos).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | Usuario |
| `rol_id` | INT | Rol |
| `fecha_asignacion` | DATETIME | Cuándo se asignó |
| `fecha_vencimiento` | DATETIME | NULL = permanente, o fecha de expiración |
| `activo` | TINYINT(1) | ¿Rol activo actualmente? |

---

### 10. `roles_permisos` (N:M)

Asignación de permisos a roles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `rol_id` | INT | Rol |
| `permiso_id` | INT | Permiso |
| `fecha_asignacion` | DATETIME | Cuándo se asignó |

---

### 11. `pedidos`

Historial de compras (relación con usuarios).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | FK usuarios |
| `numero_pedido` | VARCHAR(50) | UNIQUE - "PED-2025-00001" |
| `estado` | ENUM | pendiente, confirmado, enviado, entregado, etc. |
| `subtotal` | DECIMAL(12,2) | Total sin impuestos |
| `impuestos` | DECIMAL(12,2) | Monto de impuestos |
| `descuento` | DECIMAL(12,2) | Descuento aplicado |
| `costo_envio` | DECIMAL(12,2) | Costo de envío |
| `total` | DECIMAL(12,2) | Total final |
| `metodo_pago` | VARCHAR(50) | "tarjeta", "transferencia", etc. |
| `referencia_pago` | VARCHAR(100) | ID de transacción |
| `direccion_entrega` | TEXT | Dirección de envío |
| `fecha_pedido` | DATETIME | Cuándo se hizo |
| `fecha_entrega_real` | DATETIME | Cuándo se entregó |

---

### 12. `detalles_pedido`

Items individuales dentro de un pedido.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `pedido_id` | BIGINT | FK pedidos |
| `producto_id` | INT | Producto vendido |
| `cantidad` | INT | Unidades |
| `precio_unitario` | DECIMAL(12,2) | Precio por unidad |
| `precio_total` | DECIMAL(12,2) | cantidad × precio |
| `descuento_linea` | DECIMAL(12,2) | Descuento en esta línea |
| `impuesto_linea` | DECIMAL(12,2) | Impuesto en esta línea |

---

### 13. `estados_usuario`

Estados disponibles (ACTIVO, BLOQUEADO, etc.).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | VARCHAR(50) | UNIQUE - "ACTIVO", "BLOQUEADO" |
| `nombre` | VARCHAR(100) | "Activo" |
| `descripcion` | TEXT | Descripción del estado |
| `activo` | TINYINT(1) | ¿Estado disponible? |

---

### 14. `auditoria_usuarios`

Log de auditoría de todas las operaciones sensibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuario_id` | BIGINT | Usuario afectado |
| `tabla_afectada` | VARCHAR(100) | "usuarios", "pedidos", etc. |
| `id_registro` | BIGINT | ID del registro modificado |
| `operacion` | ENUM | INSERT, UPDATE, DELETE, RESTORE |
| `datos_anteriores` | JSON | Valores antes del cambio |
| `datos_nuevos` | JSON | Valores después del cambio |
| `usuario_admin_id` | BIGINT | Admin que realizó el cambio |
| `razon_cambio` | VARCHAR(255) | Por qué se cambió |
| `fecha_operacion` | DATETIME | Cuándo sucedió |

---

## Vistas (Views)

### 1. `v_usuarios_activos`

Lista de usuarios activos con sus roles.

```sql
SELECT * FROM v_usuarios_activos;
```

**Columnas:**
- id, uuid_usuario, email, nombre_completo
- roles (concat de todos los roles del usuario)
- fecha_creacion, fecha_ultima_conexion, activo

**Uso:** Reportes de usuarios activos, dashboards de admin

---

### 2. `v_pedidos_recientes`

Pedidos de los últimos 30 días.

```sql
SELECT * FROM v_pedidos_recientes;
```

**Columnas:**
- numero_pedido, email del usuario, nombre_completo
- estado, total, fecha_pedido
- cantidad_items

**Uso:** Dashboard de ventas recientes

---

### 3. `v_permisos_usuario`

Todos los permisos de un usuario (desglosado por rol).

```sql
SELECT * FROM v_permisos_usuario WHERE usuario_id = 123;
```

**Columnas:**
- usuario_id, email, rol_id, rol_codigo
- permiso_id, permiso_codigo, modulo, accion

**Uso:** Verificación de permisos, auditoría de acceso

---

### 4. `v_resumen_usuario`

Resumen estadístico de cada usuario.

```sql
SELECT * FROM v_resumen_usuario WHERE id = 123;
```

**Columnas:**
- id, email, nombre_completo
- sesiones_activas, total_pedidos, gasto_total
- dias_sin_actividad

**Uso:** Analytics, recomendaciones, alertas de inactividad

---

## Procedimientos Almacenados

### 1. `sp_crear_usuario_nuevo`

Crea un nuevo usuario con validaciones.

```php
// Desde PHP (PDO):
$stmt = $pdo->prepare("CALL sp_crear_usuario_nuevo(?, ?, ?, ?, @id, @msg)");
$stmt->execute([
    'email@example.com',
    'Juan Pérez',
    password_hash('contraseña', PASSWORD_BCRYPT),
    'CLIENTE'
]);

$resultado = $pdo->query("SELECT @id as usuario_id, @msg as mensaje")->fetch();
```

**Parámetros:**
- `p_email` (VARCHAR): Email único
- `p_nombre_completo` (VARCHAR): Nombre completo
- `p_contrasena_hash` (VARCHAR): Hash de contraseña
- `p_rol_codigo` (VARCHAR): Código del rol ("CLIENTE", "ADMIN", etc.)

**Output:**
- `p_usuario_id` (BIGINT): ID del usuario creado (NULL si hay error)
- `p_mensaje` (VARCHAR): Mensaje de éxito/error

**Validaciones:**
- Email debe ser único
- Nombre no puede estar vacío
- Rol debe existir

---

### 2. `sp_cambiar_contrasena`

Cambia contraseña registrando el historial.

```php
$stmt = $pdo->prepare("CALL sp_cambiar_contrasena(?, ?, ?, @exito, @msg)");
$stmt->execute([
    $usuario_id,
    password_hash('vieja_contrasena', PASSWORD_BCRYPT),
    password_hash('nueva_contrasena', PASSWORD_BCRYPT)
]);

$resultado = $pdo->query("SELECT @exito as exito, @msg as mensaje")->fetch();
```

**Parámetros:**
- `p_usuario_id` (BIGINT): ID del usuario
- `p_contrasena_hash_anterior` (VARCHAR): Hash anterior
- `p_contrasena_hash_nueva` (VARCHAR): Nuevo hash

**Output:**
- `p_exito` (TINYINT): 1 = éxito, 0 = error
- `p_mensaje` (VARCHAR): Descripción

---

### 3. `sp_registrar_intento_acceso`

Audita cada intento de login (exitoso o fallido).

```php
$stmt = $pdo->prepare("CALL sp_registrar_intento_acceso(?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $usuario_id ?? null,      // NULL si el email no existe
    'user@example.com',       // Email que intentó
    $_SERVER['REMOTE_ADDR'],  // IP del cliente
    $_SERVER['HTTP_USER_AGENT'],
    $fue_exitoso ? 1 : 0,
    $fue_exitoso ? null : 'Contraseña incorrecta'
]);
```

**Uso típico:**
```php
// En tu controlador de login
try {
    $usuario = verificarCredenciales($email, $password);
    sp_registrar_intento_acceso($usuario->id, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], 1, null);
} catch (Exception $e) {
    sp_registrar_intento_acceso(null, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], 0, 'Credenciales inválidas');
}
```

---

### 4. `sp_bloquear_usuario`

Bloquea un usuario y registra el motivo.

```php
$stmt = $pdo->prepare("CALL sp_bloquear_usuario(?, ?, ?)");
$stmt->execute([
    $usuario_a_bloquear,
    'Violación de términos de servicio',
    $admin_user_id  // Quién bloqueó
]);
```

---

### 5. `sp_obtener_usuario`

Obtiene datos completos de un usuario (con roles y permisos).

```php
$stmt = $pdo->prepare("CALL sp_obtener_usuario(?)");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
```

**Retorna:**
```
Array (
    [id] => 1
    [email] => juan@example.com
    [nombre_completo] => Juan Pérez
    [roles] => CLIENTE, VENDEDOR
    [permisos] => USUARIOS_VER, PEDIDOS_VER_PROPIOS, PRODUCTOS_VER
    ...
)
```

---

## Funciones

### 1. `fn_obtener_permisos_usuario`

Retorna lista de códigos de permiso de un usuario.

```sql
SELECT fn_obtener_permisos_usuario(123);
-- Retorna: "USUARIOS_VER,PEDIDOS_VER_PROPIOS,PRODUCTOS_VER"
```

---

### 2. `fn_usuario_tiene_permiso`

Verifica si un usuario tiene un permiso específico.

```php
// En PHP
$stmt = $pdo->prepare("SELECT fn_usuario_tiene_permiso(?, ?) as tiene_permiso");
$stmt->execute([$usuario_id, 'USUARIOS_EDITAR']);
$tiene = $stmt->fetch()['tiene_permiso'];

if ($tiene) {
    // Permite editar usuarios
} else {
    die('No tienes permiso');
}
```

---

### 3. `fn_rol_principal_usuario`

Obtiene el rol principal (de mayor nivel) de un usuario.

```sql
SELECT fn_rol_principal_usuario(123);
-- Retorna: "ADMINISTRADOR"
```

---

## Triggers

### 1. `trg_audit_usuarios_update`

Audita cambios en la tabla usuarios.

```sql
-- Cuando se actualiza un usuario, se registra automáticamente en auditoria_usuarios
-- Ejemplo: Si cambias el email o estado de un usuario, queda registrado
```

### 2. `trg_crear_perfil_usuario`

Crea automáticamente un perfil cuando se registra un usuario.

```sql
-- INSERT INTO usuarios → automáticamente INSERT en perfiles_usuario
```

### 3. `trg_audit_cambio_contrasena`

Audita cambios de contraseña.

---

## Ejemplos de Uso

### Caso 1: Registro de Nuevo Usuario

```php
<?php
require 'config/database.php';

$email = 'nuevo@example.com';
$nombre = 'Carlos López';
$password = 'miContraseña123';

// Hash la contraseña
$hash = password_hash($password, PASSWORD_BCRYPT);

// Crear usuario
$stmt = $pdo->prepare("CALL sp_crear_usuario_nuevo(?, ?, ?, ?, @id, @msg)");
$stmt->execute([$email, $nombre, $hash, 'CLIENTE']);

$resultado = $pdo->query("SELECT @id as id, @msg as msg")->fetch();

if ($resultado['id']) {
    echo "Usuario creado con ID: " . $resultado['id'];
    // Enviar email de verificación
} else {
    echo "Error: " . $resultado['msg'];
}
?>
```

### Caso 2: Login con Auditoría

```php
<?php
// Verificar credenciales
$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT id, nombre_completo, contrasena_hash FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario && password_verify($password, $usuario['contrasena_hash'])) {
    // Login exitoso
    $token = bin2hex(random_bytes(32)); // Token de sesión
    $expires = date('Y-m-d H:i:s', strtotime('+1 week'));
    
    $stmt = $pdo->prepare("INSERT INTO sesiones_usuario (usuario_id, token_sesion, fecha_expiracion, fecha_inicio, activo) VALUES (?, ?, ?, NOW(), 1)");
    $stmt->execute([$usuario['id'], $token, $expires]);
    
    // Registrar intento exitoso
    $pdo->prepare("CALL sp_registrar_intento_acceso(?, ?, ?, ?, 1, NULL)")
        ->execute([$usuario['id'], $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['token'] = $token;
} else {
    // Login fallido
    $pdo->prepare("CALL sp_registrar_intento_acceso(NULL, ?, ?, ?, 0, ?)")
        ->execute([$email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], 'Credenciales inválidas']);
    
    echo "Email o contraseña incorrectos";
}
?>
```

### Caso 3: Verificar Permisos

```php
<?php
function usuario_tiene_permiso($usuario_id, $permiso_codigo) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT fn_usuario_tiene_permiso(?, ?) as tiene");
    $stmt->execute([$usuario_id, $permiso_codigo]);
    return (bool)$stmt->fetch()['tiene'];
}

// Usar en middleware o controladores
if (usuario_tiene_permiso($user_id, 'USUARIOS_EDITAR')) {
    // Permitir edición
} else {
    header("HTTP/1.0 403 Forbidden");
    die("No tienes permiso para editar usuarios");
}
?>
```

### Caso 4: Obtener Datos Completos de Usuario

```php
<?php
$stmt = $pdo->prepare("CALL sp_obtener_usuario(?)");
$stmt->execute([123]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// $usuario contiene:
// - id, email, nombre_completo
// - foto_perfil_url, ciudad, pais
// - roles (concat): "CLIENTE, VENDEDOR"
// - permisos (concat): "USUARIOS_VER, PEDIDOS_VER_PROPIOS"
?>
```

### Caso 5: Ver Historial de Acceso

```php
<?php
$stmt = $pdo->prepare("SELECT * FROM historial_acceso WHERE usuario_id = ? ORDER BY fecha_intento DESC LIMIT 20");
$stmt->execute([$usuario_id]);
$intentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($intentos as $intento) {
    echo ($intento['exitoso'] ? '✓' : '✗') . ' ' . $intento['fecha_intento'] . ' - ' . $intento['direccion_ip'];
}
?>
```

### Caso 6: Ver Pedidos del Usuario

```php
<?php
$stmt = $pdo->prepare("SELECT * FROM v_pedidos_recientes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
```

---

## Recomendaciones de Seguridad

### 1. **Hash de Contraseñas**

```php
// ✓ CORRECTO - Usar bcrypt
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ✗ INCORRECTO
$hash = md5($password);  // No usar MD5
$hash = sha1($password); // No usar SHA1
```

### 2. **Tokens de Sesión**

```php
// ✓ CORRECTO - 64 caracteres aleatorios
$token = bin2hex(random_bytes(32)); // 64 chars hex

// ✗ INCORRECTO
$token = $usuario_id . time(); // Predecible
```

### 3. **Tokens de Recuperación de Contraseña**

```php
// ✓ CORRECTO
$token = bin2hex(random_bytes(32)); // Válido 30 minutos
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// ✗ INCORRECTO
$expires = date('Y-m-d H:i:s', strtotime('+24 hours')); // Muy largo
```

### 4. **Prepared Statements**

```php
// ✓ CORRECTO
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
$stmt->execute([$email]);

// ✗ INCORRECTO
$result = $pdo->query("SELECT * FROM usuarios WHERE email = '$email'"); // SQL injection
```

### 5. **Rate Limiting en Login**

```php
// ✗ El sistema actual NO tiene rate limiting
// TODO: Implementar

function verificar_rate_limit($email, $ip) {
    // Contar intentos fallidos en últimos 15 minutos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as intentos 
        FROM historial_acceso 
        WHERE email_intento = ? 
        AND direccion_ip = ? 
        AND exitoso = 0 
        AND fecha_intento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$email, $ip]);
    $intentos = $stmt->fetch()['intentos'];
    
    if ($intentos > 5) {
        throw new Exception("Demasiados intentos. Intenta en 15 minutos");
    }
}
```

### 6. **SSL en Conexión a BD**

En `config/database.php`:

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=babylovec',
    'usuario',
    'contraseña',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_SSL_CA => '/path/to/ca.pem',
        // Opcional: verificar certificado
    ]
);
```

### 7. **Configuración de 2FA (Preparado)**

Aunque la base de datos está lista, necesitas:

```php
// 1. Instalar librería
composer require sonata-project/google-authenticator

// 2. Generar secret
$ga = new GoogleAuthenticator();
$secret = $ga->generateSecret();
// Guardar en perfiles_usuario.codigo_2fa_secreto

// 3. Verificar token
$isValid = $ga->checkCode($secret, $user_input);
```

---

## FAQ

### P: ¿Cómo cambio de contraseña un usuario?

**R:** Usa el procedimiento `sp_cambiar_contrasena`:

```php
$stmt = $pdo->prepare("CALL sp_cambiar_contrasena(?, ?, ?, @exito, @msg)");
$stmt->execute([$user_id, $old_hash, $new_hash]);
$resultado = $pdo->query("SELECT @exito, @msg")->fetch();
```

---

### P: ¿Cómo manejo recuperación de contraseña?

**R:**

1. Usuario solicita reset
2. Generar token: `$token = bin2hex(random_bytes(32))`
3. Guardar en `recuperacion_contrasena` con expiración 30 min
4. Enviar link: `https://tusite.com/reset.php?token=$token`
5. Usuario hace click, valida token
6. Si token es válido (no usado, no expirado):
   - Genera nueva contraseña
   - Ejecuta `sp_cambiar_contrasena`
   - Marca token como usado

---

### P: ¿Cómo asigno roles a usuarios?

**R:**

```sql
INSERT INTO usuarios_roles (usuario_id, rol_id, activo)
SELECT 123, id, 1 FROM roles WHERE codigo = 'VENDEDOR';
```

O desde PHP:

```php
$stmt = $pdo->prepare("INSERT INTO usuarios_roles (usuario_id, rol_id, activo) SELECT ?, id, 1 FROM roles WHERE codigo = ?");
$stmt->execute([$user_id, 'VENDEDOR']);
```

---

### P: ¿Cómo verifico si un usuario tiene un permiso?

**R:**

```php
$tiene = $pdo->prepare("SELECT fn_usuario_tiene_permiso(?, ?)")
    ->execute([$user_id, 'USUARIOS_EDITAR'])
    ->fetch()[0] == 1;
```

---

### P: ¿Puedo tener múltiples roles?

**R:** Sí, la tabla `usuarios_roles` es muchos-a-muchos. Un usuario puede tener varios roles simultáneamente.

---

### P: ¿Qué pasa si expira una sesión?

**R:** Queda registrada en `sesiones_usuario` con `activo = 0`. Puedes limpiar sesiones expiradas:

```sql
DELETE FROM sesiones_usuario WHERE fecha_expiracion < NOW();
```

---

### P: ¿Dónde se almacenan los audits?

**R:** En la tabla `auditoria_usuarios`. Registra:
- Cambios de datos de usuario
- Cambios de contraseña
- Bloqueos
- Operaciones administrativas

---

### P: ¿Cómo deshabilito temporalmente un usuario?

**R:**

```sql
UPDATE usuarios SET estado_id = 2 WHERE id = 123;  -- INACTIVO
-- O
UPDATE usuarios SET estado_id = 5 WHERE id = 123;  -- SUSPENDIDO
```

---

### P: ¿Cómo migro de otro sistema de usuarios?

**R:**

1. Exporta usuarios desde sistema viejo
2. Hashen todas las contraseñas con bcrypt
3. Mapea roles equivalentes
4. Ejecuta INSERT en lotes

```php
foreach ($usuarios_migrados as $u) {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios 
        (email, nombre_completo, contrasena_hash, estado_id, activo, fecha_creacion)
        VALUES (?, ?, ?, 1, ?, ?)
    ");
    $stmt->execute([
        $u['email'],
        $u['nombre'],
        password_hash($u['password_temporal'], PASSWORD_BCRYPT),
        1,  // activo
        $u['fecha_registro']
    ]);
}
```

---

## Contacto y Soporte

Para dudas o mejoras, contacta al equipo de desarrollo.

---

**Última actualización:** Noviembre 16, 2025  
**Versión:** 1.0  
**Compatible con:** MySQL 5.7+  
**Licencia:** Proyecto Interno
