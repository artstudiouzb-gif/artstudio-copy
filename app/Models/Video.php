<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Slug;

/**
 * Видеозаписи: обложка + ссылка на видео (YouTube/внешнее). Блок «Медиа» на
 * главной собирает отмеченные (is_featured) автоматически.
 */
final class Video
{
    /** @return array<int, array<string, mixed>> */
    public static function all(bool $publishedOnly = false): array
    {
        $sql = 'SELECT * FROM videos';
        if ($publishedOnly) {
            $sql .= ' WHERE is_published = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, created_at DESC, id DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM videos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(string $title): ?int
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }
        $base = Slug::make($title) ?: 'video';
        $slug = $base;
        $n = 2;
        while (self::slugExists($slug)) {
            $slug = $base . '-' . $n++;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO videos (title, slug, created_at) VALUES (:t, :s, NOW())'
        );
        $stmt->execute([':t' => mb_substr($title, 0, 255), ':s' => mb_substr($slug, 0, 255)]);
        $id = (int) Database::pdo()->lastInsertId();
        self::bustPageCache();

        return $id;
    }

    public static function update(
        int $id,
        string $title,
        string $description,
        string $coverUrl,
        string $videoUrl,
        string $duration,
        bool $published,
        bool $featured,
        int $sortOrder
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE videos SET title = :t, description = :d, cover_url = :c, video_url = :v,
             duration = :dur, is_published = :p, is_featured = :f, sort_order = :o WHERE id = :id'
        );
        $stmt->execute([
            ':t' => mb_substr(trim($title), 0, 255),
            ':d' => trim($description),
            ':c' => mb_substr(trim($coverUrl), 0, 500),
            ':v' => mb_substr(trim($videoUrl), 0, 500),
            ':dur' => mb_substr(trim($duration), 0, 20),
            ':p' => $published ? 1 : 0,
            ':f' => $featured ? 1 : 0,
            ':o' => $sortOrder,
            ':id' => $id,
        ]);
        self::bustPageCache();
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM videos WHERE id = :id')->execute([':id' => $id]);
        self::bustPageCache();
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM videos WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Видео для блока «Медиа» на главной: отмеченные «показать на главном»;
     * если ни одного — откат на последние опубликованные.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forHome(int $limit = 8): array
    {
        $limit = max(1, min(24, $limit));
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM videos WHERE is_published = 1 AND is_featured = 1
             ORDER BY sort_order ASC, created_at DESC, id DESC LIMIT ' . $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (!empty($rows)) {
            return $rows;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM videos WHERE is_published = 1
             ORDER BY sort_order ASC, created_at DESC, id DESC LIMIT ' . $limit
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private static function bustPageCache(): void
    {
        \App\Core\Cache::forgetPrefix('page:');
    }
}
