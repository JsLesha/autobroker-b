# ERD: Identity

```mermaid
erDiagram
  roles ||--o{ users : has
  roles ||--o{ role_permission : grants
  permissions ||--o{ role_permission : mapped
  users ||--o{ audit_logs : acts
  users ||--o{ user_documents : owns
  users ||--o{ offices : staffs
  users ||--o{ dealers : is
  users ||--o{ api_tokens : holds
  users }o--o| departments : belongs

  roles {
    bigint id PK
    string code UK
    string title
  }

  permissions {
    bigint id PK
    string code UK
    string title
    string group_name
  }

  users {
    bigint id PK
    bigint role_id FK
    string email UK
    boolean active
    string public_offer_status
    datetime deleted_at
  }

  audit_logs {
    bigint id PK
    bigint actor_id FK
    string action
    string entity_type
    bigint entity_id
    json meta
  }

  legacy_id_map {
    bigint id PK
    string entity
    bigint old_id
    bigint new_id
  }
```

Роли: `admin`, `management`, `master`, `finance`, `logist`, `office`, `dealer`, `sub_user`, `support`, `buyer`, `seller`, `looking`, `lead_generation`.

Права — коды `*.read|create|update|delete` по доменам + меню. `admin` и `management` проходят все Policies.
