# Nuxt production cutover dry-run

## Latest staging rehearsal — 2026-11-02

Staging hybrid is deployed separately from production. Production remains on PHP.
The staging Caddy service is behind the production TLS edge, so its internal
listener must use HTTP while `SITE_DOMAIN` remains a plain hostname for the PHP
container. The stack now passes `CADDY_SITE_SCHEME` to Caddy; set it to `http`
only on the staging stack and leave the standalone production default as
`https`. This avoids binding the staging proxy only to `:443` while the outer
edge proxies to its `:80` service port.

On 2026-11-02, the staging rollback script was executed with
`PTS_STACK_ROOT=/srv/phucthinhsolar-staging`. It selected the recorded previous
web/Nuxt images, set `PTS_NUXT_HYBRID_ENABLED=0`, and returned the homepage,
catalog, and read-only PHP catalog API as HTTP 200. Web, Nuxt and DB healthchecks
passed, and read-only product/news row counts were unchanged. The current image
and hybrid flag were restored afterward. A first rehearsal exposed the Caddy
listener mismatch; the staging-only scheme setting was corrected and the
rehearsal then passed. Production configuration and traffic were not changed.

Follow-up on 2026-11-02 deployed commit `4306ed90311a` to staging and repeated
rollback to `5e59384cf93a`, then restored `4306ed90311a` with hybrid enabled.
The staging DB container image and start time did not change. The outer edge
Caddy had returned transient Docker DNS errors when the inner staging proxy was
recreated, so a 5-second retry window and active `/robots.txt` health check were
loaded only on the outer route for `phucthinhsolar-staging-proxy-1:80`. The
pre-change runtime config is backed up at
`/srv/phucthinhsolar-staging/backups/edge-caddy-before-stage-retry-20261102.json`.
Twenty post-restore homepage probes returned HTTP 200. This edge change currently
exists in Admin API runtime state and is not yet represented in a declarative
config file; persist it in a separately reviewed staging-only edge change before
relying on it across an outer Caddy restart. Production route/config was not
changed.

A fixed outer-edge log window from 01:50 to 02:15 UTC contained 26 HTTP 502
responses and 8 HTTP 503 responses for staging, alongside 4,699 HTTP 200
responses. Matching Caddy error-log records are diagnostics and must not be
counted as additional HTTP responses. The 502 diagnostics identify intermittent
Docker DNS lookup failures for the staging proxy. Five 503 responses at
02:12:48–02:12:49 UTC coincided with the `9ed23b4d84cc` staging deployment;
the inner Caddy logged `no upstreams available` while app containers were being
replaced. The current deploy script builds both images first but activates them
in place and checks health afterward. The outer Caddy's `/robots.txt` probe can
stay healthy while the inner proxy has no ready page/API upstream, so the outer
retry and that health check do not close the gap. Current staging is healthy
again, but deploy availability remains open until a pre-activation/blue-green
gate validates page, `/_nuxt/*`, and API paths before traffic switches.

The global wheel interception was removed in `9ed23b4d84cc` and deployed to
staging. Follow-up release `d0451124d0ca` fixed the independent header behavior:
Chromium on staging at 390×844 confirmed it hides on downward wheel input and
reappears on the first upward wheel input. The same browser loaded all five
homepage catalog images from responsive thumbnail URLs with no image decode
failures; the catalog listing used 320/480/640 product candidates and the first
eight product images loaded successfully. The 960px street-lighting thumbnail
was 496,857 bytes versus 2,508,792 bytes for the original PNG. Source images
remain unchanged and PHP thumbnail responses use a 30-day immutable cache.

The release was packaged from commit `d0451124d0ca`, checksum verified on the
VPS, and deployed only with `PTS_STACK_ROOT=/srv/phucthinhsolar-staging`.
Nuxt build, PHP 8.1/WebP runtime, PHP-to-MariaDB connection, and web/Nuxt/DB
healthchecks passed. Fifteen representative public routes/APIs returned 200
after redirects, and 30 sequential homepage probes at roughly one-second
intervals all returned 200 during the replacement window. The database and
production stack were not changed. The deploy remains in-place; this single
successful probe window does not resolve the earlier 502/503 incidents or
provide a blue-green availability guarantee. Keep the deployment gate open.

`verify-stack.sh` itself could not start because it sources the staging `.env`
as shell even though `CADDY_GLOBAL_OPTIONS=auto_https off` is a valid Compose
value but not a shell assignment. Equivalent Compose, health, PHP runtime,
database, and HTTPS checks were run directly and passed. Fix the verifier's
environment parsing before relying on it for the next release.

The solution alias `/giai-phap/thiet-bi-den-nang-luong-mat-troi-quy-mo-gia-dinh/`
was also re-probed on 2026-11-02: production PHP and staging Nuxt both returned
404, while the proposed canonical destination returned 200 on both. This
contradicts the older 301 evidence recorded in the route fixture; keep the alias
gate open until the live PHP redirect source/data is checked again.

Staging’s rollback rehearsal does not authorize production cutover. Production
still requires its own backup/monitoring review, approved route allowlist and
owner approval tied to the exact release.

Checkpoint: 2026-10-27. Tài liệu và công cụ trong đợt này chỉ phục vụ local
preflight. Không file nào được nối vào production Compose/Caddy; không deploy,
SSH, DNS, restart hay đổi quyền sở hữu route.

## Kết luận audit và hook tương lai

Production hiện là `Caddy proxy -> web (Apache/PHP 8.1) -> MariaDB`. File
`deploy/vps/docker-compose.yml` không có Nuxt service/origin. Trong
`deploy/vps/Caddyfile`, khối `route` xử lý lần lượt redirect `www`, header/robots
staging, rồi toàn bộ request vào `reverse_proxy web:80`. Deploy đổi duy nhất
`PTS_WEB_IMAGE`; rollback release cũng chỉ đổi image PHP và recreate `web proxy`.

Vì chưa có lifecycle, healthcheck, image pin, network và rollback state cho Nuxt,
không tạo runtime switch production ở checkpoint này. Hook an toàn trong tương
lai là **bên trong khối `route`, sau `redir @www` và hai rule staging, ngay trước
`reverse_proxy web:80`**. Tại đó một matcher hẹp mới có thể chọn Nuxt; PHP vẫn là
fallback cuối. Hook chỉ được thêm khi:

1. Nuxt service production có image immutable, healthcheck và cùng network
   `edge`; không dùng địa chỉ public hay host network.
2. Matcher lấy từ allowlist đã review, chỉ `GET|HEAD` và exact path; không dùng
   wildcard public.
3. Flag `PTS_NUXT_HYBRID_ENABLED` được triển khai thật, mặc định `0`; trạng thái
   OFF sinh cấu hình không có matcher Nuxt và được test chứng minh request vẫn
   đến `web:80`.
4. Caddy được `validate` trước reload/restart; current/previous config và image
   được ghi làm rollback point.
5. Owner phê duyệt theo đúng commit và route. File
   `owner-approval.example.json` cố ý để `approved=false`, không phải approval.

`deploy/local-nuxt-hybrid/Caddyfile.off` là fixture cú pháp local, bind
`127.0.0.1:8090` và chỉ proxy về PHP local `127.0.0.1:8080`. Nó không được mount
trong Compose. Manifest `route-allowlist.json` giữ flag OFF và pilot ở trạng thái
`blocked`; công cụ sẽ từ chối sinh cutover plan dù có approval, cho tới khi cả
public route Nuxt và service production tồn tại. Đây là chủ ý fail-closed.

## Allowlist, denylist và ranh giới nghiệp vụ

Pilot được đề xuất là `GET|HEAD /gioi-thieu/`: trang nội dung ít rủi ro, không
session, không mutation. Public Nuxt route `/gioi-thieu/` đã có trong source
(`frontend/app/pages/gioi-thieu.vue`) và được bảo vệ bởi runtime feature flag;
preview `/migration-about-preview` vẫn là route nội bộ `noindex`. Route có trong
source không đồng nghĩa route đã được đưa vào production: production Nuxt
service/origin và Caddy matcher/allowlist chưa được triển khai, nên pilot vẫn
chưa được phép bật. Không rewrite public path sang preview.

Các prefix sau luôn do PHP xử lý: `/account/`, `/administrator/`, `/api/`,
`/upload/`, `/thumbs/`, `/watermark/`. `/gio-hang/` và `/cam-on/` đã có public
Nuxt route trong source (commit `a740593` trên nhánh Batch B), nhưng cả hai
flag đều mặc định OFF và Caddy chưa chuyển traffic; khi OFF, middleware trả
người dùng về PHP, giữ query `token` của `/cam-on/`. Mọi POST/mutation vẫn phải
về PHP. Account/Admin, session/cart calculation, checkout transaction, payment
callback, order creation và media transform ở PHP theo thiết kế.

Public GUI cart và order-success đã có route/component flag-gated trong source
(`a740593`); route checkout preview vẫn là `/migration-checkout-preview` và
không có URL PHP canonical riêng tương đương `/dat-hang/`. Preview hiện chỉ đọc
giỏ hàng; `POST /_migration/checkout` trả 410 và không forward tạo đơn. Chưa tạo
public checkout route mới vì đó sẽ là quyết định URL/sản phẩm, không phải route
parity. Nếu chuyển GUI sang Nuxt ở phase sau, Nuxt chỉ render và gọi adapter
allowlist đã được triển khai/kiểm thử riêng; PHP vẫn kiểm session/quyền/CSRF,
validate giỏ hàng/địa chỉ, tính tiền/phí vận chuyển, tạo đơn, thanh toán và
idempotency. Không suy ra backend đã migrate chỉ vì GUI Nuxt trả 200.

Hai prefix `/migration-*` và `/_migration/*` bị cấm trong mapping public. Preflight
kiểm tra pilot không trùng denylist và không chứa preview path.

## Health và canary pilot

Health probe chỉ nhận loopback origin, không credential/query/path trong origin,
dùng GET, không theo redirect và không gửi cookie. Mặc định script chỉ static
preflight; thêm `--probe` mới gọi hai origin local. Preview trả 200 thì bắt buộc
có `noindex`.

Trình tự pilot sau khi tất cả gate được owner ký:

1. Chụp baseline PHP ít nhất 30 phút: request count, p50/p95 latency, 4xx/5xx,
   upstream timeout, canonical/robots và traffic vào form/API.
2. Rehearse OFF và rollback trên staging/local với cùng manifest; xác nhận exact
   path về PHP khi OFF và protected prefixes luôn về PHP.
3. Bật pilot exact path `/gioi-thieu/` cho 5% request trong 15 phút; không mở
   route thứ hai. Nếu proxy chưa có cơ chế split deterministic đã test, dùng
   window 100% riêng cho đúng một route thay vì tự chế cookie/session canary.
4. Nếu xanh, tăng 25% trong 30 phút, 50% trong 60 phút, rồi 100% pilot trong 24
   giờ. Mỗi nấc cần owner acknowledgement và lưu evidence.
5. Crawl lại canonical/robots/OG/H1/href, alias `/ve-chung-toi/`, missing slug và
   protected routes sau mỗi nấc.

Success metrics: availability pilot >= 99.9%; zero 5xx trong sample dưới 1.000
request, sau đó 5xx rate <= 0.1%; p95 không chậm hơn baseline quá 20% và không
vượt 1.5 giây; zero redirect loop; canonical đúng `/gioi-thieu/`; không
`noindex`; zero request POST/account/admin/API/checkout vào Nuxt; zero
pageerror/hydration error và zero broken critical media trong smoke sample.

Rollback ngay khi có một trong các điều kiện: bất kỳ protected/mutation request
đến Nuxt; preview/noindex bị public; redirect loop/canonical sai; 2 lỗi 5xx liên
tiếp hoặc 5xx > 0.5% trong cửa sổ 5 phút; availability < 99.5% trong 5 phút;
p95 > 2 lần baseline hoặc > 2 giây trong 5 phút; Nuxt unhealthy hai probe liên
tiếp; chức năng/nav/media P0 hỏng. Sau rollback, xác nhận flag OFF, PHP 200,
alias 301, negative status, protected ownership và metrics trở về baseline.

## 301, 404, 410 và alias ownership

- Caddy hiện chỉ sở hữu 301 host `www` sang non-www.
- PHP `libraries/router.php` sở hữu alias cố định
  `/ve-chung-toi/ -> /gioi-thieu/`,
  `/he-thong-dai-ly/ -> /lien-he/`, `/product/ -> /san-pham/` và product detail
  legacy; đồng thời xử lý redirect động từ bảng `url_redirect`, hierarchy danh
  mục và canonical giải pháp.
