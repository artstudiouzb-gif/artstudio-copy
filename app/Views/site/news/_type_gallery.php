<?php

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Core\Media;

/** @var array $news */
/** @var array $gallery */
$gallery = $gallery ?? [];
// Слайдер выводится ТОЛЬКО при наличии фотографий (задача 65).
$hasSlides = !empty($gallery);
$date = DateFormatter::long((string) $news['published_at'], Locale::current());
?>
<article class="news-single news-single--gallery">
    <h1><?= htmlspecialchars($news['title'], ENT_QUOTES) ?></h1>
    <?php if ($date !== ''): ?><time datetime="<?= htmlspecialchars(substr((string) $news['published_at'], 0, 10), ENT_QUOTES) ?>"><?= htmlspecialchars($date, ENT_QUOTES) ?></time><?php endif; ?>

    <?php if ($hasSlides): ?>
        <div class="news-slider" data-news-slider>
            <div class="news-slider__track">
                <?php foreach ($gallery as $i => $g): ?>
                    <div class="news-slider__slide skeleton<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= (int) $i ?>">
                        <?= Media::picture(
                            (string) $g['path'],
                            (string) ($g['alt_text'] ?? $news['title']),
                            isset($g['focal_x']) ? (int) $g['focal_x'] : null,
                            isset($g['focal_y']) ? (int) $g['focal_y'] : null,
                            'news-slider__img',
                            $i > 0 // первая грузится сразу, остальные лениво
                        ) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($gallery) > 1): ?>
                <button type="button" class="news-slider__nav news-slider__nav--prev" aria-label="Назад">‹</button>
                <button type="button" class="news-slider__nav news-slider__nav--next" aria-label="Вперёд">›</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="news-single__content"><?= $news['content'] ?></div>
</article>
