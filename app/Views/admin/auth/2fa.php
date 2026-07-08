<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var string|null $notice */
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Код подтверждения — вход в панель</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1>Код из Telegram</h1>
    <p class="auth-hint">Мы отправили 6-значный код на ваш телефон в Telegram —
       сообщение придёт от официального канала
       <strong>Verification&nbsp;Codes</strong> (t.me/VerificationCodes).
       Код действует 5&nbsp;минут.</p>
    <?php if (!empty($notice)): ?>
        <div class="alert alert--success"><?= htmlspecialchars($notice, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/login/2fa">
        <?= Csrf::field() ?>
        <label for="code">Код подтверждения</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus>
        <button type="submit">Подтвердить</button>
    </form>
    <form method="post" action="/admin/login/2fa/resend" style="margin-top:12px;text-align:center;">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--small" style="width:auto;">Отправить код повторно</button>
    </form>
</div>
</body>
</html>
