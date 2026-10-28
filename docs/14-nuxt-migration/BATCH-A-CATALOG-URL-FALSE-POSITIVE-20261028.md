# Batch A follow-up: catalog URL "lỗi" là false positive do container cũ

Khi verify route public Catalog (Batch A, commit `069d8a2`), QA gặp lỗi
contract validator reject `products[].url` vì có dấu `/` cuối, trong khi
`frontend/shared/contracts/public-catalog.ts` yêu cầu
`value.url === \`/san-pham/${value.slug}\`` (không `/` cuối).

## Kết luận: không phải bug code, là container test lệch code

- `libraries/pts_public_catalog_api.php:38` (`ptsCatalogProductUrl`) đã trả
  đúng `/san-pham/.$slug` (không `/` cuối) từ commit `5890e06` ("fix: PDP
  real pricing, fix product-url trailing-slash SEO bug") — TRƯỚC Batch A.
- Container gây lỗi là `pts-php81-local` (dùng chung, build từ snapshot cũ,
  không mount code trực tiếp từ worktree) — đúng y cảnh báo gốc về
  "container local rebuild/lệch code" đã nêu từ đầu phiên làm việc này.
- Verify lại bằng container dựng tạm, mount trực tiếp code từ
  `/home/admin01/src/pts-nuxt-integration-20261027` (không qua snapshot):
  `curl http://127.0.0.1:8085/api/pts_v1_catalog.php?per_page=3` trả
  `"url":"/san-pham/den-ufo-nang-luong-mat-troi-oriana-ufo-1000-1000w"` —
  đúng, không có `/` cuối. Brand detail (`pts_v1_brand_detail.php?slug=hober`)
  và PDP (`pts_v1_product_detail.php?slug=...`) cũng verify PASS bằng cùng
  cách, dữ liệu thật đúng contract.

## Bài học cho các lượt QA sau

**Không dùng `pts-php81-local` để verify code trên nhánh Nuxt.** Luôn dựng
container tạm mount trực tiếp
`/home/admin01/src/pts-nuxt-integration-20261027/domains/phucthinhsolar.com/public_html`
(read-only), tmpfs đủ cả `caches/`, `assets/caches/`, `logs/`, `thumbs/`
(xem lý do 2 path cache ở commit `0e978b0`), và nếu thư mục đó chưa tồn tại
trên disk (bị gitignore), `mkdir -p` trước khi `docker compose up`. Config
PHP gitignored (`libraries/config.php`) cần quyền đọc cho user trong
container (`chmod a+r` tạm khi test, trả lại quyền cũ sau khi xong).

Không cần sửa gì trong code Batch A — giữ nguyên.
