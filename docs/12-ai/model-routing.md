# Chọn model và effort theo task

Áp dụng cho Claude Code, Codex và ChatGPT Web. Mục tiêu: việc dễ không đốt hạn mức của model mạnh,
việc rủi ro không giao cho model yếu.

## Chấm task (trước khi bắt đầu)

Lấy mức **cao nhất** trong 4 tiêu chí, không lấy trung bình.

| Mức | Rủi ro nếu sai | Độ mơ hồ | Phạm vi | Ví dụ trong dự án |
|---|---|---|---|---|
| L0 Cơ học | Không đáng kể | Có lệnh rõ | Không cần suy luận | grep, liệt kê, chạy test/pint, đổi tên hàng loạt, đo số liệu |
| L1 Nhỏ | Sửa lại dễ | Yêu cầu rõ | 1–2 file | sửa nhãn, thêm một cột bảng, viết tài liệu, thêm một test |
| L2 Thường | Có test bắt được | Có spec | Nhiều file, một module | màn Filament mới, API theo hợp đồng có sẵn, viết lại có snapshot kiểm chứng |
| L3 Khó | Sai nghiệp vụ, khó phát hiện | Phải tự suy ra | Nhiều module | đối chiếu nghiệp vụ PHP cũ, nhập/xuất Excel, bản nháp/lịch sử, lỗi chưa rõ nguyên nhân, đánh giá UI |
| L4 Không đảo ngược | Mất dữ liệu, tiền, bảo mật | Thiết kế mới | Toàn hệ thống | migration dữ liệu thật, thanh toán, quyền, kiến trúc, kế hoạch cutover |

## Bảng chọn

| Mức | Claude | Effort | ChatGPT / Codex | Effort |
|---|---|---|---|---|
| L0 | Haiku 5.5 | low | gpt-6-luna | low |
| L1 | Sonnet 5.5 | low | gpt-6.1-terra | low |
| L2 | Sonnet 5.5 | medium | gpt-6.1-sol | medium |
| L3 | Opus 5.5 | high | gpt-6.1-sol | high |
| L4 | Opus 5.5 | xhigh | gpt-6-astra | high |

- `max`/`ultra` chỉ dùng khi chủ website yêu cầu.
- Claude Fable 5.1 chưa xếp vào bảng vì chưa đo mức tiêu hao hạn mức; chỉ dùng khi được yêu cầu.
- Tên model Codex lấy từ `~/.codex/models_cache.json` ngày 2026-11-09. Khi danh sách đổi thì cập nhật bảng này.
- Với gpt-6.1-terra, catalog chưa mô tả rõ năng lực; nếu kết quả L1 kém thì dùng gpt-6.1-sol low.

## Đổi mức trong lúc làm

- Làm sai hai lần ở cùng một việc: nâng một mức, kèm tóm tắt đã thử gì. Không thử lần thứ ba cùng mức.
- Việc tách ra thấy đơn giản hơn dự kiến: phần cơ học chuyển xuống L0/L1.
- Đụng tiền, dữ liệu thật, quyền, production: tối thiểu L3, không hạ để tiết kiệm.

## Thực tế chuyển model

Agent không tự đổi được model của chính phiên đang chạy. Việc chuyển diễn ra ở các điểm sau:

| Công cụ | Cách đổi |
|---|---|
| Claude Code, phiên chính | Người dùng gõ `/model`; effort theo cấu hình phiên |
| Claude Code, việc giao đi | Agent đặt `model` và `effort` cho từng sub-agent có kiểu (không có tác dụng với `fork`) |
| Codex, mỗi lần chạy | `codex -m <model> -c model_reasoning_effort=<mức>` hoặc chọn trong phiên |
| ChatGPT Web | Người dùng chọn model; khi điều phối thì ghi mức và lệnh chạy Codex vào task |

Sub-agent phải đọc lại ngữ cảnh từ đầu nên chỉ tách khi việc đủ lớn, độc lập và có brief rõ. Việc nhỏ làm luôn trong phiên.

## Quy ước khi giao và làm

- Mỗi task ghi một dòng đầu: `Mức: L2 | Claude Sonnet 5.5 medium` (hoặc model Codex tương ứng).
- Báo cáo ghi model và effort đã dùng cho từng phần giao đi.

## Chống đọc lại

- Trước khi mở lại module: xem `docs/16-laravel-migration/STATUS.md`. Mục CHỐT không rà lại nếu không có lỗi mới.
- Xong phần nào: test, commit, chuyển dòng sang CHỐT kèm commit, xóa file tạm.
- Đọc theo mục tiêu (grep, đoạn dòng), không đọc cả file khi chỉ cần một hàm.
- Lệnh chạy lâu: chỉ in dòng cuối hoặc dòng lỗi.
