# API Carrito de Compras

Este módulo expone dos endpoints PHP (orientados a `application/json` o `x-www-form-urlencoded`):

- `modules/CatalogoProductos/Controllers/carrito_api.php`: gestiona cabecera del carrito
- `modules/CatalogoProductos/Controllers/carrito_items_api.php`: gestiona ítems del carrito

Nota: Puedes enviar `usuario` o `session_token` para validación opcional de pertenencia del carrito.

## carrito_api.php

GET
- Por id: `?id=123` (opcional `&usuario=1` o `&session_token=abc...`)
- Por usuario o token: `?usuario=1` o `?session_token=abc...`

POST (crear)
- Body: `{ id_usuario?, session_token?, moneda?, impuesto_pct?, descuento_pct?, descuento_monto? }`
- Respuesta: `{ success, carrito }`

PUT/PATCH (actualizar cabecera)
- Query: `?id=123` (opcional `&usuario=1` o `&session_token=...`)
- Body: `{ impuesto_pct?, descuento_pct?, descuento_monto?, estado? }`

DELETE
- Query: `?id=123` (opcional `&usuario=1` o `&session_token=...`)

## carrito_items_api.php

Requiere `id_carrito` en query.

GET
- `?id_carrito=123` → `{ items: [ { id_item, id_producto, cantidad, precio_unit, subtotal_linea, imagen_principal } ] }`

POST (agregar ítem)
- Query: `?id_carrito=123` (opcional `&usuario=1` o `&session_token=...`)
- Body: `{ id_producto, cantidad, precio_unit? }` (si `precio_unit` no se envía o es 0, se toma de `productos.precio`)

PUT/PATCH (actualizar ítem)
- Query: `?id_carrito=123[&id_producto=456]` (opcional `&usuario=1` o `&session_token=...`)
- Body: `{ id_producto?, cantidad, precio_unit? }`

DELETE (eliminar ítem)
- Query: `?id_carrito=123&id_producto=456` (opcional `&usuario=1` o `&session_token=...`)

## Notas de negocio y seguridad
- Totales del carrito se recalculan vía triggers en DB tras INSERT/UPDATE/DELETE de ítems.
- Validaciones de entrada devuelven HTTP 400 con mensaje claro.
- Pertenencia del carrito (usuario/token) aplicada opcionalmente; si la proporcionas y no corresponde, HTTP 403.
- Las imágenes provienen de `vista_carrito_items` y se resuelve la imagen principal del producto.
