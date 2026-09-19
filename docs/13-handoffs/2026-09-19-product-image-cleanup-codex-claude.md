# Quy tắc phối hợp làm sạch ảnh sản phẩm — Codex ↔ Claude

Cập nhật: 2026-09-19.

## Mục tiêu

Tạo lại ảnh đại diện sản phẩm theo chuẩn:

- Chỉ giữ chủ thể vật lý của sản phẩm, phụ kiện đi kèm và các góc nhìn vật lý có trong ảnh nguồn.
- Xóa toàn bộ nền, logo, watermark, nhãn, nội dung quảng cáo, thông số và mọi chữ/model.
- Nếu logo/chữ nằm trực tiếp trên sản phẩm, xóa và tái tạo bề mặt bên dưới nhưng không thay đổi hình học hoặc chi tiết chức năng.
- Ảnh mới là PNG vuông RGBA, nền alpha thật, chủ thể không bị crop và có khoảng trống 6–8%.
- Không xóa hoặc ghi đè ảnh nguồn.

## Phân công bắt buộc

### Codex

1. Đối chiếu ảnh nguồn với manifest production.
2. Kiểm tra ảnh nguồn trước khi tạo để nhận diện đúng chủ thể và phụ kiện phải giữ.
3. Tạo đúng một candidate mới cho mỗi sản phẩm bằng imagegen.
4. Không tự duyệt chất lượng cuối, không cập nhật DB và không deploy production.
5. Lưu candidate trong workspace staging và ghi manifest với trạng thái `generated_pending_review`.

### Claude

1. So sánh trực quan từng candidate với ảnh nguồn ở kích thước đủ lớn.
2. Chỉ nhận ảnh đạt toàn bộ tiêu chí bên dưới; ảnh không đạt chuyển thành `rejected_regenerate` và ghi lý do cụ thể.
3. Với ảnh đạt, đổi/cố định filename mới, copy vào `upload/product`, cập nhật ảnh đại diện DB local bằng điều kiện khớp ID + filename cũ.
4. Test local: file tồn tại, PNG RGBA, alpha thật, URL ảnh HTTP 200, trang sản phẩm HTTP 200 và HTML dùng filename mới.
5. Chỉ triển khai production sau khi toàn bộ lô đã được duyệt; trước khi triển khai phải snapshot lại DB/file production và bỏ qua mọi sản phẩm đã thay đổi kể từ manifest.
6. Sau deploy, test lại ảnh và trang sản phẩm; giữ ảnh cũ và bảng mapping để rollback.

## Tiêu chí Claude phải kiểm tra

Candidate chỉ được nhận khi:

- Không còn nền trắng/màu, logo, watermark, chữ, số, model hoặc nhãn.
- Không còn packaging, banner, icon, huy hiệu hoặc nội dung quảng cáo không phải sản phẩm.
- Không mất bất kỳ thành phần vật lý nào có trong ảnh nguồn: đèn, tấm pin, chân đỡ, giá treo, vít, cáp, đầu nối hoặc phụ kiện.
- Không thêm linh kiện, không gộp linh kiện và không đổi số lượng chủ thể/góc nhìn vật lý.
- Không đổi hình dáng, tỷ lệ, góc nhìn, màu vật liệu, số LED, bố cục LED, số cell pin, cổng hoặc lỗ bắt vít.
- Không crop sát; toàn bộ chủ thể nằm trong khung vuông với khoảng thở hợp lý.
- Mép alpha sạch, không viền trắng/đen, không còn mảng nền hoặc bóng nền giả.
- Không bị nhòe, vỡ, méo, có chi tiết AI giả hoặc bề mặt tái tạo bất thường.

Không duyệt chỉ dựa vào việc pixel góc trong suốt; phải so sánh trực quan với ảnh nguồn.

## Quy tắc filename và manifest

- Filename mới không được trùng ảnh cũ và không ghi đè file có sẵn.
- Ưu tiên `<ma-san-pham-chuan-hoa>-clean-v1.png`; nếu thiếu mã dùng `product-<id>-clean-v1.png`.
- Nếu tên có khả năng trùng, thêm `-<product_id>` trước `-clean-v1.png`.
- Manifest bắt buộc có các cột:

```text
product_id	code	old_photo	candidate_path	new_photo	source_date_updated	status	review_notes
```

Các trạng thái hợp lệ:

```text
generated_pending_review
accepted_local
rejected_regenerate
skipped_recent_change
deployed_production
rolled_back
```

## Khóa ảnh vừa được chủ project thay

- Không xử lý sản phẩm ID `370`, mã `SUN2000-5KTL-L1`, ảnh `sun2000-5ktl-l1-01-9188.png`, cập nhật production lúc `2026-09-18 16:15:44`.
- Không xử lý bất kỳ file upload hoặc bản ghi sản phẩm nào cập nhật trong ngày 2026-09-18 hoặc 2026-09-19.
- Ngay trước khi Claude thay ảnh, nếu `photo` hoặc `date_updated` khác manifest thì phải skip; không được ghi đè.

## Cập nhật DB

- Dùng transaction và prepared statement.
- Mỗi dòng chỉ được cập nhật khi `id`, `photo` cũ và `date_updated` vẫn khớp snapshot.
- Nếu số dòng tác động không phải `1`, rollback dòng/lô đó và ghi `skipped_recent_change`.
- Không xóa ảnh cũ sau khi cập nhật.

## Deploy production

1. Backup các dòng DB bị tác động và xác nhận ảnh cũ còn tồn tại.
2. Upload candidate đã duyệt vào `/srv/phucthinhsolar/shared/upload/product/` bằng tên tạm, kiểm tra checksum rồi rename atomically.
3. Chạy transaction cập nhật DB có guard.
4. Xóa cache/thumbnail liên quan có thể tái sinh, không xóa upload gốc.
5. Test URL ảnh, trang chi tiết và ít nhất một trang danh mục ở desktop/mobile.
6. Rollback bằng mapping `new_photo → old_photo`; file mới có thể giữ lại, không cần xóa.

## Nguồn theo dõi hiện tại

- Snapshot production: `artifacts/product-image-production-manifest-2026-09-19.tsv`.
- Danh sách đã tạo/đang xử lý: `artifacts/product-image-clean-all-2026-09-19.tsv`.
- Ảnh nguồn production tạm: `/tmp/pts-product-source-20260919/`.
- 10 ảnh pilot đầu đã được Codex tạo và áp dụng local trước khi quy tắc phân công này được chốt; Claude cần kiểm tra lại như các ảnh khác.
