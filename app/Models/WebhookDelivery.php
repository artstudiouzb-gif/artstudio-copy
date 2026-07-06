<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Очередь доставок вебхуков (задача 136). Обрабатывается CLI-воркером
 * (app/Console/webhook_worker.php) с ретраями.
 */
final class WebhookDelivery
{
    private const MAX_ATTEMPTS = 3;

    public static function enqueue(int $webhookId, string $event, array $payload): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO webhook_deliveries (webhook_id, event_type, payload_json, status, created_at)
             VALUES (:w, :e, :p, :s, NOW())'
        );
        $stmt->execute([
            ':w' => $webhookId,
            ':e' => $event,
            ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ':s' => 'pending',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingBatch(int $limit = 20): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM webhook_deliveries WHERE status = 'pending' AND attempts < :max
             ORDER BY created_at ASC LIMIT :limit"
        );
        $stmt->bindValue(':max', self::MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markSent(int $id, int $responseCode): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE webhook_deliveries SET status = 'sent', sent_at = NOW(), attempts = attempts + 1,
                    response_code = :rc, last_error = NULL WHERE id = :id"
        );
        $stmt->execute([':rc' => $responseCode, ':id' => $id]);
    }

    public static function markFailed(int $id, int $responseCode, string $error): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE webhook_deliveries
             SET attempts = attempts + 1, response_code = :rc, last_error = :err,
                 status = IF(attempts + 1 >= :max, 'failed', 'pending')
             WHERE id = :id"
        );
        $stmt->bindValue(':rc', $responseCode, PDO::PARAM_INT);
        $stmt->bindValue(':err', mb_substr($error, 0, 500));
        $stmt->bindValue(':max', self::MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** @return array<int, array<string, mixed>> последние доставки для лога в админке */
    public static function recent(int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT d.*, w.url AS webhook_url FROM webhook_deliveries d
             LEFT JOIN webhooks w ON w.id = d.webhook_id
             ORDER BY d.created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
