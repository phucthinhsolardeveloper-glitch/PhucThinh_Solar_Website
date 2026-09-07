# External integrations

- SMTP/PHPMailer: outbound contact/order/member mail; config in
  `table_setting.options`; no retry/queue, failure is synchronous nhưng được
  bắt lỗi để không làm vỡ response. TLS bắt buộc verify certificate + hostname
  và từ chối self-signed. Vẫn phải gửi thử bằng mailbox thật trên hosting.
- Google reCAPTCHA: contact flow calls `Functions::checkRecaptcha`, accepts score >= 0.5/action `contact`; key/config values not documented.
- OneSignal: CMS can POST notification via cURL; feature disabled. Retry and webhook handling not found.
- Social/Zalo/analytics/master tools: values/HTML are database-driven in layout; ownership and active IDs NEEDS VERIFICATION.
- No verified payment gateway, shipping carrier API, object storage or CDN integration.
