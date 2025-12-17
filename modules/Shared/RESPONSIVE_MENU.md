# Menú Responsive - Documentación

## ✅ Implementación Completada

El sistema de menú ahora es completamente responsive y se adapta automáticamente a diferentes dispositivos.

---

## 🎯 Características Principales

### Escritorio (≥992px)
- **Sidebar fijo lateral** (260px de ancho)
- Visible permanentemente en el lado izquierdo
- Botón "☰" colapsa/expande el sidebar
- Topbar con margen izquierdo de 260px
- Contenido principal con margen izquierdo de 260px

### Móvil/Tablet (<992px)
- **Offcanvas de Bootstrap 5** (se desliza desde la izquierda)
- Sidebar oculto por defecto
- Botón "☰" en topbar abre el offcanvas
- Topbar ocupa todo el ancho de la pantalla
- Contenido principal sin márgenes laterales
- Cierre automático al hacer clic fuera del menú

---

## 📁 Archivos Modificados

### 1. `modules/Shared/Views/sidebar.html`
```html
<!-- Topbar: visible siempre -->
<nav id="appTopbar" class="navbar navbar-light bg-white border-bottom sticky-top">
  <div class="container-fluid">
    <!-- Botón para offcanvas (solo móvil) -->
    <button class="btn btn-outline-secondary d-lg-none" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#appOffcanvas">
      ☰
    </button>
    
    <!-- Botón para sidebar fijo (solo escritorio) -->
    <button class="btn btn-outline-secondary d-none d-lg-block sidebar-toggle" 
            id="sidebarToggle">
      ☰
    </button>
    
    <span class="navbar-brand mb-0 h1" id="pageTitle">Panel de Control</span>
  </div>
</nav>

<!-- Sidebar fijo (solo escritorio ≥992px) -->
<nav id="appSidebar" class="d-none d-lg-block">
  <!-- Contenido del menú -->
</nav>

<!-- Offcanvas (solo móvil <992px) -->
<div class="offcanvas offcanvas-start" id="appOffcanvas">
  <!-- Mismo contenido del menú -->
</div>
```

**Estructura Dual:**
- **#appSidebar**: Sidebar fijo visible solo en pantallas grandes (`d-none d-lg-block`)
- **#appOffcanvas**: Offcanvas de Bootstrap 5 visible solo en móviles
- Ambos contienen el mismo menú generado dinámicamente

---

### 2. `modules/Shared/Assets/css/layout.css`

#### Topbar Responsive
```css
#appTopbar {
  position: sticky;
  top: 0;
  z-index: 1040;
  box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

/* Escritorio: margen izquierdo por el sidebar */
@media (min-width: 992px) {
  #appTopbar {
    margin-left: 260px;
  }
}

/* Móvil: sin margen */
@media (max-width: 991.98px) {
  #appTopbar {
    margin-left: 0;
  }
}
```

#### Sidebar Fijo con Colapso
```css
#appSidebar {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  width: 260px;
  background: #212529;
  transition: transform 0.3s ease-in-out;
}

/* Estado colapsado en escritorio */
#appSidebar.collapsed {
  transform: translateX(-260px);
}
```

#### Contenido Principal
```css
@media (min-width: 992px) {
  #mainContent {
    margin-left: 260px;
    padding: 1.5rem;
  }
  
  /* Sin margen cuando sidebar está colapsado */
  #mainContent.sidebar-collapsed {
    margin-left: 0 !important;
  }
}

@media (max-width: 991.98px) {
  #mainContent {
    margin-left: 0;
    padding: 1rem;
  }
}
```

#### Offcanvas Personalizado
```css
.offcanvas.offcanvas-start {
  width: 280px;
}

.offcanvas-body {
  background: #212529;
  color: #fff;
  padding: 1rem;
}
```

---

### 3. `modules/Shared/Assets/js/layout.js`

#### Construcción Dual del Menú
```javascript
function buildMenu() {
  const roles = (currentUser.roles || '').split(',').map(r => r.trim());
  
  // HTML del menú generado dinámicamente
  const menuHTML = Array.from(menuItems).map(item => `
    <li class="nav-item">
      <a class="nav-link ${isActive ? 'active' : ''}" href="${item.url}">
        ${item.titulo}
      </a>
    </li>
  `).join('');
  
  // Renderizar en SIDEBAR (escritorio)
  document.getElementById('sidebarMenu').innerHTML = menuHTML;
  
  // Renderizar en OFFCANVAS (móvil)
  document.getElementById('offcanvasMenu').innerHTML = menuHTML;
  
  // Actualizar info de usuario en ambos lugares
  document.getElementById('sidebarUserInfo').innerHTML = userInfoHTML;
  document.getElementById('offcanvasUserInfo').innerHTML = userInfoHTML;
}
```

#### Eventos de Interacción
```javascript
function setupEventListeners() {
  // Toggle sidebar en escritorio (colapsar/expandir)
  document.getElementById('sidebarToggle')
    ?.addEventListener('click', toggleDesktopSidebar);
  
  // Logout en sidebar (escritorio)
  document.getElementById('btnSidebarLogout')
    ?.addEventListener('click', logout);
  
  // Logout en offcanvas (móvil)
  document.getElementById('btnOffcanvasLogout')
    ?.addEventListener('click', logout);
}

function toggleDesktopSidebar() {
  document.getElementById('appSidebar')?.classList.toggle('collapsed');
  document.getElementById('appTopbar')?.classList.toggle('sidebar-collapsed');
  document.getElementById('mainContent')?.classList.toggle('sidebar-collapsed');
}
```

---

## 🔧 Cómo Funciona

### En Escritorio (≥992px)
1. Usuario ve el **sidebar fijo** a la izquierda
2. Topbar y contenido tienen margen izquierdo de 260px
3. Botón "☰" colapsa el sidebar hacia la izquierda
4. Cuando está colapsado, topbar y contenido ocupan todo el ancho
5. Offcanvas está oculto por Bootstrap (`d-lg-none`)

### En Móvil (<992px)
1. Sidebar fijo está oculto por Bootstrap (`d-none d-lg-block`)
2. Topbar ocupa todo el ancho sin márgenes
3. Botón "☰" activa el offcanvas de Bootstrap 5
4. Offcanvas se desliza desde la izquierda con animación
5. Usuario puede cerrar con:
   - Botón "X" en el header
   - Clic fuera del offcanvas
   - Navegación a otra página

---

## 📱 Breakpoints

- **Móvil**: < 992px → Usa offcanvas
- **Tablet**: 768px - 991px → Usa offcanvas
- **Escritorio**: ≥ 992px → Usa sidebar fijo

**Bootstrap 5 breakpoint:** `992px` (lg)

---

## 🎨 Ventajas de Esta Implementación

### ✅ UX Mejorada
- **Escritorio**: Menú siempre visible, colapso opcional
- **Móvil**: Más espacio en pantalla, acceso rápido al menú

### ✅ Bootstrap Nativo
- Usa `offcanvas` de Bootstrap 5 sin JS custom
- Animaciones suaves incluidas
- Accesibilidad (ARIA) incorporada

### ✅ Un Solo Código Fuente
- `buildMenu()` genera el HTML una vez
- Se inyecta en sidebar y offcanvas
- Sin duplicación de lógica

### ✅ Roles Dinámicos
- Menú se adapta al rol del usuario
- ADMINISTRADOR: todos los módulos
- GESTOR_CONTENIDOS: catálogo
- VENDEDOR: productos + pedidos
- CLIENTE: perfil + mis pedidos

---

## 🧪 Testing

### Escritorio
1. Abrir cualquier vista en navegador (≥992px)
2. ✅ Sidebar debe estar visible a la izquierda
3. ✅ Clic en "☰" colapsa el sidebar
4. ✅ Contenido se expande al colapsar

