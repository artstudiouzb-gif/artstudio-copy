<?php
/**
 * Декоративная SVG-карта Узбекистана с локациями (пинами) и анимированным
 * «маршрутом». Используется в hero (карточка присутствия/маршрута).
 *
 * @var array $points  Список локаций: [['name'=>..,'x'=>..,'y'=>..,'cap'=>bool], ..].
 *                      Координаты — в системе viewBox 0 0 1000 620.
 * @var array $route    Опциональный маршрут: список индексов в $points, по которым
 *                      рисуется пунктирная линия. Пусто — линии Ташкент→остальные.
 * @var string $caption Подпись карточки (напр. «Присутствие · Узбекистан»).
 */

// Дефолтный набор локаций (крупные города / точки присутствия).
$points = $points ?? [
    ['name' => 'Нукус',     'x' => 180, 'y' => 278, 'cap' => false],
    ['name' => 'Бухара',    'x' => 400, 'y' => 300, 'cap' => false],
    ['name' => 'Самарканд', 'x' => 548, 'y' => 300, 'cap' => false],
    ['name' => 'Ташкент',   'x' => 700, 'y' => 252, 'cap' => true],
    ['name' => 'Андижан',   'x' => 828, 'y' => 272, 'cap' => false],
    ['name' => 'Термез',    'x' => 606, 'y' => 452, 'cap' => false],
];
$caption = $caption ?? 'Присутствие · Узбекистан';

// Маршрут по умолчанию: связная линия по городам (национальное покрытие).
$route = $route ?? [0, 1, 2, 3, 4];
$routePath = '';
foreach ($route as $i => $idx) {
    if (!isset($points[$idx])) { continue; }
    $routePath .= ($i === 0 ? 'M ' : ' L ') . $points[$idx]['x'] . ' ' . $points[$idx]['y'];
}
// Ответвление к южной точке (Термез), если она есть в наборе.
$branchPath = '';
if (isset($points[2], $points[5])) {
    $branchPath = 'M ' . $points[2]['x'] . ' ' . $points[2]['y'] . ' L ' . $points[5]['x'] . ' ' . $points[5]['y'];
}
?>
<figure class="uz-map" role="group" aria-label="Карта присутствия: <?= htmlspecialchars($caption, ENT_QUOTES) ?>">
    <figcaption class="uz-map__caption"><span class="uz-map__live"></span><?= htmlspecialchars($caption, ENT_QUOTES) ?></figcaption>
    <div class="uz-map__canvas">
        <svg class="uz-map__svg" viewBox="0 0 1000 620" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
            <defs>
                <pattern id="uzDots" width="26" height="26" patternUnits="userSpaceOnUse">
                    <circle cx="1.4" cy="1.4" r="1.4" fill="rgba(255,255,255,.10)"/>
                </pattern>
            </defs>
            <rect x="0" y="0" width="1000" height="620" fill="url(#uzDots)"/>
            <path class="uz-map__land" d="M 48 262 C 44 218, 82 192, 126 194 C 178 150, 262 130, 344 142 C 428 150, 498 150, 566 158 C 628 144, 690 150, 740 174 C 790 178, 814 196, 812 222 C 848 214, 892 232, 908 262 C 930 268, 938 296, 918 314 C 902 330, 872 330, 852 320 C 838 342, 806 350, 782 338 C 774 360, 742 368, 720 356 C 706 386, 672 402, 648 392 C 648 424, 636 468, 618 486 C 606 500, 588 494, 586 470 C 584 440, 596 410, 604 392 C 578 402, 548 396, 536 372 C 500 384, 452 380, 430 356 C 388 366, 336 356, 318 330 C 274 342, 212 340, 182 320 C 148 336, 100 332, 82 306 C 56 306, 44 288, 48 262 Z"/>
            <?php if ($routePath !== ''): ?><path class="uz-map__route" d="<?= $routePath ?>" fill="none" pathLength="1"/><?php endif; ?>
            <?php if ($branchPath !== ''): ?><path class="uz-map__route uz-map__route--branch" d="<?= $branchPath ?>" fill="none" pathLength="1"/><?php endif; ?>
            <?php foreach ($points as $p): ?>
                <?php $isCap = !empty($p['cap']); ?>
                <g class="uz-map__pin<?= $isCap ? ' uz-map__pin--cap' : '' ?>" style="--px:<?= (int) $p['x'] ?>px;--py:<?= (int) $p['y'] ?>px">
                    <?php if ($isCap): ?><circle class="uz-map__pin-ring" cx="<?= (int) $p['x'] ?>" cy="<?= (int) $p['y'] ?>" r="15"/><?php endif; ?>
                    <circle class="uz-map__pin-dot" cx="<?= (int) $p['x'] ?>" cy="<?= (int) $p['y'] ?>" r="<?= $isCap ? 7 : 5 ?>"/>
                </g>
            <?php endforeach; ?>
            <?php foreach ($points as $p): ?>
                <?php $isCap = !empty($p['cap']); $anchor = $p['x'] > 640 ? 'start' : ($p['x'] < 260 ? 'start' : 'middle'); $lx = $p['x'] + ($p['x'] > 640 ? 18 : ($p['x'] < 260 ? -70 : 0)); $ly = $p['y'] + ($isCap ? -22 : 26); ?>
                <text class="uz-map__label<?= $isCap ? ' uz-map__label--cap' : '' ?>" x="<?= (int) $lx ?>" y="<?= (int) $ly ?>" text-anchor="<?= $anchor ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></text>
            <?php endforeach; ?>
        </svg>
    </div>
    <ul class="uz-map__legend">
        <?php foreach ($points as $p): ?>
            <li<?= !empty($p['cap']) ? ' class="is-cap"' : '' ?>><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></li>
        <?php endforeach; ?>
    </ul>
</figure>
