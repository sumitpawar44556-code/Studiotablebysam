<?php
session_start();
include __DIR__ . '/inc/config.php';
include __DIR__ . '/inc/auth.php';

// ensure password storage exists
ensure_pass_file();
ensure_session();

// logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /SUMIT/admin.php');
    exit;
}

// Enforce HTTP Basic Auth only (no session-based login UI)
// Check lockout first
$ip = client_ip();
if (is_locked_out($ip)) {
  header('HTTP/1.1 403 Forbidden');
  echo 'Too many failed login attempts. Try again later.';
  exit;
}

// Request credentials if not provided
// Some PHP setups (CGI/FastCGI) do not populate PHP_AUTH_USER/PHP_AUTH_PW.
// Try to extract Basic auth from the Authorization header if present.
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
  $auth = null;
  if (!empty($_SERVER['HTTP_AUTHORIZATION'])) $auth = $_SERVER['HTTP_AUTHORIZATION'];
  if (!$auth && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
  if ($auth && stripos($auth, 'basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6));
    if ($decoded !== false) {
      $parts = explode(':', $decoded, 2);
      if (count($parts) === 2) {
        $_SERVER['PHP_AUTH_USER'] = $parts[0];
        $_SERVER['PHP_AUTH_PW'] = $parts[1];
      }
    }
  }
}

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
  header('WWW-Authenticate: Basic realm="Admin Area"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'Authentication required.';
  exit;
}

// Verify the password; the Basic Auth username is intentionally ignored.
$providedPass = $_SERVER['PHP_AUTH_PW'];
if (!verify_pass($providedPass)) {
  // record failed attempt and ask again
  record_failed_attempt($ip);
  header('WWW-Authenticate: Basic realm="Admin Area"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'Invalid credentials.';
  exit;
} else {
  // successful auth: clear failures
  clear_failed_attempts($ip);
}
include __DIR__ . '/inc/header.php';

$ordersFile = __DIR__ . '/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
    $raw = file_get_contents($ordersFile);
    $orders = json_decode($raw, true) ?: [];
}

// handle actions: clear, delete (move to deleted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  // CSRF check
  if (!isset($_POST['csrf']) || !verify_csrf($_POST['csrf'])) {
      http_response_code(400);
      echo 'Invalid CSRF token';
      exit;
  }
  if ($_POST['action'] === 'clear') {
    // record audit and clear orders
    append_audit('clear', ['count' => count($orders)]);
    file_put_contents($ordersFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: /SUMIT/admin.php');
    exit;
  }
  if ($_POST['action'] === 'unlock') {
    // clear all failed login records
    file_put_contents(failed_logins_file(), json_encode([], JSON_PRETTY_PRINT));
    append_audit('unlock', ['by' => get_auth_user()]);
    header('Location: /SUMIT/admin.php');
    exit;
  }
  if ($_POST['action'] === 'delete' && isset($_POST['index'])) {
    $idx = (int)$_POST['index'];
    if (isset($orders[$idx])) {
      $deletedFile = __DIR__ . '/orders_deleted.json';
      $deleted = [];
      if (file_exists($deletedFile)) {
          $deleted = json_decode(file_get_contents($deletedFile), true) ?: [];
      }
      $deleted[] = $orders[$idx];
      file_put_contents($deletedFile, json_encode($deleted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      // audit delete
      append_audit('delete', ['index'=>$idx, 'order'=>$deleted[count($deleted)-1]]);
      array_splice($orders, $idx, 1);
      file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: /SUMIT/admin.php');
    exit;
  }
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  append_audit('export', ['count' => count($orders)]);
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="orders.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['#','timestamp','customer','item','qty','price','table','payment','total']);
  $sum = 0;
  foreach ($orders as $i => $o) {
    $qty = isset($o['qty']) ? $o['qty'] : 1;
    $price = isset($o['price']) ? $o['price'] : 0;
    $total = $qty * $price;
    $sum += $total;
    fputcsv($out, [$i+1, $o['timestamp'] ?? '', $o['customer'] ?? '', $o['name'] ?? '', $qty, $price, $o['tableNumber'] ?? '', $o['paymentOption'] ?? '', $total]);
  }
  fputcsv($out, ['', '', '', '', '', 'Total', $sum]);
  fclose($out);
  exit;
}

?>
  <section>
    <h2>Orders (Admin)</h2>

    <div style="margin-bottom:1rem;">
      <a class="btn" href="/SUMIT/bill.php" style="margin-right:0.5rem;">View Bill</a>
      <a class="btn" href="?export=csv" style="margin-right:0.5rem;">Export CSV</a>
      <a class="btn" href="/SUMIT/admin_change_pass.php" style="margin-right:0.5rem;">Change Password</a>
      <a class="btn" href="/SUMIT/admin_restore.php" style="margin-right:0.5rem;">Restore Orders</a>
      <form method="post" onsubmit="return confirm('Unlock all logins?');" style="display:inline-block; margin-right:0.5rem;">
        <input type="hidden" name="action" value="unlock" />
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <button class="btn" type="submit">Unlock Logins</button>
      </form>
      <form method="post" onsubmit="return confirm('Clear all orders?');" style="display:inline-block;">
        <input type="hidden" name="action" value="clear" />
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <button class="btn" type="submit">Clear All Orders</button>
      </form>
    </div>

    <?php if (empty($orders)): ?>
      <p>No orders yet.</p>
    <?php else: ?>
      <table class="orders-table">
        <thead>
          <tr><th>#</th><th>Order ID</th><th>Status</th><th>Time</th><th>Customer</th><th>Item</th><th>Qty</th><th>Price</th><th>Table</th><th>Payment</th><th>Total</th></tr>
        </thead>
        <tbody>
        <?php $sum = 0; foreach ($orders as $i => $o): $total = (isset($o['price'])?$o['price']:0) * (isset($o['qty'])?$o['qty']:1); $sum += $total; ?>
          <tr>
            <td><?= ($i+1) ?></td>
            <td><?= htmlspecialchars($o['orderId'] ?? 'Older order') ?></td>
            <td><?= htmlspecialchars($o['status'] ?? 'Received') ?></td>
            <td><?= htmlspecialchars($o['timestamp'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['customer'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['qty'] ?? 1) ?></td>
            <td>Rs <?= htmlspecialchars($o['price'] ?? 0) ?></td>
            <td><?= htmlspecialchars($o['tableNumber'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['paymentOption'] ?? '') ?></td>
            <td>Rs <?= $total ?></td>
            <td style="display:flex; gap:0.3rem; flex-wrap:wrap;">
              <a class="btn btn-sm" href="/SUMIT/admin_order_details.php?i=<?= $i ?>" title="View Details">Details</a>
              <a class="btn btn-sm" href="/SUMIT/admin_edit_order.php?i=<?= $i ?>" title="Edit Order">Edit</a>
              <a class="btn btn-sm" href="/SUMIT/admin_print_order.php?i=<?= $i ?>" title="Print Bill" target="_blank">Print</a>
              <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this order?');">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="index" value="<?= $i ?>" />
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th colspan="6">Total Revenue</th><th>Rs <?= $sum ?></th></tr>
        </tfoot>
      </table>
    <?php endif; ?>

  </section>

<?php include __DIR__ . '/inc/footer.php'; ?>
