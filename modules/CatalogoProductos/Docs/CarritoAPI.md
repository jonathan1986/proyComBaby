# API Carrito de Compras

Este módulo expone dos endpoints PHP (orientados a `application/json` o `x-www-form-urlencoded`):

- `modules/CatalogoProductos/Controllers/carrito_api.php`: gestiona cabecera del carrito
- `modules/CatalogoProductos/Controllers/carrito_items_api.php`: gestiona ítems del carrito

Nota: Puedes enviar `usuario` o `session_token` para validación opcional de pertenencia del carrito.

## carrito_api.php

GET
- Por id: `?id=123` (opcional `&usuario=1` o `&session_token=abc...`)
- Por usuario o token: `?usuario=1` o `?session_token=abc...`

Nota: auto-expiración
- En las lecturas GET, si el carrito está en estado `abierto` pero su `fecha_actualizacion` es anterior al umbral configurado (`carrito.expiracion_dias`), será marcado como `expirado` y la respuesta será 404 con `{"error":"Carrito expirado"}`. Esto evita usar carritos antiguos de forma transparente.

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
 - `?id_carrito=123&count=1` → `{ lineas, cantidad_total }` (conteo optimizado; no devuelve listado)

POST (agregar ítem)
- Query: `?id_carrito=123` (opcional `&usuario=1` o `&session_token=...`)
- Body: `{ id_producto, cantidad, precio_unit? }` (si `precio_unit` no se envía o es 0, se toma de `productos.precio`)

PUT/PATCH (actualizar ítem)
- Query: `?id_carrito=123[&id_producto=456]` (opcional `&usuario=1` o `&session_token=...`)
- Body: `{ id_producto?, cantidad, precio_unit? }`

DELETE (eliminar ítem)
- Query: `?id_carrito=123&id_producto=456` (opcional `&usuario=1` o `&session_token=...`)
 - Vaciar todo: `?id_carrito=123&empty=1` (DELETE) → `{ success, emptied: true }`

## Notas de negocio y seguridad
- Totales del carrito se recalculan vía triggers en DB tras INSERT/UPDATE/DELETE de ítems.
- Validaciones de entrada devuelven HTTP 400 con mensaje claro.
- Pertenencia del carrito (usuario/token) aplicada opcionalmente; si la proporcionas y no corresponde, HTTP 403.
- Las imágenes provienen de `vista_carrito_items` y se resuelve la imagen principal del producto.
 - `count=1` usa consulta agregada (COUNT / SUM) eficiente.
 - `empty=1` elimina todas las líneas en una sola operación y pone totales a cero.

## carrito_merge_api.php (fusionar carrito anónimo con carrito de usuario)

Endpoint: `modules/CatalogoProductos/Controllers/carrito_merge_api.php`

Uso principal tras login: fusionar el carrito asociado a un `session_token` anónimo dentro del carrito abierto del usuario autenticado. Si el usuario no tiene carrito abierto, se reasigna el carrito anónimo al usuario. Si no existe carrito anónimo, retorna el carrito del usuario (creándolo si tampoco existe).

Método: `POST`

Body (JSON o form):
```
{
	"session_token": "<token_anónimo>",
	"id_usuario": 123
}
```

Respuesta exitosa:
```
{
	"success": true,
	"message": "Carritos fusionados" | "Carrito anónimo reasignado al usuario" | "No había carrito anónimo que fusionar",
	"carrito": { ...carrito_final... },
	"stats": {
		"items_fusionados": <int>,   // productos ya existentes cuya cantidad se sumó
		"items_agregados": <int>,    // productos nuevos incorporados
		"items_omitidos": <int>,     // productos no agregados por alcanzar límite
		"warnings": ["mensaje", ...]
	}
}
```

