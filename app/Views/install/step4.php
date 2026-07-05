<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var array $data */
$step = '4';
require __DIR__ . '/_header.php';
?>
<p class="auth-hint">Создайте учётную запись главного администратора. При первом входе система обязательно предложит подключить двухфакторную аутентификацию (2FA).</p>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<form method="post" action="/install/step4" class="form-grid">
    <?= Csrf::field() ?>
    <div class="form-field">
        <label for="username">Логин</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($data['username'] ?? '', ENT_QUOTES) ?>" required autocomplete="username">
    </div>
    <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES) ?>" required>
    </div>
    <div class="form-field">
        <label for="password">Пароль (минимум 10 символов)</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Завершить установку</button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
