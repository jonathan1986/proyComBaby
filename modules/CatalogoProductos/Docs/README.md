# Documentación Técnica - Módulo Catálogo de Productos

## Estructura de Carpetas
- Controllers: Lógica de negocio y validaciones
- Models: Acceso a datos y entidades
- Views: Interfaces HTML/CSS/JS
- Tests: Pruebas unitarias y funcionales
- Docs: Documentación y scripts SQL

## Esquema de Base de Datos
Ver `catalogo_productos_schema.sql` para tablas, relaciones y restricciones.

## Principales Clases
- Producto, Categoria, Atributo (Models)
- ProductoController, CategoriaController, AtributoController (Controllers)

## Validaciones
- Frontend: HTML5 y JS para tipos y rangos
- Backend: Validaciones estrictas en controladores

## Pruebas
- PHPUnit para tests unitarios de modelos

## Extensión
- El módulo sigue arquitectura modular y puede integrarse fácilmente en sistemas monolito.

## Búsqueda y Filtrado
- Implementado en modelo Producto y en la vista principal.

## Recomendaciones
- Refactorizar y extender usando principios SOLID y Clean Code.
- Mantener la documentación y los tests actualizados.
