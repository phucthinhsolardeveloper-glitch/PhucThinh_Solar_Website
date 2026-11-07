# Admin legacy — ma trận chức năng Phase 0

Tạo lúc 2026-11-07T18:52:55+07:00. `act` lấy từ `case` trong `administrator/sources/*.php`.

| Module (`com`) | Hành động (`act`) |
|---|---|
| `cache` | delete |
| `comment` | man |
| `contact` | delete, edit, man, save |
| `content_section` | delete, edit, export, import, import_legacy, import_run, inherited_blocks, man, restore, save, toggle, upload_image |
| `excel` | — |
| `excelAll` | — |
| `export` | exportExcel, man |
| `gallery` | add_photo, delete_photo, edit_photo, man_photo, save_photo |
| `import` | deleteImages, editImages, man, saveImages, uploadExcel, uploadImages |
| `index` | — |
| `integrations` | — |
| `lang` | add, create, delete, edit, man, save |
| `mail_template` | man, preview, save, save_test |
| `news` | add, add_cat, add_item, add_list, add_photo, add_sub, copy, delete, delete_cat, delete_item, delete_list, delete_photo, delete_sub, edit, edit_cat, edit_item, edit_list, edit_photo, edit_sub, man, man_cat, man_item, man_list, man_photo, man_sub, save, save_cat, save_copy, save_item, save_list, save_photo, save_sub |
| `newsletter` | add, delete, edit, man, save |
| `order` | delete, edit, man, save |
| `photo` | add_photo, delete_photo, edit_photo, man_photo, photo_static, save_photo, save_static |
| `places` | add_city, add_district, add_ward, delete_city, delete_district, delete_ward, edit_city, edit_district, edit_ward, man_city, man_district, man_ward, save_city, save_district, save_ward |
| `product` | add, add_brand, add_cat, add_color, add_document, add_item, add_list, add_photo, add_size, add_sub, bulk, bulk_commit, bulk_export, bulk_image_commit, bulk_image_preview, bulk_preview, bulk_template, copy, delete, delete_brand, delete_cat, delete_color, delete_document, delete_item, delete_list, delete_photo, delete_size, delete_sub, edit, edit_brand, edit_cat, edit_color, edit_document, edit_item, edit_list, edit_photo, edit_size, edit_sub, export_feed_csv, man, man_brand, man_cat, man_color, man_document, man_item, man_list, man_photo, man_size, man_sub, save, save_brand, save_cat, save_color, save_copy, save_document, save_item, save_list, save_photo, save_size, save_sub |
| `product_bulk` | — |
| `product_campaign` | add, edit, man, save |
| `pushOnesignal` | add, delete, edit, man, save, sync |
| `seopage` | save, update |
| `setting` | save, update |
| `sortable` | save, update |
| `static` | save, update |
| `tags` | add, delete, edit, man, save |
| `user` | add_admin, add_member, add_permission_group, delete_admin, delete_member, delete_permission_group, edit_admin, edit_member, edit_permission_group, info_admin, login, logout, man_admin, man_member, permission_group, save_admin, save_member, save_permission_group |
| `word` | — |
| `wordAll` | — |

## Loại nội dung khai báo trong `libraries/type`

- `config-type-news.php`: about-principle, about-team, cam-ket, cam-ket-dai-ly, catalog-benefit, chinh-sach, du-an, giai-phap, he-thong-cua-hang, hinh-thuc-thanh-toan, home-about, home-about-point, home-energy-chain, lich-su, ly-do-chon-chung-toi, phan-hoi-dai-ly, quick-search, quy-trinh, so-lieu-thong-ke-dai-ly, su-menh, thong-tin-lien-he, tin-tuc
- `config-type-newsletter.php`: dangkybaogia, dangkynhantin, dangkynhantin2, dangkytuyendung
- `config-type-photo.php`: about-cta, about-showroom, adv, background, banner-top, bct, catalog-campaign, chungnhan, contact-visual, dealer-hero, doitacgioithieu, favicon, gia-tri-cot-loi, hero-carousel, hinh-anh-dai-ly, hinh-anh-gioi-thieu, home-campaign, logo, logo-footer, popup, product-category-hero, slide-product, social, video, watermark, watermark-news
- `config-type-product.php`: san-pham, thu-vien-anh
- `config-type-static.php`: bct, dai-ly-1, dai-ly-2, dai-ly-3, doi-tac-cong-nghe, footer, gioi-thieu, gioi-thieu-san-pham, lienhe, tom-tat-gioi-thieu
- `config-type-tags.php`: dich-vu, san-pham, tin-tuc, tuyen-dung
