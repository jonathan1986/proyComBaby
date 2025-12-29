import { fetchProducts, fetchCategories, fetchProductImages } from './api/productosService.js';
import { createProductCard, createSkeletonCard } from './components/productoCard.js';

const state = {
    products: [],
    filteredProducts: [],
    categories: [],
    categoriesMap: new Map(),
    filters: {
        search: '',
        categories: new Set(),
        minPrice: null,
        maxPrice: null,
        tags: new Set(),
        onlyStock: false
    },
    sort: 'featured',
    pagination: {
        page: 1,
        perPage: 12
    },
    priceGuide: { min: 0, max: 0 }
};

const ui = {};
let searchDebounce;

document.addEventListener('DOMContentLoaded', () => {
    cacheElements();
    if (!ui.productGrid) {
        return;
    }
    initCatalog();
});

function cacheElements() {
    ui.productGrid = document.getElementById('productos');
    ui.categoriesContainer = document.getElementById('categoriaList');
    ui.resultCount = document.getElementById('resultCount');
    ui.activeFilters = document.getElementById('activeFilters');
    ui.pagination = document.getElementById('paginationControls');
    ui.sortSelect = document.getElementById('sortSelect');
    ui.searchInput = document.getElementById('searchInput');
    ui.priceMin = document.getElementById('priceMin');
    ui.priceMax = document.getElementById('priceMax');
    ui.priceLabel = document.getElementById('priceRangeLabel');
    ui.stockToggle = document.getElementById('stockToggle');
    ui.clearFilters = document.getElementById('clearFilters');
    ui.viewToggle = document.querySelector('.view-toggle');
    ui.chipCloud = document.querySelector('.tag-cloud');
    ui.statProductos = document.getElementById('statProductos');
}

async function initCatalog() {
    renderSkeletons();
    try {
        await loadData();
        bindEvents();
        applyFilters();
    } catch (error) {
        console.error(error);
        showError(error.message || 'No se pudo cargar el catálogo.');
    }
}

function renderSkeletons() {
    if (!ui.productGrid) {
        return;
    }
    ui.productGrid.innerHTML = '';
    Array.from({ length: 8 }).forEach(() => {
        ui.productGrid.appendChild(createSkeletonCard());
    });
}

async function loadData() {
    const [products, categories] = await Promise.all([
        fetchProducts(),
        fetchCategories()
    ]);

    state.categories = normalizeCategories(categories);
    state.categoriesMap = new Map(state.categories.map((cat) => [cat.id, cat.nombre]));
    populateCategoryFilters();

    state.products = products.map((raw, index) => normalizeProduct(raw, index));
    assignCategoryNames();
    state.priceGuide = calculatePriceGuide();
    updatePriceLabel();
    await hydrateProductImages();
    updateHeroStats();
}

function normalizeCategories(list = []) {
    return list
        .map((cat) => ({
            id: Number(cat.id_categoria ?? cat.id ?? cat.ID ?? 0),
            nombre: cat.nombre ?? 'Sin categoría'
        }))
        .filter((cat) => cat.id > 0);
}

function populateCategoryFilters() {
    if (!ui.categoriesContainer || !state.categories.length) {
        return;
    }
    ui.categoriesContainer.innerHTML = '';
    state.categories.forEach((category) => {
        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = category.id;
        const text = document.createElement('span');
        text.textContent = category.nombre;
        label.append(checkbox, text);
        ui.categoriesContainer.appendChild(label);
    });
}

function normalizeProduct(item, index) {
    const id = Number(item.id_producto ?? item.id ?? item.ID ?? index + 1);
    const price = Number(item.precio ?? item.price ?? 0);
    const comparePrice = price ? Math.round(price * 1.18) : price;
    const tagsPool = ['eco', 'essentials', 'premium', 'sale'];
    return {
        id,
        raw: item,
        name: item.nombre ?? item.title ?? `Producto ${id}`,
        description: item.descripcion ?? item.description ?? '',
        price: price < 0 ? 0 : price,
        comparePrice,
        stock: Number(item.stock ?? 0),
        stockMin: Number(item.stock_minimo ?? 0),
        status: Number(item.estado ?? 1),
        categoryId: Number(item.id_categoria ?? item.categoria_id ?? 0),
        categoryName: 'Colección general',
        tags: new Set([tagsPool[index % tagsPool.length]]),
        rating: 4 + ((index % 5) * 0.1),
        reviews: 20 + (index % 40),
        badges: [],
        imageUrl: buildPlaceholderImage(id)
    };
}

