<?php

class DbSessionHandler implements SessionHandlerInterface
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO php_sessions (id, data, last_access) VALUES (?, ?, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_access = CURRENT_TIMESTAMP"
        );
        return $stmt->execute([$id, $data]);
    }

    public function destroy($id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM php_sessions WHERE EXTRACT(EPOCH FROM (NOW() - last_access)) > ?"
        );
        $stmt->execute([$max_lifetime]);
        return $stmt->rowCount();
    }
}