Reglas de fusión:
1. Si no hay carrito anónimo con ese token: se devuelve (o crea) el carrito del usuario.
2. Si el usuario no posee carrito abierto: el carrito anónimo se reasigna (`id_usuario` se setea y `session_token` pasa a NULL).
3. Si ambos existen: se recorren las líneas del carrito anónimo:
	 - Si el producto ya está en el carrito destino: se suma la cantidad.
	 - Si no está y no se ha alcanzado el límite de líneas: se inserta la línea.
	 - Si se alcanzó el límite: se omite la línea y se registra en `warnings`.
4. El carrito fuente se marca `cancelado` y sus líneas se eliminan (decisión simplificada; si se requiriera auditoría histórica de líneas, podría omitirse el DELETE y sólo marcar un flag).
5. Se recalculan los totales del carrito destino (los triggers ya actualizan por operación, se realiza una pasada final por consistencia).

Límites y consideraciones:
- Límite de líneas configurado actualmente en el backend: 200 (coherente con `carrito_items_api.php`).
- Si múltiples fusiones se realizan casi simultáneamente, las transacciones minimizan inconsistencias. Para escenarios de alta concurrencia podría evaluarse un `SELECT ... FOR UPDATE` sobre la fila del carrito destino.
- Mantiene descuentos/impuestos del carrito destino (se ignoran los del carrito anónimo). Si se requiere mezclar reglas, habría que definir política (por ahora se prefiere consistencia del carrito ya asociado al usuario).

Errores:
- 400 si faltan `session_token` o `id_usuario`.
- 405 si método ≠ POST.
- 500 en error interno de fusión (incluye mensaje).

Alias rápido vía `carrito_api.php`:
Puedes también invocar la fusión haciendo `POST carrito_api.php?action=merge` con el mismo body; internamente redirige al endpoint principal de merge. Esto facilita tener un único endpoint base si tu frontend ya abstrae todo sobre `carrito_api.php`.

### Configuración del límite de líneas
El valor máximo de líneas distintas permitido en un carrito ahora está centralizado en `config/app.php` (clave `carrito.max_lineas`).
Puedes sobreescribirlo estableciendo la variable de entorno `CARRITO_MAX_LINEAS`. Si no se define o es inválida, se usa el default (200).
Ambos endpoints (`carrito_items_api.php` y `carrito_merge_api.php`) leen este valor dinámicamente, evitando duplicaciones de constantes.

