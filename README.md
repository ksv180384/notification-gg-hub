# notification-gg-hub (Laravel + Docker)

Проект разворачивается в Docker и включает:

- `nginx:latest` (порт **8080**)
- `php-fpm` (PHP **8.3** + Composer)
- `mysql:latest`

Laravel находится в папке `./src`.

## Запуск

```bash
docker compose up -d --build
```

Открыть в браузере: `http://localhost:8080`

## Остановка

```bash
docker compose down
```

Полная очистка (включая данные MySQL):

```bash
docker compose down -v
```

## Laravel команды

```bash
docker compose exec app php artisan -V
docker compose exec app php artisan migrate
```

## Webhook -> Telegram

Эндпоинт (принимает запрос со стороннего сервера):

- `POST http://localhost:8080/api/telegram-log`

Требуемые переменные в `src/.env`:

- `TELEGRAM_LOGGER_TOKEN`
- `TELEGRAM_LOGGER_CHAT_ID`
- `TELEGRAM_LOGGER_INGRESS_TOKEN` (секрет для защиты входящего запроса)
- `TELEGRAM_LOGGER_ALLOWED_IPS` (опционально, allowlist IP/CIDR)

Пример (curl):

```bash
curl -X POST "http://localhost:8080/api/telegram-log" \
  -H "X-Telegram-Logger-Token: <your-ingress-token>" \
  -H "Content-Type: application/json" \
  -d '{"message":"hello from webhook"}'
```

Пример (PowerShell):

```powershell
Invoke-RestMethod -Method Post -Uri "http://localhost:8080/api/telegram-log" `
  -Headers @{ "X-Telegram-Logger-Token" = "<your-ingress-token>" } `
  -ContentType "application/json" `
  -Body '{"message":"hello from webhook"}'
```

## Параметры MySQL (из `docker-compose.yml`)

- host: `db`
- port: `3306`
- database: `app`
- user: `app`
- password: `secret`
