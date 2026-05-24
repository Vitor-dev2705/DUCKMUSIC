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
