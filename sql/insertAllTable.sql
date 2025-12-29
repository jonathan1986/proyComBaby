INSERT INTO categorias (id_categoria, nombre, descripcion, id_categoria_padre, estado) VALUES
(1, 'Ropa', 'Ropa de niños', NULL, 1),
(2, 'Vestido', 'Vestidos para niñas', 1, 1);

INSERT INTO atributos (id_atributo, nombre, tipo, estado) VALUES
(1, 'Edad', 'string', 1),
(2, 'Talla', 'string', 1),
(3, 'Color', 'string', 1),
(4, 'Material', 'string', 1);

INSERT INTO proveedores (id_proveedor, nombre, contacto, telefono, email, direccion, ciudad, ruc, estado, fecha_creacion, fecha_actualizacion, usuario_creacion, usuario_actualizacion) VALUES
(1, 'ModaBebe', 'Kelly Andrade', '0912345678', 'kelly@hotmail.com', 'Inca', 'Quito', '0975614452001', 1, '2025-10-01 01:08:54', '2025-10-01 01:08:54', '', NULL),
(2, 'Angelitos', 'Ruddy Jackeline', '591123456', 'ruddy@hotmail.com', 'Gabarra', 'Lima', '123456789', 1, '2025-10-01 01:10:14', '2025-10-01 01:10:14', '', NULL);

INSERT INTO productos (id_producto, nombre, descripcion, precio, stock, stock_minimo, estado, fecha_creacion, fecha_actualizacion) VALUES
(1, 'Vestido Nohelia', 'algodon', 17.00, 20, 2, 1, '2025-10-01 01:11:23', '2025-10-01 01:11:23'),
(2, 'Vestido Diana', 'algodon suave', 21.00, 21, 2, 1, '2025-10-03 01:04:30', '2025-10-03 01:04:30'),
(3, 'Vestido Thalia', 'Algodon duro', 22.00, 22, 2, 1, '2025-10-03 01:18:01', '2025-10-03 01:18:01'),
(4, 'Vestido Briana', 'algodon 23', 23.00, 23, 2, 1, '2025-10-03 01:32:11', '2025-10-03 01:32:11'),
(5, 'Vestido Jhoysi', 'algodon 25', 25.00, 25, 2, 1, '2025-10-03 02:09:23', '2025-10-03 02:09:23'),
(6, 'Vestido Carmita', 'algodon 26', 26.00, 26, 2, 1, '2025-10-03 02:18:03', '2025-10-03 02:18:03'),
(7, 'Vestido Carolina', 'Vestido niña algodon', 28.00, 28, 2, 1, '2025-10-04 01:06:28', '2025-10-04 01:06:28');

INSERT INTO imagenes_productos (id_imagen, id_producto, archivo_imagen, principal, estado) VALUES
(1, 1, 'uploads/productos/img_68dc801b21ae1.jpg', 1, 1),
(2, 2, 'uploads/productos/img_68df214f7b52c.jpg', 1, 1),
(3, 3, 'uploads/productos/img_68df24b5a80a5.jpg', 1, 1),
(4, 4, 'uploads/productos/img_68df27e74bb34.jpg', 1, 1),
(5, 6, 'uploads/productos/img_68df329f81c34.jpg', 1, 1),
(6, 7, 'uploads/productos/img_68e07359a6251.jpg', 1, 1);

INSERT INTO productos_categorias (id_producto, id_categoria, estado) VALUES
(1, 2, 1),
(2, 2, 1),
(3, 2, 1),
(4, 2, 1),
(6, 2, 1),
(7, 2, 1);


INSERT INTO productos_atributos (id_producto, id_atributo, valor, estado) VALUES
(1, 1, '1 año', 1),
(1, 2, '1m', 1),
(1, 3, 'Rosado', 1),
(1, 4, 'Algodon', 1),
(2, 1, '2 meses', 1),
(2, 2, '2 m', 1),
(2, 3, 'Verde', 1),
(2, 4, 'Algodon', 1),
(3, 1, '3', 1),
(3, 2, '3m', 1),
(3, 3, 'Color amarillo', 1),
(3, 4, 'Algodon', 1),
(4, 1, '44', 1),
(4, 2, '4 m', 1),
(4, 3, 'lila', 1),
(4, 4, 'Algodon', 1),
(6, 1, '26 meses', 1),
(6, 2, '26 m', 1),
(6, 3, 'Naranja', 1),
(6, 4, 'Algodon', 1),
(7, 1, '8 meses', 1),
(7, 2, '8 m', 1),
(7, 3, 'Azul', 1),
(7, 4, 'Polialgodon', 1);

INSERT INTO productos_proveedores (id_producto, id_proveedor, estado) VALUES
(3, 2, 1),
(4, 1, 1),
(6, 2, 1),
(7, 2, 1);


