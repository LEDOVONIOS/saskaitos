<?php
class Contact {
    public static function create(?string $name, ?string $email, ?string $number, string $message): int {
        $stmt = db()->prepare('INSERT INTO contacts (name, email, number, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $number, $message]);
        return (int)db()->lastInsertId();
    }

    public static function recent(int $limit): array {
        $stmt = db()->prepare('SELECT * FROM contacts ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}