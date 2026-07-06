<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Сквозной поиск по админке (задача 92): страницы, новости, проекты, файлы.
 * Возвращает плоский список результатов со ссылками на редактирование.
 */
final class Search
{
    /**
     * @return array<int, array{type: string, title: string, url: string}>
     */
    public static function query(string $term, int $perType = 5): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        $pdo = Database::pdo();
        $results = [];

        // Один плейсхолдер :q через CONCAT_WS — переносимо при native prepares
        // (повтор именованного плейсхолдера в SQL недопустим без эмуляции).
        $sources = [
            ['label' => 'Страница', 'sql' => "SELECT id, title FROM pages WHERE deleted_at IS NULL AND CONCAT_WS(' ', title, slug) LIKE :q ORDER BY created_at DESC LIMIT :n", 'url' => '/admin/pages/%d/edit'],
            ['label' => 'Новость', 'sql' => "SELECT id, title FROM news WHERE deleted_at IS NULL AND CONCAT_WS(' ', title, slug) LIKE :q ORDER BY created_at DESC LIMIT :n", 'url' => '/admin/news/%d/edit'],
            ['label' => 'Проект', 'sql' => "SELECT id, title FROM projects WHERE deleted_at IS NULL AND CONCAT_WS(' ', title, slug) LIKE :q ORDER BY created_at DESC LIMIT :n", 'url' => '/admin/projects/%d/edit'],
            ['label' => 'Файл', 'sql' => "SELECT id, original_name AS title FROM files WHERE original_name LIKE :q ORDER BY created_at DESC LIMIT :n", 'url' => '/admin/files'],
        ];

        foreach ($sources as $src) {
            try {
                $stmt = $pdo->prepare($src['sql']);
                $stmt->bindValue(':q', $like);
                $stmt->bindValue(':n', $perType, \PDO::PARAM_INT);
                $stmt->execute();
                foreach ($stmt->fetchAll() as $row) {
                    $results[] = [
                        'type' => $src['label'],
                        'title' => (string) $row['title'],
                        'url' => str_contains($src['url'], '%d') ? sprintf($src['url'], (int) $row['id']) : $src['url'],
                    ];
                }
            } catch (\Throwable $e) {
                Logger::error('Search failed for ' . $src['label'] . ': ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Публичный поиск по сайту: только опубликованный контент, ссылки ведут на
     * страницы фронтенда. Ищет по страницам, новостям и записям публичных
     * пользовательских типов контента. URL локализуются под текущий язык.
     *
     * @return array<int, array{type: string, title: string, url: string, excerpt: string}>
     */
    public static function site(string $term, int $limit = 40): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        $pdo = Database::pdo();
        $lang = Locale::current();
        $results = [];

        try {
            $stmt = $pdo->prepare(
                "SELECT title, slug FROM pages
                 WHERE deleted_at IS NULL AND status = 'published' AND is_home = 0
                   AND CONCAT_WS(' ', title, slug, meta_description) LIKE :q
                 ORDER BY updated_at DESC LIMIT :n"
            );
            $stmt->bindValue(':q', $like);
            $stmt->bindValue(':n', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $results[] = [
                    'type' => 'Страница',
                    'title' => (string) $row['title'],
                    'url' => Locale::url((string) $row['slug'], $lang),
                    'excerpt' => '',
                ];
            }

            $stmt = $pdo->prepare(
                "SELECT title, slug, excerpt FROM news
                 WHERE deleted_at IS NULL AND status = 'published'
                   AND CONCAT_WS(' ', title, slug, excerpt, content) LIKE :q
                 ORDER BY published_at DESC LIMIT :n"
            );
            $stmt->bindValue(':q', $like);
            $stmt->bindValue(':n', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $results[] = [
                    'type' => 'Новость',
                    'title' => (string) $row['title'],
                    'url' => Locale::url('news/' . $row['slug'], $lang),
                    'excerpt' => mb_substr(trim(strip_tags((string) ($row['excerpt'] ?? ''))), 0, 160),
                ];
            }

            $stmt = $pdo->prepare(
                "SELECT ce.title, ce.slug, ct.slug AS type_slug, ct.name AS type_name
                 FROM content_entries ce
                 JOIN content_types ct ON ct.id = ce.type_id
                 WHERE ce.deleted_at IS NULL AND ce.status = 'published' AND ct.is_public = 1
                   AND CONCAT_WS(' ', ce.title, ce.slug, ce.data) LIKE :q
                 ORDER BY ce.created_at DESC LIMIT :n"
            );
            $stmt->bindValue(':q', $like);
            $stmt->bindValue(':n', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $results[] = [
                    'type' => (string) $row['type_name'],
                    'title' => (string) $row['title'],
                    'url' => Locale::url('catalog/' . $row['type_slug'] . '/' . $row['slug'], $lang),
                    'excerpt' => '',
                ];
            }
        } catch (\Throwable $e) {
            Logger::error('Site search failed: ' . $e->getMessage());
        }

        return $results;
    }
}
