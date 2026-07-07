<?php

declare(strict_types=1);

use App\Core\DesignSettings;

test('DesignSettings::sanitize отбрасывает неизвестные значения к дефолту', function () {
    assert_same('wide', DesignSettings::sanitize('container', 'wide'));
    assert_same('standard', DesignSettings::sanitize('container', 'bogus')); // default
    assert_true(DesignSettings::sanitize('nope', 'x') === null);
});

test('DesignSettings::cssVariables формирует корректные переменные', function () {
    $css = DesignSettings::cssVariables([
        'container' => 'wide', 'radius' => 'large', 'card_gap' => 'lg', 'density' => 'spacious',
        'button' => 'pill', 'catalog_layout' => 'cards_lg', 'header_style' => 'accent', 'header_sticky' => 'on',
    ]);
    assert_contains('--container-max:1360px', $css);
    assert_contains('--radius:22px', $css);
    assert_contains('--card-gap:32px', $css);
    assert_contains('--btn-radius:999px', $css);
});

test('DesignSettings::bodyClasses отражает макет каталога, шапку и фиксацию', function () {
    $on = DesignSettings::bodyClasses([
        'container' => 'standard', 'radius' => 'small', 'card_gap' => 'sm', 'density' => 'standard',
        'button' => 'rounded', 'catalog_layout' => 'list', 'header_style' => 'dark', 'header_sticky' => 'on',
    ]);
    assert_contains('design-catalog-list', $on);
    assert_contains('design-header-dark', $on);
    assert_contains('design-header-sticky', $on);

    $off = DesignSettings::bodyClasses([
        'container' => 'standard', 'radius' => 'small', 'card_gap' => 'sm', 'density' => 'standard',
        'button' => 'rounded', 'catalog_layout' => 'cards_sm', 'header_style' => 'light', 'header_sticky' => 'off',
    ]);
    assert_not_contains('design-header-sticky', $off);
    assert_contains('design-catalog-cards_sm', $off);
});

test('DesignSettings пресеты покрывают все опции валидными значениями', function () {
    foreach (DesignSettings::PRESETS as $name => $preset) {
        foreach (DesignSettings::OPTIONS as $key => $opt) {
            assert_true(isset($preset['values'][$key]), "пресет {$name} задаёт опцию {$key}");
            assert_true(
                isset($opt['choices'][$preset['values'][$key]]),
                "пресет {$name}: значение {$key} допустимо"
            );
        }
    }
});
