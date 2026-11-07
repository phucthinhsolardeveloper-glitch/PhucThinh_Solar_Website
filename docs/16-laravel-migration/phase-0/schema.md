# Schema local — kiểm kê Phase 0

Tạo lúc 2026-11-07T18:52:55+07:00 từ container `pts-mariadb-local`, database `phucthinhs_local`. Chỉ đọc cấu trúc và số dòng, không đọc dữ liệu.

- Số bảng: 59; engine: InnoDB
- Dấu vân tay schema (cột + index, SHA-256): `a7fffbc3fe50c3cd01179e415f8da144c41755ebe824e379ddadc57285c30cf2`
- Khóa ngoại thật: 1
  - `table_product_campaign_scope.id_campaign` → `table_product_campaign.id`

| Bảng | Engine | Số dòng | MB |
|---|---|---:|---:|
| `table_city` | InnoDB | 97 | 0.02 |
| `table_color` | InnoDB | 4 | 0.02 |
| `table_comment` | InnoDB | 0 | 0.02 |
| `table_comment_photo` | InnoDB | 0 | 0.02 |
| `table_comment_video` | InnoDB | 0 | 0.02 |
| `table_contact` | InnoDB | 2 | 0.02 |
| `table_content_section` | InnoDB | 462 | 2.11 |
| `table_content_section_backup` | InnoDB | 0 | 0.05 |
| `table_counter` | InnoDB | 88071 | 10.55 |
| `table_district` | InnoDB | 705 | 0.09 |
| `table_excel` | InnoDB | 0 | 0.02 |
| `table_gallery` | InnoDB | 499 | 0.13 |
| `table_lang` | InnoDB | 164 | 0.02 |
| `table_member` | InnoDB | 0 | 0.02 |
| `table_news` | InnoDB | 82 | 0.34 |
| `table_newsletter` | InnoDB | 0 | 0.02 |
| `table_news_cat` | InnoDB | 0 | 0.05 |
| `table_news_item` | InnoDB | 0 | 0.05 |
| `table_news_list` | InnoDB | 8 | 0.05 |
| `table_news_sub` | InnoDB | 0 | 0.05 |
| `table_news_tags` | InnoDB | 0 | 0.02 |
| `table_order` | InnoDB | 0 | 0.05 |
| `table_order_detail` | InnoDB | 0 | 0.05 |
| `table_order_status` | InnoDB | 5 | 0.02 |
| `table_payment_transaction` | InnoDB | 0 | 0.06 |
| `table_permission` | InnoDB | 346 | 0.05 |
| `table_permission_group` | InnoDB | 3 | 0.02 |
| `table_permission_legacy_backup_056` | InnoDB | 42 | 0.02 |
| `table_photo` | InnoDB | 49 | 0.03 |
| `table_product` | InnoDB | 399 | 2.59 |
| `table_product_brand` | InnoDB | 22 | 0.05 |
| `table_product_campaign` | InnoDB | 0 | 0.03 |
| `table_product_campaign_scope` | InnoDB | 0 | 0.05 |
| `table_product_cat` | InnoDB | 28 | 0.05 |
| `table_product_document` | InnoDB | 0 | 0.03 |
| `table_product_item` | InnoDB | 0 | 0.05 |
| `table_product_list` | InnoDB | 9 | 0.05 |
| `table_product_project` | InnoDB | 0 | 0.06 |
| `table_product_sale` | InnoDB | 0 | 0.02 |
| `table_product_sub` | InnoDB | 0 | 0.05 |
| `table_product_tags` | InnoDB | 1 | 0.02 |
| `table_pushonesignal` | InnoDB | 0 | 0.02 |
| `table_seo` | InnoDB | 747 | 0.38 |
| `table_seopage` | InnoDB | 9 | 0.02 |
| `table_setting` | InnoDB | 1 | 0.02 |
| `table_setting_sort` | InnoDB | 1 | 0.02 |
| `table_shipping_shipment` | InnoDB | 0 | 0.06 |
| `table_size` | InnoDB | 4 | 0.02 |
| `table_sortable` | InnoDB | 8 | 0.02 |
| `table_sortable_detail` | InnoDB | 5 | 0.02 |
| `table_sortable_index` | InnoDB | 6 | 0.02 |
| `table_static` | InnoDB | 8 | 0.02 |
| `table_tags` | InnoDB | 0 | 0.02 |
| `table_url_redirect` | InnoDB | 14 | 0.05 |
| `table_user` | InnoDB | 2 | 0.03 |
| `table_user_limit` | InnoDB | 250 | 0.05 |
| `table_user_log` | InnoDB | 137 | 0.05 |
| `table_user_online` | InnoDB | 136 | 0.08 |
| `table_ward` | InnoDB | 13920 | 1.52 |

## Phân bố theo `type`

- `table_product`: san-pham 399
- `table_news`: tin-tuc 12, chinh-sach 7, loi-ich 7, ly-do-chon-chung-toi 6, tieu-chi 5, quy-trinh 5, giai-phap 5, cam-ket-dai-ly 4, du-an 4, so-lieu-thong-ke 4, he-thong-cua-hang 4, phan-hoi-dai-ly 3, cam-ket 3, su-menh 3, so-lieu-thong-ke-dai-ly 3, hinh-thuc-thanh-toan 3, lich-su 2, solution-cta 1, solution-intro 1
- `table_photo`: doitac 10, hinh-anh-dai-ly 9, chungnhan 5, home-hero-bg 4, slide 4, hero-carousel 4, social 3, adv 2, banner-top 1, logo-footer 1, favicon 1, watermark 1, logo 1, bct 1, popup 1, watermark-news 1
- `table_static`: footer 1, su-menh 1, dai-ly-1 1, dai-ly-3 1, lienhe 1, tom-tat-gioi-thieu 1, loi-ich 1, dai-ly-2 1
- `table_content_section`: product 413, cat 27, brand 13, list 9
- `table_product_brand`: san-pham 22

## Cột theo bảng

<details><summary><code>table_city</code> (9 cột)</summary>

- `id int(11) NOT NULL PRI`
- `name varchar(255)`
- `slug varchar(255)`
- `level varchar(100)`
- `code varchar(2)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_color</code> (11 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `namevi varchar(255)`
- `nameen varchar(255)`
- `color varchar(15)`
- `type_show tinyint(1)`
- `type varchar(30)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_comment</code> (13 cột)</summary>

- `id int(11) NOT NULL PRI`
- `id_parent int(11)`
- `id_variant int(11)`
- `star int(11)`
- `title varchar(255)`
- `content text`
- `fullname varchar(255)`
- `phone varchar(20)`
- `email varchar(100)`
- `poster varchar(255)`
- `status varchar(255)`
- `type varchar(30)`
- `date_posted int(11)`

</details>
<details><summary><code>table_comment_photo</code> (3 cột)</summary>

- `id int(11) NOT NULL PRI`
- `id_parent int(11)`
- `photo varchar(255)`

</details>
<details><summary><code>table_comment_video</code> (4 cột)</summary>

- `id int(11) NOT NULL PRI`
- `id_parent int(11)`
- `photo varchar(255)`
- `video varchar(255)`

