-- MySQL 5.7 (Percona Server)
-- Script SQL para módulo Catálogo de Productos
-- Creación de tablas y relaciones


-- Tabla de categorías de productos (jerárquica)
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único de la categoría',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre de la categoría',
    descripcion TEXT COMMENT 'Descripción de la categoría',
    id_categoria_padre INT COMMENT 'ID de la categoría padre (jerarquía)',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    FOREIGN KEY (id_categoria_padre) REFERENCES categorias(id_categoria)
) COMMENT='Categorías de productos';


-- Tabla de productos
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del producto',
    nombre VARCHAR(150) NOT NULL COMMENT 'Nombre del producto',
    descripcion TEXT COMMENT 'Descripción del producto',
    precio DECIMAL(10,2) NOT NULL COMMENT 'Precio del producto',
    stock INT NOT NULL COMMENT 'Stock actual',
    stock_minimo INT DEFAULT 0 COMMENT 'Stock mínimo permitido',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización'
) COMMENT='Productos del catálogo';


-- Relación N:M entre productos y categorías
CREATE TABLE productos_categorias (
    id_producto INT NOT NULL COMMENT 'ID del producto',
    id_categoria INT NOT NULL COMMENT 'ID de la categoría',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    PRIMARY KEY (id_producto, id_categoria),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE
) COMMENT='Relación productos-categorías';


-- Imágenes asociadas a productos
CREATE TABLE imagenes_productos (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único de la imagen',
    id_producto INT NOT NULL COMMENT 'ID del producto',
    archivo_imagen VARCHAR(255) NOT NULL COMMENT 'Nombre o ruta relativa del archivo de imagen',
    principal BOOLEAN DEFAULT FALSE COMMENT 'Indica si es la imagen principal',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
) COMMENT='Imágenes de productos';


-- Tabla de atributos de productos
CREATE TABLE atributos (
    id_atributo INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del atributo',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del atributo',
    tipo ENUM('string','int','float','bool','date') NOT NULL COMMENT 'Tipo de dato del atributo',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)'
) COMMENT='Atributos de productos';


-- Relación N:M entre productos y atributos
CREATE TABLE productos_atributos (
    id_producto INT NOT NULL COMMENT 'ID del producto',
    id_atributo INT NOT NULL COMMENT 'ID del atributo',
    valor VARCHAR(255) NOT NULL COMMENT 'Valor del atributo para el producto',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    PRIMARY KEY (id_producto, id_atributo),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_atributo) REFERENCES atributos(id_atributo) ON DELETE CASCADE
) COMMENT='Relación productos-atributos';


-- Tabla de proveedores
CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del proveedor',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del proveedor',
    contacto VARCHAR(100) COMMENT 'Persona de contacto',
    telefono VARCHAR(30) COMMENT 'Teléfono',
    email VARCHAR(100) COMMENT 'Correo electrónico',
    direccion VARCHAR(255) COMMENT 'Dirección',
    ciudad VARCHAR(100) COMMENT 'Ciudad',
    ruc VARCHAR(20) COMMENT 'RUC',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
    usuario_creacion VARCHAR(50) COMMENT 'Usuario que creó el registro',
    usuario_actualizacion VARCHAR(50) COMMENT 'Usuario que actualizó el registro'
) COMMENT='Proveedores de productos';

-- Relación N:M entre productos y proveedores
CREATE TABLE productos_proveedores (
    id_producto INT NOT NULL COMMENT 'ID del producto',
    id_proveedor INT NOT NULL COMMENT 'ID del proveedor',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    PRIMARY KEY (id_producto, id_proveedor),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor) ON DELETE CASCADE
) COMMENT='Relación productos-proveedores';


-- Tabla de pedidos de reabastecimiento a proveedores
CREATE TABLE pedidos_reabastecimiento (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del pedido',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del pedido',
    id_proveedor INT NOT NULL COMMENT 'Proveedor al que se realiza el pedido',
    estado ENUM('pendiente','recibido','cancelado') DEFAULT 'pendiente' COMMENT 'Estado del pedido',
    observaciones TEXT,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
) COMMENT='Pedidos de reabastecimiento a proveedores';

