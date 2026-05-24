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

function validarDataNascimento($data) {
    $hoje = new DateTime();
    $nascimento = DateTime::createFromFormat('Y-m-d', $data);

    if (!$nascimento) return false;

    $idade = $hoje->diff($nascimento)->y;
    return $idade >= 13 && $idade <= 150;
}
