# Бэклог волн

Порядок жёсткий. DoD волны: миграции, сиды, feature-тесты, Policies, OpenAPI, экраны `f/` и `f-m/` (если API стабилен), Docker поднимается, нет секретов в git.

| Волна | Скоуп | Статус |
|---|---|---|
| 0 | Монорепо `f` `b` `f-m`, Docker, CI, агенты, ERD | in progress |
| 1 | Identity: auth Sanctum, RBAC, оферта, impersonation+audit | pending |
| 2 | Справочники + Redis cache | pending |
| 3 | Лоты, фото, чат, Meilisearch | pending |
| 4 | Shipping, контейнеры, локальная перевозка | pending |
| 5 | Finance lines, инвойсы, payments, preview | pending |
| 6 | Ledger, ЕРИП, checksums | pending |
| 7 | Rate cards, калькуляторы | pending |
| 8 | Контрагенты, encrypted credentials | pending |
| 9 | Prebid | pending |
| 10 | Интеграции (интерфейсы + stubs), Kafka profile | pending |
| 11 | ETL `legacy:import` | pending |
| 12 | Flutter-паритет, k6, security checklist | pending |

## Волна 1 — задачи агентов

- DB: расширить `users`, `roles`, `permissions`, audit, documents
- BE: login/logout/me/token/forgot/reset/impersonate, Policies
- React: логин, layout, меню по правам, профиль
- Flutter: login + shell
- QA: чужая роль не проходит API; неактивный юзер только профиль
