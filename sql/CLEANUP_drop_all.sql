-- CLEANUP_drop_all.sql (MySQL 5.7 / Percona)
-- Script para limpiar completamente la base de datos babylovec
-- ADVERTENCIA: Este script elimina TODAS las tablas, vistas, procedimientos y triggers
-- Úsalo con precaución, especialmente en ambientes de producción
-- 
-- Uso:
-- mysql -u usuario -p babylovec < CLEANUP_drop_all.sql
-- o desde Docker:
--# 1. Limpiar la base de datos
--docker exec -i proycombaby-db-1 mysql -u jonathan -pjonathandb babylovec < sql/CLEANUP_drop_all.sql

--# 2. Reinstalar el esquema completo
--docker exec -i proycombaby-db-1 mysql -u jonathan -pjonathandb babylovec < sql/MASTER_install.sql

--# 3. (Opcional) Insertar datos de prueba
--docker exec -i proycombaby-db-1 mysql -u jonathan -pjonathandb babylovec < sql/insertAllTable.sql

-- Selección de base de datos
USE babylovec;

-- Ajustes de sesión
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0; -- Deshabilitar verificación de claves foráneas temporalmente

-- ============================================================
-- ELIMINACIÓN DE VISTAS
-- ============================================================
DROP VIEW IF EXISTS vista_carrito_items;
DROP VIEW IF EXISTS vista_stock_disponible;

-- ============================================================
-- ELIMINACIÓN DE TRIGGERS
-- ============================================================

-- Triggers de alertas de bajo stock
DROP TRIGGER IF EXISTS trg_alerta_bajo_stock;

-- Triggers de carrito_items (modo simple)
DROP TRIGGER IF EXISTS trg_ci_bi;
DROP TRIGGER IF EXISTS trg_ci_bu;
DROP TRIGGER IF EXISTS trg_ci_ai;
DROP TRIGGER IF EXISTS trg_ci_au;
DROP TRIGGER IF EXISTS trg_ci_ad;

-- Triggers de recálculo multi-impuestos
DROP TRIGGER IF EXISTS trg_carrito_items_ai_recalc;
DROP TRIGGER IF EXISTS trg_carrito_items_au_recalc;
DROP TRIGGER IF EXISTS trg_carrito_items_ad_recalc;
DROP TRIGGER IF EXISTS trg_carritos_au_recalc;

-- ============================================================
-- ELIMINACIÓN DE PROCEDIMIENTOS ALMACENADOS
-- ============================================================

-- Procedimientos principales
DROP PROCEDURE IF EXISTS sp_recalcular_impuestos_carrito;

-- Procedimientos de patches (temporales)
DROP PROCEDURE IF EXISTS sp_add_idx_carritos_estado_fecha;
DROP PROCEDURE IF EXISTS sp_add_carrito_logs;
DROP PROCEDURE IF EXISTS sp_add_col_impuestos_modo;
DROP PROCEDURE IF EXISTS sp_add_col_descuento_pct;
DROP PROCEDURE IF EXISTS sp_add_col_descuento_monto;
DROP PROCEDURE IF EXISTS sp_add_col_subtotal_linea;

-- ============================================================
-- ELIMINACIÓN DE TABLAS (en orden inverso de dependencias)
-- ============================================================

-- Tablas de multi-impuestos (snapshot)
DROP TABLE IF EXISTS carritos_impuestos;
DROP TABLE IF EXISTS carrito_items_impuestos;
DROP TABLE IF EXISTS productos_impuestos;
DROP TABLE IF EXISTS impuestos;

-- Tablas de auditoría y logs
DROP TABLE IF EXISTS carrito_logs;
DROP TABLE IF EXISTS alertas_bajo_stock;

-- Tablas de inventario
DROP TABLE IF EXISTS inventario_movimientos;
DROP TABLE IF EXISTS pedidos_reabastecimiento_detalle;
DROP TABLE IF EXISTS pedidos_reabastecimiento;

-- Tablas de carrito
DROP TABLE IF EXISTS carrito_items;
DROP TABLE IF EXISTS carritos;

-- Tablas de relaciones N:M
DROP TABLE IF EXISTS productos_proveedores;
DROP TABLE IF EXISTS productos_atributos;
DROP TABLE IF EXISTS productos_categorias;

-- Tablas de imágenes
DROP TABLE IF EXISTS imagenes_productos;

-- Tablas maestras
DROP TABLE IF EXISTS atributos;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;

-- ============================================================
-- RESTAURAR CONFIGURACIÓN
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1; -- Rehabilitar verificación de claves foráneas

-- ============================================================
-- VERIFICACIÓN (opcional - descomentar para ver el estado)
-- ============================================================
-- SHOW TABLES;
-- SHOW PROCEDURE STATUS WHERE Db = 'babylovec';
-- SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'babylovec';
-- SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'babylovec';

-- Fin del script de limpieza
