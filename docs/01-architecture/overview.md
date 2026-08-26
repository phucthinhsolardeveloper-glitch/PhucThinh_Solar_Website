# Architecture

Monolith PHP server-rendered và CMS dùng chung MariaDB/filesystem.

```mermaid
flowchart LR
 B[Browser]-->W[Rewrite]-->I[index.php]-->R[router.php]-->S[sources]
 S-->D[PDODb]-->M[(MariaDB)]
 S-->T[templates]-->B
 B-->A[api PHP]-->D
 C[administrator]-->D
 C-->U[upload]
 T-->U
```

Không có service/repository layer riêng; query/business logic nằm trong source/helper/admin source. Hướng nâng giao diện an toàn: giữ routing/source/data/admin, port UI vào template/assets mới theo từng route.

Ownership: `libraries/class/` là shared services; `sources/` storefront handlers; `templates/` views; `api/` AJAX; `administrator/` CMS; `upload/` persistent; `thumbs/`/`caches/` derived.
