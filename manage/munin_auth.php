<?php
/**
 * TRUST-NG — endpoint nginx auth_request untuk /munin/.
 * Validasi sesi panel (SQLite/Argon2id) secara mandiri dan membalas:
 *   200 bila sesi valid, 401 bila tidak (nginx lalu arahkan ke /login.php).
 * Termasuk dalam daftar exempt auth_guard agar tidak memicu redirect-nya.
 */
require_once __DIR__ . '/includes/auth.php';
error_reporting(0);

tng_session_start();

if (!empty($_SESSION['tng_user']) && tng_current_pw_version()) {
    http_response_code(200);
} else {
    http_response_code(401);
}
