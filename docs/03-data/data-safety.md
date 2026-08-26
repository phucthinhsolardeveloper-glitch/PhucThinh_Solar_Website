# Data safety

Persistent data: MariaDB, `upload/`, mailbox trong `imap/`, và có thể `wp-content/` legacy. `thumbs/`/`caches/` có vẻ dẫn xuất nhưng chưa kiểm chứng khả năng tái tạo đầy đủ.

Backup snapshot có `backup/home.tar.gz`, SQL/config DirectAdmin và metadata account. User crontab rỗng. Không tìm thấy schedule, retention, off-server copy hoặc restore test: trạng thái PARTIAL.

Rủi ro: dump public chứa PII/hash; archive/log public; credential nằm trong config; MyISAM thiếu transaction/FK; backup cùng account/server không bảo vệ trước mất máy.

Trước thay đổi: export DB, snapshot upload, checksum, lưu ngoài webroot/server, ghi thời điểm và restore owner. Không thử restore trên production.
