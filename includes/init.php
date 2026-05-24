<?php

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/session_handler.php';

$handler = new DbSessionHandler($db);
session_set_save_handler($handler, true);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
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
