# Development workflow

1. Read `AGENTS.md` and domain docs.
2. Reproduce current behavior and record evidence.
3. Define scope, data impact, acceptance checks and rollback.
4. Work on local/staging with Git; no direct live edit.
5. Use small code changes and explicit SQL migration.
6. Run syntax/smoke/security checks proportional to risk.
7. Review diff for secret/PII and unrelated changes.
8. Update docs/changelog when behavior changes.
9. Backup, deploy controlled diff, clear only relevant cache, smoke test and monitor.

For Theme V2, keep old templates as rollback until page group passes parity, responsive, SEO and conversion verification.