### Móvil
1. Abrir en Chrome DevTools (responsive mode)
2. Establecer ancho < 992px
3. ✅ Sidebar no debe ser visible
4. ✅ Clic en "☰" abre offcanvas desde la izquierda
5. ✅ Clic fuera del offcanvas lo cierra

### Roles
1. Login como ADMINISTRADOR
2. ✅ Ver 9 opciones de menú
3. Login como GESTOR_CONTENIDOS
4. ✅ Ver 6 opciones de catálogo
5. Login como CLIENTE
6. ✅ Ver solo 3 opciones (inicio, pedidos, perfil)

---

## 📦 Archivos Afectados

### Nuevos/Modificados
- ✅ `modules/Shared/Views/sidebar.html` - Estructura dual
- ✅ `modules/Shared/Assets/css/layout.css` - Estilos responsive
- ✅ `modules/Shared/Assets/js/layout.js` - Lógica dual

### Vistas Integradas (sin cambios adicionales)
- ✅ `producto_gestion.html`
- ✅ `categoria_crud.html`
- ✅ `impuestos_admin.html`
- ✅ `proveedor_crud.html`
- ✅ `inventario.html`

**Todas las vistas ya cargaban `layout.css`, `layout.js` y `sidebar.html`, por lo que la actualización es automática sin tocar cada archivo.**

---

## 🚀 Sin Cambios Requeridos en las Vistas

Gracias a la arquitectura modular, **no necesitas modificar las 5 vistas existentes**. Solo actualizamos los 3 archivos compartidos:

```
modules/Shared/
  ├── Views/sidebar.html       ← Actualizado con offcanvas
  ├── Assets/css/layout.css    ← Actualizado con media queries
  └── Assets/js/layout.js      ← Actualizado con lógica dual
```

Las vistas ya tienen:
```html
<link rel="stylesheet" href="../../Shared/Assets/css/layout.css">
<div id="layoutContainer"></div>
<script src="../../Shared/Assets/js/layout.js"></script>
```

---

## 🎉 Resultado Final

### Desktop (≥992px)
```
┌─────────────┬──────────────────────────────────┐
│             │  [☰] Panel de Control            │
│             ├──────────────────────────────────┤
│  SIDEBAR    │                                  │
│             │                                  │
│  • Inicio   │        CONTENIDO PRINCIPAL       │
│  • Productos│                                  │
│  • Categoría│                                  │
│  • Impuestos│                                  │
│  • ...      │                                  │
│             │                                  │
│  [Cerrar]   │                                  │
└─────────────┴──────────────────────────────────┘
```

### Mobile (<992px)
```
┌──────────────────────────────────┐
│  [☰] Panel de Control            │  ← Topbar
├──────────────────────────────────┤
│                                  │
│                                  │
│        CONTENIDO PRINCIPAL       │
│                                  │
│                                  │
└──────────────────────────────────┘

    ┌─────────────┐
    │ [X] E-Com   │  ← Offcanvas (al tocar ☰)
    │             │
    │ • Inicio    │
    │ • Productos │
    │ • Categoría │
    │             │
    │ [Cerrar]    │
    └─────────────┘
```

---

## 📝 Notas Finales

1. **Bootstrap 5 requerido**: El offcanvas necesita Bootstrap 5.3+
2. **JavaScript habilitado**: El menú dinámico requiere JS
3. **Token válido**: Sin token, redirige automáticamente a login
4. **Roles flexibles**: Fácil agregar nuevos módulos en `MENU_CONFIG`

---

## 🔒 Seguridad

- ✅ Validación de token en cada carga
- ✅ Verificación de roles en frontend (UI) y backend (API)
- ✅ Redirección automática a login si token expira
- ✅ Logout limpia localStorage y redirige

---

**Sistema implementado exitosamente** ✨
**Responsive:** Desktop + Tablet + Móvil
**Framework:** Bootstrap 5 Offcanvas
**Arquitectura:** Dual rendering (sidebar + offcanvas)
