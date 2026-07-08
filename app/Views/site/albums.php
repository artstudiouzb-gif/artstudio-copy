<?php

use App\Core\Locale;

/** @var array $albums */

$metaTitle = 'Фотоальбомы';
$metaDescription = 'Фотогалереи и альбомы мероприятий';
require __DIR__ . '/_header.php';
?>
<div class="content-list">
    <nav class="content-crumbs" aria-label="Хлебные крошки">
        <a href="<?= htmlspecialchars(Locale::url('/'), ENT_QUOTES) ?>">Главная</a>
        <span>/</span>
        <span>Фотоальбомы</span>
    </nav>

    <header class="content-list__head">
        <h1>Фотоальбомы</h1>
    </header>

    <?php if (empty($albums)): ?>
        <p class="content-list__empty">Альбомов пока нет.</p>
    <?php else: ?>
        <div class="albums-grid">
            <?php foreach ($albums as $album): ?>
                <a class="album-card" href="<?= htmlspecialchars(Locale::url('albums/' . $album['slug']), ENT_QUOTES) ?>">
                    <?php if ($album['cover'] !== ''): ?>
                        <img class="album-card__cover" src="<?= htmlspecialchars((string) $album['cover'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars((string) $album['title'], ENT_QUOTES) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="album-card__cover album-card__cover--empty" aria-hidden="true"></span>
                    <?php endif; ?>
                    <span class="album-card__body">
                        <span class="album-card__title"><?= htmlspecialchars((string) $album['title'], ENT_QUOTES) ?></span>
                        <span class="album-card__meta"><?= (int) $album['images_count'] ?> фото</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
