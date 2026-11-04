# ADR 0010: Ngôn ngữ thiết kế PTS và phạm vi các quy tắc thị giác cũ

- Ngày: 2026-11-04
- Trạng thái: Accepted theo chỉ đạo trực tiếp của Chủ project
- Owner: Chủ project Phúc Thịnh Solar

## Bối cảnh

Project có các tài liệu UI được viết ở những giai đoạn khác nhau. Một số rule
legacy yêu cầu flat surface toàn site, giới hạn radius 2–5px, cấm shadow/glass,
breadcrumb theo một kiểu cố định, hoặc yêu cầu Nuxt sao chép hoàn toàn visual
PHP. Các rule này xung đột với các composition và motion đang được phát triển;
agent có thể hiểu baseline migration như giới hạn cho redesign sáng tạo.

Các quy tắc nghiệp vụ, nội dung, CMS, URL/SEO, media thật, responsive,
accessibility, commerce và bảo vệ dữ liệu vẫn cần thống nhất, bất kể composition
được chọn.

## Quyết định

1. `docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md` là nguồn hiện
   hành cho thị giác public PTS: màu, typography, radius, surface, elevation,
   breadcrumb treatment, motion, composition và hướng theo loại trang.
2. `docs/02-development/public-ui-ux-design-system.md` tiếp tục quy định các
   hợp đồng UX/kỹ thuật: dữ liệu và CMS, IA/luồng nghiệp vụ, URL/SEO, nguồn và
   tỷ lệ media, responsive, accessibility, behavior và Definition of Done.
   Khi câu chữ thị giác cũ trong tài liệu này mâu thuẫn với design language, tài
   liệu 12 có thẩm quyền cho quyết định thị giác.
3. Full Visual Parity với PHP là yêu cầu cho task migration/parity được giao rõ.
   Task creative redesign được phép thay đổi cách trình bày theo brief đã chọn
   và design language hiện hành; phải bảo toàn đúng nội dung/dữ liệu, URL/SEO,
   luồng nghiệp vụ và accessibility trong phạm vi task.
4. Giữ các nội dung trang chủ và đường vào nghiệp vụ đã được chủ project duyệt.
   Redesign presentation không tự cho phép xóa section/luồng; thay đổi nội dung
   hoặc cấu trúc nghiệp vụ vẫn cần phạm vi/owner direction phù hợp.
5. Motion có thể là một phần nhận diện và trải nghiệm riêng của route. Motion
   phải giữ scroll tự nhiên, không gây layout shift, hỗ trợ bàn phím/chạm, tôn
   trọng `prefers-reduced-motion` và không vượt ngân sách hiệu năng của thiết bị.
   Chọn một hiệu ứng chủ đạo cho mỗi màn hình; các chuyển động khác hỗ trợ thao
   tác hoặc định hướng.
6. Quyết định lịch sử vẫn là bằng chứng về bối cảnh, không phải luật thị giác
   hiện tại. Các điều khoản về surface/radius trong ADR 0001 mục 8, ADR 0002,
   các tham chiếu flat-surface ở ADR 0003/0006, thẩm quyền thị giác tại ADR 0007,
   cùng các điều khoản visual-parity ngoài phạm vi migration/parity ở ADR 0009
   được supersede theo phạm vi trên. Phần còn lại của từng ADR tiếp tục có hiệu
   lực nếu không xung đột với owner direction mới.

## Các nguồn cần đồng bộ

- `AGENTS.md` và `frontend/AGENTS.md` trỏ tới design language hiện hành cùng
  public UI contract cho các ràng buộc còn hiệu lực.
- `docs/15-design-delivery/00-START-HERE.md` xác định thứ tự đọc và phân biệt
  migration/parity với creative redesign.
- `docs/02-development/public-ui-ux-design-system.md` giữ hợp đồng sản phẩm,
  dữ liệu và kỹ thuật, đồng thời đánh dấu snapshot/audit cũ là lịch sử.
- `docs/15-design-delivery/02-PTS-VISUAL-LANGUAGE.md` và các tài liệu design
  khác là composition/reference; không tạo một nguồn token cạnh tranh.

## Hệ quả

- Có thể sáng tạo layout, surface và motion theo nội dung từng trang mà không
  mất nhận diện PTS hoặc hợp đồng dữ liệu/UX.
- Migration vẫn có baseline rõ ràng; redesign không bị kéo về bản PHP cũ chỉ
  vì yêu cầu parity đã được viết cho giai đoạn chuyển đổi.
- Mọi thay đổi dùng chung vẫn cần owner direction; route-level composition
  trong phạm vi task không cần tạo ngoại lệ token.
- ADR cũ không bị xóa hoặc viết lại lịch sử. Tài liệu hiện hành phải chỉ rõ
  điều khoản nào còn hiệu lực và điều khoản nào đã được thay thế.

## Alternatives

- Giữ tài liệu 2026-09 là authority duy nhất: tiếp tục xung đột với design
  language và khiến agent ưu tiên baseline cũ cho redesign.
- Xóa các ADR/UI audit cũ: làm mất bối cảnh về hành vi và quyết định đã có.
- Cho mỗi route một bộ token riêng: tạo nhận diện rời rạc và làm shared UI khó
  vận hành.

## Rollback

Chủ project có thể thay design language bằng quyết định mới và cập nhật các
entrypoint/ADR liên quan. Không có thay đổi runtime, database, production data
hoặc hạ tầng trong ADR này.
