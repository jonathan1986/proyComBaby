const currencyFormatter = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    maximumFractionDigits: 0
});

const getTemplateClone = () => {
    const template = document.getElementById('producto-card-template');
    if (template?.content?.firstElementChild) {
        return template.content.firstElementChild.cloneNode(true);
    }
    const fallback = document.createElement('article');
    fallback.className = 'product-card';
    fallback.innerHTML = `
        <div class="product-badges"></div>
        <div class="product-figure"><img alt="" /></div>
        <div class="product-info">
            <p class="product-category"></p>
            <h3 class="product-title"></h3>
            <div class="product-price"><span class="current"></span><span class="compare"></span></div>
            <button class="primary-btn add-to-cart" type="button">Agregar al carrito</button>
        </div>
    `;
    return fallback;
};

const buildBadge = ({ label, type }) => {
    const span = document.createElement('span');
    span.className = `badge ${type ? `badge-${type}` : ''}`.trim();
    span.textContent = label;
    return span;
};

export const createProductCard = (product) => {
    const card = getTemplateClone();
    card.dataset.productId = product.id;

    const badgesSlot = card.querySelector('.product-badges');
    if (badgesSlot) {
        badgesSlot.innerHTML = '';
        (product.badges || []).forEach((badge) => badgesSlot.appendChild(buildBadge(badge)));
    }

    const img = card.querySelector('img');
    if (img) {
        img.src = product.imageUrl;
        img.alt = product.name;
        img.loading = 'lazy';
    }

    const category = card.querySelector('.product-category');
    if (category) {
        category.textContent = product.categoryName || 'Colección general';
    }

    const title = card.querySelector('.product-title');
    if (title) {
        title.textContent = product.name;
    }

    const ratingValue = card.querySelector('.rating-value');
    if (ratingValue && product.rating) {
        ratingValue.textContent = product.rating.toFixed(1);
    }

    const priceCurrent = card.querySelector('.product-price .current');
    const priceCompare = card.querySelector('.product-price .compare');
    if (priceCurrent) {
        priceCurrent.textContent = currencyFormatter.format(product.price || 0);
    }
    if (priceCompare) {
        if (product.comparePrice && product.comparePrice > product.price) {
            priceCompare.style.display = 'inline';
            priceCompare.textContent = currencyFormatter.format(product.comparePrice);
        } else {
            priceCompare.style.display = 'none';
        }
    }

    const button = card.querySelector('.add-to-cart');
    if (button) {
        const disabled = product.stock <= 0 || product.status === 0;
        button.disabled = disabled;
        button.textContent = disabled ? 'Agotado' : 'Agregar al carrito';
        button.addEventListener('click', () => {
            window.dispatchEvent(
                new CustomEvent('catalog:add-to-cart', {
                    detail: product
                })
            );
        });
    }

    return card;
};

export const createSkeletonCard = () => {
    const skeleton = document.createElement('article');
    skeleton.className = 'product-card skeleton';
    skeleton.innerHTML = `
        <div class="product-figure" style="min-height: 200px;"></div>
        <div class="product-info">
            <div style="height: 14px; width: 90%; margin-bottom: 0.5rem;"></div>
            <div style="height: 18px; width: 70%; margin-bottom: 0.75rem;"></div>
            <div style="height: 16px; width: 50%;"></div>
        </div>
    `;
    return skeleton;
};