<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class MailQueue
{
    private const MAX_ATTEMPTS = 3;

    public static function enqueue(string $toEmail, string $subject, string $body, ?string $toName = null): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO mail_queue (to_email, to_name, subject, body, status, created_at)
             VALUES (:to_email, :to_name, :subject, :body, :status, NOW())'
        );
        $stmt->execute([
            ':to_email' => $toEmail,
            ':to_name' => $toName,
            ':subject' => $subject,
            ':body' => $body,
            ':status' => 'pending',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pendingBatch(int $limit = 20): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM mail_queue WHERE status = 'pending' AND attempts < :max
             ORDER BY created_at ASC LIMIT :limit"
        );
        $stmt->bindValue(':max', self::MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markSent(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE mail_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        // После MAX_ATTEMPTS помечаем как failed, иначе оставляем pending для повтора.
        $stmt = Database::pdo()->prepare(
            "UPDATE mail_queue
             SET attempts = attempts + 1,
                 last_error = :error,
                 status = IF(attempts + 1 >= :max, 'failed', 'pending')
             WHERE id = :id"
        );
        $stmt->bindValue(':error', mb_substr($error, 0, 500));
        $stmt->bindValue(':max', self::MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
