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