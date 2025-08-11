<?php
class Admin {
    public static function findByEmail(string $email): ?array {
        $stmt = db()->prepare('SELECT * FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}