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
-- Índices para optimizar búsqueda
CREATE INDEX idx_producto_nombre ON productos(nombre);
CREATE INDEX idx_producto_precio ON productos(precio);
CREATE INDEX idx_categoria_nombre ON categorias(nombre);
CREATE INDEX idx_atributo_nombre ON atributos(nombre);