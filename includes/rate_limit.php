<?php
/**
 * Rate limiting utilities for login attempts.
 * Simple session‑based implementation – suitable for small projects.
 *
 * Usage (already included in auth/login.php):
 *   verificarTentativasLogin($login);
 *   // after successful login
 *   limparTentativas($login);
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const RATE_LIMIT_MAX_ATTEMPTS = 5;      // maximum failed attempts before block
const RATE_LIMIT_BLOCK_SECONDS = 900;    // block duration (15 minutes)

/**
 * Checks and updates the login attempt counter for a given identifier.
 *
 * @param string $login The username or e‑mail used for the attempt.
 */
function verificarTentativasLogin(string $login): void {
    $key = 'login_attempts_' . strtolower($login);
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => $now, 'blocked_until' => 0];
    }
    $data = &$_SESSION[$key];

    // If currently blocked, do nothing – the login script will still run but can read $data['blocked_until'] if needed.
    if ($data['blocked_until'] > $now) {
        return;
    }

    // Reset after block period expires
    if ($data['blocked_until'] && $now >= $data['blocked_until']) {
        $data['count'] = 0;
        $data['first'] = $now;
        $data['blocked_until'] = 0;
    }

    // Increment attempt count (called on each POST before validation)
    $data['count']++;

    // If exceeded max attempts, set block timeout
    if ($data['count'] >= RATE_LIMIT_MAX_ATTEMPTS) {
        $data['blocked_until'] = $now + RATE_LIMIT_BLOCK_SECONDS;
    }
}

/**
 * Clears the attempt counter for a successful login.
 *
 * @param string $login The identifier used during login.
 */
function limparTentativas(string $login): void {
    $key = 'login_attempts_' . strtolower($login);
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}
?>
