-- Patch para entornos existentes (MySQL 5.7 no soporta IF NOT EXISTS en CREATE INDEX)
-- Aplica condicionalmente el índice si no existe ya.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_idx_carritos_estado_fecha $$
CREATE PROCEDURE sp_add_idx_carritos_estado_fecha()
BEGIN
    DECLARE v_count INT DEFAULT 0;
    SELECT COUNT(1) INTO v_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'carritos'
      AND index_name = 'idx_carritos_estado_fecha';

    IF v_count = 0 THEN
        SET @sql = 'CREATE INDEX idx_carritos_estado_fecha ON carritos(estado, fecha_actualizacion)';
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;
CALL sp_add_idx_carritos_estado_fecha();
DROP PROCEDURE sp_add_idx_carritos_estado_fecha;