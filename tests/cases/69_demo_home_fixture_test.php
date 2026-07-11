<?php

declare(strict_types=1);

// Демо-главная: фикстура блоков и бандл-изображения должны быть на месте и
// валидны, иначе «Загрузить демо-контент» даст сломанную главную.

test('Демо: фикстура главной валидна и содержит ожидаемые блоки', function () {
    $path = APP_ROOT . '/database/demo_assets/home_blocks.json';
    assert_true(is_file($path), 'файл фикстуры существует');

    $blocks = json_decode((string) file_get_contents($path), true);
    assert_true(is_array($blocks) && count($blocks) >= 6, 'минимум 6 блоков главной');

    $types = array_map(static fn ($b) => $b['type'] ?? '', $blocks);
    foreach (['hero', 'counters', 'cards_grid', 'image_cards', 'news_feature', 'media_gallery'] as $need) {
        assert_true(in_array($need, $types, true), "есть блок $need");
    }
});

test('Демо: все изображения фикстуры бандлятся в demo_assets', function () {
    $dir = APP_ROOT . '/database/demo_assets';
    $blocks = json_decode((string) file_get_contents($dir . '/home_blocks.json'), true);

    $images = [];
    array_walk_recursive($blocks, static function ($v, $k) use (&$images) {
        if ($k === 'image' && is_string($v) && $v !== '') {
            $images[] = $v;
        }
    });
    assert_true($images !== [], 'в фикстуре есть изображения');

    foreach (array_unique($images) as $url) {
        $name = basename($url);
        assert_true(is_file($dir . '/' . $name), "изображение $name лежит в demo_assets");
    }
});
