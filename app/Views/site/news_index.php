<?php

use App\Core\AssetCollector;
use App\Core\DateFormatter;
use App\Core\Locale;
use App\Core\Media;
use App\Models\News;
use App\Core\Video;

/** @var array $items */

$metaTitle = 'Новости';
$metaDescription = '';
AssetCollector::requireJs('news'); // скелетоны + fallback обложек
require __DIR__ . '/_header.php';

$crumbs = [
    ['label' => 'Главная', 'url' => Locale::url('/')],
    ['label' => 'Новости'],
];
require __DIR__ . '/_crumbs.php';

$lang = Locale::current();
?>
<div class="news-index">
    <div class="news-index__head">
        <h1 class="news-index__title">Новости</h1>
        <p class="news-index__lead">Официальные сообщения, объявления и события организации.</p>
    </div>

    <?php if (empty($items)): ?>
        <p class="news-index__empty">Пока нет опубликованных новостей.</p>
    <?php else: ?>
        <div class="news-index__grid">
            <?php foreach ($items as $item): ?>
                <?php
                $url = Locale::url('news/' . $item['slug']);
                $cover = News::getCoverImage($item);
                $isVideo = ($item['layout_type'] ?? 'standard') === 'video' && Video::isYoutube($item['video_url'] ?? null);
                $date = DateFormatter::long((string) $item['published_at'], $lang);
                ?>
                <article class="news-card<?= $isVideo ? ' news-card--video' : '' ?>">
                    <a class="news-card__link" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                        <?php if ($cover !== null): ?>
                            <span class="news-card__cover skeleton">
                                <?= Media::picture($cover, (string) $item['title'], $item['focal_x'] ?? null, $item['focal_y'] ?? null, 'news-card__img') ?>
                                <?php if ($isVideo): ?><span class="news-card__play" aria-hidden="true"></span><?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="news-card__cover news-card__cover--empty" aria-hidden="true">
                                <?php if ($isVideo): ?><span class="news-card__play" aria-hidden="true"></span><?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="news-card__body">
                            <?php if ($date !== ''): ?>
                                <time class="news-card__date" datetime="<?= htmlspecialchars(substr((string) $item['published_at'], 0, 10), ENT_QUOTES) ?>"><?= htmlspecialchars($date, ENT_QUOTES) ?></time>
                            <?php endif; ?>
                            <span class="news-card__title"><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></span>
                            <?php if (!empty($item['excerpt'])): ?>
                                <span class="news-card__excerpt"><?= htmlspecialchars(mb_substr(strip_tags((string) $item['excerpt']), 0, 160), ENT_QUOTES) ?></span>
                            <?php endif; ?>
                            <span class="news-card__more">Читать →</span>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
