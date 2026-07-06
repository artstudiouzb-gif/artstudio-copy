<?php

use App\Core\Flash;
use App\Core\HeaderConfig;
use App\Core\Locale;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Setting;

/** @var string $metaTitle */
/** @var string $metaDescription */
/** @var string $extraHeadCss */
/** @var string $ogImage */
/** @var string $ogType */

$siteName = Setting::get('site_name', 'ArtStudio');
$logo = Setting::get('logo_url', '');
$primaryColor = Setting::get('color_primary', '#1a1a1a');
$accentColor = Setting::get('color_accent', '#e63946');
$font = Setting::get('font_family', "'Inter', sans-serif");
$extraHeadCss = $extraHeadCss ?? '';

// --- Дизайн-система: тема и локальный шрифт ---
$defaultTheme = Setting::get('default_theme', 'light'); // light | dark | auto
if (!in_array($defaultTheme, ['light', 'dark', 'auto'], true)) {
    $defaultTheme = 'light';
}
$fontUrl = Setting::get('font_url', '');           // ссылка на .woff2 локального шрифта
$fontFaceName = Setting::get('font_face_name', ''); // имя семейства для @font-face

// --- SEO / Open Graph ---
$appUrl = rtrim((string) \App\Core\Config::get('app.url', ''), '/');
$canonicalUrl = $appUrl . Locale::url(Locale::path());
$ogType = $ogType ?? 'website';
// Приоритет OG-картинки: страница -> дефолтный OG:Image -> логотип (задача 116).
$defaultOg = Setting::get('default_og_image', '');
$ogImageRaw = ($ogImage ?? '') !== '' ? $ogImage : ($defaultOg !== '' ? $defaultOg : ($logo !== '' ? $logo : ''));
// Абсолютный URL для og:image.
if ($ogImageRaw !== '' && !preg_match('#^https?://#', $ogImageRaw)) {
    $ogImageRaw = $appUrl . '/' . ltrim($ogImageRaw, '/');
}
// Meta Description по умолчанию, если не задан на странице.
if (empty($metaDescription)) {
    $metaDescription = Setting::get('default_meta_description', '');
}
// Favicon / Theme Color / PWA (задача 116).
$faviconUrl = Setting::get('favicon_url', '');
$themeColor = Setting::get('theme_color', '');
$pwaShortName = Setting::get('pwa_short_name', '');

$hcfg = HeaderConfig::get();
$currentLang = Locale::current();

// --- Логотип ---
$logoHtml = '<a href="' . htmlspecialchars(Locale::url('/', $currentLang), ENT_QUOTES) . '" class="site-header__logo">';
if ($logo !== '') {
    $logoHtml .= '<img src="' . htmlspecialchars($logo, ENT_QUOTES) . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES) . '">';
} else {
    $logoHtml .= '<span>' . htmlspecialchars($siteName, ENT_QUOTES) . '</span>';
}
$logoHtml .= '</a>';

// --- Меню ---
$menuHtml = '';
$menuItems = MenuItem::activeForLang($currentLang);
if (!empty($menuItems)) {
    $menuHtml = '<nav class="site-menu">';
    foreach ($menuItems as $mi) {
        $url = MenuItem::resolveUrl($mi, $currentLang);
        // Показываем только активные дочерние пункты.
        $children = array_values(array_filter(
            $mi['children'] ?? [],
            static fn ($c) => (int) $c['is_active'] === 1
        ));

        if ($children === []) {
            $menuHtml .= '<a class="site-menu__link" href="' . htmlspecialchars($url, ENT_QUOTES) . '">'
                . htmlspecialchars($mi['title'], ENT_QUOTES) . '</a>';
            continue;
        }

        // Пункт с выпадающим подменю (dropdown на desktop hover/focus, tap на мобильных).
        $menuHtml .= '<div class="site-menu__item site-menu__item--has-children">';
        $menuHtml .= '<a class="site-menu__link" href="' . htmlspecialchars($url, ENT_QUOTES) . '">'
            . htmlspecialchars($mi['title'], ENT_QUOTES) . '</a>';
        $menuHtml .= '<button type="button" class="site-menu__toggle" aria-expanded="false" aria-label="Открыть подменю">▾</button>';
        $menuHtml .= '<div class="site-submenu">';
        foreach ($children as $child) {
            $childUrl = MenuItem::resolveUrl($child, $currentLang);
            $menuHtml .= '<a class="site-submenu__link" href="' . htmlspecialchars($childUrl, ENT_QUOTES) . '">'
                . htmlspecialchars($child['title'], ENT_QUOTES) . '</a>';
        }
        $menuHtml .= '</div></div>';
    }
    $menuHtml .= '</nav>';
}

// --- Переключатель языков ---
$langHtml = '';
$activeLangs = Language::active();
if ($hcfg['language_switcher']['enabled'] && count($activeLangs) > 1) {
    $flags = ['ru' => '🇷🇺', 'uz' => '🇺🇿', 'en' => '🇬🇧', 'kk' => '🇰🇿', 'tr' => '🇹🇷', 'de' => '🇩🇪'];
    $format = $hcfg['language_switcher']['format'];
    $path = Locale::path();
    $langHtml = '<div class="site-lang-switcher">';
    foreach ($activeLangs as $l) {
        $code = (string) $l['code'];
        $label = match ($format) {
            'name' => $l['name'],
            'flag' => $flags[$code] ?? strtoupper($code),
            default => strtoupper($code),
        };
        $href = Locale::url($path, $code);
        $isActive = $code === $currentLang ? ' is-active' : '';
        $langHtml .= '<a class="site-lang-switcher__item' . $isActive . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . htmlspecialchars((string) $label, ENT_QUOTES) . '</a>';
    }
    $langHtml .= '</div>';
}

