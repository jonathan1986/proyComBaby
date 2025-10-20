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
DELIMITER ;
CALL sp_add_col_impuestos_modo();
DROP PROCEDURE IF EXISTS sp_add_col_impuestos_modo;

-- 1b) Agregar columnas descuento_pct y descuento_monto en carritos si no existen
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_col_descuento_pct $$
CREATE PROCEDURE sp_add_col_descuento_pct()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  SELECT COUNT(*) INTO v_count
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'carritos'
    AND COLUMN_NAME = 'descuento_pct';
  IF v_count = 0 THEN
    SET @sql = 'ALTER TABLE carritos ADD COLUMN descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER impuestos_modo';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END $$
DELIMITER ;
CALL sp_add_col_descuento_pct();
DROP PROCEDURE IF EXISTS sp_add_col_descuento_pct;

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_col_descuento_monto $$
CREATE PROCEDURE sp_add_col_descuento_monto()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  SELECT COUNT(*) INTO v_count
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'carritos'
    AND COLUMN_NAME = 'descuento_monto';
  IF v_count = 0 THEN
    SET @sql = 'ALTER TABLE carritos ADD COLUMN descuento_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER descuento_pct';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END $$
DELIMITER ;
CALL sp_add_col_descuento_monto();
DROP PROCEDURE IF EXISTS sp_add_col_descuento_monto;

-- 1c) Agregar columna subtotal_linea en carrito_items si no existe
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_col_subtotal_linea $$
CREATE PROCEDURE sp_add_col_subtotal_linea()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  SELECT COUNT(*) INTO v_count
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'carrito_items'
    AND COLUMN_NAME = 'subtotal_linea';
  IF v_count = 0 THEN
    SET @sql = 'ALTER TABLE carrito_items ADD COLUMN subtotal_linea DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER cantidad';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END $$
DELIMITER ;
CALL sp_add_col_subtotal_linea();
DROP PROCEDURE IF EXISTS sp_add_col_subtotal_linea;

-- 2) Crear tablas si no existen
-- (usar delimitador ;) para SET/PREPARE/EXECUTE
SET @sql = 'CREATE TABLE IF NOT EXISTS impuestos (
  id_impuesto INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  tipo ENUM(''porcentaje'',''fijo'') NOT NULL DEFAULT ''porcentaje'',
  valor DECIMAL(10,4) NOT NULL,
  aplica_sobre ENUM(''subtotal'',''base_descuento'') NOT NULL DEFAULT ''base_descuento'',
  activo TINYINT NOT NULL DEFAULT 1
) COMMENT=''Catálogo de impuestos''';
PREPARE s1 FROM @sql; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @sql = 'CREATE TABLE IF NOT EXISTS productos_impuestos (
  id_producto INT NOT NULL,
  id_impuesto INT NOT NULL,
  PRIMARY KEY (id_producto, id_impuesto),
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT=''Impuestos aplicables por producto''';
PREPARE s2 FROM @sql; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @sql = 'CREATE TABLE IF NOT EXISTS carrito_items_impuestos (
  id_item INT NOT NULL,
  id_impuesto INT NOT NULL,
  base DECIMAL(12,2) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_item, id_impuesto),
  FOREIGN KEY (id_item) REFERENCES carrito_items(id_item) ON DELETE CASCADE,
  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT=''Desglose de impuestos por item''';
PREPARE s3 FROM @sql; EXECUTE s3; DEALLOCATE PREPARE s3;

SET @sql = 'CREATE TABLE IF NOT EXISTS carritos_impuestos (
  id_carrito INT NOT NULL,
  id_impuesto INT NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_carrito, id_impuesto),
  FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,
  FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT=''Desglose de impuestos por carrito''';
PREPARE s4 FROM @sql; EXECUTE s4; DEALLOCATE PREPARE s4;

DELIMITER $$

