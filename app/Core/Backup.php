<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use ZipArchive;

/**
 * Создание резервной копии: дамп БД (портируемый, через PDO — без внешнего
 * mysqldump, чтобы работать на дешёвых хостингах) + папки загрузок, всё в
 * единый .zip. Архив кладётся в storage/backups (вне вебрута, доступ закрыт).
 */
final class Backup
{
    public static function backupDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/backups';
    }

    /**
     * Создаёт архив и возвращает абсолютный путь к нему.
     *
     * @param bool $maintenance Включать режим обслуживания на время снятия дампа
     *                          для согласованности БД и файлов (задача 1.2).
     *                          Гарантирует, что между дампом БД и архивацией
     *                          файлов не будет параллельных загрузок → нет
     *                          «висящих» ссылок при восстановлении.
     */
    public static function create(bool $maintenance = false): string
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException('Расширение PHP zip не установлено.');
        }

        $dir = self::backupDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Не удалось создать каталог для бэкапов.');
        }

        $timestamp = date('Y-m-d_His');
        $zipPath = $dir . '/backup_' . $timestamp . '.zip';

        // Согласованность: включаем режим обслуживания на время снятия дампа.
        // В finally гарантированно восстанавливаем прежнее состояние, даже если
        // архивация упадёт с исключением.
        $prevMaintenance = null;
        if ($maintenance) {
            $prevMaintenance = \App\Models\Setting::get('maintenance_mode', '0');
            \App\Models\Setting::set('maintenance_mode', '1');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Не удалось создать zip-архив.');
            }

            // 1. Дамп базы данных.
            $zip->addFromString('database.sql', self::dumpDatabase());

            // 2. Папки загрузок.
            self::addDirectory($zip, Config::get('paths.public_uploads'), 'uploads/public');
            self::addDirectory($zip, Config::get('paths.protected_uploads'), 'uploads/protected');

            // 3. Манифест.
            $zip->addFromString('manifest.txt', sprintf(
                "ArtStudio CMS backup\nDate: %s\nApp URL: %s\n",
                date('c'),
                (string) Config::get('app.url', '')
            ));

            $zip->close();
        } finally {
            if ($maintenance && $prevMaintenance !== null) {
                \App\Models\Setting::set('maintenance_mode', $prevMaintenance);
            }
        }

        // Контрольная сумма архива рядом с ним (формат, совместимый с sha256sum -c).
        $hash = (string) hash_file('sha256', $zipPath);
        file_put_contents(self::checksumPath($zipPath), $hash . '  ' . basename($zipPath) . "\n");

        Logger::info('Резервная копия создана', [
            'file' => basename($zipPath),
            'size' => filesize($zipPath),
            'sha256' => $hash,
        ]);

        return $zipPath;
    }

    /** Путь к sidecar-файлу с контрольной суммой архива. */
    public static function checksumPath(string $zipPath): string
    {
        return $zipPath . '.sha256';
    }

    /** Читает сохранённую контрольную сумму архива (или null, если её нет). */
    public static function storedChecksum(string $zipPath): ?string
    {
        $file = self::checksumPath($zipPath);
        if (!is_file($file)) {
            return null;
        }
        $line = trim((string) file_get_contents($file));
        $hash = strtok($line, ' ');

        return $hash !== false && preg_match('/^[0-9a-f]{64}$/', $hash) ? $hash : null;
    }

    /**
     * Проверяет целостность архива по сохранённой рядом контрольной сумме.
     * Возвращает true только если .sha256 существует и совпадает с файлом.
     */
    public static function verify(string $zipPath): bool
    {
        $stored = self::storedChecksum($zipPath);
        if ($stored === null || !is_file($zipPath)) {
            return false;
        }

        return hash_equals($stored, (string) hash_file('sha256', $zipPath));
    }

    /**
     * Удаляет локальные копии старше $days дней (вместе с их .sha256).
     * Возвращает число удалённых архивов.
     */
    public static function rotate(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }
        $dir = self::backupDir();
        if (!is_dir($dir)) {
            return 0;
        }
        $cutoff = time() - $days * 86400;
        $removed = 0;
        foreach (glob($dir . '/backup_*.zip') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                @unlink(self::checksumPath($file));
                $removed++;
            }
        }
        if ($removed > 0) {
            Logger::info('Ротация бэкапов: удалены старые копии', ['removed' => $removed, 'older_than_days' => $days]);
        }

        return $removed;
    }

    /**
     * Умная ротация «дневные + недельные»: последние $keepDaily дней хранятся
     * все копии; старше — по одной (самой свежей) на ISO-неделю в пределах
     * $keepWeekly недель; всё, что старше этого окна, удаляется.
     * Возвращает число удалённых архивов.
     */
    public static function rotateSmart(int $keepDaily = 7, int $keepWeekly = 4): int
    {
        $dir = self::backupDir();
        if (!is_dir($dir) || $keepDaily <= 0) {
            return 0;
        }

        $files = [];
        foreach (glob($dir . '/backup_*.zip') ?: [] as $file) {
            $files[$file] = (int) filemtime($file);
        }

        $delete = self::selectForDeletion($files, time(), $keepDaily, $keepWeekly);
        foreach ($delete as $file) {
            @unlink($file);
            @unlink(self::checksumPath($file));
        }
        if ($delete !== []) {
            Logger::info('Ротация бэкапов (дневные+недельные): удалены старые копии', [
                'removed' => count($delete),
                'keep_daily' => $keepDaily,
                'keep_weekly' => $keepWeekly,
            ]);
        }

        return count($delete);
    }

    /**
     * Чистая логика выбора архивов на удаление (тестируемо без ФС).
     *
     * @param array<string, int> $files путь => mtime
     * @return array<int, string> пути на удаление
     */
    public static function selectForDeletion(array $files, int $now, int $keepDaily, int $keepWeekly): array
    {
        $dailyCutoff = $now - $keepDaily * 86400;
        $weeklyCutoff = $dailyCutoff - $keepWeekly * 7 * 86400;

        $delete = [];
        $bestPerWeek = []; // ISO-неделя => [путь, mtime самой свежей копии]
        foreach ($files as $path => $mtime) {
            if ($mtime >= $dailyCutoff) {
                continue; // свежие копии храним все
            }
            if ($mtime < $weeklyCutoff) {
                $delete[] = $path;
                continue;
            }
            $week = date('o-W', $mtime);
            if (!isset($bestPerWeek[$week])) {
                $bestPerWeek[$week] = [$path, $mtime];
            } elseif ($mtime > $bestPerWeek[$week][1]) {
                $delete[] = $bestPerWeek[$week][0];
                $bestPerWeek[$week] = [$path, $mtime];
            } else {
                $delete[] = $path;
            }
        }

        return $delete;
    }

    /**
     * Дублирует архив (и его .sha256) во внешний каталог, если он задан в
     * config('backup.external_dir'). Возвращает путь копии или null.
     */
    public static function copyExternal(string $zipPath): ?string
    {
        $extDir = (string) Config::get('backup.external_dir', '');
        if ($extDir === '') {
            return null;
        }
        if (!is_dir($extDir) && !@mkdir($extDir, 0750, true) && !is_dir($extDir)) {
            throw new \RuntimeException('Внешний каталог бэкапов недоступен: ' . $extDir);
        }
        $dest = rtrim($extDir, '/') . '/' . basename($zipPath);
        if (!@copy($zipPath, $dest)) {
            throw new \RuntimeException('Не удалось скопировать бэкап во внешнее хранилище.');
        }
        @copy(self::checksumPath($zipPath), self::checksumPath($dest));

        // Сверяем копию — внешнее хранилище могло усечь файл при записи.
        if (!self::verify($dest)) {
            throw new \RuntimeException('Контрольная сумма внешней копии не совпала.');
        }

        return $dest;
    }

    /**
     * Восстанавливает архив в ОТДЕЛЬНУЮ (тестовую) БД и каталог — не в боевые.
     * Проверяет контрольную сумму, разворачивает дамп и файлы, возвращает отчёт.
     *
     * @param array{host?:string,port?:string,database:string,username:string,password?:string} $db
     * @return array{ok:bool,checksum:bool,tables:int,files:int,messages:string[]}
     */
    public static function restore(string $zipPath, array $db, string $filesTargetDir): array
    {
        $report = ['ok' => false, 'checksum' => false, 'tables' => 0, 'files' => 0, 'messages' => []];

        if (!is_file($zipPath)) {
            throw new \RuntimeException('Архив не найден: ' . $zipPath);
        }
        // 1. Контрольная сумма (если sidecar есть — обязана совпасть).
        if (self::storedChecksum($zipPath) !== null) {
            $report['checksum'] = self::verify($zipPath);
            if (!$report['checksum']) {
                throw new \RuntimeException('Контрольная сумма архива не совпала — восстановление прервано.');
            }
            $report['messages'][] = 'Контрольная сумма подтверждена.';
        } else {
            $report['messages'][] = 'ВНИМАНИЕ: рядом нет .sha256 — целостность не проверена.';
        }

        // 2. Распаковка во временный каталог.
        $tmp = sys_get_temp_dir() . '/artstudio_restore_' . bin2hex(random_bytes(6));
        if (!mkdir($tmp, 0700, true)) {
            throw new \RuntimeException('Не удалось создать временный каталог распаковки.');
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Не удалось открыть архив.');
        }
        $zip->extractTo($tmp);
        $zip->close();

        // 3. Разворачиваем дамп БД в ЦЕЛЕВУЮ (тестовую) базу.
        $sqlFile = $tmp . '/database.sql';
        if (!is_file($sqlFile)) {
            throw new \RuntimeException('В архиве нет database.sql.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'] ?? '127.0.0.1',
            $db['port'] ?? '3306',
            $db['database']
        );
        $pdo = new \PDO($dsn, $db['username'], $db['password'] ?? '', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec((string) file_get_contents($sqlFile));
        $report['tables'] = (int) $pdo->query('SHOW TABLES')->rowCount();

        // 4. Разворачиваем файлы в ЦЕЛЕВОЙ каталог.
        if (!is_dir($filesTargetDir) && !mkdir($filesTargetDir, 0750, true) && !is_dir($filesTargetDir)) {
            throw new \RuntimeException('Целевой каталог файлов недоступен.');
        }
        foreach (['uploads/public', 'uploads/protected'] as $sub) {
            $src = $tmp . '/' . $sub;
            if (is_dir($src)) {
                $report['files'] += self::copyTree($src, $filesTargetDir . '/' . $sub);
            }
        }

        self::removeTree($tmp);
        $report['ok'] = true;
        $report['messages'][] = sprintf('Восстановлено таблиц: %d, файлов: %d.', $report['tables'], $report['files']);

        return $report;
    }

    /** Рекурсивно копирует дерево, возвращает число скопированных файлов. */
    private static function copyTree(string $src, string $dst): int
    {
        if (!is_dir($dst) && !mkdir($dst, 0750, true) && !is_dir($dst)) {
            throw new \RuntimeException('Не удалось создать каталог: ' . $dst);
        }
        $count = 0;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            /** @var \SplFileInfo $item */
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $target = $dst . '/' . $rel;
            if ($item->isDir()) {
                @mkdir($target, 0750, true);
            } else {
                @copy($item->getPathname(), $target);
                $count++;
            }
        }

        return $count;
    }

    private static function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            /** @var \SplFileInfo $item */
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    /**
     * Портируемый SQL-дамп всех таблиц через PDO.
     */
    public static function dumpDatabase(): string
    {
        $pdo = Database::pdo();
        $output = "-- ArtStudio CMS database dump\n-- " . date('c') . "\n\n";
        $output .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? '';

            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createSql . ";\n\n";

            $rows = $pdo->query('SELECT * FROM `' . $table . '`');
            $rowData = $rows->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rowData as $row) {
                $columns = array_map(static fn ($c) => '`' . $c . '`', array_keys($row));
                $values = array_map(static function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string) $value);
                }, array_values($row));

                $output .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
                    . implode(', ', $values) . ");\n";
            }

            if (!empty($rowData)) {
                $output .= "\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $output;
    }

    private static function addDirectory(ZipArchive $zip, ?string $path, string $zipPrefix): void
    {
        if ($path === null) {
            return;
        }
        $real = realpath($path);
        if ($real === false || !is_dir($real)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $zip->addEmptyDir($zipPrefix);

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $relative = substr($item->getPathname(), strlen($real) + 1);
            $relative = str_replace('\\', '/', $relative);
            // Не включаем .gitkeep-заглушки.
            if ($item->getFilename() === '.gitkeep') {
                continue;
            }
            $zipEntry = $zipPrefix . '/' . $relative;
            if ($item->isDir()) {
                $zip->addEmptyDir($zipEntry);
            } else {
                $zip->addFile($item->getPathname(), $zipEntry);
            }
        }
    }
}
