# Отчёт PM — фундамент (волны 0–12, каркас)

Сделано в `D:\mySecret\autobroker`: монорепо `f` / `b` / `f-m`.

- Docker в `b/`: nginx, php-fpm, queue, scheduler, **PostgreSQL 16**, redis, minio, meilisearch, mailpit; Kafka — профиль.
- Laravel 13 (актуальный скелет 2026; план упоминал 12) + Sanctum без client_secret, Policies, RBAC, audit, encrypted credentials.
- Схема всех доменов + ETL `legacy:import` + checksum кошельков.
- React-кабинет: логин, меню по правам, лоты/карточка с табами, справочники, кошельки, prebid, калькуляторы, инвойс-preview.
- Flutter: login, shell всех разделов, карточка лота с табами, secure storage.
- QA: PHPUnit 11 passed, Vitest passed, frontend production build passed.
- OpenAPI, k6, security checklist, промпты агентов.

Долг следующих итераций: пиксельный паритет всех ~60 экранов, живые AEC/Copart, auction-агент, полный ETL с дампом прода, Horizon UI, Reverb package, certificate pinning.
