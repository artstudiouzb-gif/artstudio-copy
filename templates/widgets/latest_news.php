<?php

use App\Core\Locale;

/** @var array $data */
/** @var string $lang */
$items = $data['items'] ?? [];
?>
<ul class="corp-widget-list">
    <?php foreach ($items as $item): ?>
        <li>
            <a href="<?= htmlspecialchars(Locale::url('news/' . $item['slug'], $lang), ENT_QUOTES) ?>">
                <?= htmlspecialchars($item['title'], ENT_QUOTES) ?>
            </a>
            <span class="date"><?= htmlspecialchars(substr((string) $item['published_at'], 0, 10), ENT_QUOTES) ?></span>
        </li>
    <?php endforeach; ?>
    <?php if (empty($items)): ?><li class="widget-empty">Нет новостей.</li><?php endif; ?>
</ul>
