<?php

function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function htmlsafeEcho($string) {
    echo htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function htmlsafe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// validarDataNascimento() movida para includes/validation.php
