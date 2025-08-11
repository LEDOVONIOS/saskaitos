<?php
class Number {
    public static function findByE164(string $e164): ?array {
        $stmt = db()->prepare('SELECT * FROM numbers WHERE e164 = ?');
        $stmt->execute([$e164]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $e164, string $local06): array {
        $id = (int)$e164; // numeric value as id
        $stmt = db()->prepare('INSERT INTO numbers (id, e164, local06, views, last_checked) VALUES (?, ?, ?, 0, NULL)');
        $stmt->execute([$id, $e164, $local06]);
        return self::findByE164($e164);
    }

    public static function incrementView(int $id): void {
        $stmt = db()->prepare('UPDATE numbers SET views = views + 1, last_checked = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function topSearchedToday(int $limit): array {
        $stmt = db()->prepare('SELECT * FROM numbers WHERE DATE(last_checked) = CURDATE() ORDER BY views DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function deleteByE164(string $e164): void {
        $stmt = db()->prepare('DELETE FROM numbers WHERE e164 = ?');
        $stmt->execute([$e164]);
    }
}