// ==============================
// ShirtShop - Main Application JS
// ==============================

const API = {
    inventory: 'api/inventory.php',
    sale:      'api/sale.php',
    report:    'api/report.php',
};

let cart          = [];
let allProducts   = [];
let salesChart    = null;
let currentInvCat = '';
let currentPosCat = '';
let selectedPayment = 'cash';

// ==============================
// Navigation
// ==============================
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', e => {
        e.preventDefault();
        const page = item.dataset.page;
        navigateTo(page);
    });
});

function navigateTo(page) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));

    const navEl  = document.getElementById(`nav-${page}`);
    const pageEl = document.getElementById(`page-${page}`);
    if (navEl)  navEl.classList.add('active');
    if (pageEl) pageEl.classList.add('active');

    const titles = {
        dashboard: 'Dashboard',
        inventory: 'จัดการสินค้า (Inventory)',
        pos:       'หน้าขาย (POS)',
        orders:    'คำสั่งซื้อ (Sale)',
        report:    'รายงาน (Report)',
    };
    document.getElementById('page-title').textContent = titles[page] || page;

    // โหลดข้อมูลตามหน้า
    if (page === 'dashboard') loadDashboard();
    if (page === 'inventory') loadInventory();
    if (page === 'pos')       loadPOS();
    if (page === 'orders')    loadOrders();
    if (page === 'report')    showReport('daily');
}

// Sidebar toggle
document.getElementById('sidebar-toggle').addEventListener('click', () => {
    document.body.classList.toggle('sidebar-collapsed');
});

// Clock
function updateClock() {
    const now = new Date();
    document.getElementById('time-display').textContent =
        now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// ==============================
// API Helper
// ==============================
async function apiFetch(url, options = {}) {
    try {
        const res = await fetch(url, options);
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        return { error: err.message };
    }
}

// ==============================
// DASHBOARD
// ==============================
async function loadDashboard() {
    const [summary, chartData, topProducts] = await Promise.all([
        apiFetch(`${API.report}?action=dashboard`),
        apiFetch(`${API.report}?action=chart`),
        apiFetch(`${API.report}?action=top_products&limit=5`),
    ]);

    // Update Stats
    document.getElementById('stat-total-revenue').textContent  = formatMoney(summary.total_revenue);
    document.getElementById('stat-today-revenue').textContent  = formatMoney(summary.today_revenue);
    document.getElementById('stat-total-orders').textContent   = summary.total_orders;
    document.getElementById('stat-total-products').textContent = summary.total_products;

    // Low stock badge
    const badge = document.getElementById('low-stock-badge');
    if (summary.low_stock > 0) {
        badge.style.display = 'inline';
        badge.textContent   = `⚠️ สต็อกใกล้หมด ${summary.low_stock} รายการ`;
    } else {
        badge.style.display = 'none';
    }

    // Chart
    if (salesChart) salesChart.destroy();
    const ctx = document.getElementById('sales-chart').getContext('2d');
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data:  chartData.data  || [],
                borderColor:     '#4f8ef7',
                backgroundColor: 'rgba(79,142,247,0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#4f8ef7',
                pointRadius: 5,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#94a3b8' } },
                tooltip: { callbacks: { label: ctx => '฿' + ctx.parsed.y.toLocaleString('th-TH') } }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#94a3b8', callback: v => '฿' + v.toLocaleString() }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });

    // Top Products
    const listEl = document.getElementById('top-products-list');
    if (!topProducts.length) {
        listEl.innerHTML = '<p class="loading">ยังไม่มีข้อมูลการขาย</p>';
    } else {
        listEl.innerHTML = topProducts.map((p, i) => {
            const ranks  = ['gold', 'silver', 'bronze'];
            const medals = ['🥇', '🥈', '🥉'];
            const rankClass = ranks[i] || '';
            const medal     = medals[i] || (i + 1);
            return `<div class="top-product-item">
                <div class="top-rank ${rankClass}">${medal}</div>
                <div class="top-info">
                    <div class="name">${escHtml(p.name)}</div>
                    <div class="sub"><span class="badge" style="background:rgba(255,255,255,0.05);color:var(--text-secondary);font-size:10px;">${escHtml(p.category || 'ทั่วไป')}</span> ${escHtml(p.color || '')} ${escHtml(p.size || '')}</div>
                </div>
                <div class="top-sold">
                    <div class="sold">ขาย ${p.total_sold} ตัว</div>
                    <div class="rev">${formatMoney(p.revenue)}</div>
                </div>
            </div>`;
        }).join('');
    }
}

// ==============================
// INVENTORY
// ==============================
async function loadInventory(keyword = '', category = currentInvCat) {
    let url = API.inventory;
    const params = new URLSearchParams();
    if (keyword) {
        params.append('action', 'search');
        params.append('q', keyword);
    } else if (category) {
        params.append('category', category);
    }

    if ([...params].length > 0) {
        url += '?' + params.toString();
    }

    const products = await apiFetch(url);
    renderProductsTable(products);
}

function renderProductsTable(products) {
    const tbody = document.getElementById('products-tbody');
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center loading">ไม่พบสินค้า</td></tr>';
        return;
    }
    tbody.innerHTML = products.map(p => `
        <tr>
            <td>${p.image ? `<img src="uploads/${escHtml(p.image)}" class="product-thumb">` : `<div class="product-thumb">👕</div>`}</td>
            <td><strong>${escHtml(p.name)}</strong><br><small style="color:var(--text-secondary)">${escHtml(p.description||'')}</small></td>
            <td><span class="user-role-badge" style="background:rgba(255,255,255,0.06);color:var(--text-primary);">${escHtml(p.category || 'ทั่วไป')}</span></td>
            <td style="color:var(--accent-green);font-weight:600">${formatMoney(p.price)}</td>
            <td>
                <span style="color:${p.stock <= 5 ? 'var(--accent-orange)' : 'var(--text-primary)'}">
                    ${p.stock} ชิ้น ${p.stock <= 5 ? '⚠️' : ''}
                </span>
            </td>
            <td>${escHtml(p.size || '-')}</td>
            <td>${escHtml(p.color || '-')}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-sm btn-outline" onclick="openRestockModal(${p.id}, '${escHtml(p.name)}')" title="เติมสต็อก">➕</button>
                    <button class="btn btn-sm btn-outline" onclick="editProduct(${p.id})" title="แก้ไข">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id},'${escHtml(p.name)}')" title="ลบ">🗑️</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Search Inventory
let invSearchTimer;
document.getElementById('inv-search').addEventListener('input', e => {
    clearTimeout(invSearchTimer);
    invSearchTimer = setTimeout(() => loadInventory(e.target.value), 300);
});

// Category pills on Inventory
document.getElementById('inv-category-bar').addEventListener('click', e => {
    const pill = e.target.closest('.category-pill');
    if (!pill) return;
    document.querySelectorAll('#inv-category-bar .category-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    currentInvCat = pill.dataset.cat;
    loadInventory('', currentInvCat);
});

// Add / Edit Product Modal
function openAddProductModal() {
    document.getElementById('modal-title').textContent = '+ เพิ่มสินค้า';
    document.getElementById('product-id').value    = '';
    document.getElementById('product-form').reset();
    document.getElementById('product-modal').style.display = 'flex';
}

async function editProduct(id) {
    const product = await apiFetch(`${API.inventory}?id=${id}`);
    if (product.error) return showToast(product.error, 'error');

    document.getElementById('modal-title').textContent = '✏️ แก้ไขสินค้า';
    document.getElementById('product-id').value    = product.id;
    document.getElementById('p-name').value        = product.name;
    document.getElementById('p-category').value    = product.category || 'ทั่วไป';
    document.getElementById('p-price').value       = product.price;
    document.getElementById('p-stock').value       = product.stock;
    document.getElementById('p-color').value       = product.color  || '';
    document.getElementById('p-size').value        = product.size   || '';
    document.getElementById('p-desc').value        = product.description || '';
    document.getElementById('product-modal').style.display = 'flex';
}

async function saveProduct() {
    const id   = document.getElementById('product-id').value;
    const form = document.getElementById('product-form');
    const fd   = new FormData(form);
    fd.set('name',        document.getElementById('p-name').value);
    fd.set('category',    document.getElementById('p-category').value);
    fd.set('price',       document.getElementById('p-price').value);
    fd.set('stock',       document.getElementById('p-stock').value);
    fd.set('color',       document.getElementById('p-color').value);
    fd.set('size',        document.getElementById('p-size').value);
    fd.set('description', document.getElementById('p-desc').value);

    const file = document.getElementById('p-image').files[0];
    if (file) fd.set('image', file);

    let result;
    if (id) {
        result = await apiFetch(`${API.inventory}?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                name: fd.get('name'),
                category: fd.get('category'),
                price: fd.get('price'),
                stock: fd.get('stock'),
                color: fd.get('color'),
                size: fd.get('size'),
                description: fd.get('description')
            })
        });
    } else {
        result = await fetch(API.inventory, { method: 'POST', body: fd }).then(r => r.json());
    }

    if (result.success) {
        showToast(result.message, 'success');
        closeProductModal();
        loadInventory();
    } else {
        showToast((result.errors || [result.message]).join(', '), 'error');
    }
}

async function deleteProduct(id, name) {
    if (!confirm(`ยืนยันการลบสินค้า "${name}" ?`)) return;
    const result = await apiFetch(`${API.inventory}?id=${id}`, { method: 'DELETE' });
    if (result.success) {
        showToast(result.message, 'success');
        loadInventory();
    } else {
        showToast(result.message, 'error');
    }
}

function closeProductModal() {
    document.getElementById('product-modal').style.display = 'none';
}

// Quick Restock
function openRestockModal(id, name) {
    document.getElementById('restock-product-id').value = id;
    document.getElementById('restock-product-name').textContent = `สินค้า: ${name}`;
    document.getElementById('restock-amount').value = 10;
    document.getElementById('restock-modal').style.display = 'flex';
}

async function submitRestock() {
    const id = document.getElementById('restock-product-id').value;
    const amount = parseInt(document.getElementById('restock-amount').value, 10);
    if (!amount || amount <= 0) {
        showToast('กรุณากรอกจำนวนที่ถูกต้อง', 'error');
        return;
    }

    const res = await apiFetch(`${API.inventory}?action=restock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, amount: amount })
    });

    if (res.success) {
        showToast(res.message, 'success');
        closeModal('restock-modal');
        loadInventory();
    } else {
        showToast(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
}

// ==============================
// POS (POINT OF SALE)
// ==============================
async function loadPOS(category = currentPosCat) {
    let url = `${API.sale}?action=products`;
    if (category) {
        url += `&category=${encodeURIComponent(category)}`;
    }
    const products = await apiFetch(url);
    allProducts = products;
    renderPOSProducts(products);
}

function renderPOSProducts(products) {
    const grid = document.getElementById('pos-product-grid');
    if (!products.length) {
        grid.innerHTML = '<p class="loading">ไม่พบสินค้าในหมวดหมู่นี้</p>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="product-card" onclick="addToCart(${p.id})" id="pos-card-${p.id}">
            <div class="emoji">👕</div>
            <div class="p-name">${escHtml(p.name)}</div>
            <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">${escHtml(p.category || 'ทั่วไป')}</div>
            <div class="p-price">${formatMoney(p.price)}</div>
            <div class="p-stock">เหลือ ${p.stock} ตัว</div>
        </div>
    `).join('');
}

// POS Category Filter
document.getElementById('pos-category-bar').addEventListener('click', e => {
    const pill = e.target.closest('.category-pill');
    if (!pill) return;
    document.querySelectorAll('#pos-category-bar .category-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    currentPosCat = pill.dataset.cat;
    loadPOS(currentPosCat);
});

// POS Search
let posSearchTimer;
document.getElementById('pos-search').addEventListener('input', e => {
    clearTimeout(posSearchTimer);
    posSearchTimer = setTimeout(() => {
        const kw = e.target.value.toLowerCase();
        const filtered = allProducts.filter(p =>
            p.name.toLowerCase().includes(kw) ||
            (p.color || '').toLowerCase().includes(kw) ||
            (p.category || '').toLowerCase().includes(kw)
        );
        renderPOSProducts(filtered);
    }, 200);
});

// Payment Method Selector
document.getElementById('payment-method-selector').addEventListener('click', e => {
    const opt = e.target.closest('.payment-opt');
    if (!opt) return;
    document.querySelectorAll('#payment-method-selector .payment-opt').forEach(o => o.classList.remove('active'));
    opt.classList.add('active');
    selectedPayment = opt.dataset.method;

    const cashSec = document.getElementById('cash-payment-section');
    if (selectedPayment === 'cash') {
        cashSec.style.display = 'block';
    } else {
        cashSec.style.display = 'none';
    }
    updateChangeDisplay();
});

function addToCart(productId) {
    const product = allProducts.find(p => p.id === productId);
    if (!product) return;

    const existing = cart.find(c => c.product_id === productId);
    if (existing) {
        if (existing.quantity >= product.stock) {
            showToast('สต็อกไม่เพียงพอ', 'error'); return;
        }
        existing.quantity++;
    } else {
        cart.push({ product_id: productId, name: product.name, price: product.price, quantity: 1, max: product.stock });
    }
    renderCart();
}

function changeQty(productId, delta) {
    const idx = cart.findIndex(c => c.product_id === productId);
    if (idx === -1) return;
    cart[idx].quantity += delta;
    if (cart[idx].quantity <= 0) {
        cart.splice(idx, 1);
    } else if (cart[idx].quantity > cart[idx].max) {
        cart[idx].quantity = cart[idx].max;
        showToast('สต็อกไม่เพียงพอ', 'info');
    }
    renderCart();
}

function renderCart() {
    const el    = document.getElementById('cart-items');
    const total = cart.reduce((s, c) => s + c.price * c.quantity, 0);

    if (!cart.length) {
        el.innerHTML = '<p class="empty-cart">ยังไม่มีสินค้าในตะกร้า</p>';
    } else {
        el.innerHTML = cart.map(c => `
            <div class="cart-item">
                <div class="cart-item-name">${escHtml(c.name)}</div>
                <div class="qty-control">
                    <button class="qty-btn" onclick="changeQty(${c.product_id}, -1)">-</button>
                    <span class="qty-num">${c.quantity}</span>
                    <button class="qty-btn" onclick="changeQty(${c.product_id}, 1)">+</button>
                </div>
                <div class="cart-item-price">${formatMoney(c.price * c.quantity)}</div>
            </div>
        `).join('');
    }
    document.getElementById('cart-total').textContent = formatMoney(total);
    updateChangeDisplay();
}

function clearCart() {
    cart = [];
    document.getElementById('cash-received').value = '';
    renderCart();
}

// Cash amount helpers
function setCashAmount(val) {
    const total = cart.reduce((s, c) => s + c.price * c.quantity, 0);
    const input = document.getElementById('cash-received');
    if (val === 'exact') {
        input.value = total;
    } else {
        input.value = val;
    }
    updateChangeDisplay();
}

document.getElementById('cash-received').addEventListener('input', updateChangeDisplay);

function updateChangeDisplay() {
    const total = cart.reduce((s, c) => s + c.price * c.quantity, 0);
    const received = parseFloat(document.getElementById('cash-received').value) || 0;
    const change = Math.max(0, received - total);
    document.getElementById('display-change').textContent = formatMoney(change);
}

// Submit Order
async function submitOrder() {
    const customerName  = document.getElementById('customer-name').value.trim() || 'ลูกค้าทั่วไป';
    const customerPhone = document.getElementById('customer-phone').value.trim();
    const total         = cart.reduce((s, c) => s + c.price * c.quantity, 0);
    let cashReceived    = parseFloat(document.getElementById('cash-received').value) || 0;

    if (!cart.length) {
        showToast('ไม่มีสินค้าในตะกร้า', 'error'); return;
    }

    if (selectedPayment === 'cash') {
        if (cashReceived > 0 && cashReceived < total) {
            showToast(`เงินที่รับมาน้อยกว่ายอดชำระ (${formatMoney(total)})`, 'error');
            return;
        }
        if (cashReceived === 0) cashReceived = total;
    } else {
        cashReceived = total;
    }

    const btn = document.getElementById('checkout-btn');
    btn.disabled = true; btn.textContent = 'กำลังบันทึก...';

    const result = await apiFetch(`${API.sale}?action=order`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            customer_name:   customerName,
            customer_phone:  customerPhone,
            payment_method:  selectedPayment,
            amount_received: cashReceived,
            items: cart.map(c => ({ product_id: c.product_id, quantity: c.quantity }))
        })
    });

    btn.disabled = false; btn.textContent = '✅ ชำระเงิน & ออกใบเสร็จ';

    if (result.success) {
        showToast(result.message, 'success');
        const orderId = result.order_id;

        // ล้างฟอร์ม
        cart = [];
        renderCart();
        document.getElementById('customer-name').value  = '';
        document.getElementById('customer-phone').value = '';
        document.getElementById('cash-received').value  = '';
        loadPOS(); // รีเฟรชสต็อก

        // แสดงใบเสร็จรับเงินทันที
        viewOrderDetail(orderId, true);
    } else {
        showToast(result.message, 'error');
    }
}

// ==============================
// ORDERS
// ==============================
let currentOrderStatus = '';

async function loadOrders(status = '') {
    currentOrderStatus = status;
    const orders = await apiFetch(`${API.sale}${status ? '?status=' + status : ''}`);
    renderOrdersTable(orders);
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('orders-tbody');
    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center loading">ไม่พบคำสั่งซื้อ</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(o => `
        <tr>
            <td><strong>#${o.id}</strong></td>
            <td>${escHtml(o.customer_name)}</td>
            <td>${escHtml(o.customer_phone || '-')}</td>
            <td style="color:var(--accent-green);font-weight:600">${formatMoney(o.total_amount)}</td>
            <td><span class="user-role-badge" style="background:rgba(255,255,255,0.06);color:var(--text-primary);">${paymentMethodTH(o.payment_method)}</span></td>
            <td><span class="status-badge status-${o.status}">${statusTH(o.status)}</span></td>
            <td>${formatDate(o.created_at)}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-sm btn-outline" onclick="viewOrderDetail(${o.id})" title="ดูรายละเอียด">👁️</button>
                    <button class="btn btn-sm btn-outline" onclick="viewOrderDetail(${o.id}, true)" title="พิมพ์ใบเสร็จ">🖨️</button>
                    ${o.status === 'pending' ? `
                        <button class="btn btn-sm btn-success" onclick="confirmOrder(${o.id})" title="ยืนยัน">✅</button>
                        <button class="btn btn-sm btn-danger"  onclick="cancelOrder(${o.id})" title="ยกเลิก">❌</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Filter tabs
document.getElementById('order-filter-tabs').addEventListener('click', e => {
    const tab = e.target.closest('.filter-tab');
    if (!tab) return;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    loadOrders(tab.dataset.status);
});

// View Order / Print Receipt Modal
async function viewOrderDetail(id, autoPrint = false) {
    const order = await apiFetch(`${API.sale}?action=detail&id=${id}`);
    const modal = document.getElementById('order-detail-modal');
    document.getElementById('order-detail-title').textContent = `ใบเสร็จรับเงิน #${order.id}`;

    const itemsHTML = (order.items || []).map(item => `
        <div class="r-row">
            <span>${escHtml(item.name)} x${item.quantity}</span>
            <span>${formatMoney(item.unit_price * item.quantity)}</span>
        </div>
    `).join('');

    const received = parseFloat(order.amount_received) || parseFloat(order.total_amount);
    const change   = parseFloat(order.change_amount) || 0;

    document.getElementById('order-detail-body').innerHTML = `
        <div class="receipt-paper" id="printable-receipt">
            <div class="r-center">
                <div class="r-title">👕 SHIRTSHOP</div>
                <div class="r-subtitle">ระบบขายเสื้อผ้าแฟชั่นคุณภาพ</div>
                <div class="r-subtitle">โทร: 02-123-4567 | TAX ID: 0105562000000</div>
            </div>
            <div class="r-divider"></div>
            <div class="r-row"><span>เลขที่ใบเสร็จ:</span><span>#${order.id}</span></div>
            <div class="r-row"><span>วันที่:</span><span>${formatDate(order.created_at)}</span></div>
            <div class="r-row"><span>ลูกค้า:</span><span>${escHtml(order.customer_name)}</span></div>
            <div class="r-row"><span>ชำระโดย:</span><span>${paymentMethodTH(order.payment_method)}</span></div>
            <div class="r-divider"></div>
            <div>${itemsHTML}</div>
            <div class="r-double-divider"></div>
            <div class="r-row r-total-row">
                <span>ยอดสุทธิ:</span>
                <span>${formatMoney(order.total_amount)}</span>
            </div>
            <div class="r-row">
                <span>รับเงินมา:</span>
                <span>${formatMoney(received)}</span>
            </div>
            <div class="r-row">
                <span>เงินทอน:</span>
                <span>${formatMoney(change)}</span>
            </div>
            <div class="r-divider"></div>
            <div class="r-footer">
                🙏 ขอบพระคุณที่อุดหนุนครับ<br>
                สินค้าซื้อแล้วกรุณาเก็บใบเสร็จไว้เพื่อเปลี่ยนไซส์ภายใน 7 วัน
            </div>
        </div>
    `;

    document.getElementById('order-detail-footer').innerHTML = `
        <button class="btn btn-outline" onclick="closeModal('order-detail-modal')">ปิด</button>
        <button class="btn btn-primary" onclick="window.print()">🖨️ สั่งพิมพ์ใบเสร็จ</button>
    `;

    modal.style.display = 'flex';

    if (autoPrint) {
        setTimeout(() => window.print(), 300);
    }
}

async function confirmOrder(id) {
    if (!confirm(`ยืนยันคำสั่งซื้อ #${id} ?`)) return;
    const result = await apiFetch(`${API.sale}?action=confirm&id=${id}`, { method: 'PUT' });
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) loadOrders(currentOrderStatus);
}

async function cancelOrder(id) {
    if (!confirm(`ยกเลิกคำสั่งซื้อ #${id} และคืนสต็อก?`)) return;
    const result = await apiFetch(`${API.sale}?action=cancel&id=${id}`, { method: 'PUT' });
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) loadOrders(currentOrderStatus);
}

// ==============================
// REPORT
// ==============================
async function showReport(type) {
    document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.report-section').forEach(s => s.style.display = 'none');

    const tabs = { daily: 0, monthly: 1, top: 2, lowstock: 3 };
    const tabEls = document.querySelectorAll('.report-tab');
    tabEls[tabs[type]]?.classList.add('active');

    if (type === 'daily') {
        document.getElementById('report-daily').style.display = 'block';
        const rows = await apiFetch(`${API.report}?action=daily&days=30`);
        document.getElementById('report-daily-tbody').innerHTML = rows.length
            ? rows.map(r => `<tr><td>${formatDate(r.date, true)}</td><td>${r.order_count} ออเดอร์</td><td style="color:var(--accent-green);font-weight:600">${formatMoney(r.revenue)}</td></tr>`).join('')
            : '<tr><td colspan="3" class="text-center loading">ไม่มีข้อมูล</td></tr>';
    }
    if (type === 'monthly') {
        document.getElementById('report-monthly').style.display = 'block';
        const rows = await apiFetch(`${API.report}?action=monthly`);
        document.getElementById('report-monthly-tbody').innerHTML = rows.length
            ? rows.map(r => `<tr><td>${r.month_name}</td><td>${r.order_count} ออเดอร์</td><td style="color:var(--accent-green);font-weight:600">${formatMoney(r.revenue)}</td></tr>`).join('')
            : '<tr><td colspan="3" class="text-center loading">ไม่มีข้อมูล</td></tr>';
    }
    if (type === 'top') {
        document.getElementById('report-top').style.display = 'block';
        const products = await apiFetch(`${API.report}?action=top_products&limit=5`);
        const el = document.getElementById('report-top-body');
        el.innerHTML = products.length
            ? products.map((p, i) => {
                const medals = ['🥇', '🥈', '🥉'];
                const ranks  = ['gold', 'silver', 'bronze'];
                return `<div class="top-product-item">
                    <div class="top-rank ${ranks[i] || ''}">${medals[i] || (i+1)}</div>
                    <div class="top-info">
                        <div class="name">${escHtml(p.name)}</div>
                        <div class="sub">${escHtml(p.color || '')} ${escHtml(p.size || '')}</div>
                    </div>
                    <div class="top-sold">
                        <div class="sold">ขาย ${p.total_sold} ตัว</div>
                        <div class="rev">${formatMoney(p.revenue)}</div>
                    </div>
                </div>`;
            }).join('')
            : '<p class="loading">ยังไม่มีข้อมูลการขาย</p>';
    }
    if (type === 'lowstock') {
        document.getElementById('report-lowstock').style.display = 'block';
        const rows = await apiFetch(`${API.report}?action=low_stock&threshold=5`);
        document.getElementById('report-lowstock-tbody').innerHTML = rows.length
            ? rows.map(r => `<tr>
                <td>${escHtml(r.name)}</td>
                <td style="font-weight:600;color:${r.stock === 0 ? 'var(--accent-red)' : 'var(--accent-orange)'}">${r.stock} ตัว</td>
                <td><span class="status-badge ${r.stock === 0 ? 'status-cancelled' : 'status-pending'}">${r.status_label}</span></td>
                <td><button class="btn btn-sm btn-outline" onclick="openRestockModal(${r.id}, '${escHtml(r.name)}')">➕ เติมสต็อก</button></td>
            </tr>`).join('')
            : '<tr><td colspan="4" class="text-center" style="color:var(--accent-green);padding:20px">✅ สต็อกปกติทุกรายการ</td></tr>';
    }
}

// ==============================
// Modal Helper
// ==============================
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});

// ==============================
// Toast
// ==============================
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// ==============================
// Utilities
// ==============================
function formatMoney(amount) {
    return '฿' + parseFloat(amount || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr, dateOnly = false) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (dateOnly) return d.toLocaleDateString('th-TH');
    return d.toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' });
}

function statusTH(status) {
    const map = { pending: 'รอยืนยัน', confirmed: 'ยืนยันแล้ว', cancelled: 'ยกเลิก' };
    return map[status] || status;
}

function paymentMethodTH(method) {
    const map = { cash: '💵 เงินสด', promptpay: '📱 พร้อมเพย์', credit: '💳 บัตรเครดิต' };
    return map[method] || method || 'เงินสด';
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ==============================
// Init
// ==============================
loadDashboard();
