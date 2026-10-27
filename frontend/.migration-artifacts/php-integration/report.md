# PHP public API integration report

- Status: **BLOCKED**
- HTTP checks: PASS 0 / FAIL 0 / SKIP 0 (container was not reachable)
- Slice scripts: not run (WSL has no PHP binary)
- Compose parse: PASS (`docker compose config`)
- Container: not started. WSL Docker socket is unavailable; Docker Desktop CLI returned `UtilBindVsockAnyPort: socket failed 1`.
- Local config: unavailable. `libraries/config.php` is gitignored and absent; no credential or production config was copied.

The aggregate runner is ready at `scripts/test-pts-public-api-integration.php`. It inventories the 22 read-only `pts_v1_*` endpoints plus `cart_mutate`, `lead_form`, `review`, and `shipping_quote`; mutation checks stop at invalid body/CSRF branches and snapshot read-only DB tables before/after.

No PHP contract bug was fixed because no PHP 8.1 fixture request completed. Production, DB, deployment, and route cutover state are unchanged. Rollback point: `07a50ea` / current worktree before this task.
