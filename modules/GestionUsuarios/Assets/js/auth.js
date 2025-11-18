/**
 * auth.js - Funciones de autenticación
 * Maneja registro, login, recuperación de contraseña
 */

const API_URL = '/modules/GestionUsuarios/Api';

/**
 * Registrar usuario
 */
function registroUsuario() {
    const form = document.getElementById('formRegistro');
    const datos = {
        nombre_completo: document.getElementById('nombre_completo').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        confirmar_password: document.getElementById('confirmar_password').value,
        apellido: document.getElementById('apellido').value || null
    };
    
    // Validar
    if (!validarDatos(datos, ['nombre_completo', 'email', 'password', 'confirmar_password'])) {
        mostrarAlerta('alertaRegistro', 'Por favor completa todos los campos requeridos', 'warning');
        return;
    }
    
    if (!validarEmail(datos.email)) {
        mostrarAlerta('alertaRegistro', 'Email inválido', 'warning');
        return;
    }
    
    if (datos.password.length < 8) {
        mostrarAlerta('alertaRegistro', 'La contraseña debe tener al menos 8 caracteres', 'warning');
        return;
    }
    
    if (datos.password !== datos.confirmar_password) {
        mostrarAlerta('alertaRegistro', 'Las contraseñas no coinciden', 'warning');
        return;
    }
    
    // Enviar
    mostrarSpinner('btnRegistro', true);
    
    fetch(API_URL + '/usuarios/registro', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        mostrarSpinner('btnRegistro', false);
        
        if (data.codigo === 201) {
            mostrarAlerta('alertaRegistro', 'Registro exitoso. Redirigiendo...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            mostrarAlerta('alertaRegistro', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        mostrarSpinner('btnRegistro', false);
        mostrarAlerta('alertaRegistro', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Login de usuario
 */
function loginUsuario() {
    const datos = {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value
    };
    
    // Validar
    if (!datos.email || !datos.password) {
        mostrarAlerta('alertaLogin', 'Email y contraseña requeridos', 'warning');
        return;
    }
    
    mostrarSpinner('btnLogin', true);
    
    fetch(API_URL + '/usuarios/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        mostrarSpinner('btnLogin', false);
        
        if (data.codigo === 200) {
            // Guardar token
            localStorage.setItem('token', data.datos.token);
            localStorage.setItem('usuario_id', data.datos.usuario_id);
            localStorage.setItem('usuario_email', data.datos.email);
            localStorage.setItem('usuario_nombre', data.datos.nombre);
            localStorage.setItem('usuario_roles', JSON.stringify(data.datos.roles));
            
            // Si marcó recuerdarme
            if (document.getElementById('recuerdarme').checked) {
                localStorage.setItem('recuerdame', 'true');
            }
            
            mostrarAlerta('alertaLogin', 'Login exitoso. Redirigiendo...', 'success');
            setTimeout(() => {
                window.location.href = 'perfil.html';
            }, 1500);
        } else {
            mostrarAlerta('alertaLogin', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        mostrarSpinner('btnLogin', false);
        mostrarAlerta('alertaLogin', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Solicitar recuperación de contraseña
 */
function solicitarRecuperacion() {
    const email = document.getElementById('email').value;
    
    if (!email || !validarEmail(email)) {
        mostrarAlerta('alertaSolicitar', 'Email inválido', 'warning');
        return;
    }
    
    mostrarSpinner('btnSolicitar', true);
    
    fetch(API_URL + '/usuarios/recuperar-contrasena', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(res => res.json())
    .then(data => {
        mostrarSpinner('btnSolicitar', false);
        
        if (data.codigo === 200) {
            mostrarAlerta('alertaSolicitar', 'Si el email existe, recibirás un link de recuperación', 'success');
            // En desarrollo, mostrar token
            if (data.datos && data.datos.token) {
                console.log('Token de recuperación (desarrollo):', data.datos.token);
            }
        } else {
            mostrarAlerta('alertaSolicitar', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        mostrarSpinner('btnSolicitar', false);
        mostrarAlerta('alertaSolicitar', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Validar token de recuperación
 */
function validarTokenRecuperacion(token) {
    fetch(API_URL + '/usuarios/validar-token-recuperacion?token=' + token)
    .then(res => res.json())
    .then(data => {
        if (data.codigo === 200) {
            // Token válido, mostrar paso 2
            document.getElementById('paso1').classList.add('d-none');
            document.getElementById('paso2').classList.remove('d-none');
            document.getElementById('token').value = token;
        } else {
            mostrarAlerta('alertaSolicitar', 'Token inválido o expirado', 'danger');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        mostrarAlerta('alertaSolicitar', 'Error validando token', 'danger');
    });
}

/**
 * Resetear contraseña
 */
function resetearContrasena() {
    const datos = {
        token: document.getElementById('token').value,
        password: document.getElementById('password_nueva').value,
        confirmar_password: document.getElementById('confirmar_password').value
    };
    
    if (!datos.token || !datos.password || !datos.confirmar_password) {
        mostrarAlerta('alertaResetear', 'Completa todos los campos', 'warning');
        return;
    }
    
    if (datos.password.length < 8) {
        mostrarAlerta('alertaResetear', 'La contraseña debe tener al menos 8 caracteres', 'warning');
        return;
    }
    
    if (datos.password !== datos.confirmar_password) {
        mostrarAlerta('alertaResetear', 'Las contraseñas no coinciden', 'warning');
        return;
    }
    
    mostrarSpinner('btnResetear', true);
    
    fetch(API_URL + '/usuarios/resetear-contrasena', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        mostrarSpinner('btnResetear', false);
        
        if (data.codigo === 200) {
            mostrarAlerta('alertaResetear', 'Contraseña actualizada. Redirigiendo...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            mostrarAlerta('alertaResetear', data.mensaje, 'danger');
        }
    })
    .catch(err => {
        mostrarSpinner('btnResetear', false);
        mostrarAlerta('alertaResetear', 'Error: ' + err.message, 'danger');
    });
}

/**
 * Funciones auxiliares
 */

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validarDatos(datos, campos_requeridos) {
    for (let campo of campos_requeridos) {
        if (!datos[campo] || datos[campo].toString().trim() === '') {
            return false;
        }
    }
    return true;
}

function mostrarAlerta(elementId, mensaje, tipo) {
    const alerta = document.getElementById(elementId);
    alerta.innerHTML = mensaje;
    alerta.className = 'alert alert-' + tipo;
    alerta.classList.remove('d-none');
    
    // Auto-hide en 5 segundos si es success
    if (tipo === 'success') {
        setTimeout(() => {
            alerta.classList.add('d-none');
        }, 5000);
    }
}

function mostrarSpinner(elementId, mostrar) {
    const btn = document.getElementById(elementId);
    const spinner = btn.querySelector('.spinner-border');
    const text = btn.querySelector('#btnText') || btn.querySelector('#btnText2');
    
    if (mostrar) {
        btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (text) text.classList.add('d-none');
    } else {
        btn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
        if (text) text.classList.remove('d-none');
    }
}
