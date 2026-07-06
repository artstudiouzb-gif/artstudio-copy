<?php

use App\Core\Locale;

/** @var string $query */
/** @var array $results */

$metaTitle = $query !== '' ? ('Поиск: ' . $query) : 'Поиск по сайту';
$metaDescription = '';
$robotsNoindex = true; // страницы результатов поиска не индексируем
require __DIR__ . '/_header.php';
?>
<div class="site-search-page">
    <h1>Поиск по сайту</h1>
    <form method="get" action="<?= htmlspecialchars(Locale::url('search'), ENT_QUOTES) ?>" class="site-search-page__form" role="search">
        <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES) ?>" placeholder="Что вы ищете?" aria-label="Поисковый запрос" autofocus>
        <button type="submit">Найти</button>
    </form>

    <?php if ($query !== '' && mb_strlen($query) < 2): ?>
        <p class="site-search-page__hint">Введите не менее двух символов.</p>
    <?php elseif ($query !== ''): ?>
        <p class="site-search-page__count">
            <?php if (empty($results)): ?>
                По запросу «<?= htmlspecialchars($query, ENT_QUOTES) ?>» ничего не найдено.
            <?php else: ?>
                Найдено результатов: <?= count($results) ?>
            <?php endif; ?>
        </p>
        <ul class="site-search-results">
            <?php foreach ($results as $r): ?>
                <li class="site-search-results__item">
                    <span class="site-search-results__type"><?= htmlspecialchars((string) $r['type'], ENT_QUOTES) ?></span>
                    <a class="site-search-results__link" href="<?= htmlspecialchars((string) $r['url'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $r['title'], ENT_QUOTES) ?></a>
                    <?php if (!empty($r['excerpt'])): ?>
                        <p class="site-search-results__excerpt"><?= htmlspecialchars((string) $r['excerpt'], ENT_QUOTES) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