-- Detalle de cada producto solicitado en un pedido de reabastecimiento
CREATE TABLE pedidos_reabastecimiento_detalle (
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) DEFAULT 0,
    PRIMARY KEY (id_pedido, id_producto),
    FOREIGN KEY (id_pedido) REFERENCES pedidos_reabastecimiento(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) COMMENT='Detalle de productos en pedidos de reabastecimiento';

-- Tabla de movimientos de inventario (entradas/salidas)
CREATE TABLE inventario_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del movimiento',
    id_producto INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada','salida','ajuste') NOT NULL COMMENT 'Tipo de movimiento',
    cantidad INT NOT NULL COMMENT 'Cantidad (positiva para entrada, negativa para salida)',
    motivo VARCHAR(255) COMMENT 'Motivo o referencia (ej: venta, compra, ajuste, pedido, etc.)',
    id_pedido INT NULL COMMENT 'Si aplica, referencia al pedido de reabastecimiento',
    usuario VARCHAR(50) COMMENT 'Usuario que realizó el movimiento',
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_pedido) REFERENCES pedidos_reabastecimiento(id_pedido)
) COMMENT='Movimientos de inventario (entradas/salidas/ajustes)';

-- Índice para consultas rápidas por producto y fecha
CREATE INDEX idx_mov_producto_fecha ON inventario_movimientos(id_producto, fecha);

-- Vista para control de stock disponible (puedes usarla en consultas)
CREATE OR REPLACE VIEW vista_stock_disponible AS
SELECT
    p.id_producto,
    p.nombre,
    p.stock_minimo,
    COALESCE(SUM(m.cantidad), 0) AS stock_disponible
FROM productos p
LEFT JOIN inventario_movimientos m ON p.id_producto = m.id_producto
GROUP BY p.id_producto, p.nombre, p.stock_minimo;

-- Ejemplo de consulta para alertas de bajo stock:
-- SELECT * FROM vista_stock_disponible WHERE stock_disponible <= stock_minimo;

-- Opcional: tabla de alertas de bajo stock (para registrar cuándo se generó una alerta)
CREATE TABLE alertas_bajo_stock (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    stock_actual INT NOT NULL,
    stock_minimo INT NOT NULL,
    atendida TINYINT DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) COMMENT='Alertas de bajo stock generadas automáticamente';

-- Índices para optimizar búsqueda
CREATE INDEX idx_producto_nombre ON productos(nombre);
CREATE INDEX idx_producto_precio ON productos(precio);
CREATE INDEX idx_categoria_nombre ON categorias(nombre);
CREATE INDEX idx_atributo_nombre ON atributos(nombre);

DELIMITER $$

CREATE TRIGGER trg_alerta_bajo_stock
AFTER INSERT ON inventario_movimientos
FOR EACH ROW
BEGIN
    DECLARE v_stock INT;
    DECLARE v_stock_minimo INT;

    -- Calcula el stock actual del producto
    SELECT COALESCE(SUM(cantidad),0) INTO v_stock
    FROM inventario_movimientos
    WHERE id_producto = NEW.id_producto;

    -- Obtiene el stock mínimo configurado
    SELECT stock_minimo INTO v_stock_minimo
    FROM productos
    WHERE id_producto = NEW.id_producto;

    -- Si el stock es menor o igual al mínimo, y no existe una alerta activa, crea una alerta
    IF v_stock <= v_stock_minimo THEN
        IF NOT EXISTS (
            SELECT 1 FROM alertas_bajo_stock
            WHERE id_producto = NEW.id_producto AND atendida = 0
        ) THEN
            INSERT INTO alertas_bajo_stock (id_producto, stock_actual, stock_minimo)
            VALUES (NEW.id_producto, v_stock, v_stock_minimo);
        END IF;
    END IF;
END$$

DELIMITER ;


-- Índice recomendado para mantenimiento y auto-expiración (añadido al schema base)
CREATE INDEX idx_carritos_estado_fecha ON carritos(estado, fecha_actualizacion);

-- Tabla de auditoría de cambios en carritos
CREATE TABLE carrito_logs (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT NOT NULL,
    accion ENUM('crear','actualizar_cabecera','agregar_item','actualizar_item','eliminar_item','vaciar','eliminar_carrito','merge','expirar') NOT NULL,
    detalles JSON NULL,
    usuario_id INT NULL,
    session_token VARCHAR(64) NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_carrito_fecha (id_carrito, fecha),
    FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE
) COMMENT='Auditoría de operaciones de carritos';



-- =========================
-- MÓDULO: CARRITO DE COMPRAS
-- Requisitos cubiertos:
-- - Añadir/eliminar productos
-- - Actualizar cantidades
-- - Calcular subtotal, descuentos, impuestos y total
-- - Visualización de imágenes del producto en el carrito
-- Compatible con MySQL 5.7 (Percona Server)
-- =========================

-- Tabla de carritos
CREATE TABLE carritos (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID del carrito',
    id_usuario INT NULL COMMENT 'Usuario autenticado (opcional)',
    session_token VARCHAR(64) NULL UNIQUE COMMENT 'Token de sesión anónima (opcional)',
    estado ENUM('abierto','confirmado','cancelado','expirado') NOT NULL DEFAULT 'abierto' COMMENT 'Estado del carrito',
    moneda CHAR(3) NOT NULL DEFAULT 'USD' COMMENT 'Moneda ISO 4217',
    impuesto_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de impuesto aplicado (%)',
    descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de descuento aplicado (%)',
    descuento_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento fijo aplicado',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de líneas',
    descuento_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento total calculado',
    impuesto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Impuesto total calculado',
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total a pagar',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carritos_usuario (id_usuario),
    INDEX idx_carritos_estado (estado)
) COMMENT='Carritos de compras';

-- Ítems del carrito
CREATE TABLE carrito_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID de la línea',
    id_carrito INT NOT NULL COMMENT 'Carrito al que pertenece',
    id_producto INT NOT NULL COMMENT 'Producto agregado',
    cantidad INT NOT NULL COMMENT 'Cantidad solicitada (>0)',
    precio_unit DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario capturado al momento',
    subtotal_linea DECIMAL(12,2) NOT NULL COMMENT 'cantidad * precio_unit',
    UNIQUE KEY uk_carrito_producto (id_carrito, id_producto),
    INDEX idx_items_carrito (id_carrito),
    INDEX idx_items_producto (id_producto),
    FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) COMMENT='Líneas de productos en el carrito';

-- Vista para mostrar ítems del carrito con imagen principal del producto (o primera disponible)
CREATE OR REPLACE VIEW vista_carrito_items AS
SELECT
    ci.id_item,
    ci.id_carrito,
    ci.id_producto,
    p.nombre AS producto,
    ci.cantidad,
    ci.precio_unit,
    ci.subtotal_linea,
    COALESCE(
        (SELECT ip.archivo_imagen FROM imagenes_productos ip
          WHERE ip.id_producto = ci.id_producto AND ip.principal = TRUE
          ORDER BY ip.id_imagen ASC LIMIT 1),
        (SELECT ip2.archivo_imagen FROM imagenes_productos ip2
          WHERE ip2.id_producto = ci.id_producto
          ORDER BY ip2.id_imagen ASC LIMIT 1)
    ) AS imagen_principal
FROM carrito_items ci
JOIN productos p ON p.id_producto = ci.id_producto;

-- Triggers para calcular subtotales de líneas y totales del carrito
DELIMITER $$

