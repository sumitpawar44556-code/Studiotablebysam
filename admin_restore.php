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

$deletedFile = __DIR__ . '/orders_deleted.json';
$deleted = [];
if (file_exists($deletedFile)) {
    $deleted = json_decode(file_get_contents($deletedFile), true) ?: [];
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { $msg = 'Invalid CSRF'; }
    else {
        if ($_POST['action'] === 'restore' && isset($_POST['index'])) {
            $idx = (int)$_POST['index'];
            if (isset($deleted[$idx])) {
                $ordersFile = __DIR__ . '/orders.json';
                $orders = [];
                if (file_exists($ordersFile)) $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
                $orders[] = $deleted[$idx];
                array_splice($deleted, $idx, 1);
                file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                file_put_contents($deletedFile, json_encode($deleted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                append_audit('restore', ['restored_index' => $idx, 'order' => $orders[count($orders)-1]]);
                $msg = 'Order restored.';
            }
        }
    }
}

include __DIR__ . '/inc/header.php';
?>
  <section>
    <h2>Restore Deleted Orders</h2>
    <?php if ($msg): ?><p><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <?php if (empty($deleted)): ?>
      <p>No deleted orders.</p>
    <?php else: ?>
      <table class="orders-table">
        <thead><tr><th>#</th><th>Time</th><th>Customer</th><th>Item</th><th>Qty</th><th>Price</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($deleted as $i => $o): ?>
          <tr>
            <td><?= ($i+1) ?></td>
            <td><?= htmlspecialchars($o['timestamp'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['customer'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['qty'] ?? 1) ?></td>
            <td>Rs <?= htmlspecialchars($o['price'] ?? 0) ?></td>
            <td>
              <form method="post" style="display:inline-block;">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="index" value="<?= $i ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
                <button class="btn" type="submit">Restore</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
<?php include __DIR__ . '/inc/footer.php'; ?>
