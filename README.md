# Notification Service

Сервис уведомлений с гарантией доставки, поддержкой нескольких каналов и генерацией отчётов.

## Быстрый старт

### Требования

- Docker + Docker Compose
- Make (опционально, для удобства)

### Запуск

```bash
# Собрать и запустить контейнеры
make up

# Или без Make
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php artisan migrate --force
```

Приложение доступно по адресу: `http://localhost:8080`

### Queue Worker

Для обработки очереди уведомлений запустите воркер:

```bash
make queue
# или
docker compose exec php php artisan queue:work redis --queue=default --tries=3
```

### Полезные команды

| Команда | Описание |
|---------|----------|
| `make up` | Собрать и запустить |
| `make down` | Остановить |
| `make shell` | Войти в PHP-контейнер |
| `make test` | Запустить тесты |
| `make phpstan` | Статический анализ |
| `make pint` | Форматирование кода |
| `make queue` | Запустить воркер очереди |

## API

### Уведомления

#### Создать уведомление

```bash
POST /api/notifications
Content-Type: application/json

{
    "user_id": 1,
    "message": "Ваш заказ отправлен",
    "channels": ["email", "telegram"]
}
```

#### Получить статус

```bash
GET /api/notifications/{id}
```

#### Список с фильтрацией

```bash
GET /api/notifications?status=sent&channel=email&date_from=2024-01-01&date_to=2024-12-31
```

### Отчёты

#### Запросить генерацию

```bash
POST /api/reports
Content-Type: application/json

{
    "user_id": 1,
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
}
```

#### Проверить статус

```bash
GET /api/reports/{id}
```

#### Скачать

```bash
GET /api/reports/{id}/download
```

## Архитектура

### Паттерн: Service Layer + ChannelManager (Manager Pattern)

```
┌─────────────────────┐
│   Controller        │  Тонкий, делегирует сервису
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

#### 2. ChannelManager extends Illuminate\Support\Manager

Laravel использует паттерн Manager для всех расширяемых систем (Cache, Mail, Queue, Filesystem). Это industry standard:

- **OCP**: новый канал добавляется через `extend()` без изменения кода Manager
- **Config-driven**: каналы регистрируются в конфиге
- **FQCN fallback**: если метод не найден, создаётся экземпляр по полному имени класса

```php
// Добавление нового канала — 2 шага:
// 1. Создать класс implements NotificationChannelInterface
// 2. ChannelManager::extend('sms', fn($app) => new SmsChannel())
```

#### 3. Queue для гарантии доставки

`SendNotificationJob` с retry-логикой:
- 3 попытки с экспоненциальным backoff (30, 60, 120 сек)
- При успехе → статус `sent`
- При неудаче → статус `failed` + текст ошибки

#### 4. Repository Pattern

Изоляция Eloquent от сервисного слоя:
- Легко мокировать в тестах
- Легко заменить ORM
- Единое место для всех запросов

#### 5. Event-Driven не используется

Каналы доставки явно указаны в API-запросе. Events полезны когда система сама решает куда слать (user preferences, rules). Здесь канал определён — events добавили бы сложность без пользы.

### Структура проекта

```
app/
├── Channels/           # Каналы доставки
│   ├── EmailChannel.php
│   ├── TelegramChannel.php
│   └── NotificationChannelInterface.php
├── Enums/              # Типизированные enum'ы
│   ├── NotificationChannel.php
│   └── NotificationStatus.php
├── Http/
│   ├── Controllers/
│   ├── Requests/       # FormRequest валидация
│   └── Resources/      # API трансформеры
├── Jobs/               # Queue jobs
│   ├── SendNotificationJob.php
│   └── GenerateReportJob.php
├── Models/             # Eloquent модели
├── Providers/          # Service providers
├── Repositories/       # Data access layer
└── Services/           # Business logic
    ├── ChannelManager.php
    ├── NotificationService.php
    └── ReportService.php

config/
└── notification_channels.php  # Registry каналов
```

## Что улучшить для продакшна

1. **Real delivery** — интеграция с SendGrid/Mailgun для email, Telegram Bot API
2. **Rate limiting** — `ThrottleRequests` middleware для защиты от спама
3. **Idempotency keys** — предотвращение дублей при повторных запросах
4. **Webhooks** — callback от провайдеров о реальном статусе доставки (opened, bounced)
5. **Notification templates** — система шаблонов с переменными
6. **User preferences** — пользователь выбирает каналы для типов уведомлений
7. **Monitoring** — Sentry для ошибок, Prometheus метрики для очереди
8. **API versioning** — `/api/v1/notifications` для обратной совместимости
9. **Batch notifications** — массовая рассылка с чанками
10. **Multi-tenant** — изоляция данных по организациям

## Тесты

```bash
make test
```

43 теста, 116 ассертов:
- Unit: сервисы, каналы, модель, scopes
- Feature: API endpoints, валидация, queue jobs, отчёты

## Статический анализ

```bash
make phpstan    # PHPStan level 5
make pint       # Laravel Pint
```
