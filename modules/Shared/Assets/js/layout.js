/**
 * layout.js - Sistema de menú lateral con autenticación y roles
 * Inicializa el sidebar, valida token y construye menú dinámicamente
 */

(function() {
  'use strict';

  // ==================== CONFIGURACIÓN DE MENÚ POR ROL ====================
  const MENU_CONFIG = {
    ADMINISTRADOR: [
      { titulo: '🏠 Inicio', url: '/modules/Home/Views/home.html' },
      { titulo: '🛒 Productos', url: '/modules/CatalogoProductos/Views/producto_gestion.html' },
      { titulo: '🗂️ Categorías', url: '/modules/CatalogoProductos/Views/categoria_crud.html' },
      { titulo: '💸 Impuestos', url: '/modules/CatalogoProductos/Views/impuestos_admin.html' },
      { titulo: '🤝 Proveedores', url: '/modules/CatalogoProductos/Views/proveedor_crud.html' },
      { titulo: '📦 Inventario', url: '/modules/CatalogoProductos/Views/inventario.html' },
      { titulo: '🧾 Pedidos', url: '/modules/GestionUsuarios/Views/pedidos.html' },
      { titulo: '👥 Usuarios', url: '/modules/GestionUsuarios/Views/usuarios_admin.html' },
      { titulo: '👤 Mi Perfil', url: '/modules/GestionUsuarios/Views/perfil.html' }
    ],
    GESTOR_CONTENIDOS: [
      { titulo: '🏠 Inicio', url: '/modules/Home/Views/home.html' },
      { titulo: '🛒 Productos', url: '/modules/CatalogoProductos/Views/producto_gestion.html' },
      { titulo: '🗂️ Categorías', url: '/modules/CatalogoProductos/Views/categoria_crud.html' },
      { titulo: '💸 Impuestos', url: '/modules/CatalogoProductos/Views/impuestos_admin.html' },
      { titulo: '🤝 Proveedores', url: '/modules/CatalogoProductos/Views/proveedor_crud.html' },
      { titulo: '📦 Inventario', url: '/modules/CatalogoProductos/Views/inventario.html' }
    ],
    VENDEDOR: [
      { titulo: '🏠 Inicio', url: '/modules/Home/Views/home.html' },
      { titulo: '🛒 Productos', url: '/modules/CatalogoProductos/Views/producto_gestion.html' },
      { titulo: '🗂️ Categorías', url: '/modules/CatalogoProductos/Views/categoria_crud.html' },
      { titulo: '📦 Inventario', url: '/modules/CatalogoProductos/Views/inventario.html' },
      { titulo: '🧾 Pedidos', url: '/modules/GestionUsuarios/Views/pedidos.html' }
    ],
    CLIENTE: [
      { titulo: '🏠 Inicio', url: '/modules/Home/Views/home.html' },
      { titulo: '🧾 Mis Pedidos', url: '/modules/GestionUsuarios/Views/pedidos.html' },
      { titulo: '👤 Mi Perfil', url: '/modules/GestionUsuarios/Views/perfil.html' }
    ]
  };

  let authToken = null;
  let currentUser = null;

  // ==================== INICIALIZACIÓN ====================
  window.initLayout = async function() {
    try {
      authToken = localStorage.getItem('authToken');
      if (!authToken) {
        redirectToLogin();
        return;
      }

      // Validar token y obtener usuario
      const response = await fetch('/modules/GestionUsuarios/Api/index.php?action=perfil', {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) throw new Error('Token inválido');

      const data = await response.json();
      currentUser = data.datos ? data.datos.usuario : data.usuario;

      // Verificar usuario activo
      if (currentUser.activo !== 1 || currentUser.estado_id !== 1) {
        throw new Error('Usuario no activo');
      }

      // Construir menú
      buildMenu();
      setupEventListeners();

      // Mostrar contenido principal después de autenticación exitosa
      const mainContent = document.getElementById('mainContent');
      const accessDenied = document.getElementById('accessDenied');
      if (mainContent) mainContent.style.display = 'block';
      if (accessDenied) accessDenied.style.display = 'none';

    } catch (error) {
      console.error('Error al inicializar layout:', error);
      
      // Mostrar mensaje de acceso denegado
      const mainContent = document.getElementById('mainContent');
      const accessDenied = document.getElementById('accessDenied');
      if (mainContent) mainContent.style.display = 'none';
      if (accessDenied) accessDenied.style.display = 'block';
      
      // Redirigir a login después de 3 segundos
      setTimeout(() => {
        redirectToLogin();
      }, 3000);
    }
  };

  // ==================== CONSTRUCCIÓN DEL MENÚ ====================
  function buildMenu() {
    const roles = (currentUser.roles || '')
      .split(',')
      .map(r => r.trim())
      .filter(Boolean);

    // Actualizar info de usuario en SIDEBAR (escritorio)
    const userInfoEl = document.getElementById('sidebarUserInfo');
    if (userInfoEl) {
      userInfoEl.innerHTML = `
        <div class="sidebar-user">
          <strong>${currentUser.nombre_completo || currentUser.email}</strong>
        </div>
        <div class="sidebar-user small">${roles.join(', ') || 'Sin rol'}</div>
      `;
    }

    // Actualizar info de usuario en OFFCANVAS (móvil)
    const offcanvasUserInfo = document.getElementById('offcanvasUserInfo');
    if (offcanvasUserInfo) {
      offcanvasUserInfo.innerHTML = `
        <div><strong>${currentUser.nombre_completo || currentUser.email}</strong></div>
        <div>${roles.join(', ') || 'Sin rol'}</div>
      `;
    }

    // Construir items del menú
    const menuItems = new Set();
    const menuMap = new Map();

    roles.forEach(rol => {
      const items = MENU_CONFIG[rol] || [];
      items.forEach(item => {
        const key = item.url;
        if (!menuMap.has(key)) {
          menuMap.set(key, item);
          menuItems.add(item);
        }
      });
    });

    const currentPath = window.location.pathname;
    const menuHTML = Array.from(menuItems)
      .map(item => {
        const isActive = currentPath.includes(item.url.split('/').pop());
        return `
          <li class="nav-item">
            <a class="nav-link ${isActive ? 'active' : ''}" href="${item.url}">
              ${item.titulo}
            </a>
          </li>
        `;
      })
      .join('');

    // Renderizar menú en SIDEBAR (escritorio)
    const menuList = document.getElementById('sidebarMenu');
    if (menuList) {
      menuList.innerHTML = menuHTML;
    }

    // Renderizar menú en OFFCANVAS (móvil)
    const offcanvasMenuList = document.getElementById('offcanvasMenu');
    if (offcanvasMenuList) {
      offcanvasMenuList.innerHTML = menuHTML;
    }
  }

  // ==================== EVENTOS ====================
  function setupEventListeners() {
    // Toggle sidebar en escritorio
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', toggleDesktopSidebar);
    }

    // Logout en sidebar (escritorio)
    const logoutBtn = document.getElementById('btnSidebarLogout');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', logout);
    }

    // Logout en offcanvas (móvil)
    const offcanvasLogoutBtn = document.getElementById('btnOffcanvasLogout');
    if (offcanvasLogoutBtn) {
      offcanvasLogoutBtn.addEventListener('click', logout);
    }
  }

  function toggleDesktopSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const topbar = document.getElementById('appTopbar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar) sidebar.classList.toggle('collapsed');
    if (topbar) topbar.classList.toggle('sidebar-collapsed');
    if (mainContent) mainContent.classList.toggle('sidebar-collapsed');
  }

  function logout() {
    localStorage.removeItem('authToken');
    redirectToLogin();
  }

  function redirectToLogin() {
    window.location.href = '/modules/GestionUsuarios/Views/login.html';
  }

  // ==================== HELPER PARA FETCH AUTENTICADO ====================
  window.apiFetch = async function(url, options = {}) {
    const token = localStorage.getItem('authToken');
    if (!token) {
      redirectToLogin();
      throw new Error('No hay token');
    }

    const headers = {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      ...options.headers
    };

    const response = await fetch(url, { ...options, headers });

    if (response.status === 401 || response.status === 403) {
      alert('Sesión expirada o sin permisos. Redirigiendo a login...');
      logout();
      throw new Error('No autorizado');
    }

    return response;
  };

})();
