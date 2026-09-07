# External integrations

- SMTP/PHPMailer: outbound contact/order/member/newsletter mail; cấu hình duy
  nhất là `$config['mail']` trong config production ngoài Git. Các trường SMTP
  legacy trong `table_setting.options` không còn được runtime đọc. TLS bắt buộc
  verify certificate + hostname và từ chối self-signed. Xem
  [smtp-production.md](smtp-production.md).
- Google reCAPTCHA: contact flow calls `Functions::checkRecaptcha`, accepts score >= 0.5/action `contact`; key/config values not documented.
- OneSignal: CMS can POST notification via cURL; feature disabled. Retry and webhook handling not found.
- Social/Zalo/analytics/master tools: values/HTML are database-driven in layout; ownership and active IDs NEEDS VERIFICATION.
- No verified payment gateway, shipping carrier API, object storage or CDN integration.