-- 3) Crear SP de recálculo (drop & create)
DROP PROCEDURE IF EXISTS sp_recalcular_impuestos_carrito $$
CREATE PROCEDURE sp_recalcular_impuestos_carrito(IN p_id_carrito INT)
proc: BEGIN
  DECLARE v_modo VARCHAR(5);
  DECLARE v_desc_pct DECIMAL(5,2);
  DECLARE v_desc_mto DECIMAL(10,2);
  DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0;
  DECLARE v_imp_total DECIMAL(12,2) DEFAULT 0;
  DECLARE v_subtotal2 DECIMAL(12,2) DEFAULT 0;
  DECLARE v_desc_total DECIMAL(12,2) DEFAULT 0;
  DECLARE v_base DECIMAL(12,2) DEFAULT 0;
  DECLARE v_total DECIMAL(12,2) DEFAULT 0;

  SELECT impuestos_modo, descuento_pct, descuento_monto
    INTO v_modo, v_desc_pct, v_desc_mto
  FROM carritos WHERE id_carrito = p_id_carrito;

  IF v_modo IS NULL OR v_modo <> 'multi' THEN
    LEAVE proc; -- no aplica en modo simple
  END IF;

  -- Limpiar snapshots previos
  DELETE ciimp
  FROM carrito_items_impuestos ciimp
  JOIN carrito_items ci ON ciimp.id_item = ci.id_item
  WHERE ci.id_carrito = p_id_carrito;

  DELETE FROM carritos_impuestos WHERE id_carrito = p_id_carrito;

  -- Subtotal para prorrateo
  SELECT COALESCE(SUM(subtotal_linea), 0) INTO v_subtotal
  FROM carrito_items
  WHERE id_carrito = p_id_carrito;

  -- Recalcular ítem-impuestos
  INSERT INTO carrito_items_impuestos (id_item, id_impuesto, base, monto)
  SELECT
    ci.id_item,
    pi.id_impuesto,
    GREATEST(
      CASE WHEN v_desc_mto > 0 AND v_subtotal > 0
           THEN ci.subtotal_linea - ROUND((ci.subtotal_linea / v_subtotal) * v_desc_mto, 2)
           ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea * (v_desc_pct/100), 2)
      END,
      0
    ) AS base_linea,
    CASE i.tipo
      WHEN 'porcentaje' THEN ROUND(
        GREATEST(
          CASE WHEN v_desc_mto > 0 AND v_subtotal > 0
               THEN ci.subtotal_linea - ROUND((ci.subtotal_linea / v_subtotal) * v_desc_mto, 2)
               ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea * (v_desc_pct/100), 2)
          END,
          0
        ) * (i.valor/100), 2)
      WHEN 'fijo' THEN ROUND(i.valor * ci.cantidad, 2)
    END AS monto_impuesto
  FROM carrito_items ci
  JOIN productos_impuestos pi ON pi.id_producto = ci.id_producto
  JOIN impuestos i ON i.id_impuesto = pi.id_impuesto AND i.activo = 1
  WHERE ci.id_carrito = p_id_carrito;

  -- Agrupar por carrito
  INSERT INTO carritos_impuestos (id_carrito, id_impuesto, monto)
  SELECT
    p_id_carrito,
    ciimp.id_impuesto,
    SUM(ciimp.monto)
  FROM carrito_items_impuestos ciimp
  JOIN carrito_items ci ON ciimp.id_item = ci.id_item
  WHERE ci.id_carrito = p_id_carrito
  GROUP BY ciimp.id_impuesto;

  -- Totales
  SELECT COALESCE(SUM(monto), 0) INTO v_imp_total
  FROM carritos_impuestos
  WHERE id_carrito = p_id_carrito;

  SELECT COALESCE(SUM(subtotal_linea), 0) INTO v_subtotal2
  FROM carrito_items
  WHERE id_carrito = p_id_carrito;

  SET v_desc_total = CASE WHEN v_desc_mto > 0 THEN v_desc_mto ELSE ROUND(v_subtotal2 * (v_desc_pct/100), 2) END;
  SET v_base = GREATEST(v_subtotal2 - v_desc_total, 0);
  SET v_total = v_base + v_imp_total;

  UPDATE carritos
     SET subtotal = v_subtotal2,
         descuento_total = v_desc_total,
         impuesto_total = v_imp_total,
         total = v_total
   WHERE id_carrito = p_id_carrito;
END proc $$
DELIMITER ;

-- 4) Triggers de recálculo automático cuando cambia el detalle o descuentos/modo
DELIMITER $$
DROP TRIGGER IF EXISTS trg_carrito_items_ai_recalc $$
CREATE TRIGGER trg_carrito_items_ai_recalc
AFTER INSERT ON carrito_items
FOR EACH ROW
BEGIN
  CALL sp_recalcular_impuestos_carrito(NEW.id_carrito);
END $$

DROP TRIGGER IF EXISTS trg_carrito_items_au_recalc $$
CREATE TRIGGER trg_carrito_items_au_recalc
AFTER UPDATE ON carrito_items
FOR EACH ROW
BEGIN
  IF NEW.id_carrito <> OLD.id_carrito THEN
    CALL sp_recalcular_impuestos_carrito(OLD.id_carrito);
    CALL sp_recalcular_impuestos_carrito(NEW.id_carrito);
  ELSE
    CALL sp_recalcular_impuestos_carrito(NEW.id_carrito);
  END IF;
END $$

DROP TRIGGER IF EXISTS trg_carrito_items_ad_recalc $$
CREATE TRIGGER trg_carrito_items_ad_recalc
AFTER DELETE ON carrito_items
FOR EACH ROW
BEGIN
  CALL sp_recalcular_impuestos_carrito(OLD.id_carrito);
END $$

DROP TRIGGER IF EXISTS trg_carritos_au_recalc $$
CREATE TRIGGER trg_carritos_au_recalc
AFTER UPDATE ON carritos
FOR EACH ROW
BEGIN
  IF (NEW.impuestos_modo <> OLD.impuestos_modo)
     OR (NEW.descuento_pct <> OLD.descuento_pct)
     OR (NEW.descuento_monto <> OLD.descuento_monto) THEN
    CALL sp_recalcular_impuestos_carrito(NEW.id_carrito);
  END IF;
END $$
DELIMITER ;