<?php

use App\Core\Csrf;

$pageTitle = 'Настройки дизайна';
$activeNav = 'settings';
require __DIR__ . '/../layout/header.php';

/** @var array $settings */
?>
<div class="form-card">
    <form method="post" action="/admin/settings" enctype="multipart/form-data" class="form-grid">
        <?= Csrf::field() ?>

        <div class="form-field">
            <label for="site_name">Название сайта</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="logo_file">Логотип (файл)</label>
            <input type="file" id="logo_file" name="logo_file" accept="image/*">
        </div>
        <div class="form-field">
            <label for="logo_url">...либо ссылка на логотип</label>
            <input type="text" id="logo_url" name="logo_url" value="<?= htmlspecialchars($settings['logo_url'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="color_primary">Основной цвет</label>
            <input type="text" id="color_primary" name="color_primary" value="<?= htmlspecialchars($settings['color_primary'] ?? '#1a1a1a', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="color_accent">Акцентный цвет</label>
            <input type="text" id="color_accent" name="color_accent" value="<?= htmlspecialchars($settings['color_accent'] ?? '#e63946', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="font_family">Шрифт (CSS font-family)</label>
            <input type="text" id="font_family" name="font_family" value="<?= htmlspecialchars($settings['font_family'] ?? "'Inter', sans-serif", ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="default_theme">Тема оформления</label>
            <select id="default_theme" name="default_theme">
                <?php $th = $settings['default_theme'] ?? 'light'; ?>
                <option value="light" <?= $th === 'light' ? 'selected' : '' ?>>Светлая</option>
                <option value="dark" <?= $th === 'dark' ? 'selected' : '' ?>>Тёмная</option>
                <option value="auto" <?= $th === 'auto' ? 'selected' : '' ?>>Авто (по системе)</option>
            </select>
            <span class="form-hint">Посетители могут переключать тему; выбор сохраняется в браузере.</span>
        </div>

        <div class="form-field">
            <label for="font_face_name">Локальный шрифт: имя семейства</label>
            <input type="text" id="font_face_name" name="font_face_name" value="<?= htmlspecialchars($settings['font_face_name'] ?? '', ENT_QUOTES) ?>" placeholder="напр. MyBrandFont">
            <span class="form-hint">Если задать имя и ссылку на .woff2, шрифт подключится через @font-face с preload (без мерцания). Не забудьте указать это имя в поле «Шрифт» выше.</span>
        </div>
        <div class="form-field">
            <label for="font_url">Локальный шрифт: ссылка на .woff2</label>
            <input type="text" id="font_url" name="font_url" value="<?= htmlspecialchars($settings['font_url'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/public/font.woff2">
        </div>

        <div class="form-field">
            <label for="contact_phone">Телефон</label>
            <input type="text" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="contact_email">Email</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="contact_address">Адрес</label>
            <input type="text" id="contact_address" name="contact_address" value="<?= htmlspecialchars($settings['contact_address'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="counter_codes">Коды счётчиков (Google Analytics, Яндекс.Метрика и т.п.)</label>
            <textarea id="counter_codes" name="counter_codes" style="min-height:140px; font-family: monospace;"><?= htmlspecialchars($settings['counter_codes'] ?? '', ENT_QUOTES) ?></textarea>
            <span class="form-hint">Вставляется в конец страницы как есть (доступно только администраторам).</span>
        </div>

        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
            <label for="maintenance_mode">Режим обслуживания (сайт закрыт для гостей, админам доступен)</label>
        </div>
        <div class="form-field">
            <label for="maintenance_message">Сообщение на странице обслуживания</label>
            <input type="text" id="maintenance_message" name="maintenance_message" value="<?= htmlspecialchars($settings['maintenance_message'] ?? '', ENT_QUOTES) ?>" placeholder="Сайт временно закрыт на техническое обслуживание.">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Сохранить настройки</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Резервное копирование</h2>
    <p class="form-hint">Скачать полный бэкап (дамп базы данных + загруженные файлы) одним архивом.</p>
    <form method="post" action="/admin/backup">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">Скачать бэкап (.zip)</button>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
