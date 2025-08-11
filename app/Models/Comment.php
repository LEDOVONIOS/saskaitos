<?php
class Comment {
    public static function listApprovedByNumberId(int $numberId): array {
        $stmt = db()->prepare("SELECT * FROM comments WHERE number_id = ? AND status='approved' ORDER BY id DESC");
        $stmt->execute([$numberId]);
        return $stmt->fetchAll();
    }

    public static function createPending(int $numberId, ?string $author, string $body, string $ip, string $ua): void {
        $stmt = db()->prepare("INSERT INTO comments (number_id, author, body, status, ip, user_agent) VALUES (?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$numberId, $author, $body, $ip, $ua]);
    }

    public static function countApprovedByNumberId(int $numberId): int {
        $stmt = db()->prepare("SELECT COUNT(*) FROM comments WHERE number_id = ? AND status='approved'");
        $stmt->execute([$numberId]);
        return (int)$stmt->fetchColumn();
    }

    public static function countByStatus(string $status): int {
        $stmt = db()->prepare('SELECT COUNT(*) FROM comments WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }

    public static function listByStatus(string $status, int $limit): array {
        $stmt = db()->prepare('SELECT c.*, n.e164, n.local06 FROM comments c JOIN numbers n ON n.id=c.number_id WHERE c.status = ? ORDER BY c.id DESC LIMIT ?');
        $stmt->bindValue(1, $status);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void {
        $stmt = db()->prepare('UPDATE comments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$id]);
    }
}