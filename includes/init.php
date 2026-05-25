<?php

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/session_handler.php';

$handler = new DbSessionHandler($db);
session_set_save_handler($handler, true);

$isHttps = isset($_SERVER['HTTPS']) ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly'  => true,
    'samesite'  => 'Lax'
]);

session_start();

/**
 * Resolve caminho de arquivo de midia.
 * Se ja for URL absoluta (Supabase), retorna como esta.
 * Se for caminho local, converte para caminho absoluto.
 */
function resolverMidia($caminho, $fallback = '/assets/img/capa-padrao.svg') {
    if (empty($caminho)) return $fallback;
    // URL completa do Supabase ou CDN
    if (strpos($caminho, 'http://') === 0 || strpos($caminho, 'https://') === 0) {
        return $caminho;
    }
    // Caminho local - converte para absoluto
    return '/' . ltrim($caminho, '/');
}
