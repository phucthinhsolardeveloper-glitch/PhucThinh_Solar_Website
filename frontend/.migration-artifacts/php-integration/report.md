# PHP public API integration report

- Status: **PENDING_COORDINATOR_RERUN**
- Base URL: `http://127.0.0.1:8088`
- Required execution: PHP 8.1 isolated fixture container
- Local WSL execution: unavailable because PHP is not installed and the sandbox cannot open the local HTTP socket
- HTTP/slice counters: pending the coordinator rerun
- DB snapshot: pending the coordinator rerun; the runner performs read-only snapshots and marks unavailable queries as SKIP

The runner now reads the existing container web-root fixture config without copying or reporting its contents. It uses the real solutions envelope, a valid nonexistent slug (`khong-ton-tai-abcxyz`), preserves session cookies for negative CSRF requests, and executes pure slice scripts through a temporary path-compatible tree. Valid mutation writes and stateful form scripts remain excluded.

Coordinator evidence already confirms HTTP 419 for invalid CSRF requests and HTTP 404 for the valid nonexistent detail slug. Run the committed runner in `pts-php81-codex-api`, then replace this checkpoint with the generated PASS/FAIL report artifacts.

Production, database, deployment, cleanup, and route-cutover state are unchanged. Rollback point: `4fe2979` / current worktree before follow-up.
