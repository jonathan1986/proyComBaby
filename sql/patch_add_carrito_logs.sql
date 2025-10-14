-- Patch para crear tabla carrito_logs en entornos ya instalados
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_carrito_logs $$
CREATE PROCEDURE sp_add_carrito_logs()
BEGIN
    DECLARE v_count INT DEFAULT 0;
    SELECT COUNT(*) INTO v_count FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'carrito_logs';
    IF v_count = 0 THEN
        SET @sql = 'CREATE TABLE carrito_logs (
            id_log INT AUTO_INCREMENT PRIMARY KEY,
            id_carrito INT NOT NULL,
            accion ENUM(''crear'',''actualizar_cabecera'',''agregar_item'',''actualizar_item'',''eliminar_item'',''vaciar'',''eliminar_carrito'',''merge'',''expirar'') NOT NULL,
            detalles JSON NULL,
            usuario_id INT NULL,
            session_token VARCHAR(64) NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_logs_carrito_fecha (id_carrito, fecha),
            FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE
        ) COMMENT=''Auditoría de operaciones de carritos''';
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;
CALL sp_add_carrito_logs();
DROP PROCEDURE sp_add_carrito_logs;