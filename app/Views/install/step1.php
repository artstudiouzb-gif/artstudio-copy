<?php
/** @var array $requirements */
/** @var array $permissions */
/** @var bool $allPassed */
$step = '1';
require __DIR__ . '/_header.php';
?>
<h2 style="font-size:16px;">Системные требования</h2>
<?php foreach ($requirements as $check): ?>
    <div class="install-check">
        <span class="install-check__mark <?= $check['ok'] ? 'ok' : 'fail' ?>"><?= $check['ok'] ? '✓' : '✕' ?></span>
        <span>
            <?= htmlspecialchars($check['label'], ENT_QUOTES) ?>
            <?php if (!$check['ok']): ?><span class="install-check__hint"><?= htmlspecialchars($check['hint'], ENT_QUOTES) ?></span><?php endif; ?>
        </span>
    </div>
<?php endforeach; ?>

<h2 style="font-size:16px; margin-top:20px;">Права доступа</h2>
<?php foreach ($permissions as $check): ?>
    <div class="install-check">
        <span class="install-check__mark <?= $check['ok'] ? 'ok' : 'fail' ?>"><?= $check['ok'] ? '✓' : '✕' ?></span>
        <span>
            <?= htmlspecialchars($check['label'], ENT_QUOTES) ?>
            <?php if (!$check['ok']): ?><span class="install-check__hint"><?= htmlspecialchars($check['hint'], ENT_QUOTES) ?></span><?php endif; ?>
        </span>
    </div>
<?php endforeach; ?>

<div class="form-actions" style="margin-top:24px;">
    <?php if ($allPassed): ?>
        <a href="/install/step2" class="btn btn--primary">Продолжить →</a>
    <?php else: ?>
        <a href="/install" class="btn">Проверить снова</a>
        <span class="form-hint" style="align-self:center;">Устраните отмеченные пункты и обновите страницу.</span>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
