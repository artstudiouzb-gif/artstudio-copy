<?php

declare(strict_types=1);

test('Редактор проекта объединяет все языки во вкладки и включает визуальное описание', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/projects/form.php');

    assert_contains('$activeLanguages = Language::active()', $form, 'форма использует все активные языки');
    assert_contains('$code === $defaultCode', $form, 'основной язык выделяется во вкладках');
    assert_contains('lang-tab-btn__badge', $form, 'основной язык имеет понятную метку');
    assert_same(1, substr_count($form, '<div data-lang-tabs>'), 'основной язык и переводы находятся в одной группе вкладок');

    assert_contains('name="description" data-wysiwyg', $form, 'основное описание использует текстовый редактор');
    assert_contains('name="translations[<?= $code ?>][description]" data-wysiwyg', $form, 'переводное описание использует текстовый редактор');
    assert_same(2, substr_count($form, 'data-wysiwyg'), 'редактор подключён к обоим шаблонам описания');

    assert_same(1, substr_count($form, 'name="title"'), 'основное название не дублируется');
    assert_same(1, substr_count($form, 'name="description"'), 'основное описание не дублируется');
    assert_contains('name="translations[<?= $code ?>][title]"', $form, 'имена переводных полей сохранены');
});
