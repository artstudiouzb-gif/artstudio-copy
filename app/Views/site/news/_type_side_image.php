<?php

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Core\Media;

/** @var array $news */
$cover = trim((string) ($news['image'] ?? ''));
$date = DateFormatter::long((string) $news['published_at'], Locale::current());
?>
<article class="news-single news-single--side">
    <h1><?= htmlspecialchars($news['title'], ENT_QUOTES) ?></h1>
    <?php if ($date !== ''): ?><time datetime="<?= htmlspecialchars(substr((string) $news['published_at'], 0, 10), ENT_QUOTES) ?>"><?= htmlspecialchars($date, ENT_QUOTES) ?></time><?php endif; ?>
    <div class="news-side">
        <?php if ($cover !== ''): ?>
            <aside class="news-side__media">
                <?= Media::picture($cover, (string) $news['title'], $news['focal_x'] ?? null, $news['focal_y'] ?? null, 'news-side__img', false) ?>
            </aside>
        <?php endif; ?>
        <div class="news-side__content news-single__content"><?= $news['content'] ?></div>
    </div>
</article>
