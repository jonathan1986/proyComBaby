-- Script SQL para módulo Carrito de Compras
-- Creación de tablas, relaciones, vistas, índices y triggers

-- Cabecera de carritos (carrito por sesión/cliente)
CREATE TABLE carritos (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del carrito',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del carrito',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
    estado ENUM('activo','convertido','abandonado','cancelado') NOT NULL DEFAULT 'activo' COMMENT 'Estado del carrito',

    -- Identificación de cliente/visitante
    id_cliente INT NULL COMMENT 'ID del cliente si está autenticado (opcional)',
    session_token VARCHAR(100) NOT NULL COMMENT 'Identificador de sesión o visitante',

    -- Datos de contacto (opcional, útil para recuperar carritos)
    nombre_contacto VARCHAR(150) COMMENT 'Nombre del contacto',
    telefono_contacto VARCHAR(30) COMMENT 'Teléfono del contacto',
    email_contacto VARCHAR(100) COMMENT 'Correo electrónico del contacto',

    -- Parámetros y observaciones
    moneda VARCHAR(10) NOT NULL DEFAULT 'PYG' COMMENT 'Moneda de referencia',
    observaciones TEXT COMMENT 'Observaciones generales',
    cupon_codigo VARCHAR(50) COMMENT 'Código de cupón aplicado',
    cupon_descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento por cupón',
    envio_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo de envío estimado',

    -- Totales denormalizados (mantenidos por triggers)
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de (cantidad*precio) sin descuentos ni impuestos',
    descuento_total DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento total aplicado',
    impuesto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Impuestos totales calculados',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Importe total final',

    -- Auditoría
    usuario_creacion VARCHAR(50) COMMENT 'Usuario que creó el registro',
    usuario_actualizacion VARCHAR(50) COMMENT 'Usuario que actualizó el registro'
) COMMENT='Cabecera de carritos de compras';

-- Índices para búsqueda y listados
CREATE INDEX idx_car_estado_fecha ON carritos(estado, fecha_creacion);
CREATE INDEX idx_car_session ON carritos(session_token);
CREATE INDEX idx_car_cliente ON carritos(id_cliente);


-- Detalle del carrito (ítems)
CREATE TABLE carritos_detalle (
    id_carrito INT NOT NULL COMMENT 'ID del carrito',
    id_producto INT NOT NULL COMMENT 'ID del producto agregado',
    cantidad INT NOT NULL CHECK (cantidad > 0) COMMENT 'Cantidad solicitada',
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0) COMMENT 'Precio unitario al momento de agregar',
    descuento_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (descuento_monto >= 0) COMMENT 'Descuento absoluto por línea',
    tasa_impuesto DECIMAL(5,2) NOT NULL DEFAULT 0.00 CHECK (tasa_impuesto >= 0) COMMENT 'Porcentaje de impuesto (ej. 10.00 = 10%)',
    total_linea DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de la línea (cantidad*precio - descuento + impuesto)',

    PRIMARY KEY (id_carrito, id_producto),
    FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) COMMENT='Detalle de productos en el carrito';

-- Índices para detalle
CREATE INDEX idx_cardet_carrito ON carritos_detalle(id_carrito);
CREATE INDEX idx_cardet_producto ON carritos_detalle(id_producto);


-- Vista: detalle del carrito con imagen principal del producto (con fallback)
CREATE OR REPLACE VIEW vista_carrito_detalle_imagen AS
SELECT
    d.id_carrito,
    d.id_producto,
    p.nombre,
    d.cantidad,
    d.precio_unitario,
    d.descuento_monto,
    d.tasa_impuesto,
    d.total_linea,
    COALESCE(ip.archivo_imagen, ia.archivo_imagen) AS archivo_imagen
FROM carritos_detalle d
JOIN productos p ON p.id_producto = d.id_producto
LEFT JOIN (
    SELECT id_producto, MIN(archivo_imagen) AS archivo_imagen
    FROM imagenes_productos
    WHERE principal = TRUE AND estado = 1
    GROUP BY id_producto
) ip ON ip.id_producto = d.id_producto
LEFT JOIN (
    SELECT id_producto, MIN(archivo_imagen) AS archivo_imagen
    FROM imagenes_productos
    WHERE estado = 1
    GROUP BY id_producto
) ia ON ia.id_producto = d.id_producto;


-- Vista: resumen de carritos (totales + cantidad de ítems)
CREATE OR REPLACE VIEW vista_carritos_resumen AS
SELECT
    c.id_carrito,
    c.fecha_creacion,
    c.estado,
    c.session_token,
    c.id_cliente,
    c.subtotal,
    c.descuento_total,
    c.impuesto_total,
    c.cupon_descuento,
    c.envio_monto,
    c.total,
    COALESCE(SUM(d.cantidad), 0) AS items
FROM carritos c
LEFT JOIN carritos_detalle d ON d.id_carrito = c.id_carrito
GROUP BY
    c.id_carrito,
    c.fecha_creacion,
    c.estado,
    c.session_token,
    c.id_cliente,
    c.subtotal,
    c.descuento_total,
    c.impuesto_total,
    c.cupon_descuento,
    c.envio_monto,
    c.total;


DELIMITER $$

