<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Демо-наполнение сайта DOUBLE A SOLUTIONS: тема (изумруд/золото), четыре
 * страницы (главная, о компании, услуги, контакты) из блоков-конструктора и
 * навигационное меню. Логика — общая с консольным сидером
 * database/seed_double_a.php; кнопка «Демо-контент» в админке вызывает её же.
 */
final class DemoSeeder
{
    /** @return array<string,int> счётчики созданного по разделам */
    public static function run(PDO $pdo): array
    {
        require_once \dirname(__DIR__, 2) . '/database/seed_double_a.php';

        return \seed_double_a_content($pdo);
    }
}
