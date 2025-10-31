-- patch_drop_trg_carritos_au_recalc.sql
-- Objetivo: eliminar el trigger AFTER UPDATE sobre 'carritos' que invoca sp_recalcular_impuestos_carrito
-- Motivo: MySQL 5.7 prohíbe actualizar la misma tabla desde un trigger que se dispara por esa tabla (Error 1442)
--         El recálculo se hará desde la capa de aplicación (carrito_api.php) y desde triggers de carrito_items.

DELIMITER $$
DROP TRIGGER IF EXISTS trg_carritos_au_recalc $$
DELIMITER ;

-- Fin del patch
