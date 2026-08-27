# Backend (`b/`)

Laravel 13, PHP 8.3, Sanctum, Redis-очереди, Docker.

```bash
cp .env.example .env
# APP_KEY уже можно сгенерировать: docker run --rm -v ${PWD}:/app -w /app composer:2 php artisan key:generate
docker compose up --build
docker compose exec app php artisan migrate --seed
```

Тесты: `docker run --rm -v ${PWD}:/app -w /app composer:2 php artisan test`

ETL: `php artisan legacy:import --path=/dumps/prod.sql --sanitize --dry-run`

Kafka: `docker compose --profile kafka up`

Связанные репозитории: [фронт](https://github.com/JsLesha/autobroker-f), [мобильное](https://github.com/JsLesha/autobroker-f-m).
