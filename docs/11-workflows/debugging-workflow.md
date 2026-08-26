# Debugging workflow

Capture URL/method/input/time/user role -> reproduce on staging -> trace `.htaccess`/router/source/template or API -> inspect sanitized logs/query state -> isolate smallest cause -> add regression check -> fix -> verify success/failure/permission paths -> document.

Không bật display_errors trên production và không copy logs chứa PII/secrets vào issue/chat. Nếu bug liên quan data, clone tối thiểu đã ẩn danh.
