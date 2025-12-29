const FALLBACK_BASE = '/modules/CatalogoProductos/Controllers/';
const datasetBase = typeof document !== 'undefined' ? document.body?.dataset?.apiBase : null;
const runtimeBase = typeof window !== 'undefined' ? window.__CATALOGO_API_BASE__ : null;
const API_BASE = (runtimeBase || datasetBase || FALLBACK_BASE).replace(/\/?$/, '/');

const withBase = (endpoint) => `${API_BASE}${endpoint.replace(/^\//, '')}`;

const buildUrl = (endpoint, params = {}) => {
    const url = new URL(withBase(endpoint), window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        url.searchParams.set(key, value);
    });
    return url;
};

const request = async (endpoint, params = {}) => {
    const url = buildUrl(endpoint, params);
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json'
        },
        credentials: 'same-origin'
    });

    if (!response.ok) {
        throw new Error(`Error ${response.status} al consultar ${endpoint}`);
    }

    const payload = await response.json();
    if (payload && payload.success === false) {
        throw new Error(payload.error || 'Operación rechazada por el API');
    }
    return payload || {};
};

const fetchLocalCategories = async () => {
    try {
        const res = await fetch('../data/categorias.json');
        if (!res.ok) {
            return [];
        }
        return await res.json();
    } catch (error) {
        console.warn('Sin datos locales de categorías', error);
        return [];
    }
};

export const fetchProducts = async (params = {}) => {
    const payload = await request('producto_api.php', params);
    return Array.isArray(payload.productos) ? payload.productos : [];
};

export const fetchCategories = async () => {
    try {
        const payload = await request('categoria_api.php');
        if (Array.isArray(payload.categorias)) {
            return payload.categorias;
        }
        if (payload.categoria) {
            return [payload.categoria];
        }
        return [];
    } catch (error) {
        console.warn('Fallo al leer categorías remotas, usando respaldo local', error);
        return fetchLocalCategories();
    }
};

export const fetchProductImages = async (productId) => {
    if (!productId) {
        return [];
    }
    try {
        const payload = await request('imagenes_producto_api.php', { id_producto: productId });
        return Array.isArray(payload.imagenes) ? payload.imagenes : [];
    } catch (error) {
        console.warn(`Sin imágenes para producto ${productId}`, error);
        return [];
    }
};