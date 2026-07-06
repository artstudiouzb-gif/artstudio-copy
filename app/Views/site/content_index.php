<?php

use App\Core\ContentFields;
use App\Core\Locale;

/** @var array $type */
/** @var array $fields */
/** @var array $entries */

$metaTitle = (string) $type['name'];
$metaDescription = (string) ($type['description'] ?? '');
require __DIR__ . '/_header.php';

// Короткие поля выносим в мета-строку карточки, длинные (textarea) — в анонс.
$shortFields = array_values(array_filter($fields, static fn ($f) => in_array($f['field_type'], ['text', 'number', 'date'], true)));
$longFields = array_values(array_filter($fields, static fn ($f) => $f['field_type'] === 'textarea'));
$fileFields = array_values(array_filter($fields, static fn ($f) => in_array($f['field_type'], ['file', 'image'], true)));
?>
<div class="content-list">
    <header class="content-list__head">
        <h1><?= htmlspecialchars((string) $type['name'], ENT_QUOTES) ?></h1>
        <?php if (!empty($type['description'])): ?>
            <p class="content-list__lead"><?= htmlspecialchars((string) $type['description'], ENT_QUOTES) ?></p>
        <?php endif; ?>
    </header>

    <?php if (empty($entries)): ?>
        <p class="content-list__empty">В этом разделе пока нет опубликованных записей.</p>
    <?php else: ?>
        <div class="content-cards">
            <?php foreach ($entries as $entry): ?>
                <?php $url = Locale::url('catalog/' . $type['slug'] . '/' . $entry['slug']); ?>
                <article class="content-card">
                    <h2 class="content-card__title">
                        <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $entry['title'], ENT_QUOTES) ?></a>
                    </h2>
                    <?php
                    $meta = [];
                    foreach ($shortFields as $f) {
                        $val = ContentFields::displayValue($f, $entry['data'][$f['name']] ?? null);
                        if ($val !== '') {
                            $meta[] = '<span class="content-card__meta-item"><b>' . htmlspecialchars((string) $f['label'], ENT_QUOTES) . ':</b> ' . $val . '</span>';
                        }
                    }
                    ?>
                    <?php if ($meta !== []): ?>
                        <div class="content-card__meta"><?= implode('', $meta) ?></div>
                    <?php endif; ?>
                    <?php foreach ($longFields as $f): ?>
                        <?php $val = ContentFields::displayValue($f, $entry['data'][$f['name']] ?? null); ?>
                        <?php if ($val !== ''): ?>
                            <p class="content-card__excerpt"><?= $val ?></p>
                            <?php break; // только первый длинный текст как анонс ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="content-card__foot">
                        <?php foreach ($fileFields as $f): ?>
                            <?php $val = ContentFields::displayValue($f, $entry['data'][$f['name']] ?? null); ?>
                            <?php if ($val !== '' && $f['field_type'] === 'file'): ?>
                                <span class="content-card__file">📎 <?= $val ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a class="content-card__more" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">Подробнее →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
