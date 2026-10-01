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
    static $failed = false;
    if ($db !== null) return $db;
    if ($failed) return false;
    $dir = dirname(TNG_AUTH_DB);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    try {
        $db = new PDO('sqlite:' . TNG_AUTH_DB);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA busy_timeout=3000');
        $db->exec('CREATE TABLE IF NOT EXISTS users(username TEXT PRIMARY KEY, password_hash TEXT NOT NULL, pw_version INTEGER NOT NULL DEFAULT 1, updated_at INTEGER NOT NULL)');
        $db->exec('CREATE TABLE IF NOT EXISTS login_attempts(id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, ts INTEGER NOT NULL, ok INTEGER NOT NULL)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_attempts_ip_ts ON login_attempts(ip, ts)');
        $db->exec('CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT)');
        $st = $db->prepare('SELECT COUNT(*) FROM users WHERE username=?');
        if ($st && $st->execute(array('admin')) && intval($st->fetchColumn()) === 0) {
            $legacy_file = __DIR__ . '/../.htpasswd';
            $legacy_lines = is_readable($legacy_file) ? file($legacy_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;
            if (is_array($legacy_lines)) {
                foreach ($legacy_lines as $legacy_line) {
                    $legacy_parts = explode(':', $legacy_line, 2);
                    if (count($legacy_parts) !== 2 || trim($legacy_parts[0]) !== 'admin') continue;
                    $legacy_hash = trim($legacy_parts[1]);
                    if ($legacy_hash === '') continue;
                    $insert = $db->prepare('INSERT INTO users(username,password_hash,pw_version,updated_at) VALUES(?,?,1,?)');
                    if ($insert && $insert->execute(array('admin', $legacy_hash, time()))) {
                        $db->exec('DELETE FROM login_attempts');
                    }
                    break;
                }
            }
        }
    } catch (Throwable $e) {
        $db = null;
        $failed = true;
        return false;
    }
    return $db;
}

function tng_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $session_path = '/var/lib/trustng-auth/sessions';
    if (is_dir($session_path) && is_writable($session_path)) {
        session_save_path($session_path);
    }
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
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
    if (!$db) return false;
    $since = time() - TNG_LOCK_WINDOW;
    $st = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND ts>? AND ok=0');
    if (!$st) return false;
    $st->execute(array($ip, $since));
    return intval($st->fetchColumn()) >= TNG_MAX_ATTEMPTS;
}

function tng_record_attempt($ip, $ok) {
    $db = tng_db();
    if (!$db) return false;
    $st = $db->prepare('INSERT INTO login_attempts(ip, ts, ok) VALUES(?,?,?)');
    if ($st) $st->execute(array($ip, time(), $ok ? 1 : 0));
    $db->exec('DELETE FROM login_attempts WHERE ts < ' . (time() - 86400));
    return (bool) $st;
}

function tng_get_user($username) {
    $db = tng_db();
    if (!$db) return false;
    $st = $db->prepare('SELECT username, password_hash, pw_version, updated_at FROM users WHERE username=?');
    if (!$st) return false;
    $st->execute(array($username));
    return $st->fetch(PDO::FETCH_ASSOC);
}

function tng_verify_apr1($plain, $hash) {
    if (!is_string($plain) || !is_string($hash) || strpos($hash, '$apr1$') !== 0) return false;
    $parts = explode('$', $hash);
    if (count($parts) < 4) return false;
    $salt = $parts[2];
    $len = strlen($plain);
    $text = $plain . '$apr1$' . $salt;
    $bin = pack('H32', md5($plain . $salt . $plain));
    for ($i = $len; $i > 0; $i -= 16) {
        $text .= substr($bin, 0, min(16, $i));
    }
    for ($i = $len; $i > 0; $i >>= 1) {
        $text .= ($i & 1) ? chr(0) : $plain[0];
    }
    $bin = pack('H32', md5($text));
    for ($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $plain : $bin;
        if ($i % 3) $new .= $salt;
        if ($i % 7) $new .= $plain;
        $new .= ($i & 1) ? $bin : $plain;
        $bin = pack('H32', md5($new));
    }
    $tmp = '';
    for ($i = 0; $i < 5; $i++) {
        $k = $i + 6;
        $j = $i + 12;
        if ($j === 16) $j = 5;
        $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
    }
    $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
    $computed = '$apr1$' . $salt . '$' . strtr(strrev(substr(base64_encode($tmp), 2)),
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
        './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
    return hash_equals($hash, $computed);
}

function tng_verify_legacy_htpasswd($username, $password) {
    $file = __DIR__ . '/../.htpasswd';
    if (!is_file($file) || !is_readable($file)) return false;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return false;
    foreach ($lines as $line) {
        $pos = strpos($line, ':');
        if ($pos === false) continue;
        $u = trim(substr($line, 0, $pos));
        $h = trim(substr($line, $pos + 1));
        if ($u !== $username) continue;
        if (password_verify($password, $h) || tng_verify_apr1($password, $h)) {
            return true;
        }
    }
    return false;
}

function tng_authenticate_user($username, $password) {
    $u = tng_get_user($username);
    if ($u && !empty($u['password_hash']) && password_verify($password, $u['password_hash'])) {
        return $u;
    }
    if ($u && !empty($u['password_hash']) && tng_verify_apr1($password, $u['password_hash'])) {
        tng_set_password($username, $password);
        return tng_get_user($username);
    }
    if (tng_verify_legacy_htpasswd($username, $password)) {
        tng_set_password($username, $password);
        return tng_get_user($username);
    }
    return false;
}

function tng_set_password($username, $password) {
    $db = tng_db();
    if (!$db) return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) return false;
    $now = time();
    $st = $db->prepare('INSERT INTO users(username, password_hash, pw_version, updated_at) '
        . 'VALUES(?, ?, COALESCE((SELECT pw_version+1 FROM users WHERE username=?), 1), ?) '
        . 'ON CONFLICT(username) DO UPDATE SET password_hash=excluded.password_hash, '
        . 'pw_version=users.pw_version+1, updated_at=excluded.updated_at');
    if (!$st || !$st->execute(array($username, $hash, $username, $now))) return false;
    $user = tng_get_user($username);
    return $user && hash_equals($hash, $user['password_hash']);
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

function tng_login_csrf_token() {
    $cookie_name = 'trustng_login_csrf';
    $token = isset($_COOKIE[$cookie_name]) ? strval($_COOKIE[$cookie_name]) : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        setcookie($cookie_name, $token, array(
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ));
        $_COOKIE[$cookie_name] = $token;
    }
    return $token;
}

function tng_login_csrf_check($submitted) {
    $cookie_name = 'trustng_login_csrf';
    $cookie_token = isset($_COOKIE[$cookie_name]) ? strval($_COOKIE[$cookie_name]) : '';
    return is_string($submitted)
        && preg_match('/^[a-f0-9]{64}$/', $cookie_token)
        && hash_equals($cookie_token, $submitted);
}