// --- Кнопки соцсетей ---
$socialHtml = '';
if (!empty($hcfg['social_buttons'])) {
    $socialHtml = '<div class="site-social">';
    foreach ($hcfg['social_buttons'] as $btn) {
        $socialHtml .= '<a class="site-social__link site-social__link--' . htmlspecialchars($btn['network'], ENT_QUOTES) . '" href="'
            . htmlspecialchars($btn['url'], ENT_QUOTES) . '" target="_blank" rel="noopener" aria-label="' . htmlspecialchars($btn['network'], ENT_QUOTES) . '">'
            . htmlspecialchars(mb_strtoupper(mb_substr($btn['network'], 0, 1)), ENT_QUOTES) . '</a>';
    }
    $socialHtml .= '</div>';
}

// --- CTA-кнопка ---
$ctaHtml = '';
if ($hcfg['cta']['enabled'] && $hcfg['cta']['text'] !== '') {
    $ctaHtml = '<a class="site-cta site-cta--' . htmlspecialchars($hcfg['cta']['style'], ENT_QUOTES) . '" href="'
        . htmlspecialchars($hcfg['cta']['url'] !== '' ? $hcfg['cta']['url'] : '#', ENT_QUOTES) . '">'
        . htmlspecialchars($hcfg['cta']['text'], ENT_QUOTES) . '</a>';
}

// --- Переключатель темы (показываем, если тема не фиксирована как auto) ---
$themeToggle = '';
if ($defaultTheme !== 'auto') {
    $themeToggle = '<button type="button" class="site-theme-toggle" aria-label="Сменить тему" title="Светлая/тёмная тема">◐</button>';
}

// --- Раскладка по зонам ---
$zones = ['left' => '', 'center' => '', 'right' => ''];
$zones[$hcfg['logo_position']] .= $logoHtml;
$zones[$hcfg['menu_position']] .= $menuHtml;
// Утилиты (язык, соцсети, CTA, тема) — в правую зону.
$zones['right'] .= $langHtml . $socialHtml . $ctaHtml . $themeToggle;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>" data-theme="<?= htmlspecialchars($defaultTheme, ENT_QUOTES) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
/* Применяем сохранённую тему до отрисовки, исключая мигание (FOUC). */
(function(){try{var t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();
</script>
<title><?= htmlspecialchars($metaTitle, ENT_QUOTES) ?></title>
<?php if (!empty($robotsNoindex)): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<?php if (!empty($metaDescription)): ?>
<meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
<meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES) ?>">
<meta property="og:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES) ?>">
<?php if (!empty($metaDescription)): ?>
<meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
<?php if ($ogImageRaw !== ''): ?>
<meta property="og:image" content="<?= htmlspecialchars($ogImageRaw, ENT_QUOTES) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<?php if ($fontUrl !== '' && $fontFaceName !== ''): ?>
<link rel="preload" href="<?= htmlspecialchars($fontUrl, ENT_QUOTES) ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face {
    font-family: '<?= htmlspecialchars($fontFaceName, ENT_QUOTES) ?>';
    src: url('<?= htmlspecialchars($fontUrl, ENT_QUOTES) ?>') format('woff2');
    font-weight: 100 900;
    font-display: swap;
}
</style>
<?php endif; ?>
<?php if ($faviconUrl !== ''): ?>
<link rel="icon" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
<?php endif; ?>
<?php if ($themeColor !== ''): ?>
<meta name="theme-color" content="<?= htmlspecialchars($themeColor, ENT_QUOTES) ?>">
<?php endif; ?>
<?php if ($pwaShortName !== ''): ?>
<link rel="manifest" href="/manifest.webmanifest">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($pwaShortName, ENT_QUOTES) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Core\Asset::url('/assets/css/frontend.css'), ENT_QUOTES) ?>">
<style>
:root {
    --color-primary: <?= htmlspecialchars($primaryColor, ENT_QUOTES) ?>;
    --color-accent: <?= htmlspecialchars($accentColor, ENT_QUOTES) ?>;
    --font-family: <?= htmlspecialchars($font, ENT_QUOTES) ?>;
}
</style>
<?php if ($extraHeadCss !== ''): ?>
<style id="block-styles">
<?= $extraHeadCss ?>
</style>
<?php endif; ?>
</head>
<body<?= !empty($previewNotice) ? ' class="is-preview"' : '' ?>>
<a href="#main-content" class="skip-link">Перейти к содержимому</a>
<?php if (!empty($previewNotice)): ?>
<div class="preview-bar" role="status">
    👁 Режим предпросмотра — эта версия не опубликована и закрыта от индексации.
</div>
<?php endif; ?>
<header class="site-header site-header--logo-<?= htmlspecialchars($hcfg['logo_position'], ENT_QUOTES) ?>">
    <div class="site-header__inner">
        <div class="site-header__zone site-header__zone--left"><?= $zones['left'] ?></div>
        <div class="site-header__zone site-header__zone--center"><?= $zones['center'] ?></div>
        <div class="site-header__zone site-header__zone--right"><?= $zones['right'] ?></div>
    </div>
</header>
<main class="site-content" id="main-content">
<div class="print-only print-header">
    <?php if ($logo !== ''): ?>
        <img src="<?= htmlspecialchars($logo, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
    <?php else: ?>
        <div class="print-name"><?= htmlspecialchars($siteName, ENT_QUOTES) ?></div>
    <?php endif; ?>
</div>
<?php foreach (Flash::pull() as $flash): ?>
    <div class="site-alert site-alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endforeach; ?>
