<?php
/** @var array $data */
$items = $data['items'] ?? [];
?>
<ul class="corp-widget-list">
    <?php foreach ($items as $item): ?>
        <li>
            <a href="<?= htmlspecialchars(\App\Core\Locale::url('projects/' . $item['slug']), ENT_QUOTES) ?>">
                <?= htmlspecialchars($item['title'], ENT_QUOTES) ?>
            </a>
        </li>
    <?php endforeach; ?>
    <?php if (empty($items)): ?><li class="widget-empty">Нет проектов.</li><?php endif; ?>
</ul>
