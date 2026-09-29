# Shared operating contract

Áp dụng cho developer, Claude Code và Codex.

```text
INSPECT -> UNDERSTAND -> PLAN -> IMPLEMENT -> TEST -> DEBUG -> VERIFY -> DOCUMENT -> REPORT
```

Project được kế thừa. Không mặc định hành vi hiện có là chủ ý. Trước thay đổi đáng kể phải đọc route/source/template/API, schema/config liên quan, xác định dữ liệu bị tác động, test và rollback.

## Quy tắc

- Không sửa production hoặc import đè DB nếu chưa được phép rõ ràng.
- Không xóa code/upload/cache/legacy asset trước khi xác minh.
- Không đưa credential, token, password hash hoặc PII vào tài liệu/chat.
- Không cleanup/refactor ngoài phạm vi task.
- Không bắt buộc cập nhật docs theo thời gian thực cho mọi deploy nhỏ. Tuy nhiên,
  sau milestone/chức năng lớn, migration, thay đổi kiến trúc/quy trình vận hành,
  hoặc khi implementation làm tài liệu sai, phải cập nhật tài liệu liên quan
  trước khi đóng task. Rà soát docs định kỳ theo
  `docs/06-operations/documentation-maintenance.md`; ghi các đợt rà soát lớn và
  thay đổi kiến thức vào lịch sử, không để project phát triển lệch khỏi docs.
- SQL mới phải parameterized; form mutation phải kiểm tra session/quyền và CSRF.
- Upload phải kiểm tra extension, MIME, size, filename và lưu ở nơi không thực thi PHP.
- Giữ URL/slug public trừ khi có kế hoạch redirect/SEO.
- Test file thay đổi bằng đúng PHP mục tiêu; commerce smoke test theo category -> product -> cart -> checkout -> confirmation.
- Địa chỉ nghiệp vụ mới chỉ dùng hai cấp `Tỉnh/TP -> Phường/Xã`. Không thêm lại Quận/Huyện vào checkout, bộ lọc đơn hàng hoặc form mới; `district` chỉ là dữ liệu legacy và đơn mới ghi `district = 0`. Backend phải xác thực Phường/Xã đang hiển thị và thuộc đúng Tỉnh/TP đã chọn.
- Cập nhật docs nếu implementation chứng minh tài liệu sai.

## Quy tắc phối hợp dùng chung cho mọi AI

- Đọc `AGENTS.md` và tài liệu liên quan trước khi thao tác; coi đây là hợp đồng
  chung cho Codex, Claude và các agent khác, không yêu cầu chủ project thiết lập
  lại cùng một quy trình ở từng công cụ.
- Trước khi chạy lệnh, phân loại phạm vi: `docs-only`, frontend rủi ro thấp,
  backend/API/commerce/Admin, migration/schema, security/hạ tầng. Chọn cách làm
  nhỏ nhất phù hợp: `AUTO` cho deploy thường ngày; `FAST` chỉ cho frontend hẹp;
  `SAFE` cho backend, dữ liệu, migration, bảo mật, hạ tầng hoặc khi chưa chắc.
- Không chạy full SAFE cho thay đổi nhỏ chỉ vì thói quen. Ngược lại, không dùng
  FAST để né test khi thay đổi có thể ảnh hưởng dữ liệu, auth, checkout, Admin
  hoặc layout toàn cục.
- Với tác vụ dài: chạy một quy trình có checkpoint rõ ràng, theo dõi tiến độ từ
  log/process hiện có và chẩn đoán lỗi tại điểm dừng; không lặp lại cùng một lệnh
  hoặc yêu cầu chủ project cung cấp output mà agent có thể tự đọc. Nếu timeout là
  giới hạn công cụ chứ không phải lỗi dự án, nói rõ và dùng cách chạy phù hợp.
- Khi được yêu cầu deploy/migration, thực hiện đến checkpoint an toàn cuối cùng,
  không dừng giữa chừng vì hỏi lại những lựa chọn đã được chỉ định. Nếu fail,
  giữ nguyên rollback point, báo nguyên nhân và chỉ retry sau khi đã thay đổi
  chẩn đoán hoặc cách chạy.
- Cleanup phải theo thứ tự: đo trước → xác định current/previous/rollback → chỉ
  xóa mục cụ thể. Mặc định giữ 1–2 full backup ứng dụng, current + previous +
  tối đa một rollback release/image; không xóa `shared/upload`, DB volume,
  secrets hoặc state reference. Docker build cache có thể prune sau khi kiểm tra;
  không dùng `docker system prune -a --volumes` trên production.
- Bảo trì disk/Docker phải bắt đầu bằng `df -h`, `du -x` theo từng thư mục và
  `docker system df -v`. Ưu tiên dọn build cache không dùng; image chỉ dọn sau
  khi đối chiếu container, current/previous image và rollback candidate. Khi disk
  tăng bất thường phải báo thư mục chiếm dụng và mức reclaim trước khi xóa.
  Không dọn mù bằng glob, không xóa volume DB, upload hoặc backup chưa xác minh.
- Phân biệt backup ứng dụng với backup/snapshot VPS của CloudFly. Không báo thiếu
  backup chỉ vì chưa có uploader off-site riêng; nêu rõ lớp bảo vệ nào đang có và
  lớp nào chỉ là khuyến nghị bổ sung.
- Không cần ghi docs theo thời gian thực cho mọi thay đổi nhỏ, nhưng sau milestone
  lớn hoặc đợt bảo trì phải rà soát tài liệu, ghi lịch sử và cập nhật rule nếu đã
  hình thành kiến thức vận hành mới. Xem
  `docs/06-operations/documentation-maintenance.md`.
- Báo cáo cuối luôn có: đã làm gì, commit/file nào, test và kết quả, production
  state, cleanup/backup đã tác động, rủi ro còn lại, rollback point và việc chưa
  làm. Không dùng câu “đang chạy” nếu agent không còn process/checkpoint để theo
  dõi.
- Với yêu cầu tính năng, đi theo chuỗi: xác định route/source/template/schema và
  dữ liệu bị tác động → chọn phạm vi tối thiểu → triển khai → test đúng luồng →
  cập nhật docs nếu hành vi đã trở thành kiến thức ổn định. Không tự mở rộng thành
  redesign/refactor ngoài yêu cầu.
- Comment trong code chỉ ghi rationale kỹ thuật, invariant, giới hạn hoặc mốc
  kiểm chứng cần thiết; không chép lời hội thoại, câu nói của chủ project, tên AI
  hoặc lịch sử trao đổi vào source. Nếu cần ghi quyết định sản phẩm, đặt trong
  docs/runbook và dùng ngôn ngữ trung tính.
- Giao tiếp mặc định bằng tiếng Việt, cập nhật ngắn theo checkpoint; chỉ hỏi lại
  khi thiếu quyền hoặc thiếu lựa chọn làm thay đổi đáng kể kết quả. Khi đã được
  giao rõ phạm vi thì tự thực hiện, không bắt chủ project lặp lại thông tin.

## Quy tắc UX/UI đã duyệt

Trước mọi thay đổi giao diện public, đọc
`docs/02-development/public-ui-ux-design-system.md`. Đây là nguồn chuẩn đầy đủ
cho token, component, media, responsive, accessibility và Definition of Done;
các gạch đầu dòng dưới đây là bản tóm tắt bắt buộc, không phải một hệ riêng.

