# PHP public API integration report

- Status: **PASS**
- Base URL: `http://127.0.0.1`
- HTTP checks: PASS 58 / FAIL 0 / SKIP 7
- Slice scripts: PASS 17 / FAIL 0 / SKIP 0
- DB snapshot unchanged: `true`
- Production state: unchanged; no production access, deploy, migration, or DB write.
- Rollback point: `43893ad / worktree before sanitizer-contract follow-up`

## Blockers / not testable

- brand_detail: no published local fixture entity
- product_detail: no published local fixture entity
- product_extras: no published local fixture entity
- solution_detail: no published local fixture entity
- project_detail: no published local fixture entity
- news_detail: no published local fixture entity
- policy_detail: no published local fixture entity

## Failures


Mutation scripts with valid writes were excluded: `test-public-form-submissions.php`, `test-public-lead-and-review.php`.
