<?php

use App\Core\AssetCollector;
use App\Core\DateFormatter;
use App\Core\Locale;
use App\Models\News;
use App\Models\Setting;

/** @var array $news */
/** @var array $gallery */
/** @var array $related */
/** @var ?array $prevNews */
/** @var ?array $nextNews */
$gallery = $gallery ?? [];
$related = $related ?? [];
$prevNews = $prevNews ?? null;
$nextNews = $nextNews ?? null;
$lang = Locale::current();

$metaTitle = $news['meta_title'] ?: $news['title'];
$metaDescription = $news['meta_description'] ?: ($news['excerpt'] ?? '');
$ogType = 'article';
$ogImage = News::getCoverImage($news) ?? '';

AssetCollector::requireJs('news');

require __DIR__ . '/_header.php';

// Крошки: у премиум-макета рендерятся внутри hero (см. ниже), у остальных —
// обычной полосой перед статьёй.
$crumbs = [
    ['label' => t('Главная'), 'url' => Locale::url('/')],
    ['label' => t('Новости'), 'url' => Locale::url('news')],
    ['label' => (string) $news['title']],
];

$date = (string) ($news['published_at'] ?? '');
// Дата — единым числовым форматом на всех языках: 19.07.2026.
$dateLong = $date !== '' ? DateFormatter::short($date) : '';
// Время чтения: ~180 слов в минуту по тексту статьи (юникод-подсчёт слов).
preg_match_all('/[\p{L}\p{N}]+/u', strip_tags((string) ($news['content'] ?? '')), $m);
$readMin = max(1, (int) ceil(count($m[0]) / 180));
$views = (int) ($news['views'] ?? 0);

// Слайды: обложка + галерея (уникальные пути).
$slides = [];
$cover = trim((string) ($news['image'] ?? ''));
if ($cover !== '') {
    $slides[] = ['path' => $cover, 'alt' => (string) $news['title']];
}
foreach ($gallery as $img) {
    $p = trim((string) $img['path']);
    if ($p !== '' && $p !== $cover) {
        $slides[] = ['path' => $p, 'alt' => (string) ($img['alt_text'] ?? '')];
    }
}

$keyPoints = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($news['key_points'] ?? '')) ?: [])));
$eventMeta = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($news['event_meta'] ?? '')) ?: [])));
$docs = json_decode((string) ($news['docs'] ?? '[]'), true);
$docs = is_array($docs) ? $docs : [];
$videoUrl = trim((string) ($news['video_url'] ?? ''));
$legacyPressUrl = trim((string) ($news['press_release_url'] ?? ''));
if ($legacyPressUrl !== '') {
    $alreadyInDocs = false;
    foreach ($docs as $doc) {
        if (is_array($doc) && trim((string) ($doc['url'] ?? '')) === $legacyPressUrl) {
            $alreadyInDocs = true;
            break;
        }
    }
    if (!$alreadyInDocs) {
        // Старые записи показываем в едином разделе документов до их сохранения
        // в обновлённой форме админки.
        $docs[] = ['title' => t('Пресс-релиз'), 'meta' => '', 'url' => $legacyPressUrl];
    }
}

$base = \App\Core\AppUrl::base();
$pageUrl = $base . Locale::url('news/' . $news['slug'], $lang);
$shareTitle = rawurlencode((string) $news['title']);
$shareUrl = rawurlencode($pageUrl);

