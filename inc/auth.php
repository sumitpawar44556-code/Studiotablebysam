<?php
/**
 * Authentication functions for Studio Table by Sam
 */

// Lockout tracking
$lockout_file = __DIR__ . '/lockout.json';
$lockout_threshold = 5;
$lockout_duration = 900; // 15 minutes

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ensure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Initialize CSRF token if not present
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function ensure_pass_file() {
    if (!file_exists(PASS_FILE)) {
        $hash = password_hash(ADMIN_PASS, PASSWORD_DEFAULT);
        file_put_contents(PASS_FILE, json_encode(['hash' => $hash]));
    }
}

function verify_pass($password) {
    if (!file_exists(PASS_FILE)) {
        ensure_pass_file();
    }
    $data = json_decode(file_get_contents(PASS_FILE), true);
    return isset($data['hash']) && password_verify($password, $data['hash']);
}

function is_locked_out($ip) {
    global $lockout_file, $lockout_threshold, $lockout_duration;
    if (!file_exists($lockout_file)) return false;
    
    $lockouts = json_decode(file_get_contents($lockout_file), true) ?: [];
    if (!isset($lockouts[$ip])) return false;
    
    $attempts = $lockouts[$ip];
    if ($attempts['count'] < $lockout_threshold) return false;
    
    return (time() - $attempts['last_attempt']) < $lockout_duration;
}

function record_failed_attempt($ip) {
    global $lockout_file;
    $lockouts = [];
    if (file_exists($lockout_file)) {
        $lockouts = json_decode(file_get_contents($lockout_file), true) ?: [];
    }
    
    if (!isset($lockouts[$ip])) {
        $lockouts[$ip] = ['count' => 0, 'last_attempt' => 0];
    }
    
    $lockouts[$ip]['count']++;
    $lockouts[$ip]['last_attempt'] = time();
    
    file_put_contents($lockout_file, json_encode($lockouts));
}

function clear_failed_attempts($ip) {
    global $lockout_file;
    if (!file_exists($lockout_file)) return;
    
    $lockouts = json_decode(file_get_contents($lockout_file), true) ?: [];
    unset($lockouts[$ip]);
    
    file_put_contents($lockout_file, json_encode($lockouts));
}

function append_audit($action, $details = []) {
    $entry = [
        'timestamp' => time(),
        'action' => $action,
        'ip' => client_ip(),
        'details' => $details
    ];
    
    $audit = [];
    if (file_exists(AUDIT_FILE)) {
        $audit = json_decode(file_get_contents(AUDIT_FILE), true) ?: [];
    }
    
    $audit[] = $entry;
    file_put_contents(AUDIT_FILE, json_encode($audit, JSON_PRETTY_PRINT));
}
