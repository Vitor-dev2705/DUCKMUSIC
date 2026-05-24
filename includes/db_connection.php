<?php

// Carrega variáveis: Vercel env vars > .env file > defaults
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'duckmusic');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'require');

function conectarBanco() {
    try {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSLMODE;
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        exit("Erro interno do servidor.");
    }
}

$db = conectarBanco();

function fecharConexao(&$conn) {
    $conn = null;
}

function executarQuery($sql, $params = []) {
    global $db;
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        throw $e;
    }
}

function buscarUm($sql, $params = []) {
    $stmt = executarQuery($sql, $params);
    return $stmt->fetch();
}

function buscarTodos($sql, $params = []) {
    $stmt = executarQuery($sql, $params);
    return $stmt->fetchAll();
}

function inserir($sql, $params = []) {
    global $db;

    $temReturning = (stripos($sql, 'RETURNING') !== false);

    if (!$temReturning && preg_match('/INSERT\s+INTO\s+(\w+)/i', $sql, $matches)) {
        $tabelasSemId = ['favoritos', 'musicas_playlists', 'sessoes'];
        if (!in_array($matches[1], $tabelasSemId)) {
            $sql .= ' RETURNING id';
            $temReturning = true;
        }
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    if ($temReturning) {
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }

    return null;
}

function atualizar($sql, $params = []) {
    $stmt = executarQuery($sql, $params);
    return $stmt->rowCount();
}

function excluir($sql, $params = []) {
    return atualizar($sql, $params);
}