-- BEFORE INSERT: validar estado y calcular total_linea
CREATE TRIGGER trg_cardet_bi_guardrails
BEFORE INSERT ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_estado ENUM('activo','convertido','abandonado','cancelado');
    SELECT estado INTO v_estado FROM carritos WHERE id_carrito = NEW.id_carrito;
    IF v_estado IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Carrito inexistente';
    END IF;
    IF v_estado <> 'activo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar el detalle de un carrito no activo';
    END IF;

    -- Calcular total de la línea: base = cantidad*precio - descuento; impuesto = base*(tasa/100)
    SET NEW.total_linea = ROUND(((NEW.cantidad * NEW.precio_unitario) - NEW.descuento_monto)
                         + GREATEST(0, ((NEW.cantidad * NEW.precio_unitario) - NEW.descuento_monto)) * (NEW.tasa_impuesto/100), 2);
END$$

-- BEFORE UPDATE: validar estado y recalcular total_linea
CREATE TRIGGER trg_cardet_bu_guardrails
BEFORE UPDATE ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_estado ENUM('activo','convertido','abandonado','cancelado');
    SELECT estado INTO v_estado FROM carritos WHERE id_carrito = NEW.id_carrito;
    IF v_estado IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Carrito inexistente';
    END IF;
    IF v_estado <> 'activo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar el detalle de un carrito no activo';
    END IF;

    SET NEW.total_linea = ROUND(((NEW.cantidad * NEW.precio_unitario) - NEW.descuento_monto)
                         + GREATEST(0, ((NEW.cantidad * NEW.precio_unitario) - NEW.descuento_monto)) * (NEW.tasa_impuesto/100), 2);
END$$

-- BEFORE DELETE: validar estado
CREATE TRIGGER trg_cardet_bd_guardrails
BEFORE DELETE ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_estado ENUM('activo','convertido','abandonado','cancelado');
    SELECT estado INTO v_estado FROM carritos WHERE id_carrito = OLD.id_carrito;
    IF v_estado IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Carrito inexistente';
    END IF;
    IF v_estado <> 'activo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar el detalle de un carrito no activo';
    END IF;
END$$

-- AFTER INSERT: recalcular totales del carrito
CREATE TRIGGER trg_cardet_ai_recalc
AFTER INSERT ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_desc DECIMAL(10,2);
    DECLARE v_imp DECIMAL(10,2);
    DECLARE v_id INT;
    SET v_id = NEW.id_carrito;

    SELECT 
      COALESCE(SUM(cantidad*precio_unitario),0),
      COALESCE(SUM(descuento_monto),0),
      COALESCE(SUM(GREATEST(0, (cantidad*precio_unitario - descuento_monto)) * (tasa_impuesto/100)),0)
    INTO v_subtotal, v_desc, v_imp
    FROM carritos_detalle
    WHERE id_carrito = v_id;

    UPDATE carritos
    SET subtotal = v_subtotal,
        descuento_total = v_desc,
        impuesto_total = v_imp,
        total = ROUND(v_subtotal - v_desc - cupon_descuento + v_imp + envio_monto, 2)
    WHERE id_carrito = v_id;
END$$

-- AFTER UPDATE: recalcular totales del carrito
CREATE TRIGGER trg_cardet_au_recalc
AFTER UPDATE ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_desc DECIMAL(10,2);
    DECLARE v_imp DECIMAL(10,2);
    DECLARE v_id INT;
    SET v_id = NEW.id_carrito;

    SELECT 
      COALESCE(SUM(cantidad*precio_unitario),0),
      COALESCE(SUM(descuento_monto),0),
      COALESCE(SUM(GREATEST(0, (cantidad*precio_unitario - descuento_monto)) * (tasa_impuesto/100)),0)
    INTO v_subtotal, v_desc, v_imp
    FROM carritos_detalle
    WHERE id_carrito = v_id;

    UPDATE carritos
    SET subtotal = v_subtotal,
        descuento_total = v_desc,
        impuesto_total = v_imp,
        total = ROUND(v_subtotal - v_desc - cupon_descuento + v_imp + envio_monto, 2)
    WHERE id_carrito = v_id;
END$$

-- AFTER DELETE: recalcular totales del carrito
CREATE TRIGGER trg_cardet_ad_recalc
AFTER DELETE ON carritos_detalle
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_desc DECIMAL(10,2);
    DECLARE v_imp DECIMAL(10,2);
    DECLARE v_id INT;
    SET v_id = OLD.id_carrito;

    SELECT 
      COALESCE(SUM(cantidad*precio_unitario),0),
      COALESCE(SUM(descuento_monto),0),
      COALESCE(SUM(GREATEST(0, (cantidad*precio_unitario - descuento_monto)) * (tasa_impuesto/100)),0)
    INTO v_subtotal, v_desc, v_imp
    FROM carritos_detalle
    WHERE id_carrito = v_id;

    UPDATE carritos
    SET subtotal = v_subtotal,
        descuento_total = v_desc,
        impuesto_total = v_imp,
        total = ROUND(v_subtotal - v_desc - cupon_descuento + v_imp + envio_monto, 2)
    WHERE id_carrito = v_id;
END$$

DELIMITER ;
