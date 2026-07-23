<?php

declare(strict_types=1);

// Демо DOUBLE A: кнопка «Демо-контент» разворачивает фирменный сайт (тема,
// четыре страницы, меню) через общий сидер database/seed_double_a.php.

test('Демо: DemoSeeder делегирует в сидер DOUBLE A', function () {
    $seeder = (string) file_get_contents(APP_ROOT . '/app/Core/DemoSeeder.php');
    // Демо не должно вайпать пользовательские таблицы из самого класса —
    // вся логика вынесена в отдельный сидер.
    assert_not_contains('DELETE FROM page_translations', $seeder);
    assert_contains('seed_double_a_content', $seeder);
    assert_contains('seed_double_a.php', $seeder);
});

test('Демо: сидер DOUBLE A задаёт тему, страницы и меню', function () {
    $src = (string) file_get_contents(APP_ROOT . '/database/seed_double_a.php');
    assert_contains('function seed_double_a_content(PDO $pdo): array', $src);
    assert_contains("'design_site_template' => 'double_a'", $src);
    foreach (['home', 'o-nas', 'services', 'kontakty'] as $slug) {
        assert_contains("'{$slug}' =>", $src);
    }
    assert_contains('INSERT INTO menu_items', $src);
    // Блоки главной и внутренних страниц собираются конструктором.
    assert_contains("Block::create(", $src);
});

test('Демо: типы блоков сидера зарегистрированы в конструкторе', function () {
    // Страницы DOUBLE A собраны из html-блоков — тип должен быть известен рендереру.
    assert_true(\App\Core\BlockRenderer::defaultsFor('html') !== [], 'тип html зарегистрирован');
});

test('Демо: запуск доступен только в настройках и требует код подтверждения', function () {
    $dashboard = (string) file_get_contents(APP_ROOT . '/app/Views/admin/dashboard.php');
    $settings = (string) file_get_contents(APP_ROOT . '/app/Views/admin/settings/index.php');
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/SettingsController.php');
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');

    assert_not_contains('/admin/demo-content', $dashboard);
    assert_contains('/admin/settings/demo-content', $settings);
    assert_contains('demo_confirm_code', $settings);
    assert_contains("DEMO_CONFIRM_CODE = 'DEMO'", $controller);
    assert_contains('/admin/settings/demo-content', $routes);
    assert_not_contains("[DashboardController::class, 'seedDemo']", $routes);
});
