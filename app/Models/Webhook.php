<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Настроенные исходящие вебхуки (задача 136). Один вебхук = URL + секрет,
 * подписанный на определённый тип события.
 */
final class Webhook
{
    public const EVENTS = ['form.submitted', 'news.published'];

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM webhooks ORDER BY created_at DESC')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> активные вебхуки события */
    public static function activeForEvent(string $event): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM webhooks WHERE event_type = :e AND is_active = 1'
        );
        $stmt->execute([':e' => $event]);

        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM webhooks WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(string $event, string $url, ?string $secret, bool $active): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO webhooks (event_type, url, secret, is_active, created_at)
             VALUES (:e, :u, :s, :a, NOW())'
        );
        $stmt->execute([':e' => $event, ':u' => $url, ':s' => $secret ?: null, ':a' => $active ? 1 : 0]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $event, string $url, ?string $secret, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE webhooks SET event_type = :e, url = :u, secret = :s, is_active = :a WHERE id = :id'
        );
        $stmt->execute([':e' => $event, ':u' => $url, ':s' => $secret ?: null, ':a' => $active ? 1 : 0, ':id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM webhooks WHERE id = :id')->execute([':id' => $id]);
    }
}
