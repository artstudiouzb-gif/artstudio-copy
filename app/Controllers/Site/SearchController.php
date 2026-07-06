<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\RateLimiter;
use App\Core\Search;
use App\Core\View;

/**
 * Публичный поиск по сайту (страницы, новости, записи публичных типов).
 */
final class SearchController
{
    public function index(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $results = [];

        if ($query !== '' && mb_strlen($query) >= 2) {
            // Лёгкий анти-абуз: не более 30 поисков в минуту с одного IP.
            if (RateLimiter::throttle('site_search', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 30, 1)) {
                $results = Search::site($query);
            }
        }

        View::render('site/search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