// Общие мини-иконки (событие: календарь, место, участники, теги — по кругу).
$eventIcons = [
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4m8-4v4M4 10h16"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><path d="M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><circle cx="9" cy="8" r="3"/><path d="M3 19c0-3 2.7-4.5 6-4.5s6 1.5 6 4.5"/><circle cx="17" cy="9" r="2.4"/><path d="M16 14.7c2.6.3 5 1.7 5 4.3"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><path d="M4 11V5a1 1 0 0 1 1-1h6l9 9-7 7-9-9z"/><circle cx="8.5" cy="8.5" r="1.3"/></svg>',
];
$pointIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="22" height="22"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.3 2.3 4.7-4.8"/></svg>';
$docIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/></svg>';
$dlIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17"><path d="M12 4v11m0 0 4-4m-4 4-4-4"/><path d="M5 19h14"/></svg>';
?>
<?php
// Тип отображения (выбирается в админке): standard — только обложка,
// gallery — слайдер с миниатюрами, video — модуль YouTube с play,
// side_image — компактное фото сбоку от заголовка.
$layout = News::normalizeLayout($news['layout_type'] ?? 'standard');
$videoId = \App\Core\Video::youtubeId($news['video_url'] ?? null);
if ($layout === 'video' && $videoId === null) {
    $layout = 'standard'; // без валидного YouTube-URL показываем обложку
}
// Адаптация макета к наполнению: без медиа hero занимает всю ширину,
// без тезисов левая колонка убирается, «Поделиться» уходит под статью.
$isPremium = $layout === 'premium';
$heroSlides = $layout === 'gallery' ? $slides : array_slice($slides, 0, 1);
$hasMedia = !$isPremium && ($layout === 'video' || !empty($heroSlides));
$hasLeft = !empty($keyPoints);

// Оглавление статьи (премиум): собираем из <h2>/<h3> контента и проставляем id.
$toc = [];
$contentHtml = (string) $news['content'];
if ($isPremium) {
    $n = 0;
    $contentHtml = (string) preg_replace_callback(
        '/<h([23])([^>]*)>(.*?)<\/h\1>/su',
        static function (array $m) use (&$toc, &$n): string {
            $n++;
            $id = 'sec-' . $n;
            $toc[] = ['id' => $id, 'label' => trim(strip_tags($m[3]))];
            return '<h' . $m[1] . $m[2] . ' id="' . $id . '">' . $m[3] . '</h' . $m[1] . '>';
        },
        $contentHtml
    ) ?: $contentHtml;
}

