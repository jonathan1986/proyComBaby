# Catalogo de Productos Web

Este proyecto es una aplicación web para gestionar y mostrar un catálogo de productos. A continuación se detallan las características y la estructura del proyecto.

## Estructura del Proyecto

```
catalogo-productos-web
├── src
│   ├── pages
│   │   ├── index.html        # Página de inicio de la aplicación web
│   │   └── catalogo.html     # Página principal del catálogo que muestra los productos
│   ├── scripts
│   │   ├── api
│   │   │   └── productosService.js  # Funciones para interactuar con la API del sistema de gestión de productos
│   │   ├── components
│   │   │   └── productoCard.js      # Función para crear y renderizar tarjetas de productos
│   │   └── catalogo.js              # Inicializa la página del catálogo y renderiza los productos
│   ├── styles
│   │   ├── base.css                  # Estilos base para la aplicación web
│   │   └── catalogo.css              # Estilos específicos para la página del catálogo
│   └── data
│       └── categorias.json           # Archivo JSON que contiene las categorías de productos
└── README.md                         # Documentación del proyecto
```

## Instalación

1. Clona el repositorio en tu máquina local.
2. Abre el archivo `index.html` en un navegador para ver la página de inicio.
3. Navega a la página del catálogo para ver los productos disponibles.

## Características

- **Página de inicio**: Proporciona enlaces a la página del catálogo y otras secciones relevantes.
- **Catálogo de productos**: Muestra productos dinámicamente utilizando tarjetas de productos.
- **Interacción con API**: Utiliza un servicio para obtener datos de productos y categorías.
- **Estilos responsivos**: Diseño adaptativo para una mejor experiencia en dispositivos móviles y de escritorio.

## Uso

- Puedes buscar productos utilizando el formulario de búsqueda en la página del catálogo.
- Haz clic en los productos para ver más detalles o añadirlos al carrito.

## Contribuciones

Las contribuciones son bienvenidas. Si deseas contribuir, por favor abre un issue o envía un pull request.

## Licencia

Este proyecto está bajo la Licencia MIT.