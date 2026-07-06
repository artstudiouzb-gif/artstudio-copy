# Деплой и чек-лист первого релиза

ArtStudio CMS — чистый PHP 8.2+ / MySQL(MariaDB), без Composer. Ниже —
минимальные шаги для боевого запуска.

## 1. Требования окружения

- **PHP 8.2+** с расширениями: `pdo_mysql`, `zip`, `gd`, `curl`, `mbstring`,
  `dom`, `openssl`.
- **MySQL 5.7+/MariaDB 10.3+**.
- Веб-сервер Apache (с `mod_rewrite`, `mod_headers`) или nginx.

## 2. Document root

- **Рекомендуется:** указать document root на каталог `public/`.
- **Fallback** (дешёвый shared-хостинг без смены docroot): корневой
  `.htaccess` уже переписывает трафик в `public/` и запрещает прямой доступ к
  `.php`. Требуется `AllowOverride All` (иначе `.htaccess` игнорируется).

Для nginx — см. секцию «Nginx» в `README.md`.

## 3. Установка

1. Загрузите файлы на сервер (`config/config.php` НЕ копируйте — его создаст
   установщик; убедитесь, что каталоги `storage/` и `public/uploads/` доступны
   на запись).
2. Откройте сайт в браузере — произойдёт редирект на `/install`.
3. Пройдите 4 шага: проверка окружения → БД → сайт → супер-администратор.
   Установщик создаст базу, импортирует `database/schema.sql`, запишет
   `config/config.php` и `storage/installed.lock`.
4. Первый вход в `/admin` форсит настройку 2FA (TOTP) — обязательно.
5. Создайте главную страницу (галка «Сделать главной страницей сайта»).

## 4. HTTPS

Разверните за HTTPS — тогда автоматически включатся HSTS и `Secure`-cookies
(проверка `SecurityHeaders::isHttps()`).

## 5. Обязательная ручная проверка безопасности на проде

Встроенный PHP-сервер не читает `.htaccess`, поэтому это проверяется только на
боевом Apache/nginx. Все URL ниже должны отдавать **403 или 404**:

- `https://домен/config/config.php`
- `https://домен/database/create_admin.php`
- `https://домен/database/schema.sql`
- `https://домен/storage/logs/`

Если хоть один отдаёт содержимое — включите `AllowOverride All` (Apache) или
примените nginx-конфиг из README. Подробности — в `SECURITY.md`.

## 6. Cron (фоновые воркеры)

Без cron не работают очереди писем/вебхуков/соцсетей, бэкапы и heartbeat.
Пример crontab:

```
* * * * *  php /path/to/app/Console/mail_worker.php     >> /path/to/storage/logs/mail_worker.log 2>&1
* * * * *  php /path/to/app/Console/webhook_worker.php  >> /path/to/storage/logs/webhook_worker.log 2>&1
*/5 * * * * php /path/to/app/Console/social_worker.php   >> /path/to/storage/logs/social_worker.log 2>&1
0 3 * * *  php /path/to/app/Console/backup_worker.php    >> /path/to/storage/logs/backup_worker.log 2>&1
30 3 * * * php /path/to/app/Console/gdpr_cleanup.php     >> /path/to/storage/logs/gdpr_cleanup.log 2>&1
```

`/health` возвращает `degraded` и шлёт алерт, если воркер перестал запускаться.

## 7. Рекомендуется к первому релизу (опционально)

- **SMTP** (`SMTP_*` в окружении/config) — сброс пароля и уведомления.
- **Telegram** (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`) — алерты об ошибках.
- **`BACKUP_EXTERNAL_DIR`** — копия бэкапа вне сервера (иначе локальная копия
  бесполезна при полном отказе). Один раз прогоните восстановление:
  `php database/restore.php <архив> <тестовая_БД> <каталог>`.
- Настройки: аналитика (по ID), cookie-consent, favicon/PWA, срок хранения ПДн.

## 8. Обновление существующей установки

```
php database/migrate.php status   # какие миграции новые
php database/migrate.php           # накатить новые миграции
```

Новые установки получают полную схему из `schema.sql`; существующие — через
миграции в `database/migrations/`.
