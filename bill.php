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
    $raw = file_get_contents($ordersFile);
    $orders = json_decode($raw, true) ?: [];
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
  append_audit('export-bill-pdf', ['count' => count($orders)]);
  header('Content-Type: text/html; charset=utf-8');
  header('Content-Disposition: attachment; filename="bill.html"');
  
  $sum = 0;
  $itemCount = 0;
  foreach ($orders as $o) {
    $qty = isset($o['qty']) ? $o['qty'] : 1;
    $price = isset($o['price']) ? $o['price'] : 0;
    $sum += $qty * $price;
    $itemCount++;
  }
  
  echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Bill - Studio Table by Sam</title>
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f7f7f7; }
.bill-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.bill-header { text-align: center; border-bottom: 2px solid #e85d04; padding-bottom: 20px; margin-bottom: 20px; }
.bill-header h1 { margin: 0; color: #222; }
.bill-header p { margin: 5px 0; color: #666; font-size: 14px; }
.bill-date { text-align: right; color: #666; font-size: 14px; margin-bottom: 20px; }
.bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.bill-table th { background: #fafafa; padding: 10px; text-align: left; border-bottom: 2px solid #e85d04; font-weight: 600; }
.bill-table td { padding: 10px; border-bottom: 1px solid #f3f3f3; }
.bill-table .text-right { text-align: right; }
.bill-total { text-align: right; padding: 15px 10px; border-top: 2px solid #e85d04; border-bottom: 2px solid #e85d04; }
.bill-total .total-label { font-weight: 600; color: #222; }
.bill-total .total-amount { font-size: 24px; font-weight: 700; color: #e85d04; margin-left: 10px; }
.bill-footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
.bill-summary { background: #fafafa; padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 14px; }
.bill-summary p { margin: 5px 0; }
</style>
</head>
<body>
<div class="bill-container">
  <div class="bill-header">
    <h1>Studio Table by Sam</h1>
    <p>Restaurant Bill / Invoice</p>
  </div>
  
  <div class="bill-date">
    <strong>Date:</strong> ' . date('Y-m-d H:i:s') . '
  </div>
  
  <table class="bill-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Item</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Price</th>
        <th class="text-right">Total</th>
      </tr>
    </thead>
    <tbody>';
  
  $itemNum = 1;
  foreach ($orders as $o) {
    $qty = isset($o['qty']) ? $o['qty'] : 1;
    $price = isset($o['price']) ? $o['price'] : 0;
    $total = $qty * $price;
    echo '<tr>
        <td>' . $itemNum . '</td>
        <td>' . htmlspecialchars($o['name'] ?? '') . '</td>
        <td class="text-right">' . $qty . '</td>
        <td class="text-right">Rs ' . number_format($price, 2) . '</td>
        <td class="text-right">Rs ' . number_format($total, 2) . '</td>
      </tr>';
    $itemNum++;
  }
  
  echo '    </tbody>
  </table>
  
  <div class="bill-summary">
    <p><strong>Items:</strong> ' . $itemCount . '</p>
    <p><strong>Invoice Total:</strong> Rs ' . number_format($sum, 2) . '</p>
  </div>
  
  <div class="bill-total">
    <span class="total-label">Grand Total:</span>
    <span class="total-amount">Rs ' . number_format($sum, 2) . '</span>
  </div>
  
  <div class="bill-footer">
    <p>Thank you for your order!</p>
    <p style="margin-top: 10px; font-size: 11px;">Generated on ' . date('Y-m-d H:i:s') . '</p>
  </div>
</div>
</body>
</html>';
  exit;
}

include __DIR__ . '/inc/header.php';
?>
  <section>
    <h2>Bill / Invoice</h2>
    
    <div style="margin-bottom:1rem;">
      <a class="btn" href="?export=pdf" style="margin-right:0.5rem;">Download Bill (HTML)</a>
      <a class="btn" href="/SUMIT/admin.php">Back to Orders</a>
    </div>

    <?php if (empty($orders)): ?>
      <p>No orders to display.</p>
    <?php else: ?>
      <?php
        $sum = 0;
        foreach ($orders as $o) {
          $qty = isset($o['qty']) ? $o['qty'] : 1;
          $price = isset($o['price']) ? $o['price'] : 0;
          $sum += $qty * $price;
        }
      ?>
      
      <div style="background:#fff; padding:2rem; border-radius:8px; border:1px solid #eee; max-width:600px;">
        <div style="text-align:center; border-bottom:2px solid #e85d04; padding-bottom:1.5rem; margin-bottom:1.5rem;">
          <h3 style="margin:0; color:#222;">Studio Table by Sam</h3>
          <p style="margin:0.5rem 0 0 0; color:#666; font-size:0.95rem;">Restaurant Bill / Invoice</p>
        </div>
        
        <p style="text-align:right; color:#666; font-size:0.9rem;">
          <strong>Date:</strong> <?= date('Y-m-d H:i:s') ?>
        </p>
        
        <table class="orders-table">
          <thead>
            <tr><th>#</th><th>Item</th><th>Qty</th><th>Price</th><th>Table</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php $itemNum = 1; foreach ($orders as $o): 
              $qty = isset($o['qty']) ? $o['qty'] : 1;
              $price = isset($o['price']) ? $o['price'] : 0;
              $total = $qty * $price;
            ?>
              <tr>
                <td><?= $itemNum ?></td>
                <td><?= htmlspecialchars($o['name'] ?? '') ?></td>
                <td><?= $qty ?></td>
                <td>Rs <?= number_format($price, 2) ?></td>
                <td><?= htmlspecialchars($o['tableNumber'] ?? '-') ?></td>
                <td>Rs <?= number_format($total, 2) ?></td>
              </tr>
            <?php $itemNum++; endforeach; ?>
          </tbody>
        </table>
        
        <div style="background:#fafafa; padding:1rem; border-radius:6px; margin:1.5rem 0; font-size:0.95rem;">
          <p style="margin:0.5rem 0;"><strong>Total Items:</strong> <?= count($orders) ?></p>
          <p style="margin:0.5rem 0;"><strong>Invoice Total:</strong> Rs <?= number_format($sum, 2) ?></p>
        </div>
        
        <div style="text-align:right; padding:1rem 0; border-top:2px solid #e85d04; border-bottom:2px solid #e85d04; margin:1rem 0;">
          <span style="font-weight:600; color:#222;">Grand Total:</span>
          <span style="font-size:1.8rem; font-weight:700; color:#e85d04; margin-left:0.5rem;">Rs <?= number_format($sum, 2) ?></span>
        </div>
        
        <div style="text-align:center; color:#666; font-size:0.85rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eee;">
          <p>Thank you for your order!</p>
        </div>
      </div>
    <?php endif; ?>

  </section>

<?php include __DIR__ . '/inc/footer.php'; ?>
