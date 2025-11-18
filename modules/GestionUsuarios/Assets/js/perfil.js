/**
 * perfil.js - Funciones del perfil de usuario
 * Maneja carga, actualización y visualización de datos personales y pedidos
 */

const API_URL = '/modules/GestionUsuarios/Api';

// Variables globales
let usuarioId = null;
let token = null;

document.addEventListener('DOMContentLoaded', function() {
    // Obtener datos del localStorage
    usuarioId = localStorage.getItem('usuario_id');
    token = localStorage.getItem('token');
    
    // Validar sesión
    if (!usuarioId || !token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Cargar datos del usuario
    cargarPerfil();
    
    // Event listeners
    document.getElementById('formPerfil').addEventListener('submit', actualizarPerfil);
    document.getElementById('formCambiarContrasena').addEventListener('submit', cambiarContrasena);
    document.getElementById('btnLogout').addEventListener('click', logout);
});

/**
 * Cargar perfil de usuario
 */
function cargarPerfil() {
    fetch(API_URL + '/usuarios/' + usuarioId, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.codigo === 200) {
            const usuario = data.datos;
            
            // Llenar formulario
            document.getElementById('nombre_completo').value = usuario.nombre_completo || '';
            document.getElementById('apellido').value = usuario.apellido || '';
            document.getElementById('email').value = usuario.email || '';
            document.getElementById('numero_documento').value = usuario.numero_documento || '';
            document.getElementById('telefono').value = usuario.telefono || '';
            document.getElementById('celular').value = usuario.celular || '';
            
            if (usuario.perfil) {
                document.getElementById('pais').value = usuario.perfil.pais || '';
                document.getElementById('ciudad').value = usuario.perfil.ciudad || '';
                document.getElementById('direccion_principal').value = usuario.perfil.direccion_principal || '';
                document.getElementById('biografia').value = usuario.perfil.biografia || '';
            }
        }
    })
    .catch(err => console.error('Error cargando perfil:', err));
}

/**
 * Actualizar perfil
 */
