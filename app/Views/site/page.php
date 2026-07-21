<?php

/** @var array $page */
/** @var string $content */
/** @var string $blockCss */
/** @var string $layoutType */
/** @var array|null $sidebar */

$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?? '';
$extraHeadCss = $blockCss;
$hideChrome = !empty($page['hide_chrome']); // лендинг (группа 6)
// Флаг страницы «Прозрачная шапка» — активирует режим из конструктора.
$transparentHeader = !empty($page['transparent_header']);
$isStaticPage = true;
require __DIR__ . '/_header.php';

// Первый блок — hero (шапка-герой) или page-header? Тогда он служит заголовком страницы.
$firstIsHero = false;
$firstType = '';
if (preg_match('/^\s*<section\b[^>]*\bcms-block--hero\b/', $content)) {
    $firstIsHero = true;
    $firstType = 'cms-block--hero';
} elseif (preg_match('/^\s*<section\b[^>]*\bcms-block--html\b[^>]*>\s*<section\b[^>]*\bpage-header\b/', $content)) {
    $firstIsHero = true;
    $firstType = 'page-header';
}

$pageLead = trim((string) ($page['lead'] ?? ''));

// Хлебные крошки для обычных страниц (не главная, не лендинг).
$crumbsHtml = '';
if (empty($page['is_home']) && !$hideChrome) {
    $crumbs = [
        ['label' => \App\Core\Lang::t('Главная'), 'url' => \App\Core\Locale::url('/')],
        ['label' => (string) ($page['title'] ?? '')],
    ];
    // Если первый блок страницы — hero или page-header, крошки встраиваем внутрь
    if ($firstIsHero) {
        if ($firstType === 'cms-block--hero') {
            $content = preg_replace('/(class="[^"]*\bcms-block--hero\b)/', '$1 cms-block--page-hero', $content, 1);
        }
        $crumbsClass = 'content-crumbs--on-hero';
        ob_start();
        require __DIR__ . '/_crumbs.php';
        $heroCrumbs = ob_get_clean();
        unset($crumbsClass);
        
        if ($heroCrumbs !== '') {
            if ($firstType === 'cms-block--hero') {
                $content = preg_replace('/(<div class="block-hero\b[^>]*>)/', '$1' . addcslashes($heroCrumbs, '\\$'), $content, 1);
            } elseif ($firstType === 'page-header') {
                $content = preg_replace('/(^\s*<section\b[^>]*\bcms-block--html\b[^>]*>\s*<section\b[^>]*\bpage-header\b[^>]*>\s*<div class="wrap\b[^>]*>)/', '$1' . addcslashes($heroCrumbs, '\\$'), $content, 1);
            }
        }
    } else {
        ob_start();
        require __DIR__ . '/_crumbs.php';
        $crumbsHtml = ob_get_clean();
    }
}

// Заголовок страницы + лид для простых страниц (без hero-шапки): показываем,
// когда задан лид, чтобы не менять вид существующих страниц без описания.
$showLeadHead = empty($page['is_home']) && !$hideChrome && !$firstIsHero && $pageLead !== '';
if ($showLeadHead): ?>
    <div class="content-pagehead">
        <div class="content-pagehead__inner">
            <?= $crumbsHtml ?>
            <h1 class="content-pagehead__title"><?= htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES) ?></h1>
            <p class="content-pagehead__lead"><?= nl2br(htmlspecialchars($pageLead, ENT_QUOTES)) ?></p>
        </div>
    </div>
<?php else: ?>
    <?= $crumbsHtml ?>
<?php endif; ?>
<?php
$hasSidebar = $sidebar !== null && trim($sidebar['html']) !== '';
?>
<?php if ($hasSidebar): ?>
    <div class="layout layout--<?= htmlspecialchars($sidebar['position'], ENT_QUOTES) ?>">
        <?php if ($sidebar['position'] === 'left'): ?>
            <aside class="layout__sidebar"><?= $sidebar['html'] ?></aside>
            <div class="layout__main"><?= $content ?></div>
        <?php else: ?>
            <div class="layout__main"><?= $content ?></div>
            <aside class="layout__sidebar"><?= $sidebar['html'] ?></aside>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?= $content ?>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
