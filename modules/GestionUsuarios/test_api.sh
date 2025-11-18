#!/usr/bin/env bash

# ============================================================
# Script de Testing - Módulo de Gestión de Usuarios
# ============================================================
# Este script ejecuta todos los endpoints para validar que
# el módulo está funcionando correctamente.
#
# Uso: bash test_api.sh
# ============================================================

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
API_URL="${1:-http://localhost/modules/GestionUsuarios/Api}"
TIMESTAMP=$(date +%s)
EMAIL="test${TIMESTAMP}@example.com"
PASSWORD="TestPassword123!"

# Tokens
TOKEN=""
USER_ID=""

# Función para imprimir títulos
print_title() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

# Función para imprimir resultados
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
        echo "Error: $3"
    fi
}

# Función para ejecutar requests
execute_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local token=$4
    
    if [ -z "$data" ]; then
        # GET request sin datos
        curl -s -X "$method" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"} \
            "$API_URL$endpoint"
    else
        # POST/PUT request con datos
        curl -s -X "$method" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"} \
            -d "$data" \
            "$API_URL$endpoint"
    fi
}

# ============================================================
# PRUEBA 1: Conexión a API
# ============================================================
print_title "Prueba 1: Conexión a API"
echo "Verificando conectividad a: $API_URL"

response=$(curl -s -w "\n%{http_code}" -X GET "$API_URL/usuarios")
http_code=$(echo "$response" | tail -n 1)

if [ "$http_code" -eq 404 ] || [ "$http_code" -eq 200 ]; then
    print_result 0 "API accesible" "HTTP $http_code"
else
    print_result 1 "API no accesible" "HTTP $http_code"
    echo "Verifica que el módulo esté instalado en: $API_URL"
    exit 1
fi

# ============================================================
# PRUEBA 2: Registro de Usuario
# ============================================================
print_title "Prueba 2: Registro de Usuario"
echo "Email: $EMAIL"

data="{
    \"nombre_completo\": \"Test Usuario\",
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\",
    \"confirmar_password\": \"$PASSWORD\",
    \"apellido\": \"Test\"
}"

response=$(execute_request "POST" "/usuarios/registro" "$data")
echo "Response: $response"

# Extraer usuario_id del response
USER_ID=$(echo "$response" | grep -o '"usuario_id":[0-9]*' | head -1 | cut -d: -f2)

if [ -n "$USER_ID" ] && [ "$USER_ID" -gt 0 ]; then
    print_result 0 "Usuario registrado" "ID: $USER_ID"
else
    print_result 1 "Error registrando usuario" "$response"
fi

# ============================================================
# PRUEBA 3: Login
# ============================================================
print_title "Prueba 3: Login"
echo "Email: $EMAIL"

data="{
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\"
}"

response=$(execute_request "POST" "/usuarios/login" "$data")
echo "Response: $response"

