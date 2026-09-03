<?php
/**
 * TRUST-NG auth helper — SQLite session auth (Argon2id)
 * DB di /var/lib/trustng-auth/auth.db (di luar webroot).
 */
error_reporting(0);

define('TNG_AUTH_DB', '/var/lib/trustng-auth/auth.db');
define('TNG_SETUP_FLAG', __DIR__ . '/../setup.mulai');
define('TNG_MAX_ATTEMPTS', 5);
define('TNG_LOCK_WINDOW', 900); // 15 menit

function tng_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . TNG_AUTH_DB);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA busy_timeout=3000');
    }
    return $db;
}

function tng_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('trustng_session');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ));
    session_start();
}

function tng_client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function tng_is_locked_out($ip) {
    $db = tng_db();
    $since = time() - TNG_LOCK_WINDOW;
    $st = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND ts>? AND ok=0');
    $st->execute(array($ip, $since));
    return intval($st->fetchColumn()) >= TNG_MAX_ATTEMPTS;
}

function tng_record_attempt($ip, $ok) {
    $db = tng_db();
    $st = $db->prepare('INSERT INTO login_attempts(ip, ts, ok) VALUES(?,?,?)');
    $st->execute(array($ip, time(), $ok ? 1 : 0));
    // prune lama
    $db->exec('DELETE FROM login_attempts WHERE ts < ' . (time() - 86400));
}

function tng_get_user($username) {
    $db = tng_db();
    $st = $db->prepare('SELECT username, password_hash, pw_version FROM users WHERE username=?');
    $st->execute(array($username));
    return $st->fetch(PDO::FETCH_ASSOC);
}

function tng_set_password($username, $password) {
    $db = tng_db();
    $hash = password_hash($password, PASSWORD_DEFAULT); // Argon2id di PHP 8.x
    $db->exec('INSERT INTO users(username, password_hash, pw_version, updated_at) '
        . "VALUES('" . SQLite3::escapeString($username) . "', '" . SQLite3::escapeString($hash)
        . "', COALESCE((SELECT pw_version+1 FROM users WHERE username='"
        . SQLite3::escapeString($username) . "'), 1), " . time() . ')'
        . " ON CONFLICT(username) DO UPDATE SET password_hash='" . SQLite3::escapeString($hash)
        . "', pw_version=pw_version+1, updated_at=" . time());
}

function tng_current_pw_version() {
    if (!isset($_SESSION['tng_user'])) return true;
    $u = tng_get_user($_SESSION['tng_user']);
    if (!$u) return false;
    return !isset($_SESSION['tng_pwver']) || $_SESSION['tng_pwver'] == $u['pw_version'];
}

function tng_csrf_token() {
    tng_session_start();
    if (empty($_SESSION['tng_csrf'])) {
        $_SESSION['tng_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['tng_csrf'];
}

function tng_csrf_check($token) {
    tng_session_start();
    return is_string($token) && !empty($_SESSION['tng_csrf'])
        && hash_equals($_SESSION['tng_csrf'], $token);
}