$shareBlock = static function (string $extraClass) use ($shareUrl, $shareTitle, $pageUrl): void { ?>
            <div class="newsdetail-share no-print<?= $extraClass ?>">
                <h2 class="newsdetail-share__title"><?= htmlspecialchars(t('Поделиться'), ENT_QUOTES) ?></h2>
                <div class="newsdetail-share__row">
                    <a class="newsdetail-share__btn" href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars(t('Поделиться в Telegram'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M21.9 4.6 19 19.3c-.2 1-.8 1.2-1.6.8l-4.5-3.3-2.2 2.1c-.2.2-.4.4-.9.4l.3-4.6 8.4-7.6c.4-.3-.1-.5-.6-.2L7.6 13.4l-4.5-1.4c-1-.3-1-1 .2-1.4l17.3-6.7c.8-.3 1.5.2 1.3 1.3z"/></svg></a>
                    <a class="newsdetail-share__btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars(t('Поделиться в Facebook'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H7v3h3v7h3v-7h3l1-3h-4V9c0-.6.4-1 1-1z"/></svg></a>
                    <a class="newsdetail-share__btn" href="https://x.com/intent/post?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars(t('Поделиться в X'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17.7 3H21l-7.1 8.2L22 21h-6.6l-5.1-6.1L4.5 21H1.2l7.6-8.7L1 3h6.8l4.6 5.6L17.7 3zm-1.2 16h1.8L6.9 4.9H5L16.5 19z"/></svg></a>
                    <a class="newsdetail-share__btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars(t('Поделиться в LinkedIn'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M6.5 8.8H3.6V21h2.9V8.8zM5 7.4a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4zM21 14.2c0-3.2-1.7-4.7-4-4.7-1.8 0-2.6 1-3.1 1.7V8.8H11V21h2.9v-6.5c0-1.7.8-2.7 2.2-2.7 1.3 0 2 .9 2 2.7V21H21v-6.8z"/></svg></a>
                    <button type="button" class="newsdetail-share__btn" data-copy-link="<?= htmlspecialchars($pageUrl, ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Скопировать ссылку'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17"><path d="M10 14a4.5 4.5 0 0 0 6.4 0l3.2-3.2a4.5 4.5 0 1 0-6.4-6.4L11.6 6"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3.2 3.2a4.5 4.5 0 1 0 6.4 6.4l1.6-1.6"/></svg></button>
                    <button type="button" class="newsdetail-share__btn" data-print-page aria-label="<?= htmlspecialchars(t('Распечатать'), ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-12 0v4h8v-4m-8 0h8"/></svg></button>
                </div>
            </div>
<?php };

if (!$isPremium) {
    require __DIR__ . '/_crumbs.php';
}

$sidebar = $sidebar ?? null;
$hasSidebar = $sidebar !== null && trim($sidebar['html']) !== '';
?>
<?php if ($hasSidebar): ?>
<div class="corp-wrap">
    <div class="corp-article-layout layout layout--<?= htmlspecialchars($sidebar['position'], ENT_QUOTES) ?>">
        <main class="layout__main">
<?php else: ?>
<div class="corp-wrap">
    <div>
        <main class="layout__main">
<?php endif; ?>

    <article class="corp-article">
        <header class="corp-article-header">
            <?php if (!empty($news['badge'])): ?>
                <span class="corp-article-meta"><?= htmlspecialchars((string) $news['badge'], ENT_QUOTES) ?></span>
            <?php endif; ?>
            <h1><?= htmlspecialchars((string) $news['title'], ENT_QUOTES) ?></h1>
            <div style="margin-top:24px; color:var(--text-muted); font-size:14px; font-family:var(--sans);">
                <?php if ($dateLong !== ''): ?>
                    <time datetime="<?= htmlspecialchars(substr($date, 0, 10), ENT_QUOTES) ?>"><?= htmlspecialchars($dateLong, ENT_QUOTES) ?></time> &nbsp;&middot;&nbsp;
                <?php endif; ?>
                <span><?= $readMin ?> <?= htmlspecialchars(t('мин чтения'), ENT_QUOTES) ?></span>
            </div>
        </header>

        <?php if ($cover !== ''): ?>
            <img src="<?= htmlspecialchars($cover, ENT_QUOTES) ?>" alt="<?= htmlspecialchars((string) $news['title'], ENT_QUOTES) ?>" style="width:100%; height:auto; border-radius:4px; margin-bottom:48px;">
        <?php endif; ?>

        <?php if (!empty($news['excerpt'])): ?>
            <p style="font-size:22px; line-height:1.6; color:var(--navy); margin-bottom:48px; font-weight:500;"><?= htmlspecialchars((string) $news['excerpt'], ENT_QUOTES) ?></p>
        <?php endif; ?>

        <div class="corp-article-body rich-content">
            <?= $contentHtml ?? $news['content'] ?>
        </div>

        <?php if ($videoUrl !== ''): ?>
            <div style="margin-top: 48px;">
                <a class="btn-outline" href="<?= htmlspecialchars($videoUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener">
                    <?= htmlspecialchars(t('Смотреть видео'), ENT_QUOTES) ?> ↗
                </a>
            </div>
        <?php endif; ?>
    </article>

<?php if ($hasSidebar): ?>
        </main>
        <aside class="corp-sidebar layout__sidebar">
            <?= $sidebar['html'] ?>
        </aside>
    </div>
<?php else: ?>
        </main>
    </div>
<?php endif; ?>

    <div class="newsdetail-subscribe no-print" style="display:none;"></div>
    <div class="newsdetail-related no-print" style="display:none;"></div>
    <div class="newsdetail-adjacent no-print" style="display:none;"></div>
    <?php if ($hasSidebar): ?>
    <div class="newsdetail-card--thesis-inline" style="display:none;">
        <?php foreach ($keyPoints as $kp): ?>
            <p><?= htmlspecialchars($kp, ENT_QUOTES) ?></p>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="newsdetail-side" style="display:none;">
        <?php foreach ($keyPoints as $kp): ?>
            <p><?= htmlspecialchars($kp, ENT_QUOTES) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div data-replay-label="replay" style="display:none;"></div>
</div>

<?= \App\Core\SchemaOrg::render(\App\Core\SchemaOrg::newsArticle(
    (string) $news['title'],
    $pageUrl,
    (string) ($news['published_at'] ?? ''),
    (string) ($news['excerpt'] ?? ''),
    $ogImage !== '' ? $base . $ogImage : '',
    \App\Models\Setting::get('site_name', '')
)) . "\n" ?>
<?php require __DIR__ . '/_footer.php'; ?>