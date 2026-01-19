-- MASTER_install.sql (MySQL 5.7 / Percona)
-- Generado automáticamente: 2025-10-26
-- Este archivo consolida todos los scripts SQL del proyecto en un único instalador.
-- Objetivo: Ejecutar de una sola vez sobre una base vacía; en entornos existentes, los parches son idempotentes.
-- NOTA IMPORTANTE (idempotencia):
--   - El bloque de esquema base (tablas/vistas/triggers en schema_babylovec.sql) usa CREATE sin IF NOT EXISTS y puede fallar si ya existen.
--   - En bases ya instaladas, comenta la sección del esquema base y deja solo los patch_* (que son condicionales).
--   - MySQL 5.7 no soporta CREATE OR REPLACE TRIGGER/INDEX; por eso los patches usan procedimientos con information_schema.

-- (Opcional) Selección de base de datos
-- USE babylovec;

-- Ajustes de sesión recomendados
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- ============== [origen: schema_babylovec.sql] ==============
-- ============================================================
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


-- Tabla de países
CREATE TABLE paises (
    id_pais INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del país',
    nombre VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre del país',
    codigo_iso_2 CHAR(2) NOT NULL COMMENT 'Código ISO 3166-1 alpha-2 (ej: CO, US, MX)',
    codigo_iso_3 CHAR(3) NOT NULL COMMENT 'Código ISO 3166-1 alpha-3 (ej: COL, USA, MEX)',
    codigo_telefono VARCHAR(5) COMMENT 'Código de teléfono internacional',
    activo TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo',
    INDEX idx_paises_nombre (nombre),
    INDEX idx_paises_codigo_iso (codigo_iso_2, codigo_iso_3)
) COMMENT='Catálogo de países';

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
    id_pais INT COMMENT 'ID del país del proveedor',
    descripcion TEXT NOT NULL COMMENT 'Descripción del proveedor',
    pagina_web VARCHAR(255) COMMENT 'Página web del proveedor',
    tipo_proveedor ENUM('Distribuidor','Fabricante','Mayorista','Minorista','Servicios','Otro') NOT NULL DEFAULT 'Distribuidor' COMMENT 'Tipo o categoría del proveedor',
    regimen_iva ENUM('Régimen Común','Régimen Simplificado','Gran Contribuyente','No Obligado') COMMENT 'Régimen de IVA del proveedor',
    es_sin_animo_lucro BOOLEAN DEFAULT FALSE COMMENT 'Indica si la entidad es sin ánimo de lucro',
    representante_legal VARCHAR(150) COMMENT 'Nombre del representante legal',
    estado TINYINT DEFAULT 1 NOT NULL COMMENT '1=activo, 0=inactivo (borrado lógico)',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
    usuario_creacion VARCHAR(50) COMMENT 'Usuario que creó el registro',
    usuario_actualizacion VARCHAR(50) COMMENT 'Usuario que actualizó el registro',
    FOREIGN KEY (id_pais) REFERENCES paises(id_pais),
    ADD INDEX idx_tipo_proveedor (tipo_proveedor),
    ADD INDEX idx_regimen_iva (regimen_iva
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
    precio_unitario DECIMAL(10,2) DEFAULT 0 COMMENT 'Precio de compra al proveedor',
    precio_venta DECIMAL(10,2) DEFAULT 0 COMMENT 'Precio de venta al público en el momento del pedido',
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

-- =========================
-- MÓDULO: CARRITO DE COMPRAS
-- =========================

-- Tabla de carritos
CREATE TABLE carritos (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID del carrito',
    id_usuario INT NULL COMMENT 'Usuario autenticado (opcional)',
    session_token VARCHAR(64) NULL UNIQUE COMMENT 'Token de sesión anónima (opcional)',
    estado ENUM('abierto','confirmado','cancelado','expirado') NOT NULL DEFAULT 'abierto' COMMENT 'Estado del carrito',
    moneda CHAR(3) NOT NULL DEFAULT 'USD' COMMENT 'Moneda ISO 4217',
    impuestos_modo ENUM('simple','multi') NOT NULL DEFAULT 'simple' COMMENT 'Cómo calcular impuestos: simple=impuesto_pct, multi=catálogo/desglose',
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

-- AFTER INSERT (modo simple solamente)
DROP TRIGGER IF EXISTS trg_ci_ai $$
CREATE TRIGGER trg_ci_ai
AFTER INSERT ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_modo VARCHAR(6);
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT impuestos_modo, descuento_pct, descuento_monto, impuesto_pct
      INTO v_modo, v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = NEW.id_carrito;

    IF v_modo <> 'multi' THEN
        SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
          FROM carrito_items WHERE id_carrito = NEW.id_carrito;

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
    END IF;
END $$

-- AFTER UPDATE (modo simple solamente)
DROP TRIGGER IF EXISTS trg_ci_au $$
CREATE TRIGGER trg_ci_au
AFTER UPDATE ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_modo VARCHAR(6);
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT impuestos_modo, descuento_pct, descuento_monto, impuesto_pct
      INTO v_modo, v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = NEW.id_carrito;

    IF v_modo <> 'multi' THEN
        SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
          FROM carrito_items WHERE id_carrito = NEW.id_carrito;

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
    END IF;
END $$

-- AFTER DELETE (modo simple solamente)
DROP TRIGGER IF EXISTS trg_ci_ad $$
CREATE TRIGGER trg_ci_ad
AFTER DELETE ON carrito_items
FOR EACH ROW
BEGIN
    DECLARE v_modo VARCHAR(6);
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_desc_pct DECIMAL(5,2);
    DECLARE v_desc_mto DECIMAL(10,2);
    DECLARE v_imp_pct DECIMAL(5,2);
    DECLARE v_desc_total DECIMAL(12,2);
    DECLARE v_base DECIMAL(12,2);
    DECLARE v_impuesto DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT impuestos_modo, descuento_pct, descuento_monto, impuesto_pct
      INTO v_modo, v_desc_pct, v_desc_mto, v_imp_pct
    FROM carritos WHERE id_carrito = OLD.id_carrito;

    IF v_modo <> 'multi' THEN
        SELECT COALESCE(SUM(subtotal_linea),0) INTO v_subtotal
          FROM carrito_items WHERE id_carrito = OLD.id_carrito;

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
    END IF;
END $$

DELIMITER ;

-- =========================
-- MÓDULO: IMPUESTOS (multi-impuestos y desglose)
-- =========================

-- Catálogo de impuestos
CREATE TABLE IF NOT EXISTS impuestos (
    id_impuesto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,4) NOT NULL,
    aplica_sobre ENUM('subtotal','base_descuento') NOT NULL DEFAULT 'base_descuento',
    activo TINYINT NOT NULL DEFAULT 1
) COMMENT='Catálogo de impuestos';

-- Asignación N:M de impuestos por producto
CREATE TABLE IF NOT EXISTS productos_impuestos (
    id_producto INT NOT NULL,
    id_impuesto INT NOT NULL,
    PRIMARY KEY (id_producto, id_impuesto),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT='Impuestos aplicables por producto';

-- Desglose por ítem de carrito (snapshot)
CREATE TABLE IF NOT EXISTS carrito_items_impuestos (
    id_item INT NOT NULL,
    id_impuesto INT NOT NULL,
    base DECIMAL(12,2) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id_item, id_impuesto),
    FOREIGN KEY (id_item) REFERENCES carrito_items(id_item) ON DELETE CASCADE,
    FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT='Desglose de impuestos por item';

-- Desglose por carrito (snapshot)
CREATE TABLE IF NOT EXISTS carritos_impuestos (
    id_carrito INT NOT NULL,
    id_impuesto INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id_carrito, id_impuesto),
    FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,
    FOREIGN KEY (id_impuesto) REFERENCES impuestos(id_impuesto) ON DELETE RESTRICT
) COMMENT='Desglose de impuestos por carrito';

-- ============================================================
-- ===== [origen: patch_add_precio_venta_reabastecimiento.sql] =====
-- ============================================================
-- Patch para agregar campo precio_venta a pedidos_reabastecimiento_detalle
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_precio_venta_reabastecimiento $$
CREATE PROCEDURE sp_add_precio_venta_reabastecimiento()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  
  SELECT COUNT(*) INTO v_count
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pedidos_reabastecimiento_detalle'
    AND COLUMN_NAME = 'precio_venta';
    
  IF v_count = 0 THEN
    SET @sql = 'ALTER TABLE pedidos_reabastecimiento_detalle 
                ADD COLUMN precio_venta DECIMAL(10,2) DEFAULT 0 
                COMMENT ''Precio de venta al público en el momento del pedido''
                AFTER precio_unitario';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Inicializar con el precio actual de productos
    UPDATE pedidos_reabastecimiento_detalle prd
    JOIN productos p ON p.id_producto = prd.id_producto
    SET prd.precio_venta = p.precio
    WHERE prd.precio_venta = 0 OR prd.precio_venta IS NULL;
  END IF;
END $$
DELIMITER ;
CALL sp_add_precio_venta_reabastecimiento();
DROP PROCEDURE sp_add_precio_venta_reabastecimiento;

-- ============================================================
-- ===== [origen: patch_add_idx_carritos_estado_fecha.sql] =====
-- ============================================================
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

-- ============================================================
-- ========= [origen: patch_add_carrito_logs.sql] =============
-- ============================================================
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

-- ============================================================
-- ========= [origen: patch_add_multi_impuestos.sql] ==========
-- ============================================================
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
    SET @sql = 'ALTER TABLE carritos ADD COLUMN impuestos_modo ENUM(''simple'',''multi'') NOT NULL DEFAULT ''simple'' AFTER moneda';
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
  DECLARE v_modo VARCHAR(6);
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

  -- Recalcular ítem-impuestos (respetando aplica_sobre: 'subtotal' o 'base_descuento')
  INSERT INTO carrito_items_impuestos (id_item, id_impuesto, base, monto)
  SELECT
    ci.id_item,
    pi.id_impuesto,
    CASE i.aplica_sobre
      WHEN 'subtotal' THEN ci.subtotal_linea
      ELSE GREATEST(
        CASE WHEN v_desc_mto > 0 AND v_subtotal > 0
             THEN ci.subtotal_linea - ROUND((ci.subtotal_linea / v_subtotal) * v_desc_mto, 2)
             ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea * (v_desc_pct/100), 2)
        END,
        0
      )
    END AS base_linea,
    CASE i.tipo
      WHEN 'porcentaje' THEN ROUND(
        (CASE i.aplica_sobre
           WHEN 'subtotal' THEN ci.subtotal_linea
           ELSE GREATEST(
                  CASE WHEN v_desc_mto > 0 AND v_subtotal > 0
                       THEN ci.subtotal_linea - ROUND((ci.subtotal_linea / v_subtotal) * v_desc_mto, 2)
                       ELSE ci.subtotal_linea - ROUND(ci.subtotal_linea * (v_desc_pct/100), 2)
                  END,
                  0
                )
         END) * (i.valor/100), 2)
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
DELIMITER ;

-- Fin del MASTER_install.sql