Ejemplo de cURL:
```
curl -X POST -H 'Content-Type: application/json' \
	-d '{"session_token":"abc123","id_usuario":42}' \
	http://localhost/modules/CatalogoProductos/Controllers/carrito_merge_api.php
```


	## Mantenimiento: Expiración de carritos

	Endpoint: `modules/CatalogoProductos/Controllers/carrito_expiracion.php`

	Marca como `expirado` los carritos con estado `abierto` cuya `fecha_actualizacion` es anterior a N días (configurable).

	Seguridad: requiere token de mantenimiento. Debes definir `CARRITO_MAINT_TOKEN` en el entorno o en `config/app.php` (clave `carrito.mantenimiento_token`).

	Parámetros:
	- `token` (query) o header `X-Maint-Token`: debe coincidir con el configurado.

	Configuración:
	- Días de expiración: `config/app.php` → `carrito.expiracion_dias` (ENV `CARRITO_EXP_DIAS`). Default 30.

	Respuesta:
	```
	{
		"success": true,
		"expirados": 12,
		"ids": [1, 5, 9, ...],
		"dias": 30
	}
	```

	Uso recomendado (cron):
	```
	curl -s "http://localhost/modules/CatalogoProductos/Controllers/carrito_expiracion.php?token=TU_TOKEN"
	```

	Con Docker Compose actual (web en 8080), el URL por defecto es:
	```
	http://localhost:8080/modules/CatalogoProductos/Controllers/carrito_expiracion.php?token=TU_TOKEN
	```

	### Índice recomendado para expiración
	Para acelerar las consultas de expiración, añade un índice en `carritos(estado, fecha_actualizacion)`.

	- Ya está incluido en el schema base (`sql/catalogo_productos_schema.sql`).
	- Para entornos existentes, aplica el patch condicional:

	```
	mysql -h 127.0.0.1 -P 3306 -u jonathan -p babylovec < sql/patch_add_idx_carritos_estado_fecha.sql
	```

	Sustituye credenciales según tu entorno. Con Docker, puedes entrar al contenedor `db` y ejecutar la sentencia.

	## Auditoría (carrito_logs)

	Se registra automáticamente un log por acciones relevantes del carrito:
	- crear (al crear carrito)
	- actualizar_cabecera (al actualizar totales/estado)
	- agregar_item, actualizar_item, eliminar_item, vaciar (operaciones en ítems)
	- eliminar_carrito (DELETE carrito)
	- merge (reasignación o fusión de carritos)
	- expirar (cuando el mantenimiento expira un carrito)

	Tabla: `carrito_logs` (id_carrito, accion, detalles JSON, usuario_id, session_token, ip, user_agent, fecha)

	Notas:
	- detalles almacena los parámetros relevantes (por ejemplo, producto, cantidad, stats del merge, etc.).
	- No guardes datos sensibles en `detalles`.
	- Puedes depurar operaciones o construir reportes/alertas usando esta tabla.

	### Consulta de logs (solo mantenimiento)
	Endpoint: `modules/CatalogoProductos/Controllers/carrito_logs_api.php`

	Seguridad: requiere el token de mantenimiento (`token` en query o header `X-Maint-Token`).

	Parámetros opcionales:
	- `id_carrito`: filtra por carrito
	- `accion`: una de las acciones del log
	- `desde`, `hasta`: rango de fecha (YYYY-MM-DD o DATETIME)
	- `page`, `pageSize` (default 1 y 50, máx 200)

	Ejemplo:
	```
	curl "http://localhost:8080/modules/CatalogoProductos/Controllers/carrito_logs_api.php?token=TU_TOKEN&id_carrito=123&accion=agregar_item&desde=2025-01-01&page=1&pageSize=50"
	```

	Vista HTML simple (requiere token):
	- `modules/CatalogoProductos/Views/carrito_logs.html`
	- Carga por HTTP y utiliza el header `X-Maint-Token` para autenticar.
	- Incluye botón "Purgar logs" (con confirmación) que invoca el endpoint de purga; puedes indicar opcionalmente los días de retención desde la UI.

	Exportación CSV:
	- Añade `format=csv` a la URL y el `token` en query (para descarga directa):
	```
	http://localhost:8080/modules/CatalogoProductos/Controllers/carrito_logs_api.php?token=TU_TOKEN&format=csv&accion=vaciar&desde=2025-01-01
	```
	La vista HTML incluye un botón “Exportar CSV” que construye esta URL con los filtros actuales.

	## Impuestos (modo multi)

	Además del modo simple (un solo porcentaje en `carritos.impuesto_pct`), el módulo soporta múltiples impuestos por producto con desglose.

	### Activación
	- Cada carrito tiene `carritos.impuestos_modo` con valores `simple` (default) o `multi`.
	- Para activar en un carrito existente:
	```
	UPDATE carritos SET impuestos_modo='multi' WHERE id_carrito=...;
	```
	- Al mutar ítems en modo `multi`, el backend recalcula automáticamente los impuestos usando el SP `sp_recalcular_impuestos_carrito`.

	### Administración (APIs y panel)
	- Catálogo de impuestos (token mantenimiento requerido):
	  - `modules/CatalogoProductos/Controllers/impuestos_api.php`
	    - GET: lista todos o `?codigo=IVA`
	    - POST: crear `{ codigo, nombre, tipo('porcentaje'|'fijo'), valor, aplica_sobre('base_descuento'|'subtotal'), activo }`
	    - PATCH/PUT: actualizar por `?id=...`
	    - DELETE: eliminar por `?id=...`
	- Asignación a productos (token mantenimiento requerido):
	  - `modules/CatalogoProductos/Controllers/productos_impuestos_api.php`
	    - GET `?id_producto=...`: lista impuestos asignados
	    - POST `{ id_producto, id_impuesto }`: asignar
	    - DELETE `?id_producto=...&id_impuesto=...`: quitar
	- Panel HTML simple:
	  - `modules/CatalogoProductos/Views/impuestos_admin.html`
	  - Permite: crear/editar/borrar impuestos, buscar productos y asignar/quitar impuestos.

	### Cálculo y desglose
	- Tablas nuevas:
	  - `impuestos`, `productos_impuestos`, `carrito_items_impuestos` (snapshot por ítem), `carritos_impuestos` (snapshot por carrito)
	- Cálculo en modo `multi`:
	  - Base por ítem = subtotal_línea menos descuento proporcional (si descuento fijo) o menos descuento %.
	  - Para cada impuesto del producto:
	    - porcentaje: `monto = base * (valor/100)`
	    - fijo: `monto = valor * cantidad`
	  - Los montos se agrupan en `carritos_impuestos` y se actualiza `carritos.impuesto_total` y `carritos.total`.
	- API carrito (GET): incluye `carrito.impuestos_desglose` cuando `impuestos_modo='multi'`.
	- Vista `carrito.html`: muestra un bloque “Desglose impuestos” bajo el total cuando la API envía el desglose.

	### Ejemplos
	- Crear IVA (18%):
	```
	curl -X POST \
	  -H "Content-Type: application/json" \
	  -H "X-Maint-Token: TU_TOKEN" \
	  -d '{"codigo":"IVA","nombre":"Impuesto al Valor Agregado","tipo":"porcentaje","valor":18.00,"aplica_sobre":"base_descuento","activo":1}' \
	  http://localhost:8080/modules/CatalogoProductos/Controllers/impuestos_api.php
	```
	- Asignar IVA a producto 123:
	```
	curl -X POST \
	  -H "Content-Type: application/json" \
	  -H "X-Maint-Token: TU_TOKEN" \
	  -d '{"id_producto":123,"id_impuesto":<ID_IVA>}' \
	  http://localhost:8080/modules/CatalogoProductos/Controllers/productos_impuestos_api.php
	```
	- Activar multi en carrito:
	```
	mysql> UPDATE carritos SET impuestos_modo='multi' WHERE id_carrito=<ID>;
	```

	### Migración y schema
	- El schema base ya incluye las tablas y el SP. Para bases existentes aplica el patch condicional:
	  - `sql/patch_add_multi_impuestos.sql`

	### Retención y purga de logs
	Endpoint: `modules/CatalogoProductos/Controllers/carrito_logs_purge.php`

	Elimina registros de `carrito_logs` con fecha anterior a N días (retención). Similar a la expiración de carritos.

	Seguridad: requiere token de mantenimiento (`token` en query o header `X-Maint-Token`).

	Parámetros:
	- `dias` (opcional): días de retención a aplicar. Si no se envía, usa la configuración.

	Configuración:
	- `config/app.php` → `carrito.logs_retencion_dias` (ENV `CARRITO_LOGS_RET_DIAS`). Default 90.

	Respuesta:
	```
	{
	  "success": true,
	  "dias": 90,
	  "antes": 1200,
	  "eliminados": 1180
	}
	```

	Uso recomendado (cron):
	```
	curl -s "http://localhost:8080/modules/CatalogoProductos/Controllers/carrito_logs_purge.php?token=TU_TOKEN"
	```

	Test de integración de export CSV (opcional):
	- Script: `modules/CatalogoProductos/Docs/tests/export_logs_csv_test.sh`
	- Uso:
	```
	BASE_URL="http://localhost:8080" TOKEN="TU_TOKEN" bash modules/CatalogoProductos/Docs/tests/export_logs_csv_test.sh
	```

