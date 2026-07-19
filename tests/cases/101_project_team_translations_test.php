<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Project;
use App\Models\ProjectTranslation;
use App\Models\TeamMember;
use App\Models\TeamMemberTranslation;

test('ProjectTranslation: upsert, find, localize, duplicate', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    // Создаём тестовый проект
    $slug = 'test-proj-i18n-' . bin2hex(random_bytes(3));
    $pid = Project::create([
        'title' => 'Проект RU',
        'slug' => $slug,
        'description' => 'Описание RU',
        'cover_image' => '',
        'status' => 'published',
        'sort_order' => 10,
    ]);

    // По умолчанию перевода нет
    $translations = ProjectTranslation::forProject($pid);
    assert_true(empty($translations));

    // Добавляем перевод на узбекский
    ProjectTranslation::upsert($pid, 'uz', [
        'title' => 'Loyiha UZ',
        'description' => 'Tavsif UZ',
    ]);

    // Проверяем получение перевода
    $translations = ProjectTranslation::forProject($pid);
    assert_true(isset($translations['uz']));
    assert_same('Loyiha UZ', $translations['uz']['title']);
    assert_same('Tavsif UZ', $translations['uz']['description']);

    // Проверяем локализацию при выводе
    $projRu = Project::findPublishedBySlug($slug, 'ru');
    assert_same('Проект RU', $projRu['title']);

    $projUz = Project::findPublishedBySlug($slug, 'uz');
    assert_same('Loyiha UZ', $projUz['title']);
    assert_same('Tavsif UZ', $projUz['description']);

    // Проверяем дублирование вместе с переводом
    $dupId = Project::duplicate($pid);
    assert_true($dupId !== null);

    $dupTranslations = ProjectTranslation::forProject($dupId);
    assert_true(isset($dupTranslations['uz']));
    assert_same('Loyiha UZ', $dupTranslations['uz']['title']);

    // Очистка
    Project::forceDelete($pid);
    Project::forceDelete($dupId);
});

test('TeamMemberTranslation: upsert, find, localize', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    // Создаём сотрудника
    $mid = TeamMember::create([
        'name' => 'Иван RU',
        'position' => 'Директор RU',
        'photo' => '',
        'email' => 'test@test.ru',
        'phone' => '123',
        'socials' => [],
        'status' => 'published',
        'sort_order' => 5,
    ]);

    // Добавляем перевод
    TeamMemberTranslation::upsert($mid, 'uz', [
        'name' => 'Ivan UZ',
        'position' => 'Direktor UZ',
    ]);

    // Получение перевода
    $translations = TeamMemberTranslation::forMember($mid);
    assert_true(isset($translations['uz']));
    assert_same('Ivan UZ', $translations['uz']['name']);
    assert_same('Direktor UZ', $translations['uz']['position']);

    // Проверяем localized published list
    $membersRu = TeamMember::published('ru');
    $memberRu = array_values(array_filter($membersRu, static fn (array $m) => (int) $m['id'] === $mid))[0] ?? null;
    assert_true($memberRu !== null);
    assert_same('Иван RU', $memberRu['name']);

    $membersUz = TeamMember::published('uz');
    $memberUz = array_values(array_filter($membersUz, static fn (array $m) => (int) $m['id'] === $mid))[0] ?? null;
    assert_true($memberUz !== null);
    assert_same('Ivan UZ', $memberUz['name']);
    assert_same('Direktor UZ', $memberUz['position']);

    // Очистка
    TeamMember::delete($mid);
});