function assignCategoryNames() {
    if (!state.categories.length) {
        return;
    }
    state.products.forEach((product, idx) => {
        if (product.categoryId && state.categoriesMap.has(product.categoryId)) {
            product.categoryName = state.categoriesMap.get(product.categoryId);
            return;
        }
        const fallback = state.categories[idx % state.categories.length];
        product.categoryName = fallback?.nombre ?? 'Colección general';
    });
}

function calculatePriceGuide() {
    if (!state.products.length) {
        return { min: 0, max: 0 };
    }
    const prices = state.products.map((p) => p.price);
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    return { min, max };
}

function updatePriceLabel() {
    if (!ui.priceLabel) {
        return;
    }
    const { min, max } = state.priceGuide;
    ui.priceLabel.textContent = `${formatCurrency(min)} — ${formatCurrency(max)}`;
    if (ui.priceMin) {
        ui.priceMin.placeholder = min.toString();
    }
    if (ui.priceMax) {
        ui.priceMax.placeholder = max.toString();
    }
}

async function hydrateProductImages() {
    const limit = Math.min(state.products.length, 18);
    const subset = state.products.slice(0, limit);
    await Promise.allSettled(
        subset.map(async (product) => {
            const images = await fetchProductImages(product.id);
            if (!images.length) {
                return;
            }
            const featured = images.find((img) => Number(img.principal) === 1) || images[0];
            const path = featured.archivo || featured.archivo_imagen;
            if (path) {
                product.imageUrl = normalizeAssetPath(path);
            }
        })
    );
}

function normalizeAssetPath(path) {
    if (!path) {
        return '';
    }
    if (/^https?:/i.test(path)) {
        return path;
    }
    const sanitized = path.replace(/\\/g, '/');
    return sanitized.startsWith('/') ? sanitized : `/${sanitized}`;
}

function updateHeroStats() {
    const total = state.products.length;
    if (ui.statProductos) {
        ui.statProductos.textContent = total.toString();
    }
}

function bindEvents() {
    ui.searchInput?.addEventListener('input', handleSearchInput);
    ui.categoriesContainer?.addEventListener('change', handleCategoryChange);
    ui.priceMin?.addEventListener('change', handlePriceChange);
    ui.priceMax?.addEventListener('change', handlePriceChange);
    ui.stockToggle?.addEventListener('change', () => {
        state.filters.onlyStock = ui.stockToggle.checked;
        applyFilters();
    });
    ui.sortSelect?.addEventListener('change', () => {
        state.sort = ui.sortSelect.value;
        applyFilters({ resetPage: false });
    });
    ui.clearFilters?.addEventListener('click', () => {
        resetFilters();
        applyFilters();
    });
    ui.chipCloud?.addEventListener('click', handleChipToggle);
    ui.activeFilters?.addEventListener('click', handleActiveFilterRemove);
    ui.pagination?.addEventListener('click', handlePageChange);
    ui.viewToggle?.addEventListener('click', handleViewToggle);
}

function handleSearchInput(event) {
    const value = event.target.value.trim();
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        state.filters.search = value;
        applyFilters();
    }, 250);
}

function handleCategoryChange(event) {
    const checkbox = event.target;
    if (!checkbox || checkbox.type !== 'checkbox') {
        return;
    }
    const id = Number(checkbox.value);
    if (checkbox.checked) {
        state.filters.categories.add(id);
    } else {
        state.filters.categories.delete(id);
    }
    applyFilters();
}

function handlePriceChange() {
    const min = ui.priceMin?.value ? Number(ui.priceMin.value) : null;
    const max = ui.priceMax?.value ? Number(ui.priceMax.value) : null;
    state.filters.minPrice = Number.isFinite(min) ? min : null;
    state.filters.maxPrice = Number.isFinite(max) ? max : null;
    applyFilters();
}

function handleChipToggle(event) {
    const button = event.target.closest('.chip');
    if (!button) {
        return;
    }
    const value = button.dataset.chip;
    if (!value) {
        return;
    }
    if (state.filters.tags.has(value)) {
        state.filters.tags.delete(value);
        button.classList.remove('is-active');
    } else {
        state.filters.tags.add(value);
        button.classList.add('is-active');
    }
    applyFilters();
}

function handleActiveFilterRemove(event) {
    const button = event.target.closest('button[data-filter]');
    if (!button) {
        return;
    }
    const { filter, value } = button.dataset;
    switch (filter) {
        case 'search':
            state.filters.search = '';
            if (ui.searchInput) {
                ui.searchInput.value = '';
            }
            break;
        case 'category':
            state.filters.categories.delete(Number(value));
            uncheckCategory(Number(value));
            break;
        case 'price-min':
            state.filters.minPrice = null;
            if (ui.priceMin) {
                ui.priceMin.value = '';
            }
            break;
        case 'price-max':
            state.filters.maxPrice = null;
            if (ui.priceMax) {
                ui.priceMax.value = '';
            }
            break;
        case 'stock':
            state.filters.onlyStock = false;
            if (ui.stockToggle) {
                ui.stockToggle.checked = false;
            }
            break;
        case 'tag':
            state.filters.tags.delete(value);
            toggleChipClass(value, false);
            break;
        default:
            break;
    }
    applyFilters();
}

