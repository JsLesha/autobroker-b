# Backend (`b/`)

Laravel 13, PHP 8.3, **PostgreSQL 16**, Sanctum, Redis-очереди, Docker.

```bash
cp .env.example .env
# APP_KEY: docker run --rm -v ${PWD}:/app -w /app composer:2 php artisan key:generate
docker compose up --build
docker compose exec app php artisan migrate --seed
```

Postgres с хоста: `localhost:54332` (user/password `autobroker` / `secret`).

Тесты: `docker run --rm -v ${PWD}:/app -w /app composer:2 php artisan test`

ETL (MySQL-прод → PostgreSQL): `php artisan legacy:import --path=/dumps/prod.sql --sanitize --dry-run`

Kafka: `docker compose --profile kafka up`

Связанные репозитории: [фронт](https://github.com/JsLesha/autobroker-f), [мобильное](https://github.com/JsLesha/autobroker-f-m).
