<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\WebPush;
use App\Models\WebPushSubscription;

/**
 * Публичные эндпоинты webpush: публичный VAPID-ключ и регистрация/удаление
 * подписки браузера (JSON, вызывается из assets/js/push.js).
 */
final class PushController
{
    public function key(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!WebPush::isEnabled()) {
            http_response_code(404);
            echo '{"error":"disabled"}';
            exit;
        }
        echo json_encode(['key' => WebPush::publicKey()]);
        exit;
    }

    public function subscribe(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!WebPush::isEnabled()) {
            http_response_code(404);
            echo '{"error":"disabled"}';
            exit;
        }
        $data = json_decode((string) file_get_contents('php://input'), true);
        $endpoint = (string) ($data['endpoint'] ?? '');
        $p256dh = (string) ($data['keys']['p256dh'] ?? '');
        $auth = (string) ($data['keys']['auth'] ?? '');

        if (!WebPushSubscription::save($endpoint, $p256dh, $auth)) {
            http_response_code(422);
            echo '{"error":"invalid subscription"}';
            exit;
        }
        echo '{"ok":true}';
        exit;
    }

    public function unsubscribe(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $data = json_decode((string) file_get_contents('php://input'), true);
        $endpoint = (string) ($data['endpoint'] ?? '');
        if ($endpoint !== '') {
            WebPushSubscription::deleteByEndpoint($endpoint);
        }
        echo '{"ok":true}';
        exit;
    }
}
