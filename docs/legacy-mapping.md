# Legacy dump mapping

See root `docs/legacy-mapping.md`. Dump: `Dump20260828.sql` (247 tables, schema-only).

ETL also maps status dictionaries, vehicle dicts, `doc_fees`, `transportation_agents`, `locations`, `transport_notes` → `lot_notes`, chat messages.

`php artisan legacy:import --path=... --dry-run`
`php artisan legacy:import --path=... --sanitize`
