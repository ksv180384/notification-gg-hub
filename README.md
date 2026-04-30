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

Открыть в браузере: `http://localhost:8080` (порт берётся из `.env`: `HTTP_PORT`)

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

## Прод: SSL (Let's Encrypt) + редиректы (www->без www, http->https)

В прод-режиме Nginx:

- принимает `notification-gg-hub.ru` и `www.notification-gg-hub.ru`
- делает редирект `www` → `notification-gg-hub.ru`
- делает редирект `http` → `https`
- автоматически продлевает сертификат через `certbot renew`

### Требования

- DNS записи `A/AAAA` для `notification-gg-hub.ru` и `www.notification-gg-hub.ru` указывают на ваш сервер
- Открыты порты **80** и **443** на сервере

### Первичное получение сертификата

1) Поднимите Nginx в bootstrap-режиме (только HTTP на 80 + ACME challenge):

```bash
docker compose up -d --build app db
NGINX_CONF=prod-http.conf docker compose up -d nginx
```

2) Получите сертификат (webroot):

```bash
docker compose run --rm \
  -e CERTBOT_EMAIL="you@example.com" \
  certbot certonly --webroot -w /var/www/certbot \
  --agree-tos --no-eff-email \
  -m "$CERTBOT_EMAIL" \
  -d notification-gg-hub.ru -d www.notification-gg-hub.ru
```

3) Переключите Nginx на prod-конфиг (HTTPS + редиректы) и запустите авто-renew:

```bash
NGINX_CONF=prod.conf docker compose up -d nginx certbot
```

Дальше `certbot` будет продлевать сертификат, а `nginx` будет периодически делать reload, чтобы подхватывать обновления.

## Webhook -> Telegram

Эндпоинт (принимает запрос со стороннего сервера и пересылает сообщение в Telegram):

- `POST http://localhost:8080/api/notifications`

Требуемые переменные в `src/.env`:

- `TELEGRAM_LOGGER_TOKEN` — токен Telegram-бота (исходящий вызов в Telegram Bot API)
- `TELEGRAM_LOGGER_CHAT_ID` — ID чата, куда уходит сообщение
- `NOTIFICATION_HUB_INGRESS_TOKEN` — секрет для защиты входящего запроса
- `NOTIFICATION_HUB_ALLOWED_IPS` — опционально, allowlist IP/CIDR

Пример (curl):

```bash
curl -X POST "http://localhost:8080/api/notifications" \
  -H "X-Notification-Hub-Token: <your-ingress-token>" \
  -H "Content-Type: application/json" \
  -d '{"message":"hello from webhook"}'
```

Пример (PowerShell):

```powershell
Invoke-RestMethod -Method Post -Uri "http://localhost:8080/api/notifications" `
  -Headers @{ "X-Notification-Hub-Token" = "<your-ingress-token>" } `
  -ContentType "application/json" `
  -Body '{"message":"hello from webhook"}'
```

## Параметры MySQL (из `docker-compose.yml`)

- host: `db`
- port: `3306`
- database: `app`
- user: `app`
- password: `secret`
