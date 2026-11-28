# Database hiện hành — MariaDB / Laravel

Đối chiếu migrations và models ngày 2026-11-26 (5c35860f). Compose local MariaDB 10.11; README gọi DB pts_app. Schema thật là [migrations](../../backend/database/migrations) và [Models](../../backend/app/Models). Không áp prefix table_* hoặc giả định MyISAM từ hệ cũ.

| Nhóm | Bảng thấy trong migration |
|---|---|
| Catalogue | categories, brands, products, product_images, product_documents, product_options, brand_category, product_campaigns |
| Nội dung | posts, post_categories, post_media, post_products, post_revisions, content_sections, content_section_versions, page_items, page_layouts, banners, albums, album_images, videos |
| Commerce | orders, order_items, payment_methods, payment_transactions, payment_instructions, order_documents |
| Shipping | shipments, shipment_events; provinces, wards; migration add_warehouses |
| Lead | leads, lead_events, outbox_messages |
| SEO/media | seo_metas, redirects, media_files, page_views |
| Vận hành | users, sessions, cache, jobs, failed_jobs, admin_audit_events, notifications, data_quality_daily, editorial_plan_items, settings |

Danh sách phân nhóm dựa trên Schema::create và tên migration, không phải live SHOW TABLES. Migration gần nhất add_campaign_to_orders (2026-11-26); trạng thái áp dụng xem release log và migrate:status. Không suy luận kiểu cột/constraint/engine thật khi chưa đọc migration và DB target.

Thay đổi schema: đọc migration liên quan, model và Domain consumer; tạo migration mới, không sửa migration đã chạy; test dữ liệu cũ, null/default, index, transaction, rollback. Backup trước khi migration. Không chạy migrate:fresh/refresh/legacy import trên dữ liệu cần giữ. [Checkout write path](checkout-write-path.md), [backup scripts](../../deploy/laravel13/scripts).

Lịch sử DB PHP/MyISAM: git show 5c35860f:docs/03-data/database.md.
