<?php

declare(strict_types=1);

test('WP CLI использует канонический ключ db', function () {
    $source = (string) file_get_contents(APP_ROOT . '/scripts/wp_import.php');
    assert_contains("Config::get('db')", $source);
    assert_not_contains("Config::get('database')", $source);
});

test('Файловый портал останавливает скачивание при rate limit', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Controllers/Repo/PortalController.php');
    assert_contains("if (!RateLimiter::throttle('repo_download'", $source);
    assert_contains("http_response_code(429)", $source);
});

test('RepoFile сверяет реальный MIME с ожидаемым', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Models/RepoFile.php');
    assert_contains('$expectedMime = self::ALLOWED[$extension]', $source);
    assert_contains('Содержимое файла не соответствует расширению.', $source);
});

test('Чанки изолированы по пользователю и проверяются по порядку', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/ChunkedUploadController.php');
    assert_contains("'/storage/cache/chunks/user-' . (int) Auth::id()", $source);
    assert_contains('flock($lock, LOCK_EX)', $source);
    assert_contains("Нарушен порядок или состав чанков", $source);
});

test('Web Push mutations требуют JSON, CSRF и rate limit', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/PushController.php');
    assert_contains("application/json", $source);
    assert_contains('Csrf::verify($token)', $source);
    assert_contains("RateLimiter::throttle('push_subscription'", $source);
});
