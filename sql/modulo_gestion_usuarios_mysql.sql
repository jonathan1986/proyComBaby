-- =============================================================================
-- MÓDULO DE GESTIÓN DE USUARIOS - SISTEMA E-COMMERCE
-- Compatible con MySQL 5.7
-- =============================================================================
-- Autor: Sistema de Gestión de Usuarios
-- Fecha: 2025-11-16
-- Base de datos: babylovec (existente)
-- Descripción: Incluye autenticación, perfiles, roles, permisos, historial de 
--              acceso, recuperación de contraseñas y auditoría completa.
-- =============================================================================

-- Usar la base de datos existente
USE babylovec;

-- =============================================================================
-- 1. TABLAS AUXILIARES - Estados
-- =============================================================================

-- Estados de usuario (activo, inactivo, bloqueado, etc.)
CREATE TABLE IF NOT EXISTS `estados_usuario` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_codigo` (`codigo`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estados disponibles para usuarios del sistema';

-- =============================================================================
-- 2. TABLAS PRINCIPALES - Usuarios y Autenticación
-- =============================================================================

-- Tabla principal de usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid_usuario` CHAR(36) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `contrasena_hash` VARCHAR(255) NOT NULL,
  `nombre_completo` VARCHAR(255) NOT NULL,
  `apellido` VARCHAR(255),
  `numero_documento` VARCHAR(50) UNIQUE,
  `tipo_documento` ENUM('CC','CE','PAS','NIT') DEFAULT NULL,
  `fecha_nacimiento` DATE,
  `genero` ENUM('M','F','O','ND') DEFAULT 'ND',
  `telefono` VARCHAR(20),
  `celular` VARCHAR(20),
  `estado_id` INT NOT NULL DEFAULT 1,
  `estado_verificacion_email` TINYINT(1) NOT NULL DEFAULT 0,
  `estado_verificacion_celular` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_ultima_conexion` DATETIME,
  `fecha_ultima_modificacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `fk_usuarios_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_usuario` (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_uuid` (`uuid_usuario`),
  KEY `idx_numero_doc` (`numero_documento`),
  KEY `idx_estado_id` (`estado_id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios registrados en el sistema e-commerce';

-- Sesiones de usuario (para rastrear sesiones activas)
CREATE TABLE IF NOT EXISTS `sesiones_usuario` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL,
  `token_sesion` CHAR(64) NOT NULL UNIQUE,
  `direccion_ip` VARCHAR(45),
  `user_agent` TEXT,
  `fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` DATETIME NOT NULL,
  `fecha_ultima_actividad` DATETIME,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_token_sesion` (`token_sesion`),
  KEY `idx_activo` (`activo`),
  KEY `idx_fecha_expiracion` (`fecha_expiracion`),
  KEY `idx_fecha_ultima_actividad` (`fecha_ultima_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones activas de usuarios';

-- Recuperación de contraseña
CREATE TABLE IF NOT EXISTS `recuperacion_contrasena` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL,
  `token_recuperacion` CHAR(64) NOT NULL UNIQUE,
  `email_destino` VARCHAR(255) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` DATETIME NOT NULL,
  `fecha_utilizacion` DATETIME,
  `ip_utilizacion` VARCHAR(45),
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_recuperacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_token` (`token_recuperacion`),
  KEY `idx_usado` (`usado`),
  KEY `idx_fecha_expiracion` (`fecha_expiracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens para recuperación de contraseña';

-- Historial de contraseñas (prevenir reutilización)
CREATE TABLE IF NOT EXISTS `historial_contrasenas` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL,
  `contrasena_hash_anterior` VARCHAR(255) NOT NULL,
  `contrasena_hash_nueva` VARCHAR(255) NOT NULL,
  `fecha_cambio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cambio_requerido` TINYINT(1) NOT NULL DEFAULT 0,
  `razon` VARCHAR(255),
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_fecha_cambio` (`fecha_cambio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios de contraseña';

-- Historial de acceso (auditoría de intentos de login)
CREATE TABLE IF NOT EXISTS `historial_acceso` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT,
  `email_intento` VARCHAR(255),
  `direccion_ip` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `exitoso` TINYINT(1) NOT NULL DEFAULT 0,
  `razon_fallo` VARCHAR(255),
  `fecha_intento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_historial_acceso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_exitoso` (`exitoso`),
  KEY `idx_fecha_intento` (`fecha_intento`),
  KEY `idx_ip` (`direccion_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría de intentos de acceso';

-- =============================================================================
-- 3. TABLAS DE ROLES Y PERMISOS
-- =============================================================================

-- Roles disponibles en el sistema
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `nivel_acceso` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_codigo` (`codigo`),
  KEY `idx_nivel_acceso` (`nivel_acceso`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles del sistema (Cliente, Admin, Vendedor, etc.)';

-- Permisos del sistema
CREATE TABLE IF NOT EXISTS `permisos` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(100) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT,
  `modulo` VARCHAR(50),
  `accion` VARCHAR(50),
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_codigo` (`codigo`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos granulares del sistema';

-- Relación muchos-a-muchos: Roles y Permisos
CREATE TABLE IF NOT EXISTS `roles_permisos` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT NOT NULL,
  `permiso_id` INT NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_rol_permiso` (`rol_id`, `permiso_id`),
  KEY `idx_rol_id` (`rol_id`),
  KEY `idx_permiso_id` (`permiso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de permisos a roles';

-- Relación muchos-a-muchos: Usuarios y Roles
CREATE TABLE IF NOT EXISTS `usuarios_roles` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL,
  `rol_id` INT NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` DATETIME,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_usuario_rol` (`usuario_id`, `rol_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_rol_id` (`rol_id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de roles a usuarios';

-- =============================================================================
-- 4. TABLA DE PERFILES DE USUARIO (Datos adicionales y preferencias)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `perfiles_usuario` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL UNIQUE,
  `foto_perfil_url` VARCHAR(500),
  `biografia` TEXT,
  `pais` VARCHAR(100),
  `departamento` VARCHAR(100),
  `ciudad` VARCHAR(100),
  `direccion_principal` TEXT,
  `direccion_alternativa` TEXT,
  `codigo_postal` VARCHAR(20),
  `idioma_preferido` VARCHAR(10) DEFAULT 'es',
  `zona_horaria` VARCHAR(50) DEFAULT 'America/Bogota',
  `redes_sociales` JSON,
  `preferencias_notificacion` JSON,
  `verificacion_2fa_activa` TINYINT(1) NOT NULL DEFAULT 0,
  `codigo_2fa_secreto` VARCHAR(255),
  `telefono_2fa` VARCHAR(20),
  `notificaciones_email` TINYINT(1) NOT NULL DEFAULT 1,
  `notificaciones_sms` TINYINT(1) NOT NULL DEFAULT 1,
  `notificaciones_push` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_perfil_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_pais` (`pais`),
  KEY `idx_ciudad` (`ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Perfiles extendidos y preferencias de usuarios';

-- =============================================================================
-- 5. TABLAS DE PEDIDOS (Relación Usuarios-Pedidos)
-- =============================================================================

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT NOT NULL,
  `numero_pedido` VARCHAR(50) NOT NULL UNIQUE,
  `estado` ENUM('pendiente','confirmado','enviado','entregado','cancelado','devuelto') NOT NULL DEFAULT 'pendiente',
  `subtotal` DECIMAL(12,2) NOT NULL,
  `impuestos` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `costo_envio` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL,
  `metodo_pago` VARCHAR(50),
  `referencia_pago` VARCHAR(100),
  `direccion_entrega` TEXT NOT NULL,
  `telefono_entrega` VARCHAR(20),
  `notas` TEXT,
  `fecha_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_confirmacion` DATETIME,
  `fecha_envio` DATETIME,
  `fecha_entrega_estimada` DATE,
  `fecha_entrega_real` DATETIME,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_numero_pedido` (`numero_pedido`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_pedido` (`fecha_pedido`),
  KEY `idx_fecha_entrega_estimada` (`fecha_entrega_estimada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos de los usuarios';

-- Detalles de pedidos
CREATE TABLE IF NOT EXISTS `detalles_pedido` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` BIGINT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `precio_total` DECIMAL(12,2) NOT NULL,
  `descuento_linea` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `impuesto_linea` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas_linea` TEXT,
  CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items/líneas de cada pedido';

-- =============================================================================
-- 6. TABLA DE AUDITORÍA
-- =============================================================================

CREATE TABLE IF NOT EXISTS `auditoria_usuarios` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT,
  `tabla_afectada` VARCHAR(100) NOT NULL,
  `id_registro` BIGINT,
  `operacion` ENUM('INSERT','UPDATE','DELETE','RESTORE') NOT NULL,
  `datos_anteriores` JSON,
  `datos_nuevos` JSON,
  `cambios_json` JSON,
  `usuario_admin_id` BIGINT,
  `razon_cambio` VARCHAR(255),
  `direccion_ip` VARCHAR(45),
  `fecha_operacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_tabla` (`tabla_afectada`),
  KEY `idx_operacion` (`operacion`),
  KEY `idx_fecha_operacion` (`fecha_operacion`),
  KEY `idx_admin_id` (`usuario_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría de cambios en usuarios y operaciones sensibles';

-- =============================================================================
-- 7. DATOS INICIALES
-- =============================================================================

-- Estados iniciales
INSERT IGNORE INTO `estados_usuario` (`id`, `codigo`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'ACTIVO', 'Activo', 'Usuario activo y funcional', 1),
(2, 'INACTIVO', 'Inactivo', 'Usuario inactivo (puede reactivarse)', 1),
(3, 'BLOQUEADO', 'Bloqueado', 'Usuario bloqueado por violación de términos', 1),
(4, 'VERIFICACION_PENDIENTE', 'Verificación Pendiente', 'Usuario registrado pero sin verificar email', 1),
(5, 'SUSPENDIDO', 'Suspendido', 'Usuario suspendido temporalmente', 1);

-- Roles iniciales
INSERT IGNORE INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`, `nivel_acceso`, `activo`) VALUES
(1, 'CLIENTE', 'Cliente', 'Usuario cliente estándar del e-commerce', 10, 1),
(2, 'VENDEDOR', 'Vendedor', 'Vendedor con acceso a gestión de productos y pedidos', 20, 1),
(3, 'ADMINISTRADOR', 'Administrador', 'Administrador del sistema con acceso completo', 50, 1),
(4, 'MODERADOR', 'Moderador', 'Moderador con permisos limitados de administración', 30, 1),
(5, 'GESTOR_CONTENIDOS', 'Gestor de Contenidos', 'Responsable de gestión de catálogo y contenidos', 25, 1);

-- Permisos iniciales
INSERT IGNORE INTO `permisos` (`id`, `codigo`, `nombre`, `descripcion`, `modulo`, `accion`, `activo`) VALUES
-- Permisos de Usuarios
(1, 'USUARIOS_VER', 'Ver Usuarios', 'Permiso para ver listado de usuarios', 'usuarios', 'ver', 1),
(2, 'USUARIOS_CREAR', 'Crear Usuarios', 'Permiso para crear nuevos usuarios', 'usuarios', 'crear', 1),
(3, 'USUARIOS_EDITAR', 'Editar Usuarios', 'Permiso para editar datos de usuarios', 'usuarios', 'editar', 1),
(4, 'USUARIOS_ELIMINAR', 'Eliminar Usuarios', 'Permiso para eliminar usuarios', 'usuarios', 'eliminar', 1),
(5, 'USUARIOS_BLOQUEAR', 'Bloquear Usuarios', 'Permiso para bloquear usuarios', 'usuarios', 'bloquear', 1),
-- Permisos de Pedidos
(6, 'PEDIDOS_VER_PROPIOS', 'Ver Propios Pedidos', 'Permiso para ver sus propios pedidos', 'pedidos', 'ver_propios', 1),
(7, 'PEDIDOS_VER_TODOS', 'Ver Todos Pedidos', 'Permiso para ver pedidos de cualquier usuario', 'pedidos', 'ver_todos', 1),
(8, 'PEDIDOS_EDITAR', 'Editar Pedidos', 'Permiso para editar pedidos', 'pedidos', 'editar', 1),
(9, 'PEDIDOS_CANCELAR', 'Cancelar Pedidos', 'Permiso para cancelar pedidos', 'pedidos', 'cancelar', 1),
-- Permisos de Productos
(10, 'PRODUCTOS_VER', 'Ver Productos', 'Permiso para ver catálogo de productos', 'productos', 'ver', 1),
(11, 'PRODUCTOS_CREAR', 'Crear Productos', 'Permiso para crear productos', 'productos', 'crear', 1),
(12, 'PRODUCTOS_EDITAR', 'Editar Productos', 'Permiso para editar productos', 'productos', 'editar', 1),
(13, 'PRODUCTOS_ELIMINAR', 'Eliminar Productos', 'Permiso para eliminar productos', 'productos', 'eliminar', 1),
-- Permisos de Roles y Permisos
(14, 'ROLES_VER', 'Ver Roles', 'Permiso para ver roles del sistema', 'roles', 'ver', 1),
(15, 'ROLES_EDITAR', 'Editar Roles', 'Permiso para editar roles', 'roles', 'editar', 1),
(16, 'ROLES_ASIGNAR', 'Asignar Roles', 'Permiso para asignar roles a usuarios', 'roles', 'asignar', 1),
-- Permisos de Reportes
(17, 'REPORTES_VER', 'Ver Reportes', 'Permiso para acceder a reportes', 'reportes', 'ver', 1),
(18, 'REPORTES_EXPORTAR', 'Exportar Reportes', 'Permiso para exportar reportes', 'reportes', 'exportar', 1),
-- Permisos de Configuración
(19, 'CONFIG_VER', 'Ver Configuración', 'Permiso para acceder a configuración del sistema', 'config', 'ver', 1),
(20, 'CONFIG_EDITAR', 'Editar Configuración', 'Permiso para editar configuración del sistema', 'config', 'editar', 1),
(21, 'AUDITORIA_VER', 'Ver Auditoría', 'Permiso para ver logs de auditoría', 'auditoria', 'ver', 1);

-- Asignación de permisos a roles
-- CLIENTE: solo puede ver sus propios pedidos y productos
INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`) VALUES
(1, 6),  -- CLIENTE: PEDIDOS_VER_PROPIOS
(1, 10), -- CLIENTE: PRODUCTOS_VER
-- VENDEDOR: puede gestionar productos y ver pedidos
(2, 10), -- VENDEDOR: PRODUCTOS_VER
(2, 11), -- VENDEDOR: PRODUCTOS_CREAR
(2, 12), -- VENDEDOR: PRODUCTOS_EDITAR
(2, 13), -- VENDEDOR: PRODUCTOS_ELIMINAR
(2, 6),  -- VENDEDOR: PEDIDOS_VER_PROPIOS
(2, 7),  -- VENDEDOR: PEDIDOS_VER_TODOS
(2, 8),  -- VENDEDOR: PEDIDOS_EDITAR
-- MODERADOR: acceso expandido
(4, 1),  -- MODERADOR: USUARIOS_VER
(4, 5),  -- MODERADOR: USUARIOS_BLOQUEAR
(4, 7),  -- MODERADOR: PEDIDOS_VER_TODOS
(4, 9),  -- MODERADOR: PEDIDOS_CANCELAR
(4, 17), -- MODERADOR: REPORTES_VER
-- GESTOR_CONTENIDOS: gestión de catálogo
(5, 10), -- GESTOR_CONTENIDOS: PRODUCTOS_VER
(5, 11), -- GESTOR_CONTENIDOS: PRODUCTOS_CREAR
(5, 12), -- GESTOR_CONTENIDOS: PRODUCTOS_EDITAR
(5, 14), -- GESTOR_CONTENIDOS: ROLES_VER
-- ADMINISTRADOR: todos los permisos
(3, 1),  -- ADMIN: USUARIOS_VER
(3, 2),  -- ADMIN: USUARIOS_CREAR
(3, 3),  -- ADMIN: USUARIOS_EDITAR
(3, 4),  -- ADMIN: USUARIOS_ELIMINAR
(3, 5),  -- ADMIN: USUARIOS_BLOQUEAR
(3, 6),  -- ADMIN: PEDIDOS_VER_PROPIOS
(3, 7),  -- ADMIN: PEDIDOS_VER_TODOS
(3, 8),  -- ADMIN: PEDIDOS_EDITAR
(3, 9),  -- ADMIN: PEDIDOS_CANCELAR
(3, 10), -- ADMIN: PRODUCTOS_VER
(3, 11), -- ADMIN: PRODUCTOS_CREAR
(3, 12), -- ADMIN: PRODUCTOS_EDITAR
(3, 13), -- ADMIN: PRODUCTOS_ELIMINAR
(3, 14), -- ADMIN: ROLES_VER
(3, 15), -- ADMIN: ROLES_EDITAR
(3, 16), -- ADMIN: ROLES_ASIGNAR
(3, 17), -- ADMIN: REPORTES_VER
(3, 18), -- ADMIN: REPORTES_EXPORTAR
(3, 19), -- ADMIN: CONFIG_VER
(3, 20), -- ADMIN: CONFIG_EDITAR
(3, 21); -- ADMIN: AUDITORIA_VER

-- =============================================================================
-- 8. VISTAS (Views) ÚTILES
-- =============================================================================

-- Vista: Usuarios activos con sus roles
CREATE OR REPLACE VIEW `v_usuarios_activos` AS
SELECT DISTINCT
  u.id,
  u.uuid_usuario,
  u.email,
  u.nombre_completo,
  u.apellido,
  u.estado_id,
  COALESCE(GROUP_CONCAT(r.nombre SEPARATOR ', '), 'Sin Rol') AS roles,
  u.fecha_creacion,
  u.fecha_ultima_conexion,
  u.activo
FROM usuarios u
LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id AND ur.activo = 1
LEFT JOIN roles r ON ur.rol_id = r.id
WHERE u.activo = 1 AND u.estado_id = 1
GROUP BY u.id
ORDER BY u.fecha_creacion DESC;

-- Vista: Pedidos recientes con detalles de usuario
CREATE OR REPLACE VIEW `v_pedidos_recientes` AS
SELECT
  p.id,
  p.numero_pedido,
  u.email,
  u.nombre_completo,
  p.estado,
  p.total,
  p.fecha_pedido,
  p.fecha_entrega_estimada,
  COUNT(DISTINCT dp.id) AS cantidad_items
FROM pedidos p
INNER JOIN usuarios u ON p.usuario_id = u.id
LEFT JOIN detalles_pedido dp ON p.id = dp.pedido_id
WHERE p.fecha_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.id
ORDER BY p.fecha_pedido DESC;

-- Vista: Permisos por usuario (consolidado)
CREATE OR REPLACE VIEW `v_permisos_usuario` AS
SELECT
  u.id AS usuario_id,
  u.email,
  u.nombre_completo,
  r.id AS rol_id,
  r.codigo AS rol_codigo,
  r.nombre AS rol_nombre,
  p.id AS permiso_id,
  p.codigo AS permiso_codigo,
  p.nombre AS permiso_nombre,
  p.modulo,
  p.accion
FROM usuarios u
INNER JOIN usuarios_roles ur ON u.id = ur.usuario_id AND ur.activo = 1
INNER JOIN roles r ON ur.rol_id = r.id AND r.activo = 1
INNER JOIN roles_permisos rp ON r.id = rp.rol_id
INNER JOIN permisos p ON rp.permiso_id = p.id AND p.activo = 1
ORDER BY u.email, r.nombre, p.nombre;

-- Vista: Resumen de actividad de usuario
CREATE OR REPLACE VIEW `v_resumen_usuario` AS
SELECT
  u.id,
  u.email,
  u.nombre_completo,
  u.fecha_creacion,
  u.fecha_ultima_conexion,
  COUNT(DISTINCT s.id) AS sesiones_activas,
  COUNT(DISTINCT p.id) AS total_pedidos,
  COALESCE(SUM(p.total), 0) AS gasto_total,
  DATEDIFF(NOW(), u.fecha_ultima_conexion) AS dias_sin_actividad
FROM usuarios u
LEFT JOIN sesiones_usuario s ON u.id = s.usuario_id AND s.activo = 1
LEFT JOIN pedidos p ON u.id = p.usuario_id
GROUP BY u.id
ORDER BY u.fecha_creacion DESC;

-- =============================================================================
-- 9. FUNCIONES Y PROCEDIMIENTOS ALMACENADOS
-- =============================================================================

DELIMITER //

-- Función: Obtener permisos de un usuario
DROP FUNCTION IF EXISTS `fn_obtener_permisos_usuario` //
CREATE FUNCTION `fn_obtener_permisos_usuario`(p_usuario_id BIGINT)
RETURNS TEXT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE resultado TEXT;
  SELECT GROUP_CONCAT(DISTINCT p.codigo SEPARATOR ',')
  INTO resultado
  FROM usuarios_roles ur
  INNER JOIN roles_permisos rp ON ur.rol_id = rp.rol_id
  INNER JOIN permisos p ON rp.permiso_id = p.id
  WHERE ur.usuario_id = p_usuario_id AND ur.activo = 1 AND p.activo = 1;
  RETURN COALESCE(resultado, '');
END //

-- Función: Validar si usuario tiene permiso
DROP FUNCTION IF EXISTS `fn_usuario_tiene_permiso` //
CREATE FUNCTION `fn_usuario_tiene_permiso`(p_usuario_id BIGINT, p_permiso_codigo VARCHAR(100))
RETURNS TINYINT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE tiene INT DEFAULT 0;
  SELECT COUNT(*) INTO tiene
  FROM usuarios_roles ur
  INNER JOIN roles_permisos rp ON ur.rol_id = rp.rol_id
  INNER JOIN permisos p ON rp.permiso_id = p.id
  WHERE ur.usuario_id = p_usuario_id
    AND ur.activo = 1
    AND p.codigo = p_permiso_codigo
    AND p.activo = 1
    AND (ur.fecha_vencimiento IS NULL OR ur.fecha_vencimiento > NOW());
  RETURN IF(tiene > 0, 1, 0);
END //

-- Función: Obtener rol principal de usuario
DROP FUNCTION IF EXISTS `fn_rol_principal_usuario` //
CREATE FUNCTION `fn_rol_principal_usuario`(p_usuario_id BIGINT)
RETURNS VARCHAR(100)
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE rol_principal VARCHAR(100);
  SELECT r.codigo INTO rol_principal
  FROM usuarios_roles ur
  INNER JOIN roles r ON ur.rol_id = r.id
  WHERE ur.usuario_id = p_usuario_id AND ur.activo = 1 AND r.activo = 1
  ORDER BY r.nivel_acceso DESC, ur.fecha_asignacion ASC
  LIMIT 1;
  RETURN COALESCE(rol_principal, 'CLIENTE');
END //

-- Procedimiento: Crear usuario nuevo
DROP PROCEDURE IF EXISTS `sp_crear_usuario_nuevo` //
CREATE PROCEDURE `sp_crear_usuario_nuevo`(
  IN p_uuid_usuario CHAR(36),
  IN p_email VARCHAR(255),
  IN p_nombre_completo VARCHAR(255),
  IN p_contrasena_hash VARCHAR(255),
  IN p_rol_codigo VARCHAR(50),
  OUT p_usuario_id BIGINT,
  OUT p_mensaje VARCHAR(255)
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  IF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email) THEN
    SET p_usuario_id = NULL;
    SET p_mensaje = 'El email ya existe';
  ELSE
    INSERT INTO usuarios (uuid_usuario, email, nombre_completo, contrasena_hash, estado_id, activo)
    VALUES (p_uuid_usuario, p_email, p_nombre_completo, p_contrasena_hash, 4, 1);
    
    SET p_usuario_id = LAST_INSERT_ID();
    
    IF p_rol_codigo IS NOT NULL AND p_rol_codigo != '' THEN
      INSERT IGNORE INTO usuarios_roles (usuario_id, rol_id, activo)
      SELECT p_usuario_id, id, 1
      FROM roles
      WHERE codigo = p_rol_codigo AND activo = 1;
    END IF;
    
    SET p_mensaje = 'Usuario creado exitosamente';
  END IF;
END //

-- Procedimiento: Cambiar contraseña de usuario
DROP PROCEDURE IF EXISTS `sp_cambiar_contrasena` //
CREATE PROCEDURE `sp_cambiar_contrasena`(
  IN p_usuario_id BIGINT,
  IN p_contrasena_hash_anterior VARCHAR(255),
  IN p_contrasena_hash_nueva VARCHAR(255),
  OUT p_exito TINYINT,
  OUT p_mensaje VARCHAR(255)
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  DECLARE usuario_existe INT DEFAULT 0;
  
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_exito = 0;
    SET p_mensaje = 'Error en la base de datos';
  END;
  
  SELECT COUNT(*) INTO usuario_existe FROM usuarios WHERE id = p_usuario_id;
  
  IF usuario_existe = 0 THEN
    SET p_exito = 0;
    SET p_mensaje = 'Usuario no existe';
  ELSE
    INSERT INTO historial_contrasenas (usuario_id, contrasena_hash_anterior, contrasena_hash_nueva, razon)
    VALUES (p_usuario_id, p_contrasena_hash_anterior, p_contrasena_hash_nueva, 'Cambio de contraseña');
    
    UPDATE usuarios SET contrasena_hash = p_contrasena_hash_nueva
    WHERE id = p_usuario_id;
    
    SET p_exito = 1;
    SET p_mensaje = 'Contraseña cambiad correctamente';
  END IF;
END //

-- Procedimiento: Registrar intento de acceso
DROP PROCEDURE IF EXISTS `sp_registrar_intento_acceso` //
CREATE PROCEDURE `sp_registrar_intento_acceso`(
  IN p_usuario_id BIGINT,
  IN p_email_intento VARCHAR(255),
  IN p_direccion_ip VARCHAR(45),
  IN p_user_agent TEXT,
  IN p_exitoso TINYINT,
  IN p_razon_fallo VARCHAR(255)
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  INSERT INTO historial_acceso (usuario_id, email_intento, direccion_ip, user_agent, exitoso, razon_fallo)
  VALUES (p_usuario_id, p_email_intento, p_direccion_ip, p_user_agent, p_exitoso, p_razon_fallo);
END //

-- Procedimiento: Bloquear usuario
DROP PROCEDURE IF EXISTS `sp_bloquear_usuario` //
CREATE PROCEDURE `sp_bloquear_usuario`(
  IN p_usuario_id BIGINT,
  IN p_razon VARCHAR(255),
  IN p_admin_id BIGINT
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  UPDATE usuarios SET estado_id = 3, activo = 0 WHERE id = p_usuario_id;
  
  INSERT INTO auditoria_usuarios (usuario_id, tabla_afectada, id_registro, operacion, razon_cambio, usuario_admin_id)
  VALUES (p_usuario_id, 'usuarios', p_usuario_id, 'UPDATE', p_razon, p_admin_id);
END //

-- Procedimiento: Obtener usuario con detalles
DROP PROCEDURE IF EXISTS `sp_obtener_usuario` //
CREATE PROCEDURE `sp_obtener_usuario`(
  IN p_usuario_id BIGINT
)
DETERMINISTIC
READS SQL DATA
BEGIN
  SELECT
    u.*,
    pu.foto_perfil_url,
    pu.ciudad,
    pu.pais,
    GROUP_CONCAT(DISTINCT r.codigo) AS roles,
    GROUP_CONCAT(DISTINCT p.codigo) AS permisos
  FROM usuarios u
  LEFT JOIN perfiles_usuario pu ON u.id = pu.usuario_id
  LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id AND ur.activo = 1
  LEFT JOIN roles r ON ur.rol_id = r.id
  LEFT JOIN roles_permisos rp ON r.id = rp.rol_id
  LEFT JOIN permisos p ON rp.permiso_id = p.id AND p.activo = 1
  WHERE u.id = p_usuario_id
  GROUP BY u.id;
END //

DELIMITER ;

-- =============================================================================
-- 10. TRIGGERS PARA AUDITORÍA Y MANTENIMIENTO
-- =============================================================================

-- Trigger: Auditar cambios en tabla usuarios
DELIMITER //
DROP TRIGGER IF EXISTS `trg_audit_usuarios_update` //
CREATE TRIGGER `trg_audit_usuarios_update`
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
  INSERT INTO auditoria_usuarios (
    usuario_id, tabla_afectada, id_registro, operacion,
    datos_anteriores, datos_nuevos, fecha_operacion
  ) VALUES (
    NEW.id,
    'usuarios',
    NEW.id,
    'UPDATE',
    JSON_OBJECT(
      'email', OLD.email,
      'nombre_completo', OLD.nombre_completo,
      'estado_id', OLD.estado_id,
      'activo', OLD.activo
    ),
    JSON_OBJECT(
      'email', NEW.email,
      'nombre_completo', NEW.nombre_completo,
      'estado_id', NEW.estado_id,
      'activo', NEW.activo
    ),
    NOW()
  );
END //
DELIMITER ;

-- Trigger: Crear perfil de usuario automáticamente
DELIMITER //
DROP TRIGGER IF EXISTS `trg_crear_perfil_usuario` //
CREATE TRIGGER `trg_crear_perfil_usuario`
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
  INSERT INTO perfiles_usuario (usuario_id) VALUES (NEW.id);
END //
DELIMITER ;

-- ⚠️ TRIGGER REMOVIDO: trg_limpiar_sesiones_expiradas
-- Causa conflicto de recursión en MySQL 5.7 (Error 1442)
-- Solución: Se usa EVENTO PROGRAMADO en lugar del trigger
-- Ver sección 10B más abajo

-- Trigger: Auditar cambios de contraseña
DELIMITER //
DROP TRIGGER IF EXISTS `trg_audit_cambio_contrasena` //
CREATE TRIGGER `trg_audit_cambio_contrasena`
AFTER INSERT ON historial_contrasenas
FOR EACH ROW
BEGIN
  INSERT INTO auditoria_usuarios (
    usuario_id, tabla_afectada, id_registro, operacion, razon_cambio
  ) VALUES (
    NEW.usuario_id,
    'historial_contrasenas',
    NEW.id,
    'UPDATE',
    'Cambio de contraseña'
  );
END //
DELIMITER ;

-- =============================================================================
-- 10B. EVENTO PROGRAMADO PARA LIMPIAR SESIONES
-- =============================================================================
-- Este evento se ejecuta cada hora para limpiar sesiones expiradas
-- Alternativa segura al trigger (evita Error 1442 en MySQL 5.7)

DELIMITER //
DROP EVENT IF EXISTS `evt_limpiar_sesiones_expiradas` //
CREATE EVENT `evt_limpiar_sesiones_expiradas`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  UPDATE sesiones_usuario
  SET activo = 0
  WHERE fecha_expiracion < NOW() 
     OR (activo = 0 AND fecha_ultima_actividad < DATE_SUB(NOW(), INTERVAL 7 DAY));
END //
DELIMITER ;

-- Habilitar el event scheduler
SET GLOBAL event_scheduler = ON;

-- =============================================================================
-- 11. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =============================================================================

ALTER TABLE `usuarios` ADD INDEX `idx_email_estado` (`email`, `estado_id`);
ALTER TABLE `usuarios` ADD INDEX `idx_activo_creacion` (`activo`, `fecha_creacion`);
ALTER TABLE `usuarios_roles` ADD INDEX `idx_usuario_activo` (`usuario_id`, `activo`);
ALTER TABLE `sesiones_usuario` ADD INDEX `idx_usuario_activo_expiracion` (`usuario_id`, `activo`, `fecha_expiracion`);
ALTER TABLE `pedidos` ADD INDEX `idx_usuario_estado` (`usuario_id`, `estado`);
ALTER TABLE `pedidos` ADD INDEX `idx_fecha_estado` (`fecha_pedido`, `estado`);
ALTER TABLE `historial_acceso` ADD INDEX `idx_email_fecha` (`email_intento`, `fecha_intento`);

-- =============================================================================
-- 12. COMENTARIOS DE CONFIGURACIÓN
-- =============================================================================

/*
NOTAS IMPORTANTES:
1. Los UUID se generan desde PHP (más seguro y compatible con MySQL 5.7)
2. Las contraseñas se almacenan como HASH (bcrypt, SHA-256, etc.) desde la aplicación PHP
3. Los tokens (sesión, recuperación) se generan en PHP con alta entropía
4. Los triggers de auditoría registran cambios importantes automáticamente
5. Las vistas facilitan consultas complejas sin acceso directo a múltiples tablas
6. Los procedimientos almacenados encapsulan lógica de negocio crítica
7. Los índices están optimizados para consultas frecuentes
8. JSON se usa para datos flexibles (redes sociales, preferencias, cambios)

CONFIGURACIONES RECOMENDADAS EN PHP (config/database.php):
- Generar UUID en PHP: bin2hex(random_bytes(16)) y formatear como v4
- Usar prepared statements para todas las consultas
- Validar y sanitizar entrada del usuario siempre
- Usar SSL en conexiones a BD
- Implementar password hashing con password_hash()
- Tokens de sesión: 64+ caracteres, almacenado en hash
- Rate limiting en login attempts
- 2FA con TOTP o SMS

EJEMPLOS DE USO DESDE PHP:
- Generar UUID: Utilidades::generarUuid() (ver Utils/Utilidades.php)
- Crear usuario: CALL sp_crear_usuario_nuevo(uuid, email, nombre, hash_pass, rol, @id, @msg)
- Verificar permiso: SELECT fn_usuario_tiene_permiso(?, ?)
- Obtener usuario: CALL sp_obtener_usuario(?)
- Cambiar contraseña: CALL sp_cambiar_contrasena(?, ?, ?, @exito, @msg)
*/

-- =============================================================================
-- FIN DEL MÓDULO DE GESTIÓN DE USUARIOS
-- Versión: 1.0
-- Compatible con: MySQL 5.7+
-- =============================================================================
