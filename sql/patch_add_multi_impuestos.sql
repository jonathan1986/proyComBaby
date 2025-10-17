-- Patch condicional para habilitar multi-impuestos en entornos existentes (MySQL 5.7)
DELIMITER $$

-- 1) Agregar columna impuestos_modo si no existe
DROP PROCEDURE IF EXISTS sp_add_col_impuestos_modo $$
CREATE PROCEDURE sp_add_col_impuestos_modo()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  SELECT COUNT(*) INTO v_count
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'carritos'
    AND COLUMN_NAME = 'impuestos_modo';
  IF v_count = 0 THEN
    SET @sql = 'ALTER TABLE carritos ADD COLUMN impuestos_modo ENUM(\'simple\',\'multi\') NOT NULL DEFAULT \'simple\' AFTER moneda';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END $$
CALL sp_add_col_impuestos_modo();
DROP PROCEDURE sp_add_col_impuestos_modo $$

-- 2) Crear tablas si no existen
SET @sql = 'CREATE TABLE IF NOT EXISTS impuestos (\n  id_impuesto INT AUTO_INCREMENT PRIMARY KEY,\n  codigo VARCHAR(20) NOT NULL UNIQUE,\n  nombre VARCHAR(100) NOT NULL,\n  tipo ENUM(\'porcentaje\',\'fijo\') NOT NULL DEFAULT \'porcentaje\',\n  valor DECIMAL(10,4) NOT NULL,\n  aplica_sobre ENUM(\'subtotal\',\'base_descuento\') NOT NULL DEFAULT \'base_descuento\',\n  activo TINYINT NOT NULL DEFAULT 1\n) COMMENT=\'Catálogo de impuestos\''; PREPARE s1 FROM @sql; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @sql = 'CREATE TABLE IF NOT EXISTS productos_impuestos (\n  id_producto INT NOT NULL,\n  id_impuesto INT NOT NULL,\n  PRIMARY KEY (id_producto, id_impuesto),\n  FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,\n  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT\n) COMMENT=\'Impuestos aplicables por producto\''; PREPARE s2 FROM @sql; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @sql = 'CREATE TABLE IF NOT EXISTS carrito_items_impuestos (\n  id_item INT NOT NULL,\n  id_impuesto INT NOT NULL,\n  base DECIMAL(12,2) NOT NULL,\n  monto DECIMAL(12,2) NOT NULL,\n  PRIMARY KEY (id_item, id_impuesto),\n  FOREIGN KEY (id_item) REFERENCES carrito_items(id_item) ON DELETE CASCADE,\n  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT\n) COMMENT=\'Desglose de impuestos por item\''; PREPARE s3 FROM @sql; EXECUTE s3; DEALLOCATE PREPARE s3;

SET @sql = 'CREATE TABLE IF NOT EXISTS carritos_impuestos (\n  id_carrito INT NOT NULL,\n  id_impuesto INT NOT NULL,\n  monto DECIMAL(12,2) NOT NULL,\n  PRIMARY KEY (id_carrito, id_impuesto),\n  FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,\n  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT\n) COMMENT=\'Desglose de impuestos por carrito\''; PREPARE s4 FROM @sql; EXECUTE s4; DEALLOCATE PREPARE s4;

-- 3) Crear SP de recálculo (drop & create)
DROP PROCEDURE IF EXISTS sp_recalcular_impuestos_carrito $$
CREATE PROCEDURE sp_recalcular_impuestos_carrito(IN p_id_carrito INT)
BEGIN
  DECLARE v_modo VARCHAR(5);
  DECLARE v_desc_pct DECIMAL(5,2);
  DECLARE v_desc_mto DECIMAL(10,2);

  SELECT impuestos_modo, descuento_pct, descuento_monto
    INTO v_modo, v_desc_pct, v_desc_mto
  FROM carritos WHERE id_carrito = p_id_carrito;

  IF v_modo IS NULL OR v_modo <> 'multi' THEN
    LEAVE BEGIN;
  END IF;

  DELETE ciimp FROM carrito_items_impuestos ciimp
  JOIN carrito_items ci ON ciimp.id_item = ci.id_item
  WHERE ci.id_carrito = p_id_carrito;
  DELETE FROM carritos_impuestos WHERE id_carrito = p_id_carrito;

  SET @v_subtotal := COALESCE((SELECT SUM(subtotal_linea) FROM carrito_items WHERE id_carrito = p_id_carrito), 0);

  INSERT INTO carrito_items_impuestos (id_item, id_impuesto, base, monto)
  SELECT ci.id_item, pi.id_impuesto,
    GREATEST(CASE WHEN v_desc_mto > 0 AND @v_subtotal > 0 THEN ci.subtotal_linea - ROUND((ci.subtotal_linea/@v_subtotal)*v_desc_mto,2) ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea*(v_desc_pct/100),2) END, 0) AS base_linea,
    CASE i.tipo WHEN 'porcentaje' THEN ROUND(GREATEST(CASE WHEN v_desc_mto > 0 AND @v_subtotal > 0 THEN ci.subtotal_linea - ROUND((ci.subtotal_linea/@v_subtotal)*v_desc_mto,2) ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea*(v_desc_pct/100),2) END, 0)*(i.valor/100),2) WHEN 'fijo' THEN ROUND(i.valor*ci.cantidad,2) END AS monto_impuesto
  FROM carrito_items ci
  JOIN productos_impuestos pi ON pi.id_producto = ci.id_producto
  JOIN impuestos i ON i.id_impuesto = pi.id_impuesto AND i.activo = 1
  WHERE ci.id_carrito = p_id_carrito;

  INSERT INTO carritos_impuestos (id_carrito, id_impuesto, monto)
  SELECT p_id_carrito, ciimp.id_impuesto, SUM(ciimp.monto)
  FROM carrito_items_impuestos ciimp
  JOIN carrito_items ci ON ciimp.id_item = ci.id_item
  WHERE ci.id_carrito = p_id_carrito
  GROUP BY ciimp.id_impuesto;

  SET @v_imp_total := COALESCE((SELECT SUM(monto) FROM carritos_impuestos WHERE id_carrito = p_id_carrito), 0);
  SET @v_subtotal2 := COALESCE((SELECT SUM(subtotal_linea) FROM carrito_items WHERE id_carrito = p_id_carrito), 0);
  SET @v_desc_total := CASE WHEN v_desc_mto > 0 THEN v_desc_mto ELSE ROUND(@v_subtotal2 * (v_desc_pct/100), 2) END;
  SET @v_base := GREATEST(@v_subtotal2 - @v_desc_total, 0);
  SET @v_total := @v_base + @v_imp_total;

  UPDATE carritos
     SET subtotal = @v_subtotal2,
         descuento_total = @v_desc_total,
         impuesto_total = @v_imp_total,
         total = @v_total
   WHERE id_carrito = p_id_carrito;
END $$

DELIMITER ;