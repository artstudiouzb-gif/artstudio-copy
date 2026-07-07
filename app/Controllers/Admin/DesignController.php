<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\DesignSettings;
use App\Core\Flash;
use App\Core\View;
use App\Models\Setting;

/**
 * Управление дизайном сайта: готовые конфигурации + точная настройка
 * (визуальные опции карточками, как в тема-билдере). Только супер-админ.
 */
final class DesignController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/design/index', [
            'options' => DesignSettings::OPTIONS,
            'presets' => DesignSettings::PRESETS,
            'values' => DesignSettings::current(),
            'activePreset' => Setting::get('design_preset', ''),
        ]);
    }

    public function update(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        DesignSettings::save($_POST);
        // Ручная правка снимает метку пресета (значения могли разойтись).
        Setting::set('design_preset', '');
        Cache::forgetPrefix('page:');
        Flash::success('Настройки дизайна сохранены.');
        header('Location: /admin/design');
        exit;
    }

    public function applyPreset(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $preset = (string) ($_POST['preset'] ?? '');
        if (DesignSettings::applyPreset($preset)) {
            Cache::forgetPrefix('page:');
            Flash::success('Конфигурация «' . (DesignSettings::PRESETS[$preset]['label'] ?? $preset) . '» применена.');
        } else {
            Flash::error('Неизвестная конфигурация.');
        }
        header('Location: /admin/design');
        exit;
    }
}
