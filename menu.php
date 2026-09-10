<?php include __DIR__ . '/inc/header.php'; ?>

      <div class="menu-page">
      <section class="menu-hero">
        <span class="eyebrow">Studio Table by Sam</span>
        <h2>Choose something delicious.</h2>
        <p>From quick bites to comfort food, find your next favourite and build your order.</p>
      </section>
      <section class="menu-controls">
        <label>Search: <input id="search" placeholder="Search items..." /></label>
        <label>Category:
          <select id="category">
            <option value="all">All</option>
          </select>
        </label>
      </section>

      <section id="menu" class="menu-grid">
        <!-- Items will be rendered dynamically from menu.json -->
      </section>

      <aside id="order-cart" class="order-cart" aria-live="polite">
        <div class="cart-heading">
          <h3>Your order</h3>
          <span id="cart-count">0 items</span>
        </div>
        <div id="cart-items" class="cart-items">
          <p class="cart-empty">Add food items to build one order.</p>
        </div>
        <div class="cart-total"><strong>Total</strong><strong id="cart-total">Rs 0.00</strong></div>
        <button id="checkout-btn" class="btn checkout-btn" type="button" disabled>Place Order</button>
      </aside>

      <template id="menu-item-template">
        <article class="menu-item">
          <img class="menu-img" src="" alt="" />
          <h4></h4>
          <p class="desc"></p>
          <div class="price"></div>
          <button class="order-btn btn" type="button">Add to order</button>
        </article>
      </template>
        </div>

      <?php include __DIR__ . '/inc/footer.php'; ?>
