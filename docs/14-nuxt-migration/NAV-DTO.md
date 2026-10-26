# Public site navigation DTO

`GET /api/pts_v1_site.php` trả projection read-only từ các query publish trong
`sources/allpage.php` và menu public hiện hành. DTO không bao gồm raw settings,
secret, session/cart count hoặc nội dung HTML chưa được phép render.

Ví dụ schema tối giản (giá trị chỉ minh họa kiểu dữ liệu, không phải CMS fixture):

```json
{
  "schemaVersion": 1,
  "navigationSchemaVersion": 1,
  "locale": "vi",
  "brand": {"name": "string", "hotline": "string", "logo": "/upload/photo/logo.webp", "logoFooter": "/upload/photo/logo-footer.webp"},
  "navigation": [{"label": "string", "href": "/path/", "children": [{"label": "string", "href": "/path/"}]}],
  "catalog": {"lighting": {"label": "string", "href": "/danh-muc-san-pham/.../", "items": []}, "solar": {"label": "string", "href": "/danh-muc-san-pham/.../", "items": []}, "devices": {"label": "string", "href": "/danh-muc-san-pham/.../", "items": []}},
  "productCategories": [{"id": 1, "name": "string", "slug": "string", "url": "/danh-muc-san-pham/.../"}],
  "productCategoryTree": [{"id": 1, "name": "string", "slug": "string", "url": "/danh-muc-san-pham/.../", "children": []}],
  "footer": {"companyName": "string", "contactText": "string", "policies": [], "locations": [], "socialLinks": []}
}
```

`productCategories` vẫn giữ dạng danh sách cấp gốc tương thích với contract cũ;
`catalog` và `productCategoryTree` là dữ liệu phân cấp được publish.
Logo chỉ được trả khi filename hợp lệ và file thực tế tồn tại trong thư mục
`UPLOAD_PHOTO_L`. URL ngoài chỉ nhận `http(s)`, còn nội dung footer được chuyển
thành text an toàn; policy, bản đồ và social chỉ xuất hiện khi source public có
giá trị tương ứng.