function actualizarPerfil(e) {
    e.preventDefault();
    
    const datos = {
        nombre_completo: document.getElementById('nombre_completo').value,
        apellido: document.getElementById('apellido').value,
        numero_documento: document.getElementById('numero_documento').value,
        telefono: document.getElementById('telefono').value,
        celular: document.getElementById('celular').value,
        pais: document.getElementById('pais').value,
        ciudad: document.getElementById('ciudad').value,
        direccion_principal: document.getElementById('direccion_principal').value,
        biografia: document.getElementById('biografia').value
    };
    
    // Validar
    if (!datos.nombre_completo || datos.nombre_completo.length < 3) {
        mostrarAlerta('alertaPerfil', 'El nombre debe tener al menos 3 caracteres', 'warning');
        return;
    }
    
    const btn = document.querySelector('#formPerfil button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    
    fetch(API_URL + '/usuarios/' + usuarioId + '/perfil', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        
        if (data.codigo === 200) {
            mostrarAlerta('alertaPerfil', 'Perfil actualizado exitosamente', 'success');
        } else {
            mostrarAlerta('alertaPerfil', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        mostrarAlerta('alertaPerfil', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Cambiar contraseña
 */
function cambiarContrasena(e) {
    e.preventDefault();
    
    const datos = {
        password_antigua: document.getElementById('password_actual').value,
        password_nueva: document.getElementById('password_nueva').value,
        confirmar_password: document.getElementById('confirmar_password').value
    };
    
    // Validar
    if (!datos.password_antigua || !datos.password_nueva || !datos.confirmar_password) {
        mostrarAlerta('alertaCambiarContrasena', 'Completa todos los campos', 'warning');
        return;
    }
    
    if (datos.password_nueva.length < 8) {
        mostrarAlerta('alertaCambiarContrasena', 'La contraseña debe tener al menos 8 caracteres', 'warning');
        return;
    }
    
    if (datos.password_nueva !== datos.confirmar_password) {
        mostrarAlerta('alertaCambiarContrasena', 'Las contraseñas no coinciden', 'warning');
        return;
    }
    
    const btn = document.querySelector('#formCambiarContrasena button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    
    fetch(API_URL + '/usuarios/' + usuarioId + '/cambiar-contrasena', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Cambiar Contraseña';
        
        if (data.codigo === 200) {
            mostrarAlerta('alertaCambiarContrasena', 'Contraseña actualizada exitosamente', 'success');
            document.getElementById('formCambiarContrasena').reset();
        } else {
            mostrarAlerta('alertaCambiarContrasena', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Cambiar Contraseña';
        mostrarAlerta('alertaCambiarContrasena', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Cargar pedidos
 */
function cargarPedidos() {
    fetch(API_URL + '/usuarios/' + usuarioId + '/pedidos', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.codigo === 200) {
            const { pedidos, resumen } = data.datos;
            
            // Actualizar resumen
            if (resumen) {
                document.getElementById('totalPedidos').textContent = resumen.total_pedidos || 0;
                document.getElementById('gastoTotal').textContent = formatearMoneda(resumen.gasto_total || 0);
                document.getElementById('ticketPromedio').textContent = formatearMoneda(resumen.ticket_promedio || 0);
                document.getElementById('ultimoPedido').textContent = resumen.ultimo_pedido ? formatearFecha(resumen.ultimo_pedido) : '-';
            }
            
            // Llenar tabla
            const tbody = document.getElementById('tbodyPedidos');
            tbody.innerHTML = '';
            
            if (pedidos && pedidos.length > 0) {
                pedidos.forEach(pedido => {
                    const fila = document.createElement('tr');
                    fila.innerHTML = `
                        <td><strong>${pedido.numero_pedido}</strong></td>
                        <td>${formatearFecha(pedido.fecha_pedido)}</td>
                        <td>
                            <span class="badge bg-${obtenerColorEstado(pedido.estado)}">
                                ${pedido.estado}
                            </span>
                        </td>
                        <td>${formatearMoneda(pedido.total)}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="verDetallePedido(${pedido.id})">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                        </td>
                    `;
                    tbody.appendChild(fila);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No tienes pedidos aún</td></tr>';
            }
        }
    })
    .catch(err => console.error('Error cargando pedidos:', err));
}

/**
 * Ver detalle de pedido
 */
function verDetallePedido(pedidoId) {
    fetch(API_URL + '/pedidos/' + pedidoId, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.codigo === 200) {
            const pedido = data.datos;
            
            let detalles = '<strong>Detalles del Pedido ' + pedido.numero_pedido + '</strong><br>';
            detalles += '<small>';
            detalles += 'Fecha: ' + formatearFecha(pedido.fecha_pedido) + '<br>';
            detalles += 'Estado: ' + pedido.estado + '<br>';
            detalles += 'Total: ' + formatearMoneda(pedido.total) + '<br>';
            
            if (pedido.detalles && pedido.detalles.length > 0) {
                detalles += '<br><strong>Items:</strong><br>';
                pedido.detalles.forEach(item => {
                    detalles += '- ' + item.cantidad + 'x Producto #' + item.producto_id + ' @ ' + formatearMoneda(item.precio_unitario) + '<br>';
                });
            }
            
            detalles += '</small>';
            
            alert(detalles);
        }
    })
    .catch(err => console.error('Error:', err));
}

/**
 * Logout
 */
function logout() {
    const token = localStorage.getItem('token');
    
    fetch(API_URL + '/usuarios/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token })
    })
    .then(() => {
        // Limpiar localStorage
        localStorage.clear();
        // Redirigir
        window.location.href = 'login.html';
    })
    .catch(err => {
        console.error('Error:', err);
        localStorage.clear();
        window.location.href = 'login.html';
    });
}

/**
 * Funciones auxiliares
 */

function formatearMoneda(valor) {
    return '$' + parseFloat(valor).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatearFecha(fecha) {
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES');
}

function obtenerColorEstado(estado) {
    const colores = {
        'pendiente': 'warning',
        'confirmado': 'info',
        'enviado': 'primary',
        'entregado': 'success',
        'cancelado': 'danger',
        'devuelto': 'secondary'
    };
    return colores[estado] || 'secondary';
}

function mostrarAlerta(elementId, mensaje, tipo) {
    const alerta = document.getElementById(elementId);
    alerta.innerHTML = mensaje;
    alerta.className = 'alert alert-' + tipo;
    alerta.classList.remove('d-none');
    
    // Auto-hide
    if (tipo === 'success') {
        setTimeout(() => {
            alerta.classList.add('d-none');
        }, 5000);
    }
}
