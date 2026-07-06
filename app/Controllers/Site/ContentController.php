<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Locale;
use App\Core\View;
use App\Models\ContentEntry;
use App\Models\ContentType;

/**
 * Публичный фронтенд пользовательских типов контента (Документы, Вакансии,
 * Тендеры и любые типы, созданные в админке с флагом «публичный»). Общий
 * список и карточка записи; значения кастомных полей рендерятся по типу.
 */
final class ContentController
{
    public function index(array $params): void
    {
        $type = ContentType::findBySlug((string) ($params['type'] ?? ''));
        if ($type === null || (int) ($type['is_public'] ?? 0) !== 1) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = Locale::current();
        $fields = ContentType::fields((int) $type['id']);
        $entries = [];
        foreach (ContentEntry::forType((int) $type['id'], 'published') as $entry) {
            $entry['data'] = json_decode((string) $entry['data'], true) ?: [];
            $entries[] = $this->localize($entry, $type, $lang);
        }

        View::render('site/content_index', [
            'type' => $type,
            'fields' => $fields,
            'entries' => $entries,
        ]);
    }

    public function show(array $params): void
    {
        $type = ContentType::findBySlug((string) ($params['type'] ?? ''));
        if ($type === null || (int) ($type['is_public'] ?? 0) !== 1) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $entry = ContentEntry::findPublishedBySlug((int) $type['id'], (string) ($params['slug'] ?? ''));
        if ($entry === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = Locale::current();
        $entry = $this->localize($entry, $type, $lang);

        View::render('site/content_show', [
            'type' => $type,
            'fields' => ContentType::fields((int) $type['id']),
            'entry' => $entry,
        ]);
    }

    /**
     * Накладывает перевод текущего языка на запись (для мультиязычных типов).
     *
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $type
     */
    private function localize(array $entry, array $type, string $lang): array
    {
        if ((int) ($type['has_translations'] ?? 0) !== 1) {
            return $entry;
        }
        $translations = ContentEntry::translations((int) $entry['id']);
        if (!isset($translations[$lang])) {
            return $entry;
        }
        $tr = $translations[$lang];
        if (!empty($tr['title'])) {
            $entry['title'] = $tr['title'];
        }
        if (!empty($tr['data']) && is_array($tr['data'])) {
            $entry['data'] = array_merge((array) $entry['data'], $tr['data']);
        }

        return $entry;
    }
}