function handlePageChange(event) {
    const button = event.target.closest('button[data-page]');
    if (!button || button.disabled) {
        return;
    }
    const page = Number(button.dataset.page);
    if (Number.isNaN(page)) {
        return;
    }
    const totalPages = Math.max(1, Math.ceil(state.filteredProducts.length / state.pagination.perPage));
    if (page < 1 || page > totalPages) {
        return;
    }
    state.pagination.page = page;
    renderProducts();
}

function handleViewToggle(event) {
    const button = event.target.closest('button[data-view]');
    if (!button || button.classList.contains('is-active')) {
        return;
    }
    ui.viewToggle.querySelectorAll('button').forEach((btn) => btn.classList.remove('is-active'));
    button.classList.add('is-active');
    const mode = button.dataset.view;
    if (mode === 'list') {
        ui.productGrid.classList.add('list-view');
    } else {
        ui.productGrid.classList.remove('list-view');
    }
}

function toggleChipClass(value, isActive) {
    if (!ui.chipCloud) {
        return;
    }
    const chip = ui.chipCloud.querySelector(`[data-chip="${value}"]`);
    if (chip) {
        chip.classList.toggle('is-active', isActive);
    }
}

function uncheckCategory(id) {
    if (!ui.categoriesContainer) {
        return;
    }
    const checkbox = ui.categoriesContainer.querySelector(`input[value="${id}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
}

function resetFilters() {
    state.filters.search = '';
    state.filters.categories.clear();
    state.filters.minPrice = null;
    state.filters.maxPrice = null;
    state.filters.tags.clear();
    state.filters.onlyStock = false;
    ui.searchInput && (ui.searchInput.value = '');
    ui.priceMin && (ui.priceMin.value = '');
    ui.priceMax && (ui.priceMax.value = '');
    ui.stockToggle && (ui.stockToggle.checked = false);
    ui.categoriesContainer?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.checked = false;
    });
    ui.chipCloud?.querySelectorAll('.chip').forEach((chip) => chip.classList.remove('is-active'));
}

function applyFilters({ resetPage = true } = {}) {
    let collection = [...state.products];

    if (state.filters.search) {
        const needle = state.filters.search.toLowerCase();
        collection = collection.filter((product) => product.name.toLowerCase().includes(needle));
    }

    if (state.filters.categories.size) {
        collection = collection.filter((product) => state.filters.categories.has(product.categoryId) || state.filters.categories.has(findCategoryIdByName(product.categoryName)));
    }

    if (state.filters.minPrice !== null) {
        collection = collection.filter((product) => product.price >= state.filters.minPrice);
    }
    if (state.filters.maxPrice !== null) {
        collection = collection.filter((product) => product.price <= state.filters.maxPrice);
    }

    if (state.filters.tags.size) {
        collection = collection.filter((product) => {
            return Array.from(state.filters.tags).some((tag) => product.tags.has(tag));
        });
    }

    if (state.filters.onlyStock) {
        collection = collection.filter((product) => product.stock > 0 && product.status === 1);
    }

    state.filteredProducts = sortCollection(collection);
    if (resetPage) {
        state.pagination.page = 1;
    } else {
        const totalPages = Math.max(1, Math.ceil(state.filteredProducts.length / state.pagination.perPage));
        state.pagination.page = Math.min(state.pagination.page, totalPages);
    }
    renderProducts();
    renderActiveFilters();
    updateResultCount();
}

function sortCollection(collection) {
    const sorted = [...collection];
    switch (state.sort) {
        case 'price-asc':
            return sorted.sort((a, b) => a.price - b.price);
        case 'price-desc':
            return sorted.sort((a, b) => b.price - a.price);
        case 'newest':
            return sorted.sort((a, b) => b.id - a.id);
        default:
            return sorted.sort((a, b) => (b.stock - a.stock));
    }
}

function findCategoryIdByName(name) {
    for (const [id, label] of state.categoriesMap.entries()) {
        if (label === name) {
            return id;
        }
    }
    return 0;
}

function renderProducts() {
    if (!ui.productGrid) {
        return;
    }
    ui.productGrid.innerHTML = '';

    if (!state.filteredProducts.length) {
        ui.productGrid.innerHTML = '<div class="empty-state">No encontramos productos con esos filtros. Ajusta los criterios para ver más resultados.</div>';
        ui.pagination.innerHTML = '';
        return;
    }

    const start = (state.pagination.page - 1) * state.pagination.perPage;
    const end = start + state.pagination.perPage;
    state.filteredProducts.slice(start, end).forEach((product) => {
        decorateBadges(product);
        ui.productGrid.appendChild(createProductCard(product));
    });

    renderPagination();
}

function decorateBadges(product) {
    const badges = [];
    if (product.comparePrice && product.comparePrice > product.price) {
        const discount = Math.round(((product.comparePrice - product.price) / product.comparePrice) * 100);
        if (discount > 0) {
            badges.push({ label: `-${discount}%`, type: 'sale' });
        }
    }
    if (product.stock <= product.stockMin + 5) {
        badges.push({ label: 'Últimas piezas', type: 'alert' });
    }
    if (!badges.length) {
        badges.push({ label: 'Nuevo drop', type: 'new' });
    }
    product.badges = badges;
}

function renderPagination() {
    if (!ui.pagination) {
        return;
    }
    ui.pagination.innerHTML = '';
    const totalPages = Math.max(1, Math.ceil(state.filteredProducts.length / state.pagination.perPage));
    if (totalPages <= 1) {
        return;
    }

    const fragment = document.createDocumentFragment();
    fragment.appendChild(createPageButton('‹', state.pagination.page - 1, state.pagination.page === 1));

    const pages = buildPageWindow(totalPages, state.pagination.page);
    pages.forEach((page) => {
        fragment.appendChild(createPageButton(page.toString(), page, false, page === state.pagination.page));
    });

    fragment.appendChild(createPageButton('›', state.pagination.page + 1, state.pagination.page === totalPages));
    ui.pagination.appendChild(fragment);
}

function createPageButton(label, page, disabled, isActive = false) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    button.disabled = disabled;
    button.dataset.page = page;
    if (isActive) {
        button.classList.add('is-active');
    }
    return button;
}

function buildPageWindow(totalPages, currentPage) {
    const windowSize = 5;
    const half = Math.floor(windowSize / 2);
    let start = Math.max(1, currentPage - half);
    let end = start + windowSize - 1;
    if (end > totalPages) {
        end = totalPages;
        start = Math.max(1, end - windowSize + 1);
    }
    const pages = [];
    for (let i = start; i <= end; i += 1) {
        pages.push(i);
    }
    return pages;
}

function renderActiveFilters() {
    if (!ui.activeFilters) {
        return;
    }
    const chips = [];
    if (state.filters.search) {
        chips.push({ label: `Busca: ${state.filters.search}`, filter: 'search' });
    }
    state.filters.categories.forEach((id) => {
        chips.push({ label: state.categoriesMap.get(id) || `Categoría ${id}`, filter: 'category', value: id });
    });
    if (state.filters.minPrice !== null) {
        chips.push({ label: `Desde ${formatCurrency(state.filters.minPrice)}`, filter: 'price-min' });
    }
    if (state.filters.maxPrice !== null) {
        chips.push({ label: `Hasta ${formatCurrency(state.filters.maxPrice)}`, filter: 'price-max' });
    }
    if (state.filters.onlyStock) {
        chips.push({ label: 'Solo en stock', filter: 'stock' });
    }
    state.filters.tags.forEach((tag) => {
        chips.push({ label: `Tag: ${tag}`, filter: 'tag', value: tag });
    });

    if (!chips.length) {
        ui.activeFilters.innerHTML = '';
        return;
    }

    ui.activeFilters.innerHTML = '';
    chips.forEach((chip) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chip';
        button.dataset.filter = chip.filter;
        if (chip.value !== undefined) {
            button.dataset.value = chip.value;
        }
        button.textContent = `${chip.label} x`;
        ui.activeFilters.appendChild(button);
    });
}

function updateResultCount() {
    if (!ui.resultCount) {
        return;
    }
    const total = state.filteredProducts.length;
    ui.resultCount.textContent = total === 1 ? '1 resultado' : `${total} resultados`;
}

function showError(message) {
    if (!ui.productGrid) {
        return;
    }
    ui.productGrid.innerHTML = `<div class="empty-state">${message}</div>`;
}

function buildPlaceholderImage(id) {
    const topics = ['fashion', 'streetwear', 'editorial', 'clothing'];
    const topic = topics[id % topics.length];
    return `https://images.unsplash.com/random/600x800/?${topic}&sig=${id}`;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0
    }).format(Number(value) || 0);
}