<?php
session_start();
include __DIR__ . '/inc/config.php';
include __DIR__ . '/inc/auth.php';

ensure_session();

// Enforce HTTP Basic Auth
$ip = client_ip();
if (is_locked_out($ip)) {
  header('HTTP/1.1 403 Forbidden');
  echo 'Too many failed login attempts. Try again later.';
  exit;
}
if (!isset($_SERVER['PHP_AUTH_PW'])) {
  header('WWW-Authenticate: Basic realm="Admin Area"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'Authentication required.';
  exit;
}
if (!verify_pass($_SERVER['PHP_AUTH_PW'])) {
  record_failed_attempt($ip);
  header('WWW-Authenticate: Basic realm="Admin Area"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'Invalid credentials.';
  exit;
} else {
  clear_failed_attempts($ip);
}

$ordersFile = __DIR__ . '/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
}

include __DIR__ . '/inc/header.php';
?>
  <section>
    <h2>Order Details</h2>
    
    <div style="margin-bottom:1rem;">
      <a class="btn" href="/SUMIT/admin.php">Back to Orders</a>
    </div>

    <?php if (!isset($_GET['i'])): ?>
      <p>No order selected. <a href="/SUMIT/admin.php">Back</a></p>
    <?php else: $i = (int)$_GET['i']; if (!isset($orders[$i])): ?>
      <p>Order not found. <a href="/SUMIT/admin.php">Back</a></p>
    <?php else: $o = $orders[$i]; 
      $qty = isset($o['qty']) ? $o['qty'] : 1;
      $price = isset($o['price']) ? $o['price'] : 0;
      $total = $qty * $price;
    ?>
      <div style="background:#fff; padding:2rem; border-radius:8px; border:1px solid #eee; max-width:600px;">
        <div style="border-bottom:2px solid #e85d04; padding-bottom:1.5rem; margin-bottom:1.5rem;">
          <h3 style="margin:0 0 0.5rem 0; color:#222;">Order #<?= ($i+1) ?></h3>
          <p style="margin:0; color:#666; font-size:0.9rem;">Placed on: <?= htmlspecialchars($o['timestamp'] ?? 'N/A') ?></p>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
          <div>
            <h4 style="margin:0 0 1rem 0; color:#222;">Customer Information</h4>
            <p style="margin:0.5rem 0;"><strong>Name:</strong> <?= htmlspecialchars($o['customer'] ?? 'N/A') ?></p>
            <p style="margin:0.5rem 0;"><strong>Table Number:</strong> <?= htmlspecialchars($o['tableNumber'] ?? 'N/A') ?></p>
            <p style="margin:0.5rem 0;"><strong>Payment Method:</strong> <?= htmlspecialchars($o['paymentOption'] ?? 'Cash') ?></p>
          </div>
          <div>
            <h4 style="margin:0 0 1rem 0; color:#222;">Item Details</h4>
            <p style="margin:0.5rem 0;"><strong>Item:</strong> <?= htmlspecialchars($o['name'] ?? 'N/A') ?></p>
            <p style="margin:0.5rem 0;"><strong>Item ID:</strong> <?= htmlspecialchars($o['itemId'] ?? 'N/A') ?></p>
            <p style="margin:0.5rem 0;"><strong>Quantity:</strong> <?= $qty ?></p>
            <p style="margin:0.5rem 0;"><strong>Price per Unit:</strong> Rs <?= number_format($price, 2) ?></p>
          </div>
        </div>

        <div style="background:#fafafa; padding:1.5rem; border-radius:8px; margin-bottom:1.5rem;">
          <h4 style="margin:0 0 1rem 0; color:#222;">Price Breakdown</h4>
          <table style="width:100%; border-collapse:collapse;">
            <tr style="border-bottom:1px solid #e0e0e0;">
              <td style="padding:0.5rem 0;"><strong>Subtotal:</strong></td>
              <td style="text-align:right; padding:0.5rem 0;"><strong>Rs <?= number_format($total, 2) ?></strong></td>
            </tr>
            <?php
              $discountPercent = 0;
              if ($total >= 1500) {
                $discountPercent = 30;
              } else if ($total >= 500) {
                $discountPercent = 15;
              } else if ($total >= 299) {
                $discountPercent = 10;
              }
              
              if ($discountPercent > 0) {
                $discount = round($total * ($discountPercent / 100));
                $finalTotal = $total - $discount;
            ?>
            <tr style="border-bottom:1px solid #e0e0e0;">
              <td style="padding:0.5rem 0; color:#4CAF50;"><strong><?= $discountPercent ?>% Discount:</strong></td>
              <td style="text-align:right; padding:0.5rem 0; color:#4CAF50;"><strong>-Rs <?= number_format($discount, 2) ?></strong></td>
            </tr>
            <tr style="border-bottom:2px solid #e85d04;">
              <td style="padding:1rem 0;"><strong style="font-size:1.1rem;">Final Total:</strong></td>
              <td style="text-align:right; padding:1rem 0;"><strong style="font-size:1.1rem; color:var(--accent);">Rs <?= number_format($finalTotal, 2) ?></strong></td>
            </tr>
            <?php } else { ?>
            <tr style="border-bottom:2px solid #e85d04;">
              <td style="padding:1rem 0;"><strong style="font-size:1.1rem;">Total:</strong></td>
              <td style="text-align:right; padding:1rem 0;"><strong style="font-size:1.1rem; color:var(--accent);">Rs <?= number_format($total, 2) ?></strong></td>
            </tr>
            <?php } ?>
          </table>
        </div>

        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <a class="btn" href="/SUMIT/admin_edit_order.php?i=<?= $i ?>" style="margin-top:0;">Edit Order</a>
          <a class="btn" href="/SUMIT/admin_print_order.php?i=<?= $i ?>" target="_blank" style="margin-top:0;">Print Bill</a>
          <a class="btn" href="/SUMIT/admin.php" style="margin-top:0;">Back</a>
        </div>
      </div>
    <?php endif; ?>
    <?php endif; ?>
  </section>

<?php include __DIR__ . '/inc/footer.php'; ?>