-- BEFORE INSERT: setea precio_unit si viene nulo/cero y calcula subtotal_linea
CREATE TRIGGER trg_ci_bi
BEFORE INSERT ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    IF NEW.cantidad IS NULL OR NEW.cantidad <= 0 THEN
        SET NEW.cantidad = 1; -- asegura cantidad mínima
    END IF;
    IF NEW.precio_unit IS NULL OR NEW.precio_unit <= 0 THEN
        SELECT precio INTO v_precio FROM productos WHERE id_producto = NEW.id_producto;
        SET NEW.precio_unit = COALESCE(v_precio, 0.00);
    END IF;
    SET NEW.subtotal_linea = NEW.cantidad * NEW.precio_unit;
END$$

-- BEFORE UPDATE: recalcula subtotal_linea (y precio si cambió producto y venía 0)
CREATE TRIGGER trg_ci_bu
BEFORE UPDATE ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    IF NEW.cantidad IS NULL OR NEW.cantidad <= 0 THEN
        SET NEW.cantidad = 1;
    END IF;
    IF (NEW.id_producto <> OLD.id_producto) AND (NEW.precio_unit IS NULL OR NEW.precio_unit <= 0) THEN
        SELECT precio INTO v_precio FROM productos WHERE id_producto = NEW.id_producto;
        SET NEW.precio_unit = COALESCE(v_precio, 0.00);
    END IF;
    SET NEW.subtotal_linea = NEW.cantidad * NEW.precio_unit;
END$$

-- Procedimiento inline (en cada trigger) para recalcular totales del carrito
-- AFTER INSERT
CREATE TRIGGER trg_ci_ai
AFTER INSERT ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
    FROM carrito_items WHERE id_carrito = NEW.id_carrito;

    SELECT descuento_pct, descuento_monto, impuesto_pct
      INTO v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = NEW.id_carrito;

    SET v_desc_total = IF(v_desc_mto > 0, v_desc_mto, ROUND(v_subtotal * (v_desc_pct/100), 2));
    SET v_base = GREATEST(v_subtotal - v_desc_total, 0);
    SET v_impuesto = ROUND(v_base * (v_imp_pct/100), 2);
    SET v_total = v_base + v_impuesto;

    UPDATE carritos
       SET subtotal = v_subtotal,
           descuento_total = v_desc_total,
           impuesto_total = v_impuesto,
           total = v_total
     WHERE id_carrito = NEW.id_carrito;
END$$

-- AFTER UPDATE
CREATE TRIGGER trg_ci_au
AFTER UPDATE ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
    FROM carrito_items WHERE id_carrito = NEW.id_carrito;

    SELECT descuento_pct, descuento_monto, impuesto_pct
      INTO v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = NEW.id_carrito;

    SET v_desc_total = IF(v_desc_mto > 0, v_desc_mto, ROUND(v_subtotal * (v_desc_pct/100), 2));
    SET v_base = GREATEST(v_subtotal - v_desc_total, 0);
    SET v_impuesto = ROUND(v_base * (v_imp_pct/100), 2);
    SET v_total = v_base + v_impuesto;

    UPDATE carritos
       SET subtotal = v_subtotal,
           descuento_total = v_desc_total,
           impuesto_total = v_impuesto,
           total = v_total
     WHERE id_carrito = NEW.id_carrito;
END$$

-- AFTER DELETE
CREATE TRIGGER trg_ci_ad
AFTER DELETE ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
    FROM carrito_items WHERE id_carrito = OLD.id_carrito;

    SELECT descuento_pct, descuento_monto, impuesto_pct
      INTO v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = OLD.id_carrito;

    SET v_desc_total = IF(v_desc_mto > 0, v_desc_mto, ROUND(v_subtotal * (v_desc_pct/100), 2));
    SET v_base = GREATEST(v_subtotal - v_desc_total, 0);
    SET v_impuesto = ROUND(v_base * (v_imp_pct/100), 2);
    SET v_total = v_base + v_impuesto;

    UPDATE carritos
       SET subtotal = v_subtotal,
           descuento_total = v_desc_total,
           impuesto_total = v_impuesto,
           total = v_total
     WHERE id_carrito = OLD.id_carrito;
END$$

DELIMITER ;