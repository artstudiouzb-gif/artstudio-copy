<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;

$config = require __DIR__ . '/../../config/config.php';

date_default_timezone_set($config['app']['timezone']);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

// Централизованный перехват ошибок и исключений (логирование + 500-заглушка).
ErrorHandler::register((bool) $config['app']['debug']);

Config::set($config);
Database::init($config['db']);

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name($config['session']['name']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'],
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!empty($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $config['session']['lifetime']) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}
