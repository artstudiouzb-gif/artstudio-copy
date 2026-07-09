<?php

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Core\Media;

/** @var array $news */
$cover = trim((string) ($news['image'] ?? ''));
$date = DateFormatter::long((string) $news['published_at'], Locale::current());
?>
<article class="news-single news-single--standard">
    <h1><?= htmlspecialchars($news['title'], ENT_QUOTES) ?></h1>
    <?php if ($date !== ''): ?><time datetime="<?= htmlspecialchars(substr((string) $news['published_at'], 0, 10), ENT_QUOTES) ?>"><?= htmlspecialchars($date, ENT_QUOTES) ?></time><?php endif; ?>
    <?php if ($cover !== ''): ?>
        <div class="news-single__cover">
            <?= Media::picture($cover, (string) $news['title'], $news['focal_x'] ?? null, $news['focal_y'] ?? null, 'news-single__cover-img', false) ?>
        </div>
    <?php endif; ?>
    <div class="news-single__content"><?= $news['content'] ?></div>
</article>
