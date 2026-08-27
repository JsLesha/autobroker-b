# Security checklist (волна 12)

- [x] Нет `client_secret` на клиенте
- [x] Login throttle 5/min
- [x] API throttle 120/min
- [x] Policies + `auth:sanctum` + `active`
- [x] Impersonation только admin-like, пишется в `audit_logs`
- [x] Credentials.secret encrypted
- [x] Webhooks VIN/Telegram с секретом заголовка
- [x] Файлы лотов на диск `local` (не public/)
- [x] Cookie access_token HttpOnly
- [x] Telescope не подключён в prod-скелете
- [ ] Certificate pinning Flutter на проде
- [ ] WAF / fail2ban на периметре
- [ ] Регулярный backup PostgreSQL + MinIO
