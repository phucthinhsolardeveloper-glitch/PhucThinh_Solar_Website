# Runbook: `pts:content-batch-patch`

Lệnh này cập nhật hàng loạt nội dung bài Kiến thức (`posts.kind=article`) bằng manifest có baseline hash. Mặc định chỉ preview. Lệnh không tự duyệt: mọi item phải có `qa.state=approved`, nguồn và ảnh đã xác minh, `human_approved=true` và reviewer là người thật. Những cờ này là khai báo của quy trình biên tập; lệnh không xác minh danh tính reviewer hoặc bằng chứng nguồn thay cho con người.

## Manifest schema 1

```json
{
  "schema_version": 1,
  "items": [
    {
      "id": 123,
      "kind": "article",
      "slug": "huong-dan-chon-he-thong",
      "original": {
        "body_sha256": "<64 ký tự hex từ baseline>",
        "summary_sha256": "<64 ký tự hex từ baseline>",
        "seo_title_sha256": "<64 ký tự hex từ baseline>",
        "seo_description_sha256": "<64 ký tự hex từ baseline>"
      },
      "proposed": {
        "markdown_file": "2026-11-16/articles/huong-dan-chon-he-thong.md",
        "summary": "Tóm tắt đã biên tập.",
        "seo_description": "Mô tả tìm kiếm đã kiểm tra.",
        "seo_title": "Tiêu đề SEO mới"
      },
      "qa": {
        "state": "approved",
        "sources_verified": true,
        "images_verified": true,
        "human_approved": true,
        "reviewer": "Tên người duyệt"
      }
    }
  ]
}
```

`seo_title` là tùy chọn; bỏ key thì title SEO được giữ nguyên. Body Markdown được đọc từ `backend/resources/editorial/` qua đường dẫn tương đối, chuyển HTML bằng Markdown parser rồi lọc qua `RichText`. Đường dẫn tuyệt đối, `..`, symlink thoát khỏi thư mục gốc, nội dung trống và một số dạng placeholder sẽ bị từ chối. Chỉ body, summary, SEO description và SEO title khi được cung cấp mới có thể được cập nhật. Title bài, slug/URL, publish state/date, category, cover/media và quan hệ sản phẩm không đổi. `updated_at` thay đổi khi có ghi.

## Xuất baseline

Trong backend container/local checkout, chỉ định ID tường minh và file output mới. Export chỉ đọc DB và ghi raw SHA-256 cùng ID/kind/slug/visibility; body, summary và metadata không được xuất vào file này.

```sh
docker exec pts-laravel13-local php artisan pts:content-batch-patch \
  --ids=123,124 --export-baseline=/tmp/content-baseline-2026-11-16.json
```

Baseline của local DB **không thể dùng thay baseline production**. DB production có thể đã thay đổi độc lập; phải xuất baseline từ đúng DB đích, rồi đối chiếu và review lại manifest trước khi chạy bất cứ apply nào. Không chép raw body vào repo.

## Preview và apply local

Chuẩn bị manifest với hash lấy từ baseline cùng nội dung Markdown đã duyệt. Preview không cập nhật DB; item chưa duyệt, drift, thiếu SEO row hoặc metadata mismatch sẽ là blocker và làm cả batch fail-closed.

```sh
docker exec pts-laravel13-local php artisan pts:content-batch-patch \
  --manifest=/tmp/content-batch-manifest.json
```

Sau khi người có thẩm quyền duyệt manifest và preview, chạy apply **chỉ trong local/testing**. Visible article cần `--allow-visible`.

```sh
docker exec pts-laravel13-local php artisan pts:content-batch-patch \
  --manifest=/tmp/content-batch-manifest.json --apply
```

Lệnh backup toàn bộ raw row của `posts` và `seo_metas` vào `backend/storage/app/private/content-batch-patch/` trước transaction, rồi khóa và kiểm tra lại baseline trong transaction trước khi ghi. Giữ đường dẫn backup và run report ở log để rollback/điều tra. Nếu target đã khớp, item là `NOOP`; nếu hash hiện tại khác cả baseline lẫn target thì toàn batch bị từ chối.

Lệnh có `--allow-production --approval-ref=<mã>` làm opt-in bổ sung cho production apply. Mã phải trỏ tới quyết định của người thật sau khi duyệt; lệnh không dùng approval-ref để tự duyệt QA. Không chạy production trong quá trình xây dựng tính năng này.
