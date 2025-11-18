#!/usr/bin/env powershell
<#
.SYNOPSIS
    Script para importar el módulo de Gestión de Usuarios en la base de datos
    
.DESCRIPTION
    Importa el DDL (Data Definition Language) del módulo de Gestión de Usuarios
    Compatible con MySQL 5.7+ y Docker
    
.PARAMETER Host
    Host del servidor MySQL (default: localhost)
    
.PARAMETER User
    Usuario de MySQL (default: root)
    
.PARAMETER Password
    Contraseña de MySQL (default: root)
    
.PARAMETER Database
    Base de datos destino (default: babylovec)
    
.EXAMPLE
    .\import_db.ps1 -Host localhost -User root -Password root -Database babylovec
#>

param(
    [string]$Host = "localhost",
    [string]$User = "root",
    [string]$Password = "root",
    [string]$Database = "babylovec"
)

# Colores
$Green = "`e[32m"
$Red = "`e[31m"
$Yellow = "`e[33m"
$Blue = "`e[34m"
$Reset = "`e[0m"

# Función para imprimir mensajes
function Write-Log {
    param([string]$Message, [string]$Type = "INFO")
    
    switch ($Type) {
        "SUCCESS" { Write-Host "${Green}✓ $Message${Reset}" }
        "ERROR" { Write-Host "${Red}✗ $Message${Reset}" }
        "WARNING" { Write-Host "${Yellow}⚠ $Message${Reset}" }
        "INFO" { Write-Host "${Blue}ℹ $Message${Reset}" }
        default { Write-Host $Message }
    }
}

Write-Log "================================" "INFO"
Write-Log "Importación - Módulo Gestión de Usuarios" "INFO"
Write-Log "================================" "INFO"
Write-Log ""

# Verificar que mysql está disponible
if (-not (Get-Command mysql -ErrorAction SilentlyContinue)) {
    Write-Log "MySQL no está disponible en PATH" "ERROR"
    Write-Log "Soluciones:" "INFO"
    Write-Log "1. Si usas Docker: docker exec -i <container> mysql -u $User -p $Database < sql/modulo_gestion_usuarios_mysql.sql" "INFO"
    Write-Log "2. Instala MySQL Client" "INFO"
    exit 1
}

$SqlFile = "sql/modulo_gestion_usuarios_mysql.sql"

if (-not (Test-Path $SqlFile)) {
    Write-Log "Archivo SQL no encontrado: $SqlFile" "ERROR"
    Write-Log "Asegúrate de ejecutar este script desde la raíz del proyecto" "INFO"
    exit 1
}

Write-Log "Archivo SQL encontrado: $SqlFile" "SUCCESS"
Write-Log ""

# Intentar conexión
Write-Log "Conectando a MySQL..." "INFO"
Write-Log "  Host: $Host" "INFO"
Write-Log "  Usuario: $User" "INFO"
Write-Log "  Base de datos: $Database" "INFO"
Write-Log ""

# Ejecutar importación
try {
    Write-Log "Importando DDL..." "INFO"
    
    # Construir comando
    $cmd = "mysql -h $Host -u $User -p$Password $Database < $SqlFile"
    
    # Ejecutar
    Invoke-Expression $cmd -ErrorAction Stop
    
    Write-Log "Importación completada exitosamente" "SUCCESS"
    Write-Log ""
    
    # Verificación
    Write-Log "Verificando tablas creadas..." "INFO"
    $verifyCmd = "mysql -h $Host -u $User -p$Password -e 'SHOW TABLES;' $Database"
    Invoke-Expression $verifyCmd
    
    Write-Log ""
    Write-Log "✅ El módulo está listo para usar" "SUCCESS"
    Write-Log ""
    Write-Log "Próximos pasos:" "INFO"
    Write-Log "1. Crear Api/index.php (entrypoint)" "INFO"
    Write-Log "2. Configurar URLs en frontend (Assets/js/auth.js)" "INFO"
    Write-Log "3. Acceder a Views/login.html" "INFO"
    
} catch {
    Write-Log "Error durante la importación: $_" "ERROR"
    Write-Log ""
    Write-Log "Intenta manualmente:" "INFO"
    Write-Log "mysql -h $Host -u $User -p $Database < sql/modulo_gestion_usuarios_mysql.sql" "INFO"
    exit 1
}
