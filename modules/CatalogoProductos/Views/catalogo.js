// catalogo.js
// Ejemplo de renderizado y validación adicional

document.addEventListener('DOMContentLoaded', () => {
    initCatalogo();
});

function escapeHTML(s){return (s??'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}

function baseModulo(){
    const p = location.pathname.replace(/\\/g,'/');
    const marker = '/modules/CatalogoProductos/Views/';
    const idx = p.indexOf(marker);
    return idx!==-1 ? p.substring(0, idx+1)+'modules/CatalogoProductos/' : '/modules/CatalogoProductos/';
}

const MOD_BASE = baseModulo();
const ENDPOINTS = {
    producto: MOD_BASE + 'Controllers/producto_api.php',
    carrito: MOD_BASE + 'Controllers/carrito_api.php',
    items: MOD_BASE + 'Controllers/carrito_items_api.php'
};

// Obtiene o crea un carrito anónimo usando session_token localStorage
async function ensureCarrito(){
    const token = ensureToken();
    try {
        const r = await fetch(`${ENDPOINTS.carrito}?session_token=${encodeURIComponent(token)}`);
        const d = await r.json();
        if(r.ok && d.success && d.carrito){ return d.carrito; }
    } catch {}
    const resp = await fetch(ENDPOINTS.carrito, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({session_token: token, moneda:'USD'})});
    const data = await resp.json();
    if(!resp.ok || !data.success) throw new Error(data.error||'No se pudo crear carrito');
    return data.carrito;
}

function ensureToken(){
    let t = localStorage.getItem('session_token');
    if(!t){ t = [...crypto.getRandomValues(new Uint8Array(16))].map(b=>('0'+b.toString(16)).slice(-2)).join(''); localStorage.setItem('session_token', t);} 
    return t;
}

async function initCatalogo(){
    const contenedor = document.getElementById('productos');
    if(!contenedor) return;
    contenedor.innerHTML = '<div class="text-muted">Cargando productos…</div>';
    try {
        const r = await fetch(`${ENDPOINTS.producto}?listar=1`);
        const d = await r.json();
        const lista = Array.isArray(d.productos)? d.productos : (Array.isArray(d.data)? d.data : []);
        if(!lista.length){ contenedor.innerHTML = '<div class="text-muted">Sin productos disponibles</div>'; return; }
        contenedor.innerHTML = lista.map(p => renderCardProducto(p)).join('');
    } catch(err){
        contenedor.innerHTML = `<div class="text-danger">Error: ${escapeHTML(err.message)}</div>`;
    }
    contenedor.addEventListener('click', onCatalogoClick);
    // Actualiza badge carrito
    try { await refreshCartBadge(); } catch {}

    // Escuchar cambios de otras pestañas (BroadcastChannel / localStorage fallback)
    try {
        const bc = new BroadcastChannel('cart_changes');
        bc.addEventListener('message', async (ev)=>{
            if(ev?.data?.cartId){ try { await refreshCartBadge(); } catch {} }
        });
    } catch {}
    window.addEventListener('storage', async (e)=>{
        if(e.key === 'cart_change_ping'){ try { await refreshCartBadge(); } catch {} }
    });
}

function renderCardProducto(p){
    const id = p.id_producto || p.id || '';
    const nombre = escapeHTML(p.nombre||'');
    const desc = escapeHTML(p.descripcion||'');
    const precio = Number(p.precio||0).toFixed(2);
    const stock = escapeHTML(p.stock ?? '0');
    const estado = (p.estado===1||p.estado==='activo'||p.estado==='1')? 'Activo':'Inactivo';
    const disabled = estado !== 'Activo' || Number(stock)<=0 ? 'disabled' : '';
    return `<div class="producto-card" data-id="${escapeHTML(id)}">
        <h3 class="h6">${nombre}</h3>
        <p class="small mb-1">${desc}</p>
        <div class="fw-semibold">$ ${precio}</div>
        <div class="small text-muted">Stock: ${stock} - ${estado}</div>
        <div class="mt-2 d-flex gap-2">
            <input type="number" min="1" max="999" value="1" class="form-control form-control-sm qty" style="width:80px;" ${disabled} />
            <button class="btn btn-sm btn-primary btn-add" ${disabled}>Agregar</button>
        </div>
    </div>`;
}

async function onCatalogoClick(e){
    const btn = e.target.closest('.btn-add');
    if(!btn) return;
    const card = btn.closest('.producto-card');
    const idProd = card?.getAttribute('data-id');
    const qtyInput = card.querySelector('input.qty');
    const qty = Math.min(999, Math.max(1, parseInt(qtyInput.value||'1',10)));
    btn.disabled = true; btn.textContent = '...';
    try {
        const carrito = await ensureCarrito();
        const token = ensureToken();
        const body = { id_producto: Number(idProd), cantidad: qty };
        const url = `${ENDPOINTS.items}?id_carrito=${encodeURIComponent(carrito.id_carrito)}&session_token=${encodeURIComponent(token)}`;
        const resp = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
        let data;
        try { data = await resp.json(); } catch { data = null; }
        if(!resp.ok || (data && data.success===false)){
            const msg = (data && (data.error || data.message)) || 'Error agregando';
            const det = data && data.detail ? ` (${data.detail})` : '';
            throw new Error(msg + det);
        }
        flash(card, 'Producto agregado');
        try { await refreshCartBadge(); } catch {}
    } catch(err){
        alert('No se pudo agregar: '+err.message);
    } finally {
        btn.disabled = false; btn.textContent = 'Agregar';
    }
}

function flash(el, msg){
    const old = el.style.position;
    if(getComputedStyle(el).position==='static') el.style.position='relative';
    const badge = document.createElement('div');
    badge.textContent = msg;
    badge.className = 'position-absolute top-0 end-0 translate-middle badge rounded-pill bg-success';
    badge.style.zIndex = '10';
    el.appendChild(badge);
    setTimeout(()=>{ badge.remove(); el.style.position = old; }, 1800);
}

async function refreshCartBadge(){
    const badge = document.getElementById('cartBadge');
    if(!badge) return;
    const token = ensureToken();
    let idCarrito;
    try {
        const r = await fetch(`${ENDPOINTS.carrito}?session_token=${encodeURIComponent(token)}`);
        const d = await r.json();
        if(r.ok && d.success && d.carrito) idCarrito = d.carrito.id_carrito;
    } catch {}
    if(!idCarrito){ badge.textContent = '0'; return; }
    try {
        const r2 = await fetch(`${ENDPOINTS.items}?id_carrito=${encodeURIComponent(idCarrito)}&count=1`);
        const d2 = await r2.json();
        if(r2.ok && d2.success){ badge.textContent = String(d2.cantidad_total ?? 0); return; }
    } catch {}
    badge.textContent = '0';
}

