// catalogo.js
// Ejemplo de renderizado y validación adicional

document.addEventListener('DOMContentLoaded', function() {
    // Simulación de productos (reemplazar por AJAX en integración real)
    const productos = [
        {id: 1, nombre: 'Producto 1', descripcion: 'Desc 1', precio: 100, stock: 10, stock_minimo: 2, estado: 'activo'},
        {id: 2, nombre: 'Producto 2', descripcion: 'Desc 2', precio: 200, stock: 5, stock_minimo: 1, estado: 'inactivo'}
    ];
    const contenedor = document.getElementById('productos');
    productos.forEach(p => {
        const card = document.createElement('div');
        card.className = 'producto-card';
        card.innerHTML = `<h3>${p.nombre}</h3><p>${p.descripcion}</p><strong>$${p.precio}</strong><br>Stock: ${p.stock}<br>Stock mínimo: ${p.stock_minimo}<br>Estado: ${p.estado}`;
        contenedor.appendChild(card);
    });
});
