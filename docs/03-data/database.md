# Database

Dump `public_html/phucthinhsolar_com.sql` có 48 bảng, prefix `table_`, engine MyISAM. ALTER section chỉ thêm primary key/auto increment; không thấy foreign key/index nghiệp vụ.

Nhóm bảng:

- Catalogue: `product`, `product_list`, `product_cat`, `product_item`, `product_sub`, `product_brand`, `product_sale`, `product_tags`, `color`, `size`, `gallery`.
- Content: `news` và hierarchy, `static`, `photo`, `tags`, `seo`, `seopage`, `setting`.
- Commerce: `order`, `order_detail`, `order_status`.
- Identity/RBAC: `member`, `user`, `permission_group`, `permission`, `user_limit`, `user_log`.
- Engagement: `comment`, `comment_photo`, `comment_video`, `contact`, `newsletter`.
- Support: location, counter/online, sortable, import/export, push notification.

Quan hệ là logic qua `id_*` và code query, không được DB enforce. Ví dụ `order_detail.id_order -> order.id`, product hierarchy/brand, gallery `id_parent`, permission group. Xóa parent có thể để orphan; phải kiểm tra thủ công.

Không có migration framework. Mọi thay đổi schema mới phải tạo SQL migration có forward/rollback note, test trên clone và không sửa dump canonical làm cơ chế deploy.
