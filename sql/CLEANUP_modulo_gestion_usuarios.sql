-- =============================================================================
-- SCRIPT DE LIMPIEZA - MÓDULO DE GESTIÓN DE USUARIOS
-- =============================================================================
-- Este script elimina TODA la estructura creada por el módulo de gestión de usuarios
-- ADVERTENCIA: Esta operación es IRREVERSIBLE. Hará una copia de seguridad primero.
-- =============================================================================

USE babylovec;

-- =============================================================================
-- 1. ELIMINAR TRIGGERS
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_audit_usuarios_update`;
DROP TRIGGER IF EXISTS `trg_crear_perfil_usuario`;
DROP TRIGGER IF EXISTS `trg_limpiar_sesiones_expiradas`;
DROP TRIGGER IF EXISTS `trg_audit_cambio_contrasena`;

-- =============================================================================
-- 2. ELIMINAR PROCEDIMIENTOS ALMACENADOS
-- =============================================================================

DROP PROCEDURE IF EXISTS `sp_crear_usuario_nuevo`;
DROP PROCEDURE IF EXISTS `sp_cambiar_contrasena`;
DROP PROCEDURE IF EXISTS `sp_registrar_intento_acceso`;
DROP PROCEDURE IF EXISTS `sp_bloquear_usuario`;
DROP PROCEDURE IF EXISTS `sp_obtener_usuario`;

-- =============================================================================
-- 3. ELIMINAR FUNCIONES
-- =============================================================================

DROP FUNCTION IF EXISTS `fn_obtener_permisos_usuario`;
DROP FUNCTION IF EXISTS `fn_usuario_tiene_permiso`;
DROP FUNCTION IF EXISTS `fn_rol_principal_usuario`;

-- =============================================================================
-- 4. ELIMINAR VISTAS
-- =============================================================================

DROP VIEW IF EXISTS `v_usuarios_activos`;
DROP VIEW IF EXISTS `v_pedidos_recientes`;
DROP VIEW IF EXISTS `v_permisos_usuario`;
DROP VIEW IF EXISTS `v_resumen_usuario`;

-- =============================================================================
-- 5. ELIMINAR TABLAS (en orden para respetar foreign keys)
-- =============================================================================

-- Tablas relacionadas a pedidos
DROP TABLE IF EXISTS `detalles_pedido`;
DROP TABLE IF EXISTS `pedidos`;

-- Tabla de auditoría
DROP TABLE IF EXISTS `auditoria_usuarios`;

-- Tablas de roles y permisos
DROP TABLE IF EXISTS `roles_permisos`;
DROP TABLE IF EXISTS `usuarios_roles`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;

-- Tabla de perfil de usuario
DROP TABLE IF EXISTS `perfiles_usuario`;

-- Tablas de autenticación y sesiones
DROP TABLE IF EXISTS `historial_acceso`;
DROP TABLE IF EXISTS `historial_contrasenas`;
DROP TABLE IF EXISTS `recuperacion_contrasena`;
DROP TABLE IF EXISTS `sesiones_usuario`;
DROP TABLE IF EXISTS `usuarios`;

-- Tabla de estados
DROP TABLE IF EXISTS `estados_usuario`;

-- =============================================================================
-- FIN DEL SCRIPT DE LIMPIEZA
-- =============================================================================
-- Todas las tablas, vistas, procedimientos, funciones y triggers han sido eliminados.
-- La base de datos babylovec ha sido restaurada a su estado anterior.
-- =============================================================================
