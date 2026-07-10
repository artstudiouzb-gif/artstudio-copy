<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var array $data */
$step = '2';
require __DIR__ . '/_header.php';
?>
<p class="auth-hint">Укажите параметры подключения к MySQL. Если база уже создана в панели хостинга — установщик подключится к ней и зальёт структуру таблиц; если базы нет и у пользователя есть права — создаст её сам. На shared-хостинге базу и пользователя создайте заранее в панели и назначьте пользователя на базу со всеми привилегиями.</p>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<form method="post" action="/install/step2" class="form-grid">
    <?= Csrf::field() ?>
    <div class="form-field">
        <label for="db_host">Хост</label>
        <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($data['host'] ?? 'localhost', ENT_QUOTES) ?>" required>
    </div>
    <div class="form-field">
        <label for="db_port">Порт</label>
        <input type="text" id="db_port" name="db_port" value="<?= htmlspecialchars($data['port'] ?? '3306', ENT_QUOTES) ?>" required>
    </div>
    <div class="form-field">
        <label for="db_name">Имя базы данных</label>
        <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($data['database'] ?? 'artstudio_cms', ENT_QUOTES) ?>" required>
    </div>
    <div class="form-field">
        <label for="db_user">Пользователь</label>
        <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($data['username'] ?? '', ENT_QUOTES) ?>" required>
    </div>
    <div class="form-field">
        <label for="db_pass">Пароль</label>
        <input type="password" id="db_pass" name="db_pass" value="">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Проверить и продолжить →</button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
