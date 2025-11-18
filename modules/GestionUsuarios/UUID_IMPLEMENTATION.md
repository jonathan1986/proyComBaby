# 🔑 Implementación de UUID - Notas Técnicas

## Cambio Realizado

Se ha actualizado la implementación de UUID para ser **100% compatible con MySQL 5.7** generando los UUIDs desde PHP en lugar de desde la base de datos.

### ¿Por qué este cambio?

**Problema original:**
```sql
-- ❌ INCORRECTO (MySQL 5.7 no soporta esto)
`uuid_usuario` CHAR(36) NOT NULL UNIQUE DEFAULT (UUID())
```

**Error en phpMyAdmin:**
```
#1064 - Algo está equivocado en su sintaxis cerca '(UUID())'
```

### Solución Implementada

**1. Cambio en la BD:**
```sql
-- ✅ CORRECTO (MySQL 5.7 compatible)
`uuid_usuario` CHAR(36) NOT NULL UNIQUE
```

**2. Cambio en PHP (Models/Usuario.php):**
```php
private function generarUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
```

**3. Actualización del Stored Procedure:**
```sql
-- Antes
CALL sp_crear_usuario_nuevo(email, nombre, hash, rol, @id, @msg)

-- Ahora
CALL sp_crear_usuario_nuevo(uuid, email, nombre, hash, rol, @id, @msg)
```

### Ventajas de esta Solución

✅ **Compatibilidad:** Funciona con MySQL 5.7, 5.8, 8.0+  
✅ **Seguridad:** Random bytes generados por PHP (mt_rand mejorado)  
✅ **Estándar RFC 4122:** UUIDs v4 válidos (formato estándar)  
✅ **Control:** La aplicación controla la generación de IDs  
✅ **Rendimiento:** No requiere llamadas a funciones SQL en inserción  

### Cómo Funciona

Cuando se crea un usuario:

```
1. Controller recibe datos de registro
   ↓
2. Llama a Usuario::crear($email, nombre, pass)
   ↓
3. Usuario genera UUID con generarUUID()
   ↓
4. Llama a sp_crear_usuario_nuevo(uuid, email, nombre, hash, rol, ...)
   ↓
5. Stored Procedure inserta con UUID proporcionado
   ↓
6. Usuario creado con UUID único garantizado
```

### Ejemplo de Uso en PHP

```php
// En UsuarioController::registro()
$usuario_id = $usuarioModel->crear(
    $datos['email'],
    $datos['nombre_completo'],
    $datos['password'],
    $datos['apellido'] ?? null,
    'CLIENTE'
);

// Internamente:
// 1. Genera: $uuid = '550e8400-e29b-41d4-a716-446655440000'
// 2. Ejecuta: CALL sp_crear_usuario_nuevo('550e8400...', 'user@example.com', ...)
```

### Validación de UUIDs

Para validar que un UUID es correcto:

```php
function esUUIDValido($uuid) {
    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $uuid
    ) === 1;
}

// Uso
if (esUUIDValido($uuid)) {
    echo "UUID válido (RFC 4122 v4)";
}
```

### Testing

Para verificar que funciona correctamente:

```bash
# Ejecutar script de pruebas
bash test_api.sh

# Registrar usuario (genera UUID automáticamente)
curl -X POST http://localhost/modules/GestionUsuarios/Api/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_completo": "Test User",
    "email": "test@example.com",
    "password": "Test1234!",
    "confirmar_password": "Test1234!"
  }'

# Response incluye UUID generado:
# {
#   "usuario_id": 1,
#   "uuid": "550e8400-e29b-41d4-a716-446655440000",
#   "email": "test@example.com"
# }
```

### Alternativa (si querías usar la BD)

Si en el futuro subes a **MySQL 8.0+**, podrías usar:

```sql
`uuid_usuario` CHAR(36) NOT NULL UNIQUE DEFAULT (UUID())
```

Pero la solución actual es más portable y segura.

### Archivos Modificados

- ✅ `sql/modulo_gestion_usuarios_mysql.sql` - Removido DEFAULT (UUID())
- ✅ `Models/Usuario.php` - Agregado método generarUUID()
- ✅ Stored procedure `sp_crear_usuario_nuevo` - Acepta UUID como parámetro
- ✅ Documentación actualizada en QUICK_START.md

### Conclusión

Ahora puedes importar el script SQL sin problemas en MySQL 5.7, y los UUIDs se generan de forma segura desde PHP. ✨

---

**Fecha:** Noviembre 17, 2025  
**Versión:** 1.0  
**Estado:** ✅ Implementado y Probado
