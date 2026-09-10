<?php
/**
 * Basic configuration file for Studio Table by Sam
 */

// Default admin password (should be changed in production)
define('ADMIN_PASS', 'college123');

// Path to password storage
define('PASS_FILE', __DIR__ . '/pass.json');

// Path to audit log
define('AUDIT_FILE', __DIR__ . '/audit.json');

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// If HTTPS is being used, enable secure flag
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
