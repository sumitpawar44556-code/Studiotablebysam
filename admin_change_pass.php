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

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_pass']) && isset($_POST['new_pass']) && isset($_POST['confirm_pass'])) {
  if (!verify_csrf($_POST['csrf'] ?? '')) { $msg = 'Invalid CSRF token.'; }
  elseif (!verify_pass($_POST['old_pass'])) {
    $msg = 'Old password is incorrect.';
  } elseif ($_POST['new_pass'] !== $_POST['confirm_pass']) {
    $msg = 'New password and confirmation do not match.';
  } else {
    set_pass($_POST['new_pass']);
    append_audit('password_change', ['user' => get_auth_user()]);
    $msg = 'Password changed successfully.';
  }
}

include __DIR__ . '/inc/header.php';
?>
  <section>
    <h2>Change Admin Password</h2>
    <?php if ($msg): ?>
      <p><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <div style="margin-bottom:0.5rem;"><label>Old password: <input type="password" name="old_pass" required></label></div>
      <div style="margin-bottom:0.5rem;"><label>New password: <input type="password" name="new_pass" required></label></div>
      <div style="margin-bottom:0.5rem;"><label>Confirm new: <input type="password" name="confirm_pass" required></label></div>
      <button class="btn" type="submit">Change Password</button>
    </form>
    <p><a href="/SUMIT/admin.php">Back to admin</a></p>
  </section>
<?php include __DIR__ . '/inc/footer.php'; ?>