- `sources/product.php` sở hữu một số canonical category redirect.
- Router/resolver PHP trả 404 thật cho route không match, slug product legacy
  không tồn tại, hierarchy/solution không hợp lệ và source không hợp lệ.
- Không tìm thấy registry/nhánh 410 hiện hành. Vì vậy không được biến missing
  route thành 410 theo suy đoán. Retired content chỉ được trả 410 sau khi owner
  duyệt danh sách rõ ràng và có test; còn lại giữ 404 PHP.

Trong pilot, proxy không sở hữu alias/entity redirect. Alias và negative route
tiếp tục đi PHP; chỉ canonical exact path có thể vào Nuxt. Trước cutover phải
chạy `migration-seo-audit.cjs` để giữ bằng chứng redirect chain/status/final URL,
không coi soft-200 là đạt.

## Production gate matrix

| Gate | PASS evidence | Owner/manual | Trạng thái |
|---|---|---|---|
| Backup | DB + upload checksum; CloudFly snapshot state được đối chiếu | Owner xác nhận freshness/RPO | Chưa chạy, ngoài scope |
| Rollback rehearsal | Flag OFF đưa pilot về PHP; config/image previous xác định; thời gian đo được | Operator staging | Chưa có runtime flag |
| PHP health | `/` và pilot 200; alias/negative đúng status | QA | Local probe có sẵn |
| Nuxt health | service healthy; public pilot 200 không noindex; preview vẫn noindex | QA | Bị chặn: production Nuxt service/origin chưa được triển khai; source route đã có |
| Proxy allowlist | exact GET/HEAD pilot; denylist và POST về PHP | Reviewer | Manifest local PASS, runtime chưa có |
| Feature flag OFF | default `0`; OFF config không có Nuxt matcher | Reviewer | PASS local fixture |
| Owner approval | approval gắn commit + route + UTC timestamp | Project owner | Chưa cấp |
| Monitoring | dashboard/log query cho upstream/status/latency và alert thresholds | Operator | Chưa cấu hình |
| Cutover | Caddy validate, canary stages và SEO smoke đều PASS | Owner + operator | Không được phép trong task này |
| Rollback verification | PHP ownership/status/SEO và metrics baseline phục hồi | QA + operator | Checklist/command chỉ được generate |

Không gate nào được đánh dấu PASS từ production trong đợt này; không có request
production nào được thực hiện.

## Lệnh local an toàn

```bash
# Static validation, mặc định read-only/OFF
node scripts/nuxt-cutover-preflight.mjs

# Unit + shell guard tests; không Docker/browser/network
./scripts/test-nuxt-cutover.sh

# Chỉ khi hai origin local đã chạy; remote origin bị từ chối
node scripts/nuxt-cutover-preflight.mjs --probe \
  --php-origin http://127.0.0.1:8080 \
  --nuxt-origin http://127.0.0.1:3100

# In rollback command/checklist; không chạy các command được in
node scripts/nuxt-cutover-preflight.mjs --rollback-plan

# Nếu có binary Caddy local, validate fixture mà không apply
caddy validate --config deploy/local-nuxt-hybrid/Caddyfile.off --adapter caddyfile
```

`--cutover-plan` yêu cầu approval file và full commit SHA, sau đó vẫn block vì
pilot hiện là `blocked`. Script không có code SSH, deploy, restart, DNS, ghi DB
hay apply Caddy.

## Việc còn owner/manual

- Duyệt kiến trúc Nuxt production (image, resource limit, health, logging,
  network, origin auth nếu cần) và implementation flag fail-closed.
- Hoàn tất SEO/canonical/OG/status parity cho route pilot; source route đã có,
  nhưng chưa có service/origin production và chưa được expose qua Caddy.
- Xác nhận backup/snapshot và diễn tập rollback trên staging.
- Cấp approval gắn đúng commit/route, chọn canary window và trực monitoring.
- Thực hiện mọi thay đổi production/deploy/restart ở task riêng. Không có bước
  nào trong tài liệu này tự cấp quyền cutover.