INSERT INTO pedidos_reabastecimiento (id_pedido, fecha, id_proveedor, estado, observaciones) VALUES
(1, '2025-10-04 04:09:22', 1, 'recibido', 'compra de octubre'),
(2, '2025-10-04 04:12:08', 2, 'recibido', 'pedido octubre'),
(3, '2025-10-04 04:30:29', 2, 'recibido', 'compra de octubre'),
(4, '2025-10-04 04:34:26', 2, 'recibido', 'pedido octubre'),
(5, '2025-10-04 04:38:23', 2, 'recibido', 'pedido octubre'),
(6, '2025-10-04 04:53:02', 2, 'recibido', 'pedido octubre'),
(7, '2025-10-04 05:07:10', 2, 'recibido', 'pedido octubre'),
(8, '2025-10-04 05:32:32', 2, 'recibido', 'pedido');


INSERT INTO pedidos_reabastecimiento_detalle (id_pedido, id_producto, cantidad, precio_unitario, precio_venta) VALUES
(1, 6, 15, 16.00, 26.00),
(2, 2, 7, 13.15, 23.00),
(2, 4, 5, 14.00, 24.00),
(2, 7, 6, 13.00, 25.00),
(3, 1, 7, 12.00, 22.00),
(3, 7, 5, 13.15, 25.00),
(4, 5, 10, 11.00, 21.00),
(5, 3, 8, 13.00, 23.00),
(6, 4, 6, 15.00, 24.00),
(7, 1, 6, 11.00, 22.00),
(8, 3, 6, 12.00, 23.00);


INSERT INTO inventario_movimientos (id_movimiento, id_producto, fecha, tipo, cantidad, motivo, id_pedido, usuario) VALUES
(1, 7, '2025-10-04 01:30:44', 'entrada', 28, 'Ingreso de mercaderia', NULL, ''),
(2, 6, '2025-10-04 04:19:06', 'entrada', 15, 'recepción pedido', 1, 'system'),
(3, 2, '2025-10-04 04:19:31', 'entrada', 4, 'recepción parcial pedido 2', NULL, ''),
(4, 4, '2025-10-04 04:19:31', 'entrada', 2, 'recepción parcial pedido 2', NULL, ''),
(5, 7, '2025-10-04 04:19:31', 'entrada', 2, 'recepción parcial pedido 2', NULL, ''),
(6, 2, '2025-10-04 04:20:43', 'entrada', 3, 'recepción parcial pedido 2', NULL, ''),
(7, 4, '2025-10-04 04:20:43', 'entrada', 3, 'recepción parcial pedido 2', NULL, ''),
(8, 7, '2025-10-04 04:20:43', 'entrada', 3, 'recepción parcial pedido 2', NULL, ''),
(9, 2, '2025-10-04 04:21:19', 'entrada', 7, 'recepción pedido', 2, 'system'),
(10, 4, '2025-10-04 04:21:19', 'entrada', 5, 'recepción pedido', 2, 'system'),
(11, 7, '2025-10-04 04:21:19', 'entrada', 6, 'recepción pedido', 2, 'system'),
(12, 1, '2025-10-04 04:30:47', 'entrada', 2, 'recepción parcial pedido 3', NULL, ''),
(13, 7, '2025-10-04 04:30:47', 'entrada', 1, 'recepción parcial pedido 3', NULL, ''),
(14, 1, '2025-10-04 04:31:20', 'entrada', 2, 'recepción parcial pedido 3', NULL, ''),
(15, 7, '2025-10-04 04:31:20', 'entrada', 2, 'recepción parcial pedido 3', NULL, ''),
(16, 1, '2025-10-04 04:31:46', 'entrada', 3, 'recepción parcial pedido 3', NULL, ''),
(17, 7, '2025-10-04 04:31:46', 'entrada', 2, 'recepción parcial pedido 3', NULL, ''),
(18, 1, '2025-10-04 04:32:58', 'entrada', 7, 'recepción pedido', 3, 'system'),
(19, 7, '2025-10-04 04:32:58', 'entrada', 5, 'recepción pedido', 3, 'system'),
(20, 5, '2025-10-04 04:34:44', 'entrada', 2, 'recepción parcial pedido 4', NULL, ''),
(21, 5, '2025-10-04 04:35:20', 'entrada', 5, 'recepción parcial pedido 4', NULL, ''),
(22, 5, '2025-10-04 04:36:00', 'entrada', 10, 'recepción pedido', 4, 'system'),
(23, 3, '2025-10-04 04:39:06', 'entrada', 2, 'recepción parcial pedido 5', NULL, ''),
(24, 3, '2025-10-04 04:41:37', 'entrada', 4, 'recepción parcial pedido 5', NULL, ''),
(25, 3, '2025-10-04 04:42:33', 'entrada', 8, 'recepción pedido', 5, 'system');

INSERT INTO `impuestos` (`id_impuesto`, `codigo`, `nombre`, `tipo`, `valor`, `aplica_sobre`, `activo`) VALUES
(1, 'IVA 15', 'Impuesto al valor agregado 15', 'porcentaje', 15.0000, 'base_descuento', 1),
(2, 'IVA 12', 'Impuesto al valor agregado 12', 'porcentaje', 12.0000, 'base_descuento', 1),
(3, 'ICE 9', 'Impuesto al consumo especial 9', 'porcentaje', 9.0000, 'base_descuento', 1);
