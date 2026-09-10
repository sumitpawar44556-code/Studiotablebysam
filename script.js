document.addEventListener('DOMContentLoaded', function () {
  const revealElements = document.querySelectorAll('.reveal');
  const progressBar = document.querySelector('.scroll-progress span');

  if (revealElements.length) {
    const revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.16 });
    revealElements.forEach(element => revealObserver.observe(element));
  }

  function updateScrollProgress() {
    if (!progressBar) return;
    const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
    progressBar.style.width = `${scrollableHeight > 0 ? (window.scrollY / scrollableHeight) * 100 : 0}%`;
  }

  if (progressBar) {
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    updateScrollProgress();
  }

  const search = document.getElementById('search');
  const category = document.getElementById('category');
  const menuEl = document.getElementById('menu');
  const template = document.getElementById('menu-item-template');
  const cartItemsEl = document.getElementById('cart-items');
  const cartCountEl = document.getElementById('cart-count');
  const cartTotalEl = document.getElementById('cart-total');
  const checkoutBtn = document.getElementById('checkout-btn');
  let items = [];
  const cart = new Map();

  function render(list) {
    if (!menuEl) return;
    menuEl.innerHTML = '';
    list.forEach(item => {
      if (!template) return;
      const node = template.content.cloneNode(true);
      const article = node.querySelector('.menu-item');
      article.dataset.category = item.category;
      const image = node.querySelector('img.menu-img');
      image.src = new URL(item.image, document.baseURI).href;
      image.alt = item.name;
      image.addEventListener('error', function () {
        if (this.src.endsWith('/food-placeholder.svg')) return;
        this.src = '/SUMIT/images/food-placeholder.svg';
      });
      node.querySelector('h4').textContent = item.name;
      node.querySelector('.desc').textContent = item.desc;
      node.querySelector('.price').textContent = 'Rs ' + item.price;
      const btn = node.querySelector('.order-btn');
      btn.addEventListener('click', function () {
        cart.set(item.id, { ...item, qty: (cart.get(item.id)?.qty || 0) + 1 });
        renderCart();
      });
      menuEl.appendChild(node);
    });
  }

  function renderCart() {
    if (!cartItemsEl) return;
    const selectedItems = Array.from(cart.values());
    const itemCount = selectedItems.reduce((sum, item) => sum + item.qty, 0);
    const totalPrice = selectedItems.reduce((sum, item) => sum + item.price * item.qty, 0);
    cartCountEl.textContent = itemCount + (itemCount === 1 ? ' item' : ' items');
    cartTotalEl.textContent = 'Rs ' + totalPrice.toFixed(2);
    checkoutBtn.disabled = selectedItems.length === 0;
    cartItemsEl.innerHTML = '';

    if (selectedItems.length === 0) {
      cartItemsEl.innerHTML = '<p class="cart-empty">Add food items to build one order.</p>';
      return;
    }

    selectedItems.forEach(item => {
      const row = document.createElement('div');
      row.className = 'cart-row';
      row.innerHTML = `<span>${item.name}<small>Rs ${item.price} each</small></span>
        <span class="cart-actions"><button type="button" data-action="decrease" aria-label="Remove one ${item.name}">-</button>
        <strong>${item.qty}</strong><button type="button" data-action="increase" aria-label="Add one ${item.name}">+</button></span>`;
      row.querySelector('[data-action="decrease"]').addEventListener('click', () => updateCart(item, -1));
      row.querySelector('[data-action="increase"]').addEventListener('click', () => updateCart(item, 1));
      cartItemsEl.appendChild(row);
    });
  }

  function updateCart(item, change) {
    const updatedQty = (cart.get(item.id)?.qty || 0) + change;
    if (updatedQty <= 0) cart.delete(item.id);
    else cart.set(item.id, { ...item, qty: updatedQty });
    renderCart();
  }

  function checkout() {
    const selectedItems = Array.from(cart.values());
    if (selectedItems.length === 0) return;
    const cust = prompt('Enter your name to place this order');
        if (!cust) return;
    const table = prompt('Table Number', '');
    if (!table) return;
    const payment = prompt('Payment Option (Cash/Card/Online)', 'Cash');
    if (!payment) return;
    const totalPrice = selectedItems.reduce((sum, item) => sum + item.price * item.qty, 0);
    placeOrder({
      itemId: selectedItems.length === 1 ? selectedItems[0].id : selectedItems.map(item => item.id),
      name: selectedItems.map(item => item.name).join(', '),
      price: totalPrice,
      qty: 1,
      items: selectedItems.map(({ id, name, price, qty }) => ({ itemId: id, name, price, qty })),
      customer: cust,
      tableNumber: table,
      paymentOption: payment
    });
  }

  function filterAndRender() {
    const q = (search?.value || '').toLowerCase();
    const cat = (category?.value || 'all');
    const filtered = items.filter(it => {
      const text = (it.name + ' ' + it.desc + ' ' + it.price).toLowerCase();
      const matchesQuery = q === '' || text.includes(q);
      const matchesCat = cat === 'all' || it.category === cat;
      return matchesQuery && matchesCat;
    });
    render(filtered);
  }

  function populateCategories() {
    if (!category) return;
    const categories = [...new Set(items.map(item => item.category).filter(Boolean))];
    category.innerHTML = '<option value="all">All</option>';
    categories.forEach(value => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value.replace(/[-_]/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
      category.appendChild(option);
    });
  }

  function showOrderModal(order, totalPrice) {
    let discountPercent = 0;
    if (totalPrice >= 1500) {
      discountPercent = 30;
    } else if (totalPrice >= 500) {
      discountPercent = 15;
    } else if (totalPrice >= 299) {
      discountPercent = 10;
    }
    
    const discount = Math.round(totalPrice * (discountPercent / 100));
    const finalPrice = totalPrice - discount;
    
    let modalHTML = `
      <div id="orderModal" class="modal-overlay">
        <div class="modal-content">
          <div class="modal-header success">
            <div class="success-icon">✓</div>
            <h2>Order Placed Successfully!</h2>
          </div>
          <div class="modal-body">
            <p><strong>Order ID:</strong> ${order.orderId || 'Saved successfully'}</p>
            <p><strong>Status:</strong> ${order.status || 'Pending'}</p>
            <p><strong>Customer:</strong> ${order.customer}</p>
            <p><strong>Items:</strong><br>${order.items ? order.items.map(item => `${item.name} x ${item.qty}`).join('<br>') : `${order.name} x ${order.qty}`}</p>
            <p><strong>Table Number:</strong> ${order.tableNumber || 'N/A'}</p>
            <p><strong>Payment:</strong> ${order.paymentOption || 'Cash'}</p>
            <hr style="margin:1rem 0; border:none; border-top:1px solid #eee;">
            <div class="price-breakdown">
              <p><strong>Order Total:</strong> Rs ${totalPrice.toFixed(2)}</p>`;
    
    if (discount > 0) {
      modalHTML += `<p style="color:#4CAF50;"><strong>🎉 ${discountPercent}% Discount Applied:</strong> -Rs ${discount.toFixed(2)}</p>
              <p style="font-size:1.2rem; font-weight:700; color:var(--accent);"><strong>Final Price:</strong> Rs ${finalPrice.toFixed(2)}</p>`;
    }
    
    modalHTML += `</div>
          </div>
          <div class="modal-footer">
            <button class="btn modal-close" onclick="document.getElementById('orderModal').remove()">Close</button>
          </div>
        </div>
      </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Auto-close after 8 seconds
    setTimeout(() => {
      const modal = document.getElementById('orderModal');
      if (modal) modal.remove();
    }, 8000);
  }

  function placeOrder(order) {
    const totalPrice = order.items
      ? order.items.reduce((sum, item) => sum + item.price * item.qty, 0)
      : order.price * order.qty;
    fetch(new URL('orders.php', document.baseURI), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(order)
    }).then(async response => {
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'The order could not be saved.');
      return result;
    }).then(resp => {
      order.orderId = resp.orderId;
      order.status = resp.status;
      showOrderModal(order, totalPrice);
      cart.clear();
      renderCart();
    }).catch(err => {
      alert('Order failed: ' + err.message);
      console.error(err);
    });

  }

  if (checkoutBtn) checkoutBtn.addEventListener('click', checkout);
  renderCart();

  // load menu.json
  fetch(new URL('menu.json', document.baseURI)).then(response => {
    if (!response.ok) throw new Error('Menu data could not be loaded.');
    return response.json();
  }).then(data => {
    items = data;
    populateCategories();
    filterAndRender();
  }).catch(err => console.error('Failed to load menu.json', err));

  if (search) search.addEventListener('input', filterAndRender);
  if (category) category.addEventListener('change', filterAndRender);
});
