# Cambios en la Tabla Proveedores - Enero 2026

## Resumen de Cambios

Se han agregado los siguientes campos a la tabla `proveedores` para enriquecer la información de los proveedores del sistema:

## Nuevos Campos

| Campo | Tipo | Obligatorio | Descripción | Valores/Ejemplo |
|-------|------|-------------|-------------|-----------------|
| `pais` | VARCHAR(100) | ✅ Sí | País del proveedor | Colombia, Argentina, etc. |
| `descripcion` | TEXT | ✅ Sí | Descripción del proveedor | Información sobre el negocio |
| `pagina_web` | VARCHAR(255) | ❌ No | Página web del proveedor | https://www.ejemplo.com |
| `tipo_proveedor` | ENUM | ✅ Sí | Tipo o categoría del proveedor | Distribuidor, Fabricante, Mayorista, Minorista, Servicios, Otro |
| `regimen_iva` | ENUM | ❌ No | Régimen de IVA del proveedor | Régimen Común, Régimen Simplificado, Gran Contribuyente, No Obligado |
| `es_sin_animo_lucro` | BOOLEAN | ❌ No | Si es entidad sin ánimo de lucro | true/false (default: false) |
| `representante_legal` | VARCHAR(150) | ❌ No | Nombre del representante legal | Juan Pérez García |

## Cambios de Estructura

### Tabla: `proveedores`

**Campos agregados (en orden):**
1. `pais` (después de `ruc`)
2. `descripcion`
3. `pagina_web`
4. `tipo_proveedor`
5. `regimen_iva`
6. `es_sin_animo_lucro`
7. `representante_legal`

### Índices Agregados

Se han creado los siguientes índices para optimizar búsquedas:
- `idx_pais` - Para búsquedas por país
- `idx_tipo_proveedor` - Para búsquedas por tipo de proveedor
- `idx_regimen_iva` - Para búsquedas por régimen IVA

## Archivos Modificados

1. **sql/MASTER_install.sql** - Actualizado con los nuevos campos en la definición CREATE TABLE
2. **sql/patch_proveedores_campos_adicionales.sql** - Nuevo archivo con script de migración idempotente

## Cómo Aplicar los Cambios

### Opción 1: Nueva Instalación
Si estás haciendo una instalación nueva, simplemente ejecuta:
```bash
mysql -u usuario -p base_datos < sql/MASTER_install.sql
```

### Opción 2: Base de Datos Existente
Si ya tienes una base de datos instalada, ejecuta solo el patch:
```bash
mysql -u usuario -p base_datos < sql/patch_proveedores_campos_adicionales.sql
```

## Valores Predeterminados

- `pais`: 'Colombia'
- `tipo_proveedor`: 'Distribuidor'
- `es_sin_animo_lucro`: FALSE

## Notas Importantes

1. Los campos `pais` y `descripcion` son **obligatorios** - asegúrate de proporcionar valores al insertar/actualizar proveedores
2. El campo `pagina_web` debe incluir el protocolo (http:// o https://)
3. Se recomienda usar los valores predefinidos en los ENUMs para mantener consistencia
4. Los campos de auditoría (`fecha_creacion`, `usuario_creacion`, etc.) se mantienen como estaban

## Verificación

Para verificar que los cambios se aplicaron correctamente, ejecuta:
```sql
DESCRIBE proveedores;
```

Deberías ver todos los nuevos campos listados con sus respectivos tipos y propiedades.

## Rollback (si es necesario)

Si necesitas revertir los cambios:
```sql
ALTER TABLE proveedores DROP COLUMN representante_legal;
ALTER TABLE proveedores DROP COLUMN es_sin_animo_lucro;
ALTER TABLE proveedores DROP COLUMN regimen_iva;
ALTER TABLE proveedores DROP COLUMN tipo_proveedor;
ALTER TABLE proveedores DROP COLUMN pagina_web;
ALTER TABLE proveedores DROP COLUMN descripcion;
ALTER TABLE proveedores DROP COLUMN pais;
DROP INDEX idx_pais ON proveedores;
DROP INDEX idx_tipo_proveedor ON proveedores;
DROP INDEX idx_regimen_iva ON proveedores;
```
