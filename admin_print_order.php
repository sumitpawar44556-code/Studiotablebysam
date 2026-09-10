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

if (!isset($_GET['i']) || !isset($orders[(int)$_GET['i']])) {
  header('HTTP/1.0 404 Not Found');
  echo 'Order not found.';
  exit;
}

$i = (int)$_GET['i'];
$o = $orders[$i];
$qty = isset($o['qty']) ? $o['qty'] : 1;
$price = isset($o['price']) ? $o['price'] : 0;
$total = $qty * $price;

// Calculate discount
$discountPercent = 0;
if ($total >= 1500) {
  $discountPercent = 30;
} else if ($total >= 500) {
  $discountPercent = 15;
} else if ($total >= 299) {
  $discountPercent = 10;
}

$discount = $discountPercent > 0 ? round($total * ($discountPercent / 100)) : 0;
$finalTotal = $total - $discount;

append_audit('print-order', ['index' => $i, 'order' => $o]);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Order Receipt - Studio Table by Sam</title>
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f7f7f7; }
.receipt { max-width: 500px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.receipt-header { text-align: center; border-bottom: 3px dashed #e85d04; padding-bottom: 20px; margin-bottom: 20px; }
.receipt-header h1 { margin: 0; font-size: 1.3rem; color: #222; }
.receipt-header p { margin: 5px 0; color: #666; font-size: 12px; }
.receipt-date { text-align: right; color: #666; font-size: 12px; margin-bottom: 15px; }
.section { margin: 15px 0; }
.section-title { font-weight: 600; color: #222; border-bottom: 1px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 10px; font-size: 14px; }
.receipt-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.receipt-row.header { font-weight: 600; border-bottom: 1px solid #e0e0e0; }
.receipt-row.total { font-weight: 700; font-size: 15px; border-top: 2px solid #e85d04; border-bottom: 2px solid #e85d04; padding: 12px 0; }
.receipt-row.discount { color: #4CAF50; font-weight: 600; }
.receipt-footer { text-align: center; color: #666; font-size: 11px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0; }
.text-right { text-align: right; }
@media print {
  body { background: #fff; padding: 0; }
  .receipt { box-shadow: none; border-radius: 0; }
  .no-print { display: none; }
}
.no-print { text-align: center; margin-bottom: 20px; }
.no-print button { padding: 10px 20px; background: #e85d04; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
.no-print button:hover { background: #d54a03; }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">🖨️ Print Receipt</button>
  <button onclick="window.close()">✕ Close</button>
</div>

<div class="receipt">
  <div class="receipt-header">
    <h1>Studio Table by Sam</h1>
    <p>Restaurant Bill / Receipt</p>
  </div>
  
  <div class="receipt-date">
    <strong>Order #<?= ($i+1) ?></strong><br>
    <?= date('d-M-Y H:i:s', strtotime($o['timestamp'] ?? 'now')) ?>
  </div>

  <div class="section">
    <div class="section-title">Customer Information</div>
    <div class="receipt-row">
      <span>Name:</span>
      <span class="text-right"><?= htmlspecialchars($o['customer'] ?? 'N/A') ?></span>
    </div>
    <div class="receipt-row">
      <span>Table:</span>
      <span class="text-right"><?= htmlspecialchars($o['tableNumber'] ?? 'N/A') ?></span>
    </div>
    <div class="receipt-row">
      <span>Payment:</span>
      <span class="text-right"><?= htmlspecialchars($o['paymentOption'] ?? 'Cash') ?></span>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Order Details</div>
    <div class="receipt-row header">
      <span>Item</span>
      <span class="text-right">Qty × Price = Amount</span>
    </div>
    <div class="receipt-row">
      <span><?= htmlspecialchars($o['name'] ?? 'Item') ?></span>
      <span class="text-right"><?= $qty ?> × Rs <?= number_format($price, 2) ?> = Rs <?= number_format($total, 2) ?></span>
    </div>
  </div>

  <div class="section">
    <div class="receipt-row">
      <span>Subtotal:</span>
      <span class="text-right">Rs <?= number_format($total, 2) ?></span>
    </div>
    <?php if ($discount > 0): ?>
    <div class="receipt-row discount">
      <span>🎉 <?= $discountPercent ?>% Discount:</span>
      <span class="text-right">-Rs <?= number_format($discount, 2) ?></span>
    </div>
    <?php endif; ?>
    <div class="receipt-row total">
      <span><?= $discount > 0 ? 'Final Total:' : 'Total:' ?></span>
      <span class="text-right" style="color:#e85d04;">Rs <?= number_format($finalTotal, 2) ?></span>
    </div>
  </div>

  <div class="receipt-footer">
    <p>Thank you for your order!</p>
    <p>Please visit us again</p>
    <p style="margin-top: 10px; font-size: 10px;">Generated: <?= date('Y-m-d H:i:s') ?></p>
  </div>
</div>

</body>
</html>