# Extraer token
TOKEN=$(echo "$response" | grep -o '"token":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
    print_result 0 "Login exitoso" "Token: ${TOKEN:0:20}..."
else
    print_result 1 "Error en login" "$response"
fi

# ============================================================
# PRUEBA 4: Obtener Perfil
# ============================================================
print_title "Prueba 4: Obtener Perfil"
echo "Usuario ID: $USER_ID"

response=$(execute_request "GET" "/usuarios/$USER_ID" "" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"email\""; then
    print_result 0 "Perfil obtenido" "OK"
else
    print_result 1 "Error obteniendo perfil" "$response"
fi

# ============================================================
# PRUEBA 5: Actualizar Perfil
# ============================================================
print_title "Prueba 5: Actualizar Perfil"

data="{
    \"nombre_completo\": \"Test Usuario Actualizado\",
    \"ciudad\": \"Bogotá\",
    \"pais\": \"Colombia\",
    \"telefono\": \"3001234567\"
}"

response=$(execute_request "PUT" "/usuarios/$USER_ID/perfil" "$data" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Perfil actualizado" "OK"
else
    print_result 1 "Error actualizando perfil" "$response"
fi

# ============================================================
# PRUEBA 6: Cambiar Contraseña
# ============================================================
print_title "Prueba 6: Cambiar Contraseña"
NEW_PASSWORD="NuevaPassword456!"

data="{
    \"password_antigua\": \"$PASSWORD\",
    \"password_nueva\": \"$NEW_PASSWORD\",
    \"confirmar_password\": \"$NEW_PASSWORD\"
}"

response=$(execute_request "POST" "/usuarios/$USER_ID/cambiar-contrasena" "$data" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Contraseña cambiada" "OK"
    PASSWORD=$NEW_PASSWORD  # Actualizar para futuras pruebas
else
    print_result 1 "Error cambiando contraseña" "$response"
fi

# ============================================================
# PRUEBA 7: Solicitar Recuperación de Contraseña
# ============================================================
print_title "Prueba 7: Solicitar Recuperación"

data="{
    \"email\": \"$EMAIL\"
}"

response=$(execute_request "POST" "/usuarios/recuperar-contrasena" "$data")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Recuperación solicitada" "Email enviado"
else
    print_result 1 "Error solicitando recuperación" "$response"
fi

# ============================================================
# PRUEBA 8: Validar Sesión
# ============================================================
print_title "Prueba 8: Validar Sesión"

data="{
    \"token\": \"$TOKEN\"
}"

response=$(execute_request "POST" "/usuarios/validar-sesion" "$data" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Sesión válida" "OK"
else
    print_result 1 "Sesión inválida" "$response"
fi

# ============================================================
# PRUEBA 9: Listar Roles
# ============================================================
print_title "Prueba 9: Listar Roles"

response=$(execute_request "GET" "/roles" "" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Roles listados" "OK"
else
    print_result 1 "Error listando roles" "$response"
fi

# ============================================================
# PRUEBA 10: Listar Permisos
# ============================================================
print_title "Prueba 10: Listar Permisos"

response=$(execute_request "GET" "/permisos" "" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Permisos listados" "OK"
else
    print_result 1 "Error listando permisos" "$response"
fi

# ============================================================
# PRUEBA 11: Obtener Pedidos (si existen)
# ============================================================
print_title "Prueba 11: Obtener Pedidos"

response=$(execute_request "GET" "/usuarios/$USER_ID/pedidos" "" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Pedidos obtenidos" "OK"
else
    print_result 1 "Error obteniendo pedidos" "$response"
fi

# ============================================================
# PRUEBA 12: Obtener Permisos del Usuario
# ============================================================
print_title "Prueba 12: Obtener Permisos del Usuario"

response=$(execute_request "GET" "/usuarios/$USER_ID/permisos" "" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Permisos del usuario obtenidos" "OK"
else
    print_result 1 "Error obteniendo permisos" "$response"
fi

# ============================================================
# PRUEBA 13: Logout
# ============================================================
print_title "Prueba 13: Logout"

data="{
    \"token\": \"$TOKEN\"
}"

response=$(execute_request "POST" "/usuarios/logout" "$data" "$TOKEN")
echo "Response: $response"

if echo "$response" | grep -q "\"codigo\":200"; then
    print_result 0 "Logout exitoso" "Sesión terminada"
else
    print_result 1 "Error en logout" "$response"
fi

# ============================================================
# RESUMEN
# ============================================================
print_title "Resumen de Testing"
echo -e "${GREEN}Testing completado${NC}"
echo ""
echo "Información del test:"
echo "- API URL: $API_URL"
echo "- Email de test: $EMAIL"
echo "- Usuario ID: $USER_ID"
echo ""
echo "Próximos pasos:"
echo "1. Verifica los logs en: modules/GestionUsuarios/logs/"
echo "2. Accede a login.html para probar el frontend"
echo "3. Revisa README.md para más detalles"
echo ""