- Trang chủ là showroom bán hàng: sản phẩm và danh mục chiếm phần lớn nội dung; giới thiệu công ty, giải pháp dài, quy trình và dự án để ở trang chuyên biệt.
- Mỗi section dùng một tiêu đề chính rõ ràng. Không thêm eyebrow, gạch chân, nhãn nhỏ hoặc họa tiết chỉ để trang trí nếu chúng chiếm không gian mà không bổ sung thông tin hay hành động.
- Danh mục sản phẩm trang chủ nằm trên một hàng ngang và cuộn ngang khi thiếu chỗ; không tự xuống thành nhiều hàng làm kéo dài trang. Ẩn thanh scrollbar thị giác, nhưng phải giữ kéo chuột/trackpad và vuốt cảm ứng hoạt động.
- Trang chủ product-first hiện không render Hero; không tự bật lại banner/slider Hero nếu chưa có yêu cầu mới từ chủ project.
- Header không có mục Trang chủ; logo là đường dẫn về trang chủ. Nhãn public của `/ve-chung-toi/` là “Giới thiệu”, không đổi URL.
- Điều hướng chính desktop giữ nút Danh mục sản phẩm và đúng 5 nhóm: Giới thiệu, Giải pháp, Dự án, Kiến thức, Hợp tác. Không đưa Tin tức, Đại lý hoặc Liên hệ trở lại thành mục top-level; chúng là nội dung con của Kiến thức/Hợp tác/Giới thiệu. Giữ các URL public cũ hoạt động để bảo toàn SEO và link đã chia sẻ.
- Hạn chế bo góc lớn. Card, khung ảnh, ô tìm kiếm và CTA dạng chữ chỉ bo nhẹ khoảng 2–5px; chỉ dùng hình tròn/pill cho icon hoặc trạng thái có lý do chức năng.
- Ưu tiên bề mặt phẳng trên toàn website: card nội dung, card sản phẩm, danh mục, bài viết và khối thông tin phải hòa vào nền section, không bọc thêm nền gần giống nền trang, border, gạch phân cách, bo góc hoặc shadow chỉ để trang trí. Tạo phân cấp bằng khoảng trắng, lưới, typography và ảnh; chỉ giữ border cho form control, bảng dữ liệu, focus, trạng thái chọn hoặc ranh giới có ý nghĩa thao tác.
- Breadcrumb không có border/gạch chân dưới. Các hub/trang con cũng tuân thủ quy tắc một tiêu đề chính, không eyebrow, đường kẻ hay dấu trang trí thừa như trang chủ.
- Không lặp lại cùng một thông điệp ở nhiều section. Mỗi khối phải phục vụ một mục tiêu cụ thể: tìm danh mục, xem sản phẩm, xác nhận thương hiệu, đọc tin hoặc chuyển đổi.
- Nội dung marketing có khả năng thay đổi phải lấy từ dữ liệu đang quản lý trong Admin; cần fallback an toàn khi chưa nhập dữ liệu.
- Khi tham chiếu website khác, học nhịp nội dung và khả năng bán hàng nhưng giữ nhận diện, dữ liệu thật và hành vi của Phúc Thịnh Solar; không sao chép máy móc.
- Giao diện phẳng không đồng nghĩa với trang trắng tối giản và thiếu điểm nhấn. Trang dài phải có ảnh sản phẩm, ảnh ứng dụng hoặc banner chiến dịch có mục đích để tạo nhịp; mọi vị trí marketing phải quản lý được từ Admin và có công tắc Hiển thị, không dùng viền/card rỗng để bù cho thiếu nội dung.
- Ảnh sản phẩm dùng khung vuông 1:1 và `object-fit: contain`; logo/chứng nhận cũng dùng `contain`, không crop. Ảnh tin tức 16:9, dự án 4:3, ảnh nội dung/đại lý/liên hệ 8:5 hoặc tỷ lệ được ghi rõ tại field Admin; ảnh campaign catalog dùng 16:5. Không kéo giãn, không phóng ảnh nhỏ vượt kích thước tự nhiên, không chèn chữ trực tiếp vào ảnh khi frontend đã render chữ.
- Ảnh cover phải giữ chủ thể trong vùng an toàn trung tâm 70–75% để crop responsive không che/cắt chi tiết quan trọng. Không dùng ảnh AI/stock làm bằng chứng khách hàng, dự án, chứng nhận hoặc năng lực thực tế; các vị trí đó chỉ dùng dữ liệu có thể xác minh.
- Trang Giải pháp và bài Kiến thức dùng CKEditor cho nội dung biên tập dài với H2/H3, đoạn văn, danh sách, bảng và ảnh; không tái sử dụng composer khối dành cho mô tả sản phẩm. Hero, điều hướng, sản phẩm/dự án liên quan, CTA/form và dữ liệu có quan hệ phải do template/Admin field chuyên trách render, không chép cứng vào CKEditor.
- `Giải pháp` và `Danh mục sản phẩm` đều có landing page truy cập được. Menu con là lối tắt, không được biến nhãn cha thành trigger hover-only; trên mobile phải có lối `Tất cả giải pháp`/`Xem tất cả sản phẩm`. Card/menu Giải pháp ưu tiên đi thẳng landing nội dung, không bắt người dùng qua trang nhóm trung gian khi nhóm chỉ có một nội dung đại diện.

Báo cáo cho chủ project bằng tiếng Việt: kết quả, file thay đổi, test, rủi ro còn lại, deploy và rollback.
