<?php
$step = '4';
require __DIR__ . '/_header.php';
?>
<div style="text-align:center;">
    <div style="font-size:52px;">✓</div>
    <h2 style="font-size:20px;">Установка завершена!</h2>
    <p class="auth-hint">
        ArtStudio CMS готова к работе. При первом входе вам будет предложено
        подключить двухфакторную аутентификацию (2FA).
    </p>
    <p style="margin-top:20px;">
        <a href="/admin/login" class="btn btn--primary">Войти в панель управления →</a>
    </p>
    <p class="form-hint" style="margin-top:16px;">
        В целях безопасности удалите каталог установщика доступа не требуется —
        установщик уже заблокирован файлом <code>storage/installed.lock</code>.
    </p>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
