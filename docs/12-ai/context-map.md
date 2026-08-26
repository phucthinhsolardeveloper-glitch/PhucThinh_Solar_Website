# AI context map

```text
All tasks -> AGENTS.md -> fact-map/status
Frontend -> architecture + request-flow + feature inventory -> templates/assets
Backend -> architecture + API/data -> sources/api/libraries
Database -> database + data-safety -> SQL/query implementation
Auth -> auth-rbac + security-model -> admin/source Functions
Deployment -> deployment + backup-restore
Unknown legacy behavior -> audit -> implementation -> runtime evidence
```

Chỉ nạp context tối thiểu cần thiết. Không đọc/hiển thị secret values trong `config.php`, SQL INSERT, backup config hoặc mail data.