</details>
<details><summary><code>table_contact</code> (13 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `fullname varchar(255)`
- `email varchar(150)`
- `phone varchar(20)`
- `file_attach varchar(255)`
- `subject varchar(255)`
- `content mediumtext`
- `notes mediumtext`
- `address mediumtext`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`
- `numb int(11)`

</details>
<details><summary><code>table_content_section</code> (9 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `scope enum('product','brand','list','cat') NOT NULL MUL`
- `scope_id int(10) unsigned NOT NULL`
- `mode varchar(16) NOT NULL`
- `inherit_source varchar(16) NOT NULL`
- `blocksvi mediumtext`
- `blocksen mediumtext`
- `status varchar(64) NOT NULL`
- `date_updated int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_content_section_backup</code> (13 cột)</summary>

- `id bigint(20) unsigned NOT NULL PRI`
- `content_section_id int(10) unsigned NOT NULL MUL`
- `scope varchar(16) NOT NULL MUL`
- `scope_id int(10) unsigned NOT NULL`
- `blocksvi mediumtext`
- `blocksen mediumtext`
- `mode varchar(16) NOT NULL`
- `inherit_source varchar(16) NOT NULL`
- `status varchar(64) NOT NULL`
- `date_updated int(10) unsigned NOT NULL`
- `action varchar(24) NOT NULL`
- `date_created int(10) unsigned NOT NULL`
- `created_by int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_counter</code> (3 cột)</summary>

- `id int(11) NOT NULL PRI`
- `tm int(11) MUL`
- `ip varchar(16) MUL`

</details>
<details><summary><code>table_district</code> (10 cột)</summary>

- `id int(11) NOT NULL PRI`
- `id_city int(11)`
- `name varchar(255)`
- `slug varchar(255)`
- `code varchar(3)`
- `level varchar(100)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_excel</code> (4 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `type varchar(30)`
- `numb int(11)`

</details>
<details><summary><code>table_gallery</code> (22 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_parent int(11) MUL`
- `photo varchar(255)`
- `hash varchar(20)`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `id_color int(11)`
- `file_attach varchar(255)`
- `link_video mediumtext`
- `numb int(11)`
- `type varchar(30)`
- `com varchar(255)`
- `kind varchar(30)`
- `val varchar(30)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`
- `price double NOT NULL`
- `solieuvi varchar(225)`
- `solieuen varchar(225)`
- `donvivi varchar(225)`
- `donvien varchar(225)`

</details>
<details><summary><code>table_lang</code> (5 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `lang_define mediumtext`
- `langvi mediumtext`
- `langen mediumtext`
- `numb int(11) unsigned`

</details>
<details><summary><code>table_member</code> (16 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_social tinyint(1)`
- `username varchar(225)`
- `password varchar(225)`
- `confirm_code varchar(255)`
- `avatar varchar(255)`
- `fullname varchar(225)`
- `phone varchar(20)`
- `email varchar(100)`
- `address varchar(225)`
- `gender tinyint(1)`
- `login_session varchar(255)`
- `lastlogin varchar(255)`
- `status varchar(255)`
- `birthday int(11)`
- `numb int(11)`

</details>
<details><summary><code>table_news</code> (40 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_user int(11) unsigned NOT NULL`
- `id_list int(11)`
- `id_item int(11)`
- `id_cat int(11)`
- `id_sub int(11)`
- `photo varchar(255)`
- `photo1 varchar(225)`
- `options mediumtext`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `title_indexvi varchar(225)`
- `title_indexen varchar(225)`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_created int(11)`
- `date_updated int(11)`
- `view int(11)`
- `solieu varchar(225)`
- `address varchar(225)`
- `project_city_id int(10) unsigned NOT NULL MUL`
- `project_ward_id int(10) unsigned NOT NULL MUL`
- `project_address_detail varchar(255)`
- `project_start_date date`
- `project_end_date date`
- `phone varchar(225)`
- `email varchar(225)`
- `tenkhachhang varchar(225)`
- `ngayhopdong varchar(225)`
- `ggmap varchar(225)`
- `content1vi text`
- `content1en text`
- `map_iframe text`

</details>
<details><summary><code>table_news_cat</code> (17 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_news_item</code> (18 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `id_cat int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_updated int(11)`
- `date_created int(11)`

</details>
<details><summary><code>table_news_list</code> (16 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_news_sub</code> (19 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `id_cat int(11)`
- `id_item int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_updated int(11)`
- `date_created int(11)`

</details>
<details><summary><code>table_news_tags</code> (3 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_parent int(11)`
- `id_tags int(11)`

</details>
<details><summary><code>table_newsletter</code> (24 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `fullname varchar(255)`
- `file_attach varchar(255)`
- `email varchar(150)`
- `phone varchar(20)`
- `subject varchar(255)`
- `content mediumtext`
- `notes varchar(1024)`
- `confirm_status varchar(255)`
- `address mediumtext`
- `type varchar(100)`
- `product_id int(10) unsigned NOT NULL`
- `profile_key varchar(32)`
- `need_code varchar(64)`
- `details text`
- `city_id int(10) unsigned NOT NULL`
- `ward_id int(10) unsigned NOT NULL`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`
- `numb int(11)`
- `loaihinh varchar(225)`
- `congsuat varchar(225)`
- `company varchar(225)`

</details>
<details><summary><code>table_order</code> (26 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_user int(11)`
- `code varchar(25)`
- `checkout_token char(64) UNI`
- `fullname varchar(255)`
- `phone varchar(20)`
- `address varchar(255)`
- `email varchar(100)`
- `order_payment int(11)`
- `payment_method_code varchar(40)`
- `payment_transaction_ref varchar(120)`
- `carrier_code varchar(40)`
- `carrier_order_code varchar(120)`
- `carrier_status varchar(40)`
- `temp_price double`
- `total_price double`
- `city int(11)`
- `district int(11)`
- `ward int(11)`
- `ship_price double`
- `requirements mediumtext`
- `notes mediumtext`
- `date_created int(11)`
- `order_status int(11)`
- `numb int(11)`
- `payment_status int(4)`

</details>
<details><summary><code>table_order_detail</code> (11 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_order int(11) MUL`
- `id_product int(11) MUL`
- `photo varchar(255)`
- `name varchar(255)`
- `code varchar(255)`
- `color varchar(255)`
- `size varchar(255)`
- `regular_price double`
- `sale_price double`
- `quantity int(11)`

</details>
<details><summary><code>table_order_status</code> (4 cột)</summary>

- `id int(11) NOT NULL PRI`
- `namevi varchar(255)`
- `nameen varchar(255)`
- `class_order varchar(30)`

</details>
<details><summary><code>table_payment_transaction</code> (11 cột)</summary>

- `id bigint(20) unsigned NOT NULL PRI`
- `id_order int(10) unsigned NOT NULL MUL`
- `provider varchar(40) NOT NULL MUL`
- `transaction_ref varchar(120) NOT NULL`
- `provider_transaction_ref varchar(160)`
- `amount decimal(18,2) NOT NULL`
- `status varchar(30) NOT NULL MUL`
- `payload_hash char(64)`
- `payload mediumtext`
- `created_at int(10) unsigned NOT NULL`
- `updated_at int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_permission</code> (3 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_permission_group int(11)`
- `permission varchar(255)`

</details>
<details><summary><code>table_permission_group</code> (6 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `name varchar(255)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_permission_legacy_backup_056</code> (3 cột)</summary>

- `id_permission_group int(11) NOT NULL PRI`
- `permission varchar(255) NOT NULL PRI`
- `backed_up_at int(11) NOT NULL`

</details>
<details><summary><code>table_photo</code> (19 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `link mediumtext`
- `link_video mediumtext`
- `options mediumtext`
- `type varchar(30) MUL`
- `act varchar(30)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`
- `show_stt tinyint(1) NOT NULL`
- `video varchar(225)`

</details>
<details><summary><code>table_product</code> (65 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `id_item int(11)`
- `id_cat int(11)`
- `id_sub int(11)`
- `id_brand int(11)`
- `photo varchar(255)`
- `photo2 varchar(225)`
- `photo_khuyenmai varchar(255)`
- `photo_feed varchar(255)`
- `options mediumtext`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `highlightsvi mediumtext`
- `highlightsen mediumtext`
- `applicationsvi mediumtext`
- `applicationsen mediumtext`
- `installationvi mediumtext`
- `installationen mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `code varchar(30)`
- `regular_price double`
- `discount double`
- `cong_suat decimal(6,1) unsigned`
- `video_url varchar(500)`
- `battery_capacity varchar(100)`
- `panel_power varchar(100)`
- `light_source varchar(255)`
- `lamp_dimensions varchar(150)`
- `color_temperature varchar(100)`
- `light_color varchar(100)`
- `material varchar(255)`
- `charging_time varchar(100)`
- `lighting_time varchar(100)`
- `waterproof_rating varchar(50)`
- `led_chip varchar(100)`
- `lighting_area varchar(100)`
- `installation_height varchar(100)`
- `panel_dimensions varchar(150)`
- `package_contents text`
- `carton_quantity varchar(100)`
- `carton_dimensions varchar(150)`
- `weight int(10) unsigned`
- `warranty varchar(150)`
- `additional_specs mediumtext`
- `certificatevi mediumtext`
- `certificateen mediumtext`
- `sale_price double`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `pdp_profile varchar(32)`
- `date_created int(11)`
- `date_updated int(11)`
- `view int(11)`
- `khoanggia varchar(225)`
- `thongsokythuatvi text`
- `thongsokythuaten text`
- `chinhsachmuahangvi text`
- `chinhsachmuahangen text`

</details>
<details><summary><code>table_product_brand</code> (16 cột)</summary>

- `id int(11) NOT NULL PRI`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_product_campaign</code> (9 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `name varchar(255) NOT NULL`
- `photo varchar(255) NOT NULL`
- `priority int(11) NOT NULL`
- `start_at int(10) unsigned NOT NULL`
- `end_at int(10) unsigned NOT NULL`
- `status varchar(64) NOT NULL MUL`
- `date_created int(10) unsigned NOT NULL`
- `date_updated int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_product_campaign_scope</code> (4 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `id_campaign int(10) unsigned NOT NULL MUL`
- `scope_type enum('all','list','cat','brand','product') NOT NULL MUL`
- `scope_id int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_product_cat</code> (17 cột)</summary>

- `id int(11) NOT NULL PRI`
- `id_list int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_product_document</code> (10 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `id_product int(10) unsigned NOT NULL MUL`
- `document_type varchar(32) NOT NULL`
- `titlevi varchar(255) NOT NULL`
- `titleen varchar(255)`
- `file_path varchar(500) NOT NULL`
- `numb int(11) NOT NULL`
- `status varchar(64) NOT NULL`
- `date_created int(10) unsigned NOT NULL`
- `date_updated int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_product_item</code> (18 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `id_cat int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_updated int(11)`
- `date_created int(11)`

</details>
<details><summary><code>table_product_list</code> (17 cột)</summary>

- `id int(11) NOT NULL PRI`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `pdp_profile varchar(32)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_product_project</code> (5 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `id_product int(10) unsigned NOT NULL MUL`
- `id_project int(10) unsigned NOT NULL MUL`
- `numb int(10) unsigned NOT NULL`
- `status varchar(50) NOT NULL`

</details>
<details><summary><code>table_product_sale</code> (4 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_parent int(11)`
- `id_color int(11)`
- `id_size int(11)`

</details>
<details><summary><code>table_product_sub</code> (19 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_list int(11)`
- `id_cat int(11)`
- `id_item int(11)`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `photo varchar(255)`
- `options mediumtext`
- `numb int(11)`
- `status varchar(255)`
- `type varchar(30) MUL`
- `date_updated int(11)`
- `date_created int(11)`

</details>
<details><summary><code>table_product_tags</code> (3 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_parent int(11)`
- `id_tags int(11)`

</details>
<details><summary><code>table_pushonesignal</code> (7 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `name varchar(255)`
- `link varchar(255)`
- `photo varchar(255)`
- `description mediumtext`
- `date_created int(11)`
- `numb int(11)`

</details>
<details><summary><code>table_seo</code> (11 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_parent int(11)`
- `com varchar(30) MUL`
- `act varchar(30)`
- `type varchar(30)`
- `titlevi mediumtext`
- `keywordsvi mediumtext`
- `descriptionvi mediumtext`
- `titleen mediumtext`
- `keywordsen mediumtext`
- `descriptionen mediumtext`

</details>
<details><summary><code>table_seopage</code> (10 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `options mediumtext`
- `type varchar(30)`
- `titlevi mediumtext`
- `keywordsvi mediumtext`
- `descriptionvi mediumtext`
- `titleen mediumtext`
- `keywordsen mediumtext`
- `descriptionen mediumtext`

</details>
<details><summary><code>table_setting</code> (9 cột)</summary>

- `id int(11) NOT NULL PRI`
- `options mediumtext`
- `mastertool mediumtext`
- `headjs mediumtext`
- `bodyjs mediumtext`
- `namevi varchar(255)`
- `nameen varchar(255)`
- `analytics mediumtext`
- `web_as varchar(255)`

</details>
<details><summary><code>table_setting_sort</code> (11 cột)</summary>

- `id int(11) NOT NULL PRI`
- `options mediumtext`
- `mastertool mediumtext`
- `headjs mediumtext`
- `bodyjs mediumtext`
- `namevi varchar(255)`
- `nameen varchar(255)`
- `analytics mediumtext`
- `hotline int(4)`
- `phone int(4)`
- `email int(4)`

</details>
<details><summary><code>table_shipping_shipment</code> (11 cột)</summary>

- `id bigint(20) unsigned NOT NULL PRI`
- `id_order int(10) unsigned NOT NULL MUL`
- `provider varchar(40) NOT NULL MUL`
- `quote_ref varchar(120)`
- `order_code varchar(120)`
- `status varchar(40) NOT NULL MUL`
- `fee decimal(18,2) NOT NULL`
- `cod_amount decimal(18,2) NOT NULL`
- `payload mediumtext`
- `created_at int(10) unsigned NOT NULL`
- `updated_at int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_size</code> (8 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `namevi varchar(255)`
- `nameen varchar(255)`
- `type varchar(30)`
- `numb int(11)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_sortable</code> (7 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `name varchar(255) NOT NULL`
- `display_order int(5) NOT NULL`
- `created datetime NOT NULL`
- `modified datetime NOT NULL`
- `status enum('1','0') NOT NULL`
- `type varchar(255)`

</details>
<details><summary><code>table_sortable_detail</code> (7 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `name varchar(255) NOT NULL`
- `display_order int(5) NOT NULL`
- `created datetime NOT NULL`
- `modified datetime NOT NULL`
- `status enum('1','0') NOT NULL`
- `type varchar(255)`

</details>
<details><summary><code>table_sortable_index</code> (7 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `name varchar(255) NOT NULL`
- `display_order int(5) NOT NULL`
- `created datetime NOT NULL`
- `modified datetime NOT NULL`
- `status enum('1','0') NOT NULL`
- `type varchar(255)`

</details>
<details><summary><code>table_static</code> (17 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `options mediumtext`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `file_attach varchar(255)`
- `type varchar(30)`
- `status varchar(255)`
- `namkinhnghiem varchar(225)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_tags</code> (16 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `photo varchar(255)`
- `options mediumtext`
- `slugvi varchar(255)`
- `slugen varchar(255)`
- `contenten mediumtext`
- `contentvi mediumtext`
- `descen mediumtext`
- `descvi mediumtext`
- `nameen varchar(255)`
- `namevi varchar(255)`
- `numb int(11)`
- `type varchar(30)`
- `status varchar(255)`
- `date_created int(11)`
- `date_updated int(11)`

</details>
<details><summary><code>table_url_redirect</code> (5 cột)</summary>

- `id int(10) unsigned NOT NULL PRI`
- `old_path varchar(512) NOT NULL UNI`
- `entity_table varchar(32) NOT NULL MUL`
- `entity_id int(10) unsigned NOT NULL`
- `date_created int(10) unsigned NOT NULL`

</details>
<details><summary><code>table_user</code> (19 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_permission int(11)`
- `username varchar(225) UNI`
- `password varchar(225)`
- `confirm_code varchar(255)`
- `avatar varchar(255)`
- `fullname varchar(225)`
- `phone varchar(20)`
- `email varchar(100)`
- `address varchar(225)`
- `gender tinyint(1)`
- `login_session varchar(255)`
- `user_token varchar(255)`
- `lastlogin varchar(255)`
- `status varchar(255)`
- `role tinyint(1)`
- `secret_key varchar(255)`
- `birthday int(11)`
- `numb int(11)`

</details>
<details><summary><code>table_user_limit</code> (5 cột)</summary>

- `id int(11) NOT NULL PRI`
- `login_ip varchar(50) NOT NULL`
- `login_attempts int(11) NOT NULL`
- `attempt_time int(11) NOT NULL`
- `locked_time int(11) NOT NULL`

</details>
<details><summary><code>table_user_log</code> (5 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_user int(11)`
- `ip varchar(16)`
- `timelog int(11)`
- `user_agent mediumtext`

</details>
<details><summary><code>table_user_online</code> (3 cột)</summary>

- `session char(100) MUL`
- `time int(11) MUL`
- `ip varchar(255)`

</details>
<details><summary><code>table_ward</code> (12 cột)</summary>

- `id int(11) unsigned NOT NULL PRI`
- `id_city int(11)`
- `id_district int(11)`
- `name varchar(255)`
- `slug varchar(255)`
- `code varchar(5)`
- `level varchar(100)`
- `numb int(11)`
- `status varchar(255)`
- `date_updated int(11)`
- `date_created int(11)`
- `ship_price double`

</details>

## Index

- `table_city`.`PRIMARY` (id)
- `table_color`.`PRIMARY` (id)
- `table_comment`.`PRIMARY` (id)
- `table_comment_photo`.`PRIMARY` (id)
- `table_comment_video`.`PRIMARY` (id)
- `table_contact`.`PRIMARY` (id)
- `table_content_section`.`PRIMARY` (id)
- `table_content_section`.`uq_content_section_scope` (scope,scope_id)
- `table_content_section_backup`.`idx_content_section_backup_scope` (scope,scope_id,id)
- `table_content_section_backup`.`idx_content_section_backup_section` (content_section_id,id)
- `table_content_section_backup`.`PRIMARY` (id)
- `table_counter`.`idx_counter_ip_tm` (ip,tm)
- `table_counter`.`idx_counter_tm` (tm)
- `table_counter`.`PRIMARY` (id)
- `table_district`.`PRIMARY` (id)
- `table_excel`.`PRIMARY` (id)
- `table_gallery`.`idx_gallery_parent` (id_parent,com,type)
- `table_gallery`.`PRIMARY` (id)
- `table_lang`.`PRIMARY` (id)
- `table_member`.`PRIMARY` (id)
- `table_news`.`idx_news_project_city` (project_city_id)
- `table_news`.`idx_news_project_ward` (project_ward_id)
- `table_news`.`idx_route_type_slug` (type,slugvi)
- `table_news`.`idx_type_numb_id` (type,numb,id)
- `table_news`.`PRIMARY` (id)
- `table_newsletter`.`PRIMARY` (id)
- `table_news_cat`.`idx_route_type_slug` (type,slugvi)
- `table_news_cat`.`idx_type_numb_id` (type,numb,id)
- `table_news_cat`.`PRIMARY` (id)
- `table_news_item`.`idx_route_type_slug` (type,slugvi)
- `table_news_item`.`idx_type_numb_id` (type,numb,id)
- `table_news_item`.`PRIMARY` (id)
- `table_news_list`.`idx_route_type_slug` (type,slugvi)
- `table_news_list`.`idx_type_numb_id` (type,numb,id)
- `table_news_list`.`PRIMARY` (id)
- `table_news_sub`.`idx_route_type_slug` (type,slugvi)
- `table_news_sub`.`idx_type_numb_id` (type,numb,id)
- `table_news_sub`.`PRIMARY` (id)
- `table_news_tags`.`PRIMARY` (id)
- `table_order`.`PRIMARY` (id)
- `table_order`.`ux_order_checkout_token` (checkout_token)
- `table_order_detail`.`idx_order_detail_order` (id_order)
- `table_order_detail`.`idx_order_detail_product` (id_product)
- `table_order_detail`.`PRIMARY` (id)
- `table_order_status`.`PRIMARY` (id)
- `table_payment_transaction`.`idx_payment_order` (id_order)
- `table_payment_transaction`.`idx_payment_status` (status)
- `table_payment_transaction`.`PRIMARY` (id)
- `table_payment_transaction`.`ux_payment_provider_ref` (provider,transaction_ref)
- `table_permission`.`PRIMARY` (id)
- `table_permission_group`.`PRIMARY` (id)
- `table_permission_legacy_backup_056`.`uq_group_permission` (id_permission_group,permission)
- `table_photo`.`idx_photo_type_act` (type,act)
- `table_photo`.`PRIMARY` (id)
- `table_product`.`idx_product_type_list_cat` (type,id_list,id_cat)
- `table_product`.`idx_route_type_slug` (type,slugvi)
- `table_product`.`idx_type_numb_id` (type,numb,id)
- `table_product`.`PRIMARY` (id)
- `table_product_brand`.`idx_route_type_slug` (type,slugvi)
- `table_product_brand`.`idx_type_numb_id` (type,numb,id)
- `table_product_brand`.`PRIMARY` (id)
- `table_product_campaign`.`idx_product_campaign_active` (status,start_at,end_at,priority,id)
- `table_product_campaign`.`PRIMARY` (id)
- `table_product_campaign_scope`.`idx_product_campaign_scope_target` (scope_type,scope_id,id_campaign)
- `table_product_campaign_scope`.`PRIMARY` (id)
- `table_product_campaign_scope`.`uq_product_campaign_scope` (id_campaign,scope_type,scope_id)
- `table_product_cat`.`idx_route_type_slug` (type,slugvi)
- `table_product_cat`.`idx_type_numb_id` (type,numb,id)
- `table_product_cat`.`PRIMARY` (id)
- `table_product_document`.`idx_product_document_product` (id_product,status,numb,id)
- `table_product_document`.`PRIMARY` (id)
- `table_product_item`.`idx_route_type_slug` (type,slugvi)
- `table_product_item`.`idx_type_numb_id` (type,numb,id)
- `table_product_item`.`PRIMARY` (id)
- `table_product_list`.`idx_route_type_slug` (type,slugvi)
- `table_product_list`.`idx_type_numb_id` (type,numb,id)
- `table_product_list`.`PRIMARY` (id)
- `table_product_project`.`idx_product_project_product` (id_product,numb,id)
- `table_product_project`.`idx_product_project_project` (id_project,numb,id)
- `table_product_project`.`PRIMARY` (id)
- `table_product_project`.`ux_product_project_pair` (id_product,id_project)
- `table_product_sale`.`PRIMARY` (id)
- `table_product_sub`.`idx_route_type_slug` (type,slugvi)
- `table_product_sub`.`idx_type_numb_id` (type,numb,id)
- `table_product_sub`.`PRIMARY` (id)
- `table_product_tags`.`PRIMARY` (id)
- `table_pushonesignal`.`PRIMARY` (id)
- `table_seo`.`idx_seo_lookup` (com,act,type,id_parent)
- `table_seo`.`PRIMARY` (id)
- `table_seopage`.`PRIMARY` (id)
- `table_setting`.`PRIMARY` (id)
- `table_setting_sort`.`PRIMARY` (id)
- `table_shipping_shipment`.`idx_shipping_order` (id_order)
- `table_shipping_shipment`.`idx_shipping_status` (status)
- `table_shipping_shipment`.`PRIMARY` (id)
- `table_shipping_shipment`.`ux_shipping_provider_order` (provider,order_code)
- `table_size`.`PRIMARY` (id)
- `table_sortable`.`PRIMARY` (id)
- `table_sortable_detail`.`PRIMARY` (id)
- `table_sortable_index`.`PRIMARY` (id)
- `table_static`.`PRIMARY` (id)
- `table_tags`.`PRIMARY` (id)
- `table_url_redirect`.`idx_url_redirect_entity` (entity_table,entity_id)
- `table_url_redirect`.`PRIMARY` (id)
- `table_url_redirect`.`uq_url_redirect_old_path` (old_path)
- `table_user`.`PRIMARY` (id)
- `table_user`.`uniq_user_username` (username)
- `table_user_limit`.`PRIMARY` (id)
- `table_user_log`.`PRIMARY` (id)
- `table_user_online`.`idx_user_online_session` (session)
- `table_user_online`.`idx_user_online_time` (time)
- `table_ward`.`PRIMARY` (id)
