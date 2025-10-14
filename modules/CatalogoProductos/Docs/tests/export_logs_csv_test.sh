#!/usr/bin/env bash
set -euo pipefail
# Pequeño test de integración para export CSV de carrito_logs
# Requiere: BASE_URL y TOKEN en el entorno
# Ejemplo:
#   BASE_URL="http://localhost:8080" TOKEN="mi_token" ./export_logs_csv_test.sh

: "${BASE_URL:?Define BASE_URL (p.ej. http://localhost:8080)}"
: "${TOKEN:?Define TOKEN de mantenimiento}"

URL="${BASE_URL}/modules/CatalogoProductos/Controllers/carrito_logs_api.php?format=csv&token=${TOKEN}&desde=$(date -u +%Y-01-01)"

echo "Descargando CSV desde: $URL" >&2
http_code=$(curl -sS -o /tmp/carrito_logs.csv -w "%{http_code}" "$URL")

if [ "$http_code" != "200" ]; then
  echo "Fallo HTTP ($http_code)" >&2
  exit 1
fi

# Validar que el archivo tiene encabezados esperados
head -n 1 /tmp/carrito_logs.csv | grep -q "id_log,id_carrito,accion,detalles,usuario_id,session_token,ip,user_agent,fecha" && \
  echo "OK: CSV con encabezados correctos" || \
  (echo "ERROR: encabezados inesperados"; exit 1)

# Mostrar primeras 3 líneas como vista previa
echo "--- Preview ---"
head -n 3 /tmp/carrito_logs.csv
