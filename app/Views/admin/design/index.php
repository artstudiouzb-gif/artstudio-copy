<?php

use App\Core\Csrf;

$pageTitle = 'Дизайн сайта';
$activeNav = 'design';
require __DIR__ . '/../layout/header.php';

/** @var array $options */
/** @var array $presets */
/** @var array $values */
/** @var string $activePreset */

// Группируем опции точной настройки по разделам.
$grouped = [];
foreach ($options as $key => $opt) {
    $grouped[$opt['group']][$key] = $opt;
}
?>
<p class="form-hint">Готовые конфигурации применяют набор настроек одним кликом. Ниже — точная настройка: выберите вариант для каждого параметра. Изменения сразу применяются к сайту.</p>

<section class="design-section">
    <h2 class="design-section__title">Готовые конфигурации</h2>
    <div class="design-presets">
        <?php foreach ($presets as $pkey => $preset): ?>
            <form method="post" action="/admin/design/preset" class="design-preset<?= $activePreset === $pkey ? ' is-active' : '' ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="preset" value="<?= htmlspecialchars($pkey, ENT_QUOTES) ?>">
                <div class="design-preset__head">
                    <strong><?= htmlspecialchars($preset['label'], ENT_QUOTES) ?></strong>
                    <?php if ($activePreset === $pkey): ?><span class="design-preset__badge">Активна</span><?php endif; ?>
                </div>
                <p class="design-preset__desc"><?= htmlspecialchars($preset['desc'], ENT_QUOTES) ?></p>
                <button type="submit" class="btn btn--small btn--primary">Применить</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<form method="post" action="/admin/design" class="design-fine">
    <?= Csrf::field() ?>
    <?php foreach ($grouped as $groupName => $groupOpts): ?>
        <section class="design-section">
            <h2 class="design-section__title"><?= htmlspecialchars($groupName, ENT_QUOTES) ?></h2>
            <?php foreach ($groupOpts as $key => $opt): ?>
                <div class="design-opt">
                    <div class="design-opt__label">
                        <span><?= htmlspecialchars($opt['label'], ENT_QUOTES) ?></span>
                        <?php if (!empty($opt['hint'])): ?><small><?= htmlspecialchars($opt['hint'], ENT_QUOTES) ?></small><?php endif; ?>
                    </div>
                    <div class="design-opt__choices">
                        <?php foreach ($opt['choices'] as $val => $label): ?>
                            <label class="design-card">
                                <input type="radio" name="<?= htmlspecialchars($key, ENT_QUOTES) ?>" value="<?= htmlspecialchars($val, ENT_QUOTES) ?>" <?= ($values[$key] ?? '') === $val ? 'checked' : '' ?>>
                                <span class="design-card__preview design-card__preview--<?= htmlspecialchars($key . '-' . $val, ENT_QUOTES) ?>"></span>
                                <span class="design-card__label"><?= htmlspecialchars($label, ENT_QUOTES) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <div class="design-actions">
        <button type="submit" class="btn btn--primary">Сохранить настройки дизайна</button>
    </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
