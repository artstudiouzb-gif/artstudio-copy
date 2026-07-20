<?php

/** @var array|null $page */
/** @var array $languages */
/** @var bool $isEdit */
/** @var string $blockLang */
?>
<div class="form-card page-settings-sidebar">
    <h2>Настройки страницы</h2>

    <div class="form-grid">
        <div class="form-field">
            <label for="status">Статус</label>
            <select id="status" name="status">
                <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
            </select>
        </div>

        <div class="form-field">
            <label for="slug">ЧПУ (slug)</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '', ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
            <span class="form-hint">Общий для всех языков. Адрес: /&lt;slug&gt; и /<?= htmlspecialchars($languages[1]['code'] ?? 'uz', ENT_QUOTES) ?>/&lt;slug&gt;.</span>
        </div>

        <div class="form-field">
            <label for="layout_type">Макет страницы</label>
            <select id="layout_type" name="layout_type">
                <option value="no_sidebar" <?= ($page['layout_type'] ?? 'no_sidebar') === 'no_sidebar' ? 'selected' : '' ?>>Без сайдбара</option>
                <option value="left_sidebar" <?= ($page['layout_type'] ?? '') === 'left_sidebar' ? 'selected' : '' ?>>Левый сайдбар</option>
                <option value="right_sidebar" <?= ($page['layout_type'] ?? '') === 'right_sidebar' ? 'selected' : '' ?>>Правый сайдбар</option>
            </select>
            <span class="form-hint">Содержимое сайдбара настраивается в разделе «Виджеты».</span>
        </div>

        <div class="page-settings-sidebar__options">
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="is_home" name="is_home" value="1" <?= !empty($page['is_home']) ? 'checked' : '' ?>>
                <label for="is_home">Сделать главной страницей</label>
            </div>

            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="hide_chrome" name="hide_chrome" value="1" <?= !empty($page['hide_chrome']) ? 'checked' : '' ?>>
                <label for="hide_chrome">Скрыть шапку и футер</label>
            </div>

            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="transparent_header" name="transparent_header" value="1" <?= !empty($page['transparent_header']) ? 'checked' : '' ?>>
                <label for="transparent_header">Прозрачная шапка</label>
            </div>
            <p class="form-hint">Для прозрачной шапки нужен полноширинный hero первым блоком и включённый режим в конструкторе шапки.</p>
        </div>
    </div>

    <div class="form-actions form-actions--sticky">
        <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить</button>
        <a href="/admin/pages" class="btn">Отмена</a>
        <?php if ($isEdit): ?>
            <a href="/admin/pages/<?= (int) $page['id'] ?>/preview?block_lang=<?= urlencode($blockLang) ?>"
               class="btn" target="_blank" rel="noopener">Предпросмотр ↗</a>
        <?php endif; ?>
    </div>
</div>
