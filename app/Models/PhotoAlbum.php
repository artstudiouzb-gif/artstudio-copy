<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Slug;

/**
 * Фотоальбомы: галереи изображений с обложкой. Изображения хранятся ссылками
 * на файлы медиабиблиотеки (photo_album_images, каскадное удаление по FK).
 */
final class PhotoAlbum
{
    /** @return array<int, array<string, mixed>> */
    public static function all(bool $publishedOnly = false): array
    {
        $sql = 'SELECT a.*, (SELECT COUNT(*) FROM photo_album_images i WHERE i.album_id = a.id) AS images_count
                FROM photo_albums a';
        if ($publishedOnly) {
            $sql .= ' WHERE a.is_published = 1';
        }
        $sql .= ' ORDER BY a.created_at DESC, a.id DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM photo_albums WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM photo_albums WHERE slug = :slug AND is_published = 1 LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Создаёт альбом; slug — из названия, при коллизии добавляется суффикс. */
    public static function create(string $title, string $description = '', string $coverUrl = '', bool $published = true): ?int
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }
        $base = Slug::make($title) ?: 'album';
        $slug = $base;
        $n = 2;
        while (self::slugExists($slug)) {
            $slug = $base . '-' . $n++;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO photo_albums (title, slug, description, cover_url, is_published, created_at)
             VALUES (:t, :s, :d, :c, :p, NOW())'
        );
        $stmt->execute([
            ':t' => mb_substr($title, 0, 255),
            ':s' => mb_substr($slug, 0, 255),
            ':d' => trim($description),
            ':c' => mb_substr(trim($coverUrl), 0, 500),
            ':p' => $published ? 1 : 0,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $title, string $description, string $coverUrl, bool $published): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE photo_albums SET title = :t, description = :d, cover_url = :c, is_published = :p WHERE id = :id'
        );
        $stmt->execute([
            ':t' => mb_substr(trim($title), 0, 255),
            ':d' => trim($description),
            ':c' => mb_substr(trim($coverUrl), 0, 500),
            ':p' => $published ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM photo_albums WHERE id = :id')->execute([':id' => $id]);
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM photo_albums WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public static function images(int $albumId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM photo_album_images WHERE album_id = :a ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':a' => $albumId]);

        return $stmt->fetchAll();
    }

    public static function addImage(int $albumId, string $imageUrl, string $caption = ''): ?int
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '' || self::findById($albumId) === null) {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM photo_album_images WHERE album_id = :a'
        );
        $stmt->execute([':a' => $albumId]);
        $next = (int) $stmt->fetchColumn();

        $stmt = Database::pdo()->prepare(
            'INSERT INTO photo_album_images (album_id, image_url, caption, sort_order, created_at)
             VALUES (:a, :u, :c, :o, NOW())'
        );
        $stmt->execute([
            ':a' => $albumId,
            ':u' => mb_substr($imageUrl, 0, 500),
            ':c' => mb_substr(trim($caption), 0, 255),
            ':o' => $next,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function deleteImage(int $imageId): void
    {
        Database::pdo()->prepare('DELETE FROM photo_album_images WHERE id = :id')->execute([':id' => $imageId]);
    }

    /** Обложка альбома: заданная вручную или первое фото. */
    public static function coverFor(array $album): string
    {
        $cover = trim((string) ($album['cover_url'] ?? ''));
        if ($cover !== '') {
            return $cover;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT image_url FROM photo_album_images WHERE album_id = :a ORDER BY sort_order ASC, id ASC LIMIT 1'
        );
        $stmt->execute([':a' => (int) $album['id']]);

        return (string) ($stmt->fetchColumn() ?: '');
    }
}
