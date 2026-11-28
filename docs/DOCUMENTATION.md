# Quy chuẩn tài liệu sống

## Hai lối vào
- AI: AGENTS.md / CLAUDE.md → [AI-START.md](AI-START.md).
- Người: README → [SO-TAY-CHU-PROJECT.md](SO-TAY-CHU-PROJECT.md).
- Cùng dùng [CURRENT.md](CURRENT.md), source, test, schema và các tài liệu nghiệp vụ/vận hành hiện có.

## Nguồn sự thật theo loại
| Nội dung | Nơi duy trì |
|---|---|
| Kiến trúc, nghiệp vụ, API, database | Tài liệu chuyên sâu hiện có + implementation/schema/test |
| Hiện trạng & việc tiếp theo | CURRENT.md, trỏ release log và task thực tế |
| Deploy, backup, rollback | Runbook / release log của project |
| Quyết định thay đổi | ADR hiện có hoặc mục quyết định của project |
| Hướng dẫn người | Sổ tay: ví dụ thao tác, chỉ dẫn tới source/canonical docs |
| Quy tắc AI | AGENTS.md (hoặc CLAUDE.md khi chưa có AGENTS); AI-START.md chỉ routing/quy trình |

## Định nghĩa hoàn thành cho thay đổi
Khi behavior, API, schema, cấu hình, quyền, deploy hoặc đường dẫn đổi: cập nhật doc sở hữu nội dung, kiểm tra liên kết từ hai lối vào, ghi trạng thái nghiệm thu và hạn chế. Nếu không có tác động tài liệu, nói rõ lý do trong báo cáo. Không cập nhật hàng loạt chỉ vì đổi ngày.

Tài liệu cũ là lịch sử; nếu hướng dẫn sai hoặc mâu thuẫn, ghi cảnh báo và link thay thế ngay đầu file. Không xóa quyết định, audit, runbook cũ hoặc bằng chứng release chỉ để giảm số file. Ưu tiên sửa nội dung được sử dụng thực tế. Không ghi secret, token, cookie hoặc PII.

## Kiểm tra tài liệu
Đọc như người mới: tìm được file cần sửa, chạy local, test, tìm log, rollback; đọc như agent mới: xác định scope, base SHA, rule và bước tiếp theo. Xác nhận link tương đối tồn tại, lệnh phù hợp phiên bản và môi trường. Kiểm chứng runtime riêng trước khi gọi production là hiện hành.

Lịch rà soát: ngay trong thay đổi liên quan; sau release quan trọng kiểm lại trạng thái và hai lối vào. Không cần job tự động hay thêm công cụ.
