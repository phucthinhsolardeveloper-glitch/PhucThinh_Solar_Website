# Dependency map

Không có root Composer/npm manifest. Dependencies được copy vào source:

- AltoRouter: routing; PDODb: PDO wrapper/query builder.
- PHPMailer: SMTP; PHPExcel/PHPWord: CMS import/export; WebpConvert: image conversion.
- MobileDetect, Bootstrap/jQuery/UI plugins.
- CKEditor/CKFinder/elFinder trong CMS.

Không tìm thấy app-level Redis, queue, ORM, payment/shipping SDK hay test framework. Manifest tìm thấy chỉ thuộc WebpConvert và elFinder/CKFinder subtree.
