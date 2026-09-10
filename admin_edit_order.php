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

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'])) {
  if (!verify_csrf($_POST['csrf'] ?? '')) { $msg = 'Invalid CSRF'; }
  else {
    $idx = (int)$_POST['index'];
    if (isset($orders[$idx])) {
      $before = $orders[$idx];
      $orders[$idx]['customer'] = $_POST['customer'] ?? $orders[$idx]['customer'];
      $orders[$idx]['name'] = $_POST['name'] ?? $orders[$idx]['name'];
      $orders[$idx]['qty'] = (int)($_POST['qty'] ?? $orders[$idx]['qty']);
      $orders[$idx]['price'] = (float)($_POST['price'] ?? $orders[$idx]['price']);
      $orders[$idx]['tableNumber'] = $_POST['tableNumber'] ?? $orders[$idx]['tableNumber'];
      $orders[$idx]['paymentOption'] = $_POST['paymentOption'] ?? $orders[$idx]['paymentOption'];
      file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      append_audit('edit', ['index' => $idx, 'before' => $before, 'after' => $orders[$idx]]);
      $msg = 'Order updated.';
    } else { $msg = 'Order not found.'; }
  }
}

include __DIR__ . '/inc/header.php';
?>
  <section>
    <h2>Edit Order</h2>
    <?php if ($msg): ?><p><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <?php if (!isset($_GET['i'])): ?>
      <p>No order selected. <a href="/SUMIT/admin.php">Back</a></p>
    <?php else: $i = (int)$_GET['i']; if (!isset($orders[$i])): ?>
      <p>Order not found. <a href="/SUMIT/admin.php">Back</a></p>
    <?php else: $o = $orders[$i]; ?>
      <form method="post">
        <input type="hidden" name="index" value="<?= $i ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <div><label>Customer: <input name="customer" value="<?= htmlspecialchars($o['customer'] ?? '') ?>"></label></div>
        <div><label>Item name: <input name="name" value="<?= htmlspecialchars($o['name'] ?? '') ?>"></label></div>
        <div><label>Qty: <input name="qty" type="number" value="<?= htmlspecialchars($o['qty'] ?? 1) ?>"></label></div>
        <div><label>Price: <input name="price" type="number" step="0.01" value="<?= htmlspecialchars($o['price'] ?? 0) ?>"></label></div>
        <div><label>Table Number: <input name="tableNumber" value="<?= htmlspecialchars($o['tableNumber'] ?? '') ?>"></label></div>
        <div><label>Payment Option: <input name="paymentOption" value="<?= htmlspecialchars($o['paymentOption'] ?? '') ?>"></label></div>
        <button class="btn" type="submit">Save</button>
      </form>
    <?php endif; ?>
    <?php endif; ?>
  </section>
<?php include __DIR__ . '/inc/footer.php'; ?>
