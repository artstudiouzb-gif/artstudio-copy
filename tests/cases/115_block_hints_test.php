<?php

declare(strict_types=1);

use App\Core\BlockHints;

test('Подсказки блока: кнопка без ссылки не молчит', function () {
    $hints = BlockHints::forBlock('banner', ['title' => 'Баннер', 'button_text' => 'Подробнее', 'button_url' => '']);
    assert_same(1, count($hints));
    assert_contains('не указана ссылка', $hints[0]);

    // Полностью заполненная кнопка вопросов не вызывает.
    assert_same([], BlockHints::forBlock('banner', ['button_text' => 'Подробнее', 'button_url' => '/news']));
    // Пустая пара — редактор просто не пользуется кнопкой.
    assert_same([], BlockHints::forBlock('banner', ['button_text' => '', 'button_url' => '']));
    // Ссылка без подписи — тоже нормально: у блоков есть подписи по умолчанию.
    assert_same([], BlockHints::forBlock('banner', ['button_text' => '', 'button_url' => '/news']));
});

test('Подсказки блока: автоисточник прячет ручные карточки', function () {
    $hints = BlockHints::forBlock('image_cards', [
        'source' => 'projects',
        'items' => [['title' => 'Раз'], ['title' => 'Два']],
    ]);
    assert_same(1, count($hints));
    assert_contains('(2)', $hints[0], 'сколько элементов потеряется');
    assert_contains('Вручную', $hints[0], 'сказано, как починить');

    // Ручной источник — ничего не теряется.
    assert_same([], BlockHints::forBlock('image_cards', ['source' => 'manual', 'items' => [['title' => 'Раз']]]));
    // Автоисточник без ручных элементов — обычный сценарий.
    assert_same([], BlockHints::forBlock('image_cards', ['source' => 'projects', 'items' => []]));
});

test('Подсказки блока: слайд без фото и форма без формы', function () {
    $slider = BlockHints::forBlock('slider', ['slides' => [
        ['image' => '/uploads/public/a.jpg'],
        ['image' => '', 'caption' => 'Подпись без фото'],
    ]]);
    assert_same(1, count($slider));
    assert_contains('без изображения: 1', $slider[0]);

    $form = BlockHints::forBlock('form', ['form_id' => null]);
    assert_contains('Форма не выбрана', $form[0]);
    assert_same([], BlockHints::forBlock('form', ['form_id' => 3]));
});

test('Подсказки блока: пустой блок определяется по итоговому рендеру', function () {
    assert_true(BlockHints::rendersEmpty(['id' => 1, 'type' => 'faq', 'data' => '{}', 'custom_css' => '']));
    assert_false(BlockHints::rendersEmpty([
        'id' => 2, 'type' => 'text', 'custom_css' => '',
        'data' => json_encode(['title' => 'Заголовок', 'content' => '<p>Текст</p>']),
    ]));
    // Блок вне окна показа пустым не считается: он заполнен, просто скрыт.
    assert_false(BlockHints::rendersEmpty([
        'id' => 3, 'type' => 'text', 'custom_css' => '',
        'data' => json_encode(['content' => '<p>Текст</p>', '_visible_to' => '2000-01-01 00:00']),
    ]));
});

test('Сохранение блока показывает предупреждения редактору', function () {
    $src = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/Admin/BlockController.php');
    assert_contains('BlockHints::forBlock', $src);
    assert_contains('BlockHints::rendersEmpty', $src);
});
