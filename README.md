# Notification Service

REST API для управления уведомлениями с гарантией доставки,
поддержкой нескольких каналов и генерацией отчётов.

## Стек

- **Backend:** Laravel 12, PHP 8.4
- **Database:** MySQL 8
- **Cache/Queue:** Redis 7
- **Web Server:** Nginx 1.27 → PHP-FPM
- **Containerization:** Docker Compose
- **Static Analysis:** PHPStan (Larastan) level 5

## Требования

- [Docker](https://www.docker.com/) >= 24.0
- [Docker Compose](https://docs.docker.com/compose/) >= 2.20
- 2 GB свободной RAM

## Быстрый старт

### Развёртывание одной командой

```bash
# 1. Клонировать репозиторий
git clone https://github.com/thermonuclear/jilfond_ru_test.git
cd jilfond_ru_test

# 2. Запустить
# Windows
start.bat

# Linux/macOS
chmod +x start.sh && ./start.sh
```

После запуска:
- API: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

### Ручная установка

```bash
# 1. Клонировать репозиторий
git clone https://github.com/thermonuclear/jilfond_ru_test.git
cd jilfond_ru_test

# 2. Поднять контейнеры
docker compose up -d --build

# 3. Установить зависимости
docker compose exec php composer install

# 4. Сгенерировать ключ
docker compose exec php php artisan key:generate

# 5. Применить миграции
docker compose exec php php artisan migrate --force
```

## Основные команды

| Команда | Описание |
|---------|----------|
| `docker compose up -d --build` | Поднять контейнеры |
| `docker compose down` | Остановить контейнеры |
| `docker compose exec php bash` | Войти в PHP-контейнер |
| `docker compose exec php php artisan migrate` | Применить миграции |
| `docker compose exec php php artisan migrate:fresh --seed` | Пересоздать БД |
| `docker compose exec php php artisan db:seed` | Заполнить тестовыми данными |
| `docker compose exec php php artisan test` | Запустить тесты |
| `docker compose exec php php artisan pint` | Форматировать код |
| `docker compose exec php php artisan queue:work` | Запустить воркер очереди |

## API

### Уведомления

#### Создать уведомление

```
POST /api/notifications
Content-Type: application/json

{
    "user_id": 1,
    "message": "Ваш заказ отправлен",
    "channels": ["email", "telegram"]
}
```

**Ответ 201:**

```json
{
    "data": {
        "id": 1,
        "status": "pending",
        "date_from": "2024-01-01T00:00:00+00:00",
        "date_to": "2024-12-31T00:00:00+00:00"
    }
}
```

#### Получить статус

```
GET /api/notifications/{id}
```

**Ответ 200:**

```json
{
    "data": {
        "id": 1,
        "user_id": 1,
        "channel": "email",
        "message": "Ваш заказ отправлен",
        "status": "sent",
        "attempts": 1,
        "last_error": null,
        "sent_at": "2024-01-15T10:30:00+00:00",
        "created_at": "2024-01-15T10:29:00+00:00"
    }
}
```

#### Список с фильтрацией

```
GET /api/notifications?status=sent&channel=email&date_from=2024-01-01&date_to=2024-12-31
```

**Ответ 200:**

```json
{
    "data": [...],
    "links": {...},
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

### Отчёты

#### Запросить генерацию

```
POST /api/reports
Content-Type: application/json

{
    "user_id": 1,
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
}
```

**Ответ 201 (новый) или 200 (существующий):**

```json
{
    "data": {
        "id": 1,
        "status": "pending",
        "date_from": "2024-01-01T00:00:00+00:00",
        "date_to": "2024-12-31T00:00:00+00:00"
    }
}
```

#### Проверить статус

```
GET /api/reports/{id}
```

**Ответ 200 (ready):**

```json
{
    "data": {
        "id": 1,
        "status": "ready",
        "date_from": "2024-01-01T00:00:00+00:00",
        "date_to": "2024-12-31T00:00:00+00:00"
    }
}
```

**Ответ 200 (failed):**

```json
{
    "data": {
        "id": 1,
        "status": "failed",
        "date_from": "2024-01-01T00:00:00+00:00",
        "date_to": "2024-12-31T00:00:00+00:00",
        "error_message": "Database connection timeout"
    }
}
```

#### Скачать

```
GET /api/reports/{id}/download
```

Возвращает CSV-файл со статистикой по каналам и ошибкам.

## Переменные окружения

### Корневой `.env` (Docker)

| Переменная | По умолчанию | Описание |
|------------|-------------|----------|
| `APP_NAME` | `jilfond_ru_test` | Имя проекта (контейнеры) |
| `NGINX_PORT` | `8080` | Порт веб-сервера |
| `MYSQL_PORT` | `3306` | Порт MySQL |
| `PHPMYADMIN_PORT` | `8081` | Порт phpMyAdmin |
| `REDIS_PORT` | `6379` | Порт Redis |
| `DB_DATABASE` | `jilfond` | Имя БД |
| `DB_USERNAME` | `jilfond` | Пользователь БД |
| `DB_PASSWORD` | `secret` | Пароль БД |

### `app/.env` (Laravel)

| Переменная | По умолчанию | Описание |
|------------|-------------|----------|
| `QUEUE_CONNECTION` | `redis` | Драйвер очереди |
| `CACHE_STORE` | `redis` | Драйвер кеша |
| `REDIS_HOST` | `redis` | Хост Redis |

## Гарантированная доставка

### Как это работает

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Request  │────>│  Create  │────>│  Queue   │────>│  Send    │
│ POST     │     │ pending  │     │  (Redis) │     │  Job     │
│          │     │ in DB    │     │          │     │          │
└──────────┘     └──────────┘     └──────────┘     └────┬─────┘
                                                        │
                                              ┌─────────┴─────────┐
                                              │                   │
                                         success             failure
                                              │                   │
                                         sent status         retry: 30s→60s→120s
                                                                 │
                                                          max tries?
                                                         ┌───┴────┐
                                                         │        │
                                                       yes      no
                                                         │        │
                                                    failed     retry
                                                    status
```

### Обработка сбоев

| Сценарий | Механизм | Результат |
|----------|----------|-----------|
| Временная ошибка канала | retry 3x с backoff 30→60→120s | Успех на повторной попытке |
| Постоянная ошибка | `failed()` callback → `status=failed` | Запись в БД, доступна retry |
| Воркер упал | Redis `retry_after=120s` | Job возвращается в очередь |
| TTL истёк | `retryUntil(+12h)` | Job удаляется из очереди |
| Счётчик попыток | `increment('attempts')` в начале `handle()` | Корректный подсчёт |

### Повторная отправка failed уведомлений

```bash
php artisan notifications:retry-failed           # до 50 уведомлений
php artisan notifications:retry-failed --limit=100
```

## Отчёты: обработка сбоев

### Что происходит при падении генерации

| Сценарий | Механизм | Результат |
|----------|----------|-----------|
| Ошибка до записи файла | retry → failed() | Чисто, статус `failed` |
| Ошибка после записи файла | `failed()` удаляет orphan-файл | Файл удалён, статус `failed` |
| Повторный POST с теми же параметрами | Дедупликация по user_id + period | Возвращает существующий отчёт |
| Ручной retry failed отчётов | `reports:retry-failed` | Job переотправлен в очередь |

### Атомарность

Файл записывается на диск **до** обновления статуса. Если update статуса падает — `failed()` callback удаляет orphan-файл и сбрасывает `file_path` в `null`.

## Архитектура

### Слои приложения

```
┌─────────────────────┐
│   Controller        │  Тонкий HTTP-слой, делегирует сервису
└──────────┬──────────┘
           │
┌──────────▼──────────┐
│   Service           │  Бизнес-логика, оркестрация
└──────────┬──────────┘
           │
     ┌─────┴─────┐
     ▼           ▼
┌─────────┐ ┌─────────────┐
│Repository│ │ChannelManager│  extends Manager
└─────┬───┘ └──────┬──────┘
      │            │
      ▼      ┌─────┴─────┐
     DB     │  Channel  │  Email, Telegram, ...
            └───────────┘
```

### Ключевые решения

#### 1. Кастомная реализация вместо Laravel Notifications

Встроенная система Laravel Notifications не хранит статус доставки в queryable формате. Для ТЗ требуется API с фильтрацией по статусу и каналу, поэтому создана собственная таблица `notifications`.

**Альтернатива:** Laravel Notifications + events. Не использовано — events добавили бы сложность без пользы, т.к. каналы определены в запросе.

#### 2. ChannelManager extends Manager

Laravel использует паттерн Manager для всех расширяемых систем (Cache, Mail, Queue, Filesystem).

- **OCP**: новый канал через `extend()` без изменения кода Manager
- **Config-driven**: каналы регистрируются в конфиге
- **FQCN fallback**: если метод не найден — экземпляр по полному имени класса

#### 3. Repository Pattern

Изоляция Eloquent от сервисного слоя: моки в тестах, единое место для запросов.

**Альтернатива:** Eloquent scopes напрямую в Service. Не использовано — сложнее тестировать и менять ORM.

#### 4. Queue для гарантии доставки

`SendNotificationJob`: 3 попытки, exponential backoff, TTL 12h, корректный счётчик attempts.

#### 5. Event-Driven не используется

Каналы доставки явно указаны в API-запросе. Events полезны когда система сама решает куда слать (user preferences, rules).

## Тесты

```bash
docker compose exec php php artisan test
```

43 теста, 116 ассертов:
- **Unit:** сервисы, каналы, модель, scopes
- **Feature:** API endpoints, валидация, queue jobs, отчёты

## Что улучшить для продакшна

### Альтернативные архитектуры

**Более простая:** Controller → Model напрямую, без Service/Repository/Queue. Уведомления отправляются синхронно в контроллере. Подходит если нагрузка маленькая и retry не нужен.

**Более сложная:** CQRS + Event Sourcing. Каждое изменение состояния — событие в event store. Отдельные read/write модели. Message Broker (RabbitMQ) вместо Redis. Подходит при высокой нагрузке и строгих требованиях к аудиту.

### Что добавить

1. **Real delivery** — SendGrid/Mailgun для email, Telegram Bot API
2. **Laravel Horizon** — дашборд очереди, алерты при большом backlog
3. **Rate limiting** — защита API от спама
4. **Шаблоны уведомлений** — система шаблонов с переменными
5. **User preferences** — пользователь выбирает каналы
6. **API versioning** — `/api/v1/notifications`

## Устранение проблем

### Контейнеры не запускаются

```bash
# Пересобрать контейнеры
docker compose build --no-cache

# Очистить volumes и начать заново
docker compose down -v
docker compose up -d --build
```

### Ошибка подключения к БД

```bash
# Проверить что MySQL здоров
docker compose exec mysql mysqladmin ping -h localhost -u root -prootsecret

# Перезапустить MySQL
docker compose restart mysql
```

### Тесты падают

```bash
# Очистить кеш конфига
docker compose exec php php artisan config:clear

# Перезапустить тесты
docker compose exec php php artisan test
```

## Структура проекта

```
jilfond_ru_test/
├── app/                              # Laravel application
│   ├── app/
│   │   ├── Channels/                 # Каналы доставки
│   │   │   ├── EmailChannel.php
│   │   │   ├── TelegramChannel.php
│   │   │   └── NotificationChannelInterface.php
│   │   ├── Console/Commands/         # Artisan команды
│   │   │   ├── RetryFailedNotifications.php
│   │   │   └── RetryFailedReports.php
│   │   ├── Enums/                    # Типизированные enum'ы
│   │   │   ├── NotificationChannel.php
│   │   │   └── NotificationStatus.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── NotificationController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Requests/             # FormRequest валидация
│   │   │   └── Resources/            # API трансформеры
│   │   ├── Jobs/                     # Queue jobs
│   │   │   ├── SendNotificationJob.php
│   │   │   └── GenerateReportJob.php
│   │   ├── Models/                   # Eloquent модели
│   │   ├── Providers/                # Service providers
│   │   ├── Repositories/             # Data access layer
│   │   └── Services/                 # Business logic
│   │       ├── ChannelManager.php
│   │       ├── NotificationService.php
│   │       └── ReportService.php
│   ├── config/
│   │   └── notification_channels.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   └── console.php
│   └── tests/
│       ├── Feature/
│       │   ├── NotificationApiTest.php
│       │   └── ReportApiTest.php
│       └── Unit/
│           ├── NotificationServiceTest.php
│           └── ChannelManagerTest.php
├── docker/
│   ├── php/                          # PHP-FPM Dockerfile
│   └── nginx/                        # Nginx Dockerfile
├── mysql/init/                       # DB init scripts
├── docker-compose.yml
├── .env.example                      # Docker variables template
├── app/.env.example                  # Laravel variables template
├── start.bat / start.sh              # One-command setup
└── Makefile
```
