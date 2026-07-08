<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\PasswordPolicy;
use App\Core\View;
use App\Models\SessionRegistry;
use App\Models\User;

/**
 * Профиль администратора: смена пароля, управление активными сессиями,
 * телефон для кода входа через Telegram (Verification Codes).
 */
final class ProfileController
{
    public function index(): void
    {
        Auth::requireLogin();

        $userId = (int) Auth::id();
        $currentHash = SessionRegistry::hash(session_id());

        View::render('admin/profile/index', [
            'sessions' => SessionRegistry::forUser($userId),
            'currentHash' => $currentHash,
            'profileUser' => User::findById($userId),
            'error' => null,
        ]);
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $userId = (int) Auth::id();
        $user = User::findById($userId);

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $error = null;
        if (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Текущий пароль указан неверно.';
        } elseif ($new !== $confirm) {
            $error = 'Новый пароль и подтверждение не совпадают.';
        } else {
            $errors = PasswordPolicy::validate($new, [(string) $user['username'], (string) $user['email']]);
            if ($errors !== []) {
                $error = implode(' ', $errors);
            }
        }

        if ($error !== null) {
            \App\Core\Logger::security('Отклонён слабый/некорректный пароль при смене', [
                'user' => (string) ($user['username'] ?? ''),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            Flash::error($error);
            header('Location: /admin/profile');
            exit;
        }

        User::updatePassword($userId, $new);
        // Завершаем все прочие сессии; текущую оставляем активной.
        SessionRegistry::revokeAllExcept($userId, session_id());

        \App\Core\Logger::security('Пароль администратора изменён', [
            'user' => (string) ($user['username'] ?? ''),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        Flash::success('Пароль изменён. Другие сессии завершены.');
        header('Location: /admin/profile');
        exit;
    }

    public function revokeSession(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        SessionRegistry::revoke((int) Auth::id(), (int) $params['id']);
        Flash::success('Сессия отозвана.');
        header('Location: /admin/profile');
        exit;
    }

    public function revokeOthers(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        SessionRegistry::revokeAllExcept((int) Auth::id(), session_id());
        \App\Core\Logger::security('Отзыв всех прочих сессий администратора', [
            'user' => (string) ($_SESSION['username'] ?? ''),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        Flash::success('Все другие сессии завершены.');
        header('Location: /admin/profile');
        exit;
    }

    /**
     * Телефон для кода входа через Telegram (E.164). Изменение подтверждается
     * текущим паролем — иначе угнанная сессия могла бы перевесить 2FA на себя.
     */
    public function updatePhone(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $userId = (int) Auth::id();
        $user = User::findById($userId);

        if (!$user || !password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            Flash::error('Неверный пароль. Телефон не изменён.');
            header('Location: /admin/profile');
            exit;
        }

        $raw = trim((string) ($_POST['phone'] ?? ''));
        if ($raw === '') {
            User::updatePhone($userId, null);
            Flash::success('Телефон удалён — вход будет выполняться без кода подтверждения.');
        } else {
            $phone = \App\Core\TelegramGateway::normalizePhone($raw);
            if ($phone === null) {
                Flash::error('Некорректный номер. Укажите телефон в международном формате, например +998901234567.');
                header('Location: /admin/profile');
                exit;
            }
            User::updatePhone($userId, $phone);
            Flash::success('Телефон сохранён. Коды входа будут приходить в Telegram (Verification Codes).');
        }

        header('Location: /admin/profile');
        exit;
    }
}
