# External integrations

- SMTP/PHPMailer: outbound contact/order/member/newsletter mail; cấu hình duy
  nhất là `$config['mail']` trong config production ngoài Git. Các trường SMTP
  legacy trong `table_setting.options` không còn được runtime đọc. TLS bắt buộc
  verify certificate + hostname và từ chối self-signed. Xem
  [smtp-production.md](smtp-production.md).
- Google reCAPTCHA: contact flow calls `Functions::checkRecaptcha`, accepts score >= 0.5/action `contact`; key/config values not documented.
- OneSignal: CMS can POST notification via cURL; feature disabled. Retry and webhook handling not found.
- Social/Zalo/analytics/master tools: values/HTML are database-driven in layout; ownership and active IDs NEEDS VERIFICATION.
- Payment/shipping integration core đã được dựng tại
  [commerce-provider-setup.md](commerce-provider-setup.md): VNPay callback có
  xác thực chữ ký, transaction ledger và registry cho GHN/GHTK/Viettel Post/
  Nhất Tín. Provider vẫn tắt cho tới khi nhập credential và hoàn tất mapping kho,
  dịch vụ, địa chỉ, kiện hàng; chưa coi là verified production integration.
