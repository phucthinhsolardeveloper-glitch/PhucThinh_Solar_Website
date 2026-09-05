<?php
if (!defined('SOURCES')) die('Error');

require_once LIBRARIES.'admin_product_pricing.php';
require_once LIBRARIES.'admin_product_completeness.php';
require_once LIBRARIES.'product_content_blocks.php';

function productValidateCommercialFields($data)
{
    $pricing = adminNormalizeProductPricing($data);
    $errors = $pricing['errors'];
    if(isset($data['cong_suat']) && trim((string)$data['cong_suat']) !== '')
    {
        $wattage = str_replace(',', '.', trim((string)$data['cong_suat']));
        if(!is_numeric($wattage) || (float)$wattage < 0) $errors[] = 'Công suất không hợp lệ';
    }
    return $errors;
}

function productBulkColumns()
{
    return array(
        'action' => array('label' => 'Thao tác', 'kind' => 'control', 'export' => false),
        'id' => array('label' => 'ID sản phẩm', 'kind' => 'control'),
        'expected_updated_at' => array('label' => 'Mốc cập nhật', 'kind' => 'control', 'export' => false),
        'code' => array('label' => 'Mã sản phẩm', 'kind' => 'text', 'db' => 'code', 'max' => 30, 'required_create' => true),
        'namevi' => array('label' => 'Tên sản phẩm', 'kind' => 'text', 'db' => 'namevi', 'max' => 255, 'required_create' => true),
        'slugvi' => array('label' => 'Đường dẫn', 'kind' => 'slug', 'db' => 'slugvi', 'max' => 255, 'export' => false),
        'id_list' => array('label' => 'ID danh mục cấp 1', 'kind' => 'category', 'db' => 'id_list', 'export' => false),
        '_list_name' => array('label' => 'Danh mục cấp 1', 'kind' => 'category_name', 'target' => 'id_list'),
        'id_cat' => array('label' => 'ID danh mục cấp 2', 'kind' => 'category', 'db' => 'id_cat', 'export' => false),
        '_cat_name' => array('label' => 'Danh mục cấp 2', 'kind' => 'category_name', 'target' => 'id_cat'),
        'id_brand' => array('label' => 'ID thương hiệu', 'kind' => 'category', 'db' => 'id_brand', 'export' => false),
        '_brand_name' => array('label' => 'Thương hiệu', 'kind' => 'category_name', 'target' => 'id_brand'),
        'cong_suat' => array('label' => 'Công suất (W)', 'kind' => 'decimal', 'db' => 'cong_suat'),
        'regular_price' => array('label' => 'Giá gốc', 'kind' => 'money', 'db' => 'regular_price'),
        'sale_price' => array('label' => 'Giá bán/khuyến mãi', 'kind' => 'money', 'db' => 'sale_price'),
        // Giữ cột text cũ ở chế độ import-only để các file đã phát hành vẫn dùng được.
        'status' => array('label' => 'Tình trạng website', 'kind' => 'status', 'db' => 'status', 'export' => false),
        'is_visible' => array('label' => 'Hiển thị (1/0)', 'kind' => 'boolean_status'),
        'is_featured' => array('label' => 'Bán chạy (1/0)', 'kind' => 'boolean_status'),
        'requires_quote' => array('label' => 'Liên hệ báo giá (1/0)', 'kind' => 'boolean_status'),
        'is_promo' => array('label' => 'Khuyến mãi/Sự kiện (1/0)', 'kind' => 'boolean_status'),
        'photo_khuyenmai' => array('label' => 'Tên file khung khuyến mãi PNG (đã upload sẵn qua Quản lý file)', 'kind' => 'image_filename', 'db' => 'photo_khuyenmai', 'max' => 255),
        'numb' => array('label' => 'Thứ tự', 'kind' => 'integer', 'db' => 'numb', 'export' => false),
        'descvi' => array('label' => 'Mô tả ngắn', 'kind' => 'html', 'db' => 'descvi'),
        'contentvi' => array('label' => 'Nội dung', 'kind' => 'html', 'db' => 'contentvi'),
        'highlightsvi' => array('label' => 'Điểm nổi bật (Tiêu đề | Giải thích)', 'kind' => 'content_block_paired', 'db' => 'highlightsvi', 'limit' => 6),
        'applicationsvi' => array('label' => 'Ứng dụng phù hợp (mỗi dòng một ý)', 'kind' => 'content_block', 'db' => 'applicationsvi', 'limit' => 8),
        'installationvi' => array('label' => 'Bộ sản phẩm / hướng dẫn (mỗi dòng một ý)', 'kind' => 'content_block', 'db' => 'installationvi', 'limit' => 10),
        'sectionsvi' => array('label' => 'Mô tả dài (khối) — ## tiêu đề, - danh sách, ![](file) ảnh', 'kind' => 'content_sections'),
        'chinhsachmuahangvi' => array('label' => 'Chính sách mua hàng', 'kind' => 'html', 'db' => 'chinhsachmuahangvi'),
        'video_url' => array('label' => 'Video YouTube', 'kind' => 'youtube', 'db' => 'video_url', 'max' => 500),
        'light_source' => array('label' => 'Nguồn sáng', 'kind' => 'text', 'db' => 'light_source', 'max' => 255),
        'lamp_dimensions' => array('label' => 'Kích thước đèn', 'kind' => 'text', 'db' => 'lamp_dimensions', 'max' => 150),
        'color_temperature' => array('label' => 'Nhiệt độ màu (CCT)', 'kind' => 'text', 'db' => 'color_temperature', 'max' => 100),
        'light_color' => array('label' => 'Màu ánh sáng', 'kind' => 'text', 'db' => 'light_color', 'max' => 100),
        'battery_capacity' => array('label' => 'Dung lượng pin', 'kind' => 'text', 'db' => 'battery_capacity', 'max' => 100),
        'panel_power' => array('label' => 'Công suất tấm pin', 'kind' => 'text', 'db' => 'panel_power', 'max' => 100),
        'material' => array('label' => 'Chất liệu', 'kind' => 'text', 'db' => 'material', 'max' => 255),
        'charging_time' => array('label' => 'Thời gian sạc', 'kind' => 'text', 'db' => 'charging_time', 'max' => 100),
        'lighting_time' => array('label' => 'Thời gian chiếu sáng', 'kind' => 'text', 'db' => 'lighting_time', 'max' => 100),
        'waterproof_rating' => array('label' => 'Chuẩn chống nước', 'kind' => 'text', 'db' => 'waterproof_rating', 'max' => 50),
        'led_chip' => array('label' => 'Chip LED', 'kind' => 'text', 'db' => 'led_chip', 'max' => 100),
        'lighting_area' => array('label' => 'Diện tích chiếu sáng', 'kind' => 'text', 'db' => 'lighting_area', 'max' => 100),
        'installation_height' => array('label' => 'Độ cao lắp đặt', 'kind' => 'text', 'db' => 'installation_height', 'max' => 100),
        'panel_dimensions' => array('label' => 'Kích thước tấm pin', 'kind' => 'text', 'db' => 'panel_dimensions', 'max' => 150),
        'package_contents' => array('label' => 'Bộ sản phẩm gồm', 'kind' => 'text', 'db' => 'package_contents'),
        'carton_quantity' => array('label' => 'Số lượng / thùng', 'kind' => 'text', 'db' => 'carton_quantity', 'max' => 100),
        'carton_dimensions' => array('label' => 'Kích thước thùng', 'kind' => 'text', 'db' => 'carton_dimensions', 'max' => 150),
        'warranty' => array('label' => 'Bảo hành', 'kind' => 'text', 'db' => 'warranty', 'max' => 150),
        'additional_specs' => array('label' => 'Thông số khác (Tên | Giá trị)', 'kind' => 'spec_lines', 'db' => 'additional_specs'),
        'certificatevi' => array('label' => 'Chứng nhận/kiểm nghiệm', 'kind' => 'html', 'db' => 'certificatevi'),
        'photo' => array('label' => 'Tên ảnh hiện tại (chỉ đọc)', 'kind' => 'readonly')
    );
}

function productBulkVisibleColumns()
{
    return array_filter(productBulkColumns(), function ($meta) {
        return !isset($meta['export']) || $meta['export'] !== false;
    });
}

function productBulkEditableFields()
{
    $fields = array();
    foreach (productBulkColumns() as $meta) {
        if (!empty($meta['db'])) $fields[$meta['db']] = true;
    }
    return array_keys($fields);
}

function productBulkNormalizeHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return mb_strtolower($value, 'UTF-8');
}

function productBulkHeaderMap()
{
    $map = array();
    foreach (productBulkColumns() as $key => $meta) {
        // Hỗ trợ cả mã cột và nhãn cột trong file import.
        $map[productBulkNormalizeHeader($key)] = $key;
        $map[productBulkNormalizeHeader($meta['label'])] = $key;
    }
    return $map;
}

function productBulkCsrfToken()
{
    if (empty($_SESSION['product_bulk_csrf'])) {
        $_SESSION['product_bulk_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['product_bulk_csrf'];
}

function productBulkCheckCsrf()
{
    return !empty($_POST['product_bulk_csrf']) && !empty($_SESSION['product_bulk_csrf'])
        && hash_equals($_SESSION['product_bulk_csrf'], (string) $_POST['product_bulk_csrf']);
}

function productBulkImageUploadDirectory()
{
    return rtrim(ROOT, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR;
}

function productBulkImageRemovePreviewDirectory($path)
{
    $root = realpath(sys_get_temp_dir());
    $resolved = is_dir($path) ? realpath($path) : false;
    if(!$root || !$resolved || dirname($resolved) !== $root || strpos(basename($resolved), 'pts-product-images-') !== 0) return;
    foreach(scandir($resolved) as $name) {
        if($name === '.' || $name === '..') continue;
        $file = $resolved.DIRECTORY_SEPARATOR.$name;
        if(is_file($file)) @unlink($file);
    }
    @rmdir($resolved);
}

function productBulkImageValidateFile($path, $extension, $declaredSize = 0)
{
    global $config;
    $allowed = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
    if(!isset($allowed[$extension])) return 'Chỉ hỗ trợ JPG, PNG hoặc WEBP.';
    $size = is_file($path) ? filesize($path) : 0;
    if($size <= 0 || $size > 5 * 1024 * 1024 || ($declaredSize > 0 && $size !== (int)$declaredSize)) return 'Ảnh rỗng, vượt 5 MB hoặc dữ liệu ZIP không đầy đủ.';
    $image = @getimagesize($path);
    if(!$image || empty($image['mime']) || strtolower($image['mime']) !== $allowed[$extension]) return 'Nội dung ảnh không khớp phần mở rộng.';
    if(empty($image[0]) || empty($image[1]) || $image[0] > 12000 || $image[1] > 12000 || $image[0] * $image[1] > 24000000) return 'Kích thước ảnh không hợp lệ hoặc vượt giới hạn xử lý.';
    $maxWidth = !empty($config['website']['upload']['max-width']) ? (int)$config['website']['upload']['max-width'] : 1920;
    $maxHeight = !empty($config['website']['upload']['max-height']) ? (int)$config['website']['upload']['max-height'] : 1920;
    if($extension === 'webp' && ($image[0] > $maxWidth || $image[1] > $maxHeight)) return 'Ảnh WEBP phải không quá '.$maxWidth.'×'.$maxHeight.' px.';
    return '';
}

function productBulkImageFinalizeFile($path, $extension)
{
    global $func, $config;
    if($extension === 'jpg' || $extension === 'jpeg') $func->correctImageOrientation($path);
    $image = @getimagesize($path);
    if(!$image) return false;
    $maxWidth = !empty($config['website']['upload']['max-width']) ? (int)$config['website']['upload']['max-width'] : 1920;
    $maxHeight = !empty($config['website']['upload']['max-height']) ? (int)$config['website']['upload']['max-height'] : 1920;
    if($image[0] <= $maxWidth && $image[1] <= $maxHeight) return true;
    // Bộ resize legacy chưa ghi được WEBP; từ chối ảnh WEBP quá cỡ thay vì đổi định dạng ngầm.
    if($extension === 'webp') return false;
    return $func->smartResizeImage($path, null, $maxWidth, $maxHeight, true);
}

function productBulkImageEntryIdentity($entry)
{
    $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
    $stem = trim(pathinfo($entry, PATHINFO_FILENAME));
    $code = $stem;
    $sequence = 1;
    if(preg_match('/^(.*)-(\d{2,3})$/u', $stem, $match)) {
        $code = trim($match[1]);
        $sequence = (int)$match[2];
    }
    return array(
        'code' => $code,
        'sequence' => $sequence,
        'role' => $sequence === 1 ? 'main' : 'gallery',
        'extension' => $extension,
        'filename' => $code.'-'.str_pad((string)$sequence, 2, '0', STR_PAD_LEFT).'.'.$extension
    );
}

function productBulkReadImageArchive($file, $type)
{
    global $d;
    $result = array('rows' => array(), 'errors' => array(), 'counts' => array('ready' => 0, 'ready_main' => 0, 'ready_gallery' => 0, 'existing' => 0, 'missing_product' => 0));
    if(!class_exists('ZipArchive')) {
        $result['errors'][] = 'Máy chủ chưa bật ZipArchive.';
        return $result;
    }
    if(empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['errors'][] = 'Không nhận được file ZIP hoặc file upload bị lỗi.';
        return $result;
    }
    if((int)$file['size'] <= 0 || (int)$file['size'] > 20 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
        $result['errors'][] = 'File phải là ZIP từ 1 byte đến 20 MB.';
        return $result;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if(!in_array($mime, array('application/zip', 'application/x-zip-compressed', 'application/octet-stream'), true)) {
        $result['errors'][] = 'Nội dung file không phải ZIP hợp lệ.';
        return $result;
    }
    $zip = new ZipArchive();
    if($zip->open($file['tmp_name']) !== true) {
        $result['errors'][] = 'Không thể mở file ZIP.';
        return $result;
    }
    if($zip->numFiles < 1 || $zip->numFiles > 200) {
        $result['errors'][] = 'ZIP phải chứa từ 1 đến 200 ảnh.';
        $zip->close();
        return $result;
    }
    $previewDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-product-images-'.bin2hex(random_bytes(16));
    if(!mkdir($previewDir, 0700)) {
        $result['errors'][] = 'Không thể tạo vùng xem trước an toàn.';
        $zip->close();
        return $result;
    }
    $products = array();
    foreach($d->rawQuery('select id, code, namevi, photo, date_updated from #_product where type = ?', array($type)) as $product) {
        $key = mb_strtolower(trim((string)$product['code']), 'UTF-8');
        if($key !== '') $products[$key] = $product;
    }
    $existingGallery = array();
    foreach($d->rawQuery('select id_parent, photo from #_gallery where com = ? and type = ? and kind = ? and val = ?', array('product', $type, 'man', $type)) as $gallery) {
        $existingGallery[(int)$gallery['id_parent'].'|'.mb_strtolower(trim((string)$gallery['photo']), 'UTF-8')] = true;
    }
    $seen = array();
    $totalSize = 0;
    for($index = 0; $index < $zip->numFiles; $index++) {
        $stat = $zip->statIndex($index);
        $entry = isset($stat['name']) ? (string)$stat['name'] : '';
        if($entry === '' || substr($entry, -1) === '/') continue;
        if(basename($entry) !== $entry || strpos($entry, "\0") !== false) {
            $result['errors'][] = 'ZIP không được chứa thư mục hoặc đường dẫn lồng nhau: '.$entry;
            continue;
        }
        $identity = productBulkImageEntryIdentity($entry);
        $extension = $identity['extension'];
        $code = $identity['code'];
        $key = mb_strtolower($code, 'UTF-8');
        $seenKey = $key.'|'.$identity['sequence'];
        $size = !empty($stat['size']) ? (int)$stat['size'] : 0;
        $totalSize += $size;
        if($totalSize > 100 * 1024 * 1024) {
            $result['errors'][] = 'Tổng dung lượng giải nén vượt 100 MB.';
            break;
        }
        $row = array_merge($identity, array('entry' => $entry, 'status' => 'error', 'message' => '', 'product' => null, 'path' => ''));
        if($code === '' || $identity['sequence'] < 1 || isset($seen[$seenKey])) {
            $row['message'] = $code === '' ? 'Tên file phải có mã sản phẩm.' : 'Mã và số thứ tự ảnh bị lặp trong ZIP.';
            $result['errors'][] = $entry.': '.$row['message'];
            $result['rows'][] = $row;
            continue;
        }
        $seen[$seenKey] = true;
        $target = $previewDir.DIRECTORY_SEPARATOR.str_pad((string)$index, 4, '0', STR_PAD_LEFT).'.'.$extension;
        $stream = $zip->getStream($entry);
        $output = $stream ? fopen($target, 'xb') : false;
        if(!$stream || !$output) {
            if($stream) fclose($stream);
            if($output) fclose($output);
            $row['message'] = 'Không thể đọc ảnh trong ZIP.';
            $result['errors'][] = $entry.': '.$row['message'];
            $result['rows'][] = $row;
            continue;
        }
        stream_copy_to_stream($stream, $output, 5 * 1024 * 1024 + 1);
        fclose($stream);
        fclose($output);
        $validationError = productBulkImageValidateFile($target, $extension, $size);
        if($validationError !== '') {
            @unlink($target);
            $row['message'] = $validationError;
            $result['errors'][] = $entry.': '.$validationError;
        } elseif(empty($products[$key])) {
            @unlink($target);
            $row['status'] = 'missing_product';
            $row['message'] = 'Không tìm thấy mã sản phẩm.';
            $result['counts']['missing_product']++;
        } else {
            $row['product'] = $products[$key];
            $galleryKey = (int)$products[$key]['id'].'|'.mb_strtolower($identity['filename'], 'UTF-8');
            if($identity['role'] === 'main' && trim((string)$products[$key]['photo']) !== '') {
                @unlink($target);
                $row['status'] = 'existing';
                $row['message'] = 'Sản phẩm đã có ảnh đại diện; hệ thống sẽ không thay thế.';
                $result['counts']['existing']++;
            } elseif($identity['role'] === 'gallery' && isset($existingGallery[$galleryKey])) {
                @unlink($target);
                $row['status'] = 'existing';
                $row['message'] = 'Ảnh phụ cùng tên đã có trong album.';
                $result['counts']['existing']++;
            } else {
                $row['status'] = 'ready';
                $row['message'] = $identity['role'] === 'main' ? 'Sẵn sàng bổ sung ảnh đại diện.' : 'Sẵn sàng thêm vào album ảnh phụ.';
                $row['path'] = $target;
                $result['counts']['ready']++;
                $result['counts'][$identity['role'] === 'main' ? 'ready_main' : 'ready_gallery']++;
            }
        }
        $result['rows'][] = $row;
    }
    $zip->close();
    if(!empty($result['errors']) || $result['counts']['ready'] === 0) {
        if($result['counts']['ready'] === 0 && empty($result['errors'])) $result['errors'][] = 'Không có ảnh nào đủ điều kiện bổ sung.';
        productBulkImageRemovePreviewDirectory($previewDir);
        $result['preview_dir'] = '';
    } else $result['preview_dir'] = $previewDir;
    return $result;
}

function productBulkImagePreview()
{
    global $type, $bulkImagePreview, $bulkMessage;
    $bulkImagePreview = null;
    if(!productBulkCheckCsrf()) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        return;
    }
    $bulkImagePreview = productBulkReadImageArchive(isset($_FILES['product_bulk_images']) ? $_FILES['product_bulk_images'] : null, $type);
    if(!empty($bulkImagePreview['errors'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'ZIP còn lỗi nên chưa thể xác nhận.');
        return;
    }
    $token = bin2hex(random_bytes(24));
    $payloadRows = array();
    foreach($bulkImagePreview['rows'] as $row) if($row['status'] === 'ready') {
        $payloadRows[] = array('id' => (int)$row['product']['id'], 'code' => $row['code'], 'name' => $row['product']['namevi'], 'date_updated' => (int)$row['product']['date_updated'], 'extension' => $row['extension'], 'path' => $row['path'], 'sequence' => (int)$row['sequence'], 'role' => $row['role'], 'filename' => $row['filename']);
    }
    $_SESSION['product_bulk_image_preview'][$token] = array('type' => $type, 'created_at' => time(), 'directory' => $bulkImagePreview['preview_dir'], 'rows' => $payloadRows);
    $bulkImagePreview['token'] = $token;
}

function productBulkImageCommit()
{
    global $d, $cache, $func, $type, $bulkMessage, $bulkImageResult;
    if(!productBulkCheckCsrf() || empty($_POST['confirm_bulk_images'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Phiên làm việc hoặc xác nhận bổ sung ảnh không hợp lệ.');
        return;
    }
    $token = isset($_POST['image_preview_token']) ? preg_replace('/[^a-f0-9]/', '', $_POST['image_preview_token']) : '';
    $state = !empty($_SESSION['product_bulk_image_preview'][$token]) ? $_SESSION['product_bulk_image_preview'][$token] : null;
    if(!$state || $state['type'] !== $type || time() - (int)$state['created_at'] > 1800 || !is_dir($state['directory'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Bản xem trước ảnh đã hết hạn hoặc không hợp lệ.');
        return;
    }
    $uploadDir = productBulkImageUploadDirectory();
    $createdFiles = array();
    $updatedRows = array();
    $updatedProductIds = array();
    $insertedGalleryIds = array();
    try {
        foreach($state['rows'] as $row) {
            $current = $d->rawQueryOne('select id, code, photo, date_updated from #_product where id = ? and type = ? limit 0,1', array((int)$row['id'], $type));
            $role = !empty($row['role']) ? $row['role'] : 'main';
            $sequence = !empty($row['sequence']) ? (int)$row['sequence'] : 1;
            $filename = !empty($row['filename']) ? $row['filename'] : $row['code'].'-'.str_pad((string)$sequence, 2, '0', STR_PAD_LEFT).'.'.$row['extension'];
            $updatedInThisBatch = isset($updatedProductIds[(int)$row['id']]);
            if(empty($current) || trim((string)$current['code']) !== $row['code'] || (!$updatedInThisBatch && (int)$current['date_updated'] !== (int)$row['date_updated'])) throw new Exception('Sản phẩm mã '.$row['code'].' đã thay đổi sau bước xem trước');
            if($role === 'main' && trim((string)$current['photo']) !== '') throw new Exception('Sản phẩm mã '.$row['code'].' đã có ảnh đại diện sau bước xem trước');
            if($role === 'gallery' && $d->rawQueryOne('select id from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? and photo = ? limit 0,1', array((int)$row['id'], 'product', $type, 'man', $type, $filename))) throw new Exception('Ảnh phụ '.$filename.' đã có sau bước xem trước');
            $validationError = productBulkImageValidateFile($row['path'], $row['extension']);
            if($validationError !== '') throw new Exception('Ảnh của mã '.$row['code'].' không còn hợp lệ');
            if($filename === '' || basename($filename) !== $filename || file_exists($uploadDir.$filename)) throw new Exception('Tên ảnh '.$filename.' không an toàn hoặc đã tồn tại');
            if(!copy($row['path'], $uploadDir.$filename)) throw new Exception('Không thể lưu ảnh cho mã '.$row['code']);
            @chmod($uploadDir.$filename, 0644);
            $createdFiles[] = $uploadDir.$filename;
            if(!productBulkImageFinalizeFile($uploadDir.$filename, $row['extension'])) throw new Exception('Không thể chuẩn hóa kích thước ảnh cho mã '.$row['code']);
            $finalValidation = productBulkImageValidateFile($uploadDir.$filename, $row['extension']);
            if($finalValidation !== '') throw new Exception('Ảnh '.$filename.' không đạt giới hạn sau khi chuẩn hóa: '.$finalValidation);
            if($role === 'main') {
                $d->where('id', (int)$row['id']);
                $d->where('type', $type);
                $d->where('photo', '');
                if(!$d->update('product', array('photo' => $filename, 'date_updated' => time()))) throw new Exception('Không thể cập nhật ảnh cho mã '.$row['code']);
                $updatedRows[] = array('id' => (int)$row['id'], 'date_updated' => (int)$row['date_updated']);
                $updatedProductIds[(int)$row['id']] = true;
            } else {
                if(!$d->insert('gallery', array('id_parent'=>(int)$row['id'], 'photo'=>$filename, 'namevi'=>'', 'numb'=>$sequence, 'type'=>$type, 'com'=>'product', 'kind'=>'man', 'val'=>$type, 'status'=>'hienthi', 'date_created'=>time(), 'date_updated'=>0))) throw new Exception('Không thể thêm ảnh phụ cho mã '.$row['code']);
                $galleryId = (int)$d->getLastInsertId();
                if(!$galleryId) throw new Exception('Không xác định được ảnh phụ vừa thêm cho mã '.$row['code']);
                $insertedGalleryIds[] = $galleryId;
            }
        }
        $cache->delete();
        $bulkImageResult = array('updated' => count($updatedRows), 'gallery' => count($insertedGalleryIds));
        $bulkMessage = array('type' => 'success', 'text' => 'Đã bổ sung '.count($updatedRows).' ảnh đại diện và '.count($insertedGalleryIds).' ảnh phụ; không thay hoặc xóa ảnh cũ.');
        productBulkImageRemovePreviewDirectory($state['directory']);
        unset($_SESSION['product_bulk_image_preview'][$token]);
    } catch(Exception $e) {
        foreach($insertedGalleryIds as $galleryId) $d->rawQuery('delete from #_gallery where id = ?', array($galleryId));
        foreach($updatedRows as $updatedRow) {
            $d->where('id', $updatedRow['id']);
            $d->where('type', $type);
            $d->update('product', array('photo' => '', 'date_updated' => $updatedRow['date_updated']));
        }
        foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
        $bulkMessage = array('type' => 'danger', 'text' => $e->getMessage().'. Các ảnh trong lượt này đã được phục hồi.');
    }
}

function productBulkNormalizeAction($value)
{
    $value = mb_strtolower(trim((string) $value), 'UTF-8');
    $map = array(
        'add' => 'create', 'them' => 'create', 'tạo' => 'create', 'tạo mới' => 'create',
        'edit' => 'update', 'sua' => 'update', 'sửa' => 'update', 'cập nhật' => 'update',
        'xoa' => 'archive', 'xóa' => 'archive', 'delete' => 'archive', 'ẩn' => 'archive', 'ẩn sản phẩm' => 'archive',
        'boqua' => 'skip', 'bỏ qua' => 'skip', 'không nhập' => 'skip'
    );
    return isset($map[$value]) ? $map[$value] : $value;
}

function productBulkParseSpecLines($value)
{
    $result = array('specs' => array(), 'errors' => array());
    $labels = array();
    foreach(preg_split('/\R/u', (string) $value) as $lineNumber => $line) {
        if(trim($line) === '') continue;
        $parts = explode('|', $line, 2);
        if(count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            $result['errors'][] = 'Dòng '.($lineNumber + 1).' phải theo mẫu: Tên thông số | Giá trị';
            continue;
        }
        $label = trim(strip_tags($parts[0]));
        $value = trim(strip_tags($parts[1]));
        $key = mb_strtolower(preg_replace('/\s+/u', ' ', $label), 'UTF-8');
        if(isset($labels[$key])) {
            $result['errors'][] = 'Tên thông số bị trùng: '.$label;
            continue;
        }
        $labels[$key] = true;
        $result['specs'][] = array('label' => $label, 'value' => $value);
    }
    return $result;
}

function productBulkDatabaseColumns()
{
    global $d;
    static $columns = null;
    if ($columns !== null) return $columns;
    $columns = array();
    foreach ($d->rawQuery('DESCRIBE #_product') as $row) $columns[$row['Field']] = true;
    return $columns;
}

function productBulkCategoryMaps($type)
{
    global $d;
    $maps = array('list' => array(), 'cat' => array(), 'brand' => array());
    foreach ($d->rawQuery('select id, namevi, slugvi from #_product_list where type = ? order by numb,id', array($type)) as $row) {
        $maps['list'][(int) $row['id']] = $row;
    }
    foreach ($d->rawQuery('select id, id_list, namevi, slugvi from #_product_cat where type = ? order by numb,id', array($type)) as $row) {
        $maps['cat'][(int) $row['id']] = $row;
    }
    foreach ($d->rawQuery('select id, namevi, slugvi from #_product_brand where type = ? order by numb,id', array($type)) as $row) {
        $maps['brand'][(int) $row['id']] = $row;
    }
    return $maps;
}

function productBulkProductMaps($type)
{
    global $d;
    $maps = array('id' => array(), 'code' => array());
    foreach ($d->rawQuery('select * from #_product where type = ?', array($type)) as $row) {
        $maps['id'][(int) $row['id']] = $row;
        $code = strtolower(trim((string) $row['code']));
        if ($code !== '') $maps['code'][$code] = $row;
    }
    return $maps;
}

function productBulkSlugOwners()
{
    global $d;
    $owners = array();
    $tables = array('product_list', 'product_cat', 'product_item', 'product_sub', 'product_brand', 'product', 'news_list', 'news_cat', 'news_item', 'news_sub', 'news', 'tags');
    foreach ($tables as $table) {
        foreach ($d->rawQuery('select id, slugvi, slugen from #_'.$table) as $row) {
            foreach (array('slugvi', 'slugen') as $field) {
                $slug = strtolower(trim((string) $row[$field]));
                if ($slug !== '') $owners[$slug][] = array('table' => $table, 'id' => (int) $row['id']);
            }
        }
    }
    return $owners;
}

function productBulkCellValue($value)
{
    if (is_null($value)) return '';
    $value = trim((string) $value);
    if (strlen($value) > 1 && $value[0] === "'" && in_array($value[1], array('=', '+', '-', '@'), true)) {
        $value = substr($value, 1);
    }
    return $value;
}

function productBulkReadUpload($file)
{
    $result = array('headers' => array(), 'rows' => array(), 'baseline' => array(), 'errors' => array());
    if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['errors'][] = 'Không nhận được file hoặc file upload bị lỗi.';
        return $result;
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
        $result['errors'][] = 'File phải có dung lượng từ 1 byte đến 5 MB.';
        return $result;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('csv', 'xls', 'xlsx'), true)) {
        $result['errors'][] = 'Chỉ hỗ trợ file .csv, .xls hoặc .xlsx.';
        return $result;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = array(
        'csv' => array('text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'),
        'xls' => array('application/vnd.ms-excel', 'application/x-ole-storage', 'application/octet-stream'),
        'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream')
    );
    if (!in_array($mime, $allowedMimes[$ext], true)) {
        $result['errors'][] = 'Nội dung file không khớp với phần mở rộng (MIME: '.htmlspecialchars($mime).').';
        return $result;
    }
    $rawRows = array();
    if ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            $result['errors'][] = 'Không thể đọc file CSV.';
            return $result;
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $counts = array(',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t"));
        arsort($counts);
        $delimiter = key($counts);
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) $rawRows[] = $cells;
        fclose($handle);
    } else {
        require_once LIBRARIES.'PHPExcel.php';
        require_once LIBRARIES.'PHPExcel/IOFactory.php';
        try {
            $reader = PHPExcel_IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            $excel = $reader->load($file['tmp_name']);
            $sheet = $excel->getSheet(0);
            $highestRow = min((int) $sheet->getHighestRow(), 502);
            $highestColumn = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
            for ($row = 1; $row <= $highestRow; $row++) {
                $cells = array();
                for ($column = 0; $column < $highestColumn; $column++) {
                    $cells[] = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                }
                $rawRows[] = $cells;
            }
            $systemSheet = $excel->getSheetByName('_HeThong');
            if ($systemSheet) {
                $systemHighestRow = min((int) $systemSheet->getHighestRow(), 502);
                $systemHighestColumn = PHPExcel_Cell::columnIndexFromString($systemSheet->getHighestColumn());
                $systemHeaders = array();
                for ($column = 0; $column < $systemHighestColumn; $column++) {
                    $key = trim((string) $systemSheet->getCellByColumnAndRow($column, 1)->getValue());
                    if ($key !== '') $systemHeaders[$column] = $key;
                }
                for ($row = 2; $row <= $systemHighestRow; $row++) {
                    $baselineRow = array();
                    foreach ($systemHeaders as $column => $key) {
                        $baselineRow[$key] = (string) $systemSheet->getCellByColumnAndRow($column, $row)->getValue();
                    }
                    $baselineId = !empty($baselineRow['id']) ? (int) $baselineRow['id'] : 0;
                    if ($baselineId > 0) $result['baseline'][$baselineId] = $baselineRow;
                }
            }
            $excel->disconnectWorksheets();
            unset($excel);
        } catch (Throwable $e) {
            $result['errors'][] = 'Không thể đọc file Excel: '.$e->getMessage();
            return $result;
        }
    }
    if (empty($rawRows)) {
        $result['errors'][] = 'File không có dữ liệu.';
        return $result;
    }
    $headers = array();
    $headerMap = productBulkHeaderMap();
    foreach ($rawRows[0] as $index => $header) {
        $displayHeader = preg_replace('/\s+/u', ' ', trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)));
        if ($displayHeader === '') continue;
        $normalizedHeader = productBulkNormalizeHeader($displayHeader);
        if (!isset($headerMap[$normalizedHeader])) {
            $result['errors'][] = 'Cột không được hỗ trợ: '.$displayHeader;
            continue;
        }
        $key = $headerMap[$normalizedHeader];
        if (isset($headers[$key])) $result['errors'][] = 'Tiêu đề cột bị lặp: '.$displayHeader;
        $headers[$key] = $index;
    }
    if (!isset($headers['id']) && !isset($headers['code'])) $result['errors'][] = 'File phải có cột ID sản phẩm hoặc Mã sản phẩm.';
    if (!empty($result['errors'])) return $result;
    $result['headers'] = array_keys($headers);
    $limit = min(count($rawRows), 501);
    for ($rowIndex = 1; $rowIndex < $limit; $rowIndex++) {
        $row = array('_row_number' => $rowIndex + 1);
        $hasValue = false;
        foreach ($headers as $header => $columnIndex) {
            $value = isset($rawRows[$rowIndex][$columnIndex]) ? productBulkCellValue($rawRows[$rowIndex][$columnIndex]) : '';
            $row[$header] = $value;
            if ($value !== '') $hasValue = true;
        }
        if ($hasValue) $result['rows'][] = $row;
    }
    if (count($rawRows) > 501) $result['errors'][] = 'Mỗi lần chỉ được xử lý tối đa 500 dòng dữ liệu.';
    if (empty($result['rows'])) $result['errors'][] = 'File không có dòng sản phẩm.';
    return $result;
}

function productBulkFindCategoryIdByName($rows, $name)
{
    $name = mb_strtolower(trim((string) $name), 'UTF-8');
    if ($name === '') return 0;
    $matches = array();
    foreach ($rows as $id => $row) {
        if (mb_strtolower(trim((string) $row['namevi']), 'UTF-8') === $name) $matches[] = (int) $id;
    }
    return count($matches) === 1 ? $matches[0] : (count($matches) > 1 ? -1 : 0);
}

function productBulkBrandKey($name)
{
    $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $name)), 'UTF-8');
    return hash('sha256', $normalized);
}

function productBulkBrandChoicesFromPost()
{
    $choices = array();
    $posted = !empty($_POST['brand_resolution']) && is_array($_POST['brand_resolution']) ? $_POST['brand_resolution'] : array();
    foreach ($posted as $key => $choice) {
        if (!preg_match('/^[a-f0-9]{64}$/', (string) $key)) continue;
        $choice = trim((string) $choice);
        if ($choice === 'create' || $choice === 'blank' || preg_match('/^existing:[1-9][0-9]*$/', $choice)) $choices[$key] = $choice;
    }
    return $choices;
}

function productBulkProductChoicesFromPost()
{
    $choices = array();
    $posted = !empty($_POST['product_resolution']) && is_array($_POST['product_resolution']) ? $_POST['product_resolution'] : array();
    foreach ($posted as $key => $choice) {
        if (!preg_match('/^[a-f0-9]{64}$/', (string) $key)) continue;
        $choice = trim((string) $choice);
        if ($choice === 'create' || $choice === 'skip' || preg_match('/^update:[1-9][0-9]*$/', $choice)) $choices[$key] = $choice;
    }
    return $choices;
}

function productBulkProductMatchKey($row, $slug, $productId)
{
    return hash('sha256', (int) $row.'|'.strtolower(trim((string) $slug)).'|'.(int) $productId);
}

function productBulkUniqueSlug($baseSlug, $slugOwners, $seenSlugs = array())
{
    $baseSlug = strtolower(trim((string) $baseSlug));
    $slug = $baseSlug;
    $suffix = 1;
    while (!empty($slugOwners[$slug]) || isset($seenSlugs[$slug])) {
        $suffix++;
        $slug = $baseSlug.'-'.$suffix;
    }
    return $slug;
}

function productBulkHtmlToText($value)
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if(strpos($value, '&lt;') !== false) $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('~<br\s*/?>~iu', "\n", $value);
    $value = preg_replace('~</(?:p|div|h[1-6]|tr|li)>~iu', "\n", $value);
    $value = preg_replace('~<li\b[^>]*>~iu', '- ', $value);
    $value = strip_tags($value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lines = array();
    foreach(preg_split('/\R/u', $value) as $line) {
        $line = trim(preg_replace('/[\t ]+/u', ' ', $line));
        if($line !== '') $lines[] = $line;
    }
    return implode("\n", $lines);
}

function productBulkComparableValue($value, $kind = '')
{
    if ($value === null) return '';
    $value = trim(htmlspecialchars_decode((string) $value, ENT_QUOTES));
    if ($kind === 'html') return productBulkHtmlToText($value);
    if ($kind === 'status') {
        $statuses = array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
        sort($statuses, SORT_STRING);
        return implode(',', $statuses);
    }
    if ($kind === 'spec_lines' && $value !== '') {
        $specs = json_decode($value, true);
        if (is_array($specs)) {
            $canonical = array();
            $labelCounts = array();
            foreach ($specs as $spec) {
                if (empty($spec['label']) || !isset($spec['value'])) continue;
                $label = trim((string) $spec['label']);
                $specKey = mb_strtolower(preg_replace('/\s+/u', ' ', $label), 'UTF-8');
                if ($specKey === '') continue;
                $labelCounts[$specKey] = isset($labelCounts[$specKey]) ? $labelCounts[$specKey] + 1 : 1;
                if($labelCounts[$specKey] > 1) $label .= ' ('.$labelCounts[$specKey].')';
                $canonical[] = array('label' => $label, 'value' => trim((string) $spec['value']));
            }
            return json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    if (in_array($kind, array('money', 'decimal', 'integer', 'category'), true) && $value !== '' && is_numeric($value)) {
        return rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');
    }
    return $value;
}

function productBulkValidateRows($rawRows, $type, $baseline = array(), $overwriteChoices = array(), $brandChoices = array(), $requireBrandChoices = false, $productChoices = array(), $requireProductChoices = false)
{
    global $func, $config;
    $result = array('rows' => array(), 'errors' => 0, 'warnings' => 0, 'conflicts' => 0, 'new_brands' => array(), 'product_matches' => array(), 'available_brands' => array(), 'counts' => array('create' => 0, 'update' => 0, 'archive' => 0, 'skip' => 0, 'unchanged' => 0, 'draft_create' => 0, 'hide_update' => 0, 'show_update' => 0));
    $products = productBulkProductMaps($type);
    $categories = productBulkCategoryMaps($type);
    $result['available_brands'] = $categories['brand'];
    $slugOwners = productBulkSlugOwners();
    $databaseColumns = productBulkDatabaseColumns();
    $columns = productBulkColumns();
    $seenIds = array();
    $seenCodes = array();
    $seenSlugs = array();
    $allowedStatus = !empty($config['product'][$type]['check']) ? array_keys($config['product'][$type]['check']) : array('hienthi');
    foreach ($rawRows as $raw) {
        $requestedAction = productBulkNormalizeAction(isset($raw['action']) ? $raw['action'] : '');
        $item = array('row' => (int) $raw['_row_number'], 'action' => $requestedAction, 'id' => 0, 'code' => '', 'name' => '', 'data' => array(), 'sections_raw' => '', 'existing' => null, 'pending_brand_key' => '', 'pending_product_key' => '', 'errors' => array(), 'warnings' => array(), 'conflicts' => array(), 'changes' => array());
        if ($item['action'] === '') $item['action'] = 'auto';
        if (!in_array($item['action'], array('auto', 'create', 'update', 'upsert', 'archive', 'skip'), true)) {
            $item['errors'][] = 'Thao tác không hợp lệ.';
        }
        if ($item['action'] === 'skip') {
            $result['counts']['skip']++;
            $result['rows'][] = $item;
            continue;
        }
        $rawId = isset($raw['id']) ? trim((string) $raw['id']) : '';
        $id = $rawId !== '' ? filter_var($rawId, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : false;
        if ($rawId !== '' && $id === false) $item['errors'][] = 'ID không hợp lệ.';
        $item['id'] = $id ? (int) $id : 0;
        $code = isset($raw['code']) ? trim((string) $raw['code']) : '';
        $item['code'] = $code;
        $codeKey = strtolower($code);
        if ($item['id'] > 0) {
            if (isset($seenIds[$item['id']])) $item['errors'][] = 'ID bị lặp trong file (đã có ở dòng '.$seenIds[$item['id']].').';
            $seenIds[$item['id']] = $item['row'];
        }
        if ($codeKey !== '') {
            if (isset($seenCodes[$codeKey])) $item['errors'][] = 'Mã sản phẩm bị lặp trong file (đã có ở dòng '.$seenCodes[$codeKey].').';
            $seenCodes[$codeKey] = $item['row'];
        }
        if ($item['action'] === 'create' && $item['id'] > 0) $item['errors'][] = 'Dòng tạo mới không được nhập ID.';
        if (in_array($item['action'], array('update', 'archive'), true) && $item['id'] <= 0) $item['errors'][] = 'Dòng update/archive bắt buộc có ID để tránh cập nhật nhầm.';
        $existingById = $item['id'] > 0 && isset($products['id'][$item['id']]) ? $products['id'][$item['id']] : null;
        $existingByCode = $codeKey !== '' && isset($products['code'][$codeKey]) ? $products['code'][$codeKey] : null;
        if ($existingById && $existingByCode && (int) $existingById['id'] !== (int) $existingByCode['id']) {
            $item['errors'][] = 'ID và mã sản phẩm đang chỉ đến hai sản phẩm khác nhau.';
        }
        $existing = $existingById ?: $existingByCode;
        $candidateSlug = !empty($raw['slugvi']) ? $func->changeTitle($raw['slugvi']) : (!empty($raw['namevi']) ? $func->changeTitle($raw['namevi']) : '');
        if (!$existing && $candidateSlug !== '' && !empty($slugOwners[$candidateSlug]) && in_array($item['action'], array('auto', 'create', 'upsert'), true)) {
            $matchedProductIds = array();
            $hasOtherOwner = false;
            foreach ($slugOwners[$candidateSlug] as $owner) {
                if ($owner['table'] === 'product') $matchedProductIds[(int) $owner['id']] = true;
                else $hasOtherOwner = true;
            }
            if (!$hasOtherOwner && count($matchedProductIds) === 1) {
                $matchedProductId = (int) key($matchedProductIds);
                if (!empty($products['id'][$matchedProductId])) {
                    $matchedProduct = $products['id'][$matchedProductId];
                    $matchKey = productBulkProductMatchKey($item['row'], $candidateSlug, $matchedProductId);
                    $result['product_matches'][$matchKey] = array('key' => $matchKey, 'row' => $item['row'], 'slug' => $candidateSlug, 'incoming_code' => $code, 'incoming_name' => isset($raw['namevi']) ? trim((string) $raw['namevi']) : '', 'existing_id' => $matchedProductId, 'existing_code' => (string) $matchedProduct['code'], 'existing_name' => (string) $matchedProduct['namevi']);
                    $choice = isset($productChoices[$matchKey]) ? $productChoices[$matchKey] : '';
                    if ($choice === 'update:'.$matchedProductId) {
                        $existing = $matchedProduct;
                        $item['id'] = $matchedProductId;
                        $item['action'] = 'update';
                    } elseif ($choice === 'create') {
                        $raw['slugvi'] = productBulkUniqueSlug($candidateSlug, $slugOwners, $seenSlugs);
                    } elseif ($choice === 'skip') {
                        $item['action'] = 'skip';
                        $item['name'] = isset($raw['namevi']) ? trim((string) $raw['namevi']) : '';
                        $result['counts']['skip']++;
                        $result['rows'][] = $item;
                        continue;
                    } elseif ($requireProductChoices) {
                        $item['errors'][] = 'Chưa chọn cách xử lý sản phẩm trùng slug “'.$candidateSlug.'”.';
                        $item['pending_product_key'] = $matchKey;
                    } else {
                        $item['pending_product_key'] = $matchKey;
                    }
                }
            }
        }
        if ($item['action'] === 'auto') {
            $item['action'] = $existing ? 'update' : 'create';
        }
        if (!$existing && $item['action'] === 'upsert' && $existingByCode) $existing = $existingByCode;
        if (in_array($item['action'], array('update', 'archive'), true) && !$existing) $item['errors'][] = 'Không tìm thấy sản phẩm thuộc đúng loại với ID này.';
        if ($item['action'] === 'create' && $existingByCode) $item['errors'][] = 'Mã sản phẩm đã tồn tại trong database.';
        if ($existing && $codeKey !== '' && isset($products['code'][$codeKey]) && (int) $products['code'][$codeKey]['id'] !== (int) $existing['id']) {
            $item['errors'][] = 'Mã sản phẩm đang thuộc về một sản phẩm khác.';
        }
        $item['existing'] = $existing;
        if ($existing) $item['id'] = (int) $existing['id'];
        if ($existing && empty($baseline) && !empty($raw['expected_updated_at']) && (int) $raw['expected_updated_at'] !== (int) $existing['date_updated']) {
            $item['errors'][] = 'Sản phẩm đã thay đổi sau khi export; hãy export lại trước khi cập nhật.';
        }
        if ($item['action'] === 'archive') {
            if ($existing) {
                $status = array_filter(explode(',', (string) $existing['status']));
                $status = array_values(array_diff($status, array('hienthi', 'banchay')));
                $item['data'] = array('status' => implode(',', $status), 'date_updated' => time());
                $item['name'] = $existing['namevi'];
                $result['counts']['archive']++;
            }
            $result['errors'] += count($item['errors']);
            $result['warnings'] += count($item['warnings']);
            $result['rows'][] = $item;
            continue;
        }
        $isCreate = $item['action'] === 'create' || ($item['action'] === 'upsert' && !$existing);
        $resolvedAction = $isCreate ? 'create' : 'update';
        $item['action'] = $resolvedAction;
        $hasBooleanStatus = false;
        $booleanStatusValues = array();
        foreach (array('is_visible', 'is_featured', 'requires_quote', 'is_promo') as $booleanStatusKey) {
            if (!array_key_exists($booleanStatusKey, $raw) || trim((string) $raw[$booleanStatusKey]) === '') continue;
            $booleanStatusValue = trim((string) $raw[$booleanStatusKey]);
            if (!in_array($booleanStatusValue, array('0', '1'), true)) {
                $item['errors'][] = productBulkColumns()[$booleanStatusKey]['label'].' chỉ nhận 1 hoặc 0.';
                continue;
            }
            $hasBooleanStatus = true;
            $booleanStatusValues[$booleanStatusKey] = $booleanStatusValue === '1';
        }
        if ($hasBooleanStatus) {
            $baseStatuses = $existing ? array_filter(array_map('trim', explode(',', (string) $existing['status']))) : array('hienthi');
            $visible = array_key_exists('is_visible', $booleanStatusValues) ? $booleanStatusValues['is_visible'] : in_array('hienthi', $baseStatuses, true);
            $featured = array_key_exists('is_featured', $booleanStatusValues) ? $booleanStatusValues['is_featured'] : in_array('banchay', $baseStatuses, true);
            $requiresQuote = array_key_exists('requires_quote', $booleanStatusValues) ? $booleanStatusValues['requires_quote'] : in_array('baogia', $baseStatuses, true);
            $promo = array_key_exists('is_promo', $booleanStatusValues) ? $booleanStatusValues['is_promo'] : in_array('khuyenmai', $baseStatuses, true);
            $raw['status'] = implode(',', array_filter(array($visible ? 'hienthi' : '', $featured ? 'banchay' : '', $requiresQuote ? 'baogia' : '', $promo ? 'khuyenmai' : '')));
        }
        foreach (array('_list_name' => array('list', 'id_list'), '_cat_name' => array('cat', 'id_cat'), '_brand_name' => array('brand', 'id_brand')) as $sourceKey => $definition) {
            if (!array_key_exists($sourceKey, $raw) || trim((string) $raw[$sourceKey]) === '') continue;
            $resolvedId = productBulkFindCategoryIdByName($categories[$definition[0]], $raw[$sourceKey]);
            if ($resolvedId === -1) $item['errors'][] = productBulkColumns()[$sourceKey]['label'].' bị trùng tên trong hệ thống; hãy chọn lại trong file mới.';
            elseif ($resolvedId === 0 && $sourceKey === '_brand_name') {
                $brandName = trim(preg_replace('/\s+/u', ' ', (string) $raw[$sourceKey]));
                $brandKey = productBulkBrandKey($brandName);
                if (!isset($result['new_brands'][$brandKey])) $result['new_brands'][$brandKey] = array('key' => $brandKey, 'name' => $brandName, 'rows' => array());
                $result['new_brands'][$brandKey]['rows'][] = $item['row'];
                $choice = isset($brandChoices[$brandKey]) ? $brandChoices[$brandKey] : '';
                if ($choice === 'create') {
                    $item['pending_brand_key'] = $brandKey;
                    $item['warnings'][] = 'Sẽ tạo thương hiệu mới “'.$brandName.'” khi xác nhận.';
                } elseif ($choice === 'blank') {
                    $raw['id_brand'] = 0;
                    $item['warnings'][] = 'Thương hiệu “'.$brandName.'” sẽ được bỏ trống theo lựa chọn.';
                } elseif (preg_match('/^existing:([1-9][0-9]*)$/', $choice, $match) && !empty($categories['brand'][(int) $match[1]])) {
                    $raw['id_brand'] = (int) $match[1];
                    $item['warnings'][] = 'Thương hiệu “'.$brandName.'” sẽ ghép vào “'.$categories['brand'][(int) $match[1]]['namevi'].'”.';
                } elseif ($requireBrandChoices) {
                    $item['errors'][] = 'Chưa chọn cách xử lý thương hiệu mới “'.$brandName.'”.';
                }
            }
            elseif ($resolvedId === 0) $item['errors'][] = productBulkColumns()[$sourceKey]['label'].' không tồn tại.';
            else $raw[$definition[1]] = $resolvedId;
        }
        if (array_key_exists('sectionsvi', $raw)) {
            $sv = trim((string) $raw['sectionsvi']);
            if ($sv === '__CLEAR__') $item['sections_raw'] = '__CLEAR__';
            elseif ($sv !== '') $item['sections_raw'] = $sv;
        }
        foreach ($columns as $key => $meta) {
            if (empty($meta['db']) || !isset($databaseColumns[$meta['db']]) || !array_key_exists($key, $raw)) continue;
            $value = trim((string) $raw[$key]);
            if (!$isCreate && $value === '') continue;
            if ($value === '__CLEAR__') {
                $item['data'][$meta['db']] = in_array($meta['kind'], array('money', 'decimal', 'integer', 'category'), true) ? 0 : null;
                continue;
            }
            if ($value === '' && $isCreate) continue;
            if (isset($meta['max']) && mb_strlen($value, 'UTF-8') > $meta['max']) $item['errors'][] = $meta['label'].' vượt quá '.$meta['max'].' ký tự.';
            if ($meta['kind'] === 'money') {
                $normalized = str_replace(array(',', ' '), '', $value);
                if ($normalized === '' || !is_numeric($normalized) || (float) $normalized < 0) $item['errors'][] = $meta['label'].' phải là số không âm.';
                else $item['data'][$meta['db']] = (float) $normalized;
            } elseif ($meta['kind'] === 'decimal') {
                $normalized = str_replace(',', '.', $value);
                if (!is_numeric($normalized) || (float) $normalized < 0 || (float) $normalized > 99999.9) $item['errors'][] = $meta['label'].' phải nằm trong khoảng 0–99999.9.';
                else $item['data'][$meta['db']] = (float) $normalized;
            } elseif ($meta['kind'] === 'integer') {
                if (filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) === false) $item['errors'][] = $meta['label'].' phải là số nguyên không âm.';
                else $item['data'][$meta['db']] = (int) $value;
            } elseif ($meta['kind'] === 'category') {
                if (filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) === false) $item['errors'][] = $meta['label'].' phải là ID hợp lệ.';
                else $item['data'][$meta['db']] = (int) $value;
            } elseif ($meta['kind'] === 'slug') {
                $item['data'][$meta['db']] = $func->changeTitle($value);
            } elseif ($meta['kind'] === 'status') {
                $statusMap = array('hiển thị' => 'hienthi', 'bán chạy' => 'banchay', 'tạm ẩn' => '', 'hienthi' => 'hienthi', 'banchay' => 'banchay');
                $statuses = array();
                foreach (array_map('trim', explode(',', $value)) as $statusValue) {
                    $normalizedStatus = mb_strtolower($statusValue, 'UTF-8');
                    $statuses[] = array_key_exists($normalizedStatus, $statusMap) ? $statusMap[$normalizedStatus] : $statusValue;
                }
                $statuses = array_values(array_unique(array_filter($statuses)));
                foreach ($statuses as $status) if (!in_array($status, $allowedStatus, true)) $item['errors'][] = 'Trạng thái không hỗ trợ: '.$status;
                $item['data'][$meta['db']] = implode(',', $statuses);
            } elseif ($meta['kind'] === 'spec_lines') {
                $parsedSpecs = productBulkParseSpecLines($value);
                foreach($parsedSpecs['errors'] as $specError) $item['errors'][] = $meta['label'].': '.$specError.'.';
                $item['data'][$meta['db']] = $parsedSpecs['specs'] ? json_encode($parsedSpecs['specs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            } elseif ($meta['kind'] === 'content_block' || $meta['kind'] === 'content_block_paired') {
                $parsedBlock = productContentBlockParse($value, $meta['kind'] === 'content_block_paired', $meta['limit']);
                foreach($parsedBlock['errors'] as $blockError) $item['errors'][] = $meta['label'].': '.$blockError;
                $item['data'][$meta['db']] = $parsedBlock['items'] ? json_encode($parsedBlock['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            } elseif ($meta['kind'] === 'image_filename') {
                if (!preg_match('/^[A-Za-z0-9_.-]+$/', $value)) $item['errors'][] = $meta['label'].' chỉ nhận tên file (không đường dẫn, không ký tự đặc biệt).';
                elseif (strtolower(pathinfo($value, PATHINFO_EXTENSION)) !== 'png') $item['errors'][] = $meta['label'].' chỉ nhận file PNG để giữ nền trong suốt.';
                elseif (!file_exists(UPLOAD_PRODUCT.$value)) $item['errors'][] = $meta['label'].': file "'.$value.'" chưa tồn tại trong upload/product — upload qua Quản lý file trước.';
                else $item['data'][$meta['db']] = $value;
            } elseif ($meta['kind'] === 'youtube') {
                if ($value !== '') {
                    $parts = parse_url($value);
                    $host = !empty($parts['host']) ? strtolower($parts['host']) : '';
                    $allowedHosts = array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com');
                    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($host, $allowedHosts, true)) $item['errors'][] = 'Video phải là URL YouTube hợp lệ.';
                    else $item['data'][$meta['db']] = $value;
                }
            } else {
                $item['data'][$meta['db']] = $value;
            }
        }
        $name = isset($item['data']['namevi']) ? $item['data']['namevi'] : ($existing ? $existing['namevi'] : '');
        $item['name'] = $name;
        if ($isCreate && trim($name) === '') $item['errors'][] = 'Dòng tạo mới bắt buộc có Tên sản phẩm.';
        if ($isCreate && $code === '') $item['errors'][] = 'Dòng tạo mới bắt buộc có Mã sản phẩm.';
        if ($isCreate && !isset($item['data']['slugvi']) && $name !== '') $item['data']['slugvi'] = $func->changeTitle($name);
        $slug = isset($item['data']['slugvi']) ? strtolower(trim($item['data']['slugvi'])) : ($existing ? strtolower(trim($existing['slugvi'])) : '');
        if ($slug === '') $item['errors'][] = 'Slug không được trống.';
        if ($slug !== '') {
            if (isset($seenSlugs[$slug])) $item['errors'][] = 'Slug bị lặp trong file (đã có ở dòng '.$seenSlugs[$slug].').';
            $seenSlugs[$slug] = $item['row'];
            if (!empty($slugOwners[$slug])) {
                foreach ($slugOwners[$slug] as $owner) {
                    if ($item['pending_product_key'] === '' && !($owner['table'] === 'product' && $existing && $owner['id'] === (int) $existing['id'])) {
                        $item['errors'][] = 'Slug đã được sử dụng trong hệ thống.';
                        break;
                    }
                }
            }
        }
        $effectiveList = isset($item['data']['id_list']) ? $item['data']['id_list'] : ($existing ? (int) $existing['id_list'] : 0);
        $effectiveCat = isset($item['data']['id_cat']) ? $item['data']['id_cat'] : ($existing ? (int) $existing['id_cat'] : 0);
        $effectiveBrand = isset($item['data']['id_brand']) ? $item['data']['id_brand'] : ($existing ? (int) $existing['id_brand'] : 0);
        /* Sản phẩm không có danh mục cấp 1 vẫn lưu được nhưng không xuất hiện ở
           bất kỳ trang danh mục nào và breadcrumb bị cụt -> chặn ngay khi tạo. */
        if ($isCreate && $effectiveList <= 0) $item['errors'][] = 'Dòng tạo mới bắt buộc có Danh mục cấp 1.';
        if ($effectiveList > 0 && empty($categories['list'][$effectiveList])) $item['errors'][] = 'ID danh mục cấp 1 không tồn tại.';
        if ($effectiveCat > 0 && empty($categories['cat'][$effectiveCat])) $item['errors'][] = 'ID danh mục cấp 2 không tồn tại.';
        if ($effectiveCat > 0 && !empty($categories['cat'][$effectiveCat]) && $effectiveList > 0 && (int) $categories['cat'][$effectiveCat]['id_list'] !== $effectiveList) $item['errors'][] = 'Danh mục cấp 2 không thuộc danh mục cấp 1 đã chọn.';
        if ($effectiveBrand > 0 && empty($categories['brand'][$effectiveBrand])) $item['errors'][] = 'ID thương hiệu không tồn tại.';
        $effectivePricing = adminNormalizeProductPricing(array(
            'regular_price' => isset($item['data']['regular_price']) ? $item['data']['regular_price'] : ($existing ? $existing['regular_price'] : 0),
            'sale_price' => isset($item['data']['sale_price']) ? $item['data']['sale_price'] : ($existing ? $existing['sale_price'] : 0)
        ));
        foreach($effectivePricing['errors'] as $pricingError) $item['errors'][] = $pricingError;
        if (!$isCreate && $existing) {
            $baselineRow = isset($baseline[(int) $existing['id']]) ? $baseline[(int) $existing['id']] : null;
            foreach ($item['data'] as $field => $incomingValue) {
                if ($field === 'date_updated' || !array_key_exists($field, $existing)) continue;
                $fieldKind = isset($columns[$field]['kind']) ? $columns[$field]['kind'] : ($field === 'discount' ? 'money' : '');
                $currentValue = productBulkComparableValue($existing[$field], $fieldKind);
                $incomingComparable = productBulkComparableValue($incomingValue, $fieldKind);
                if ($currentValue === $incomingComparable) {
                    unset($item['data'][$field]);
                    continue;
                }
                if ($baselineRow && array_key_exists($field, $baselineRow)) {
                    $baseValue = productBulkComparableValue($baselineRow[$field], $fieldKind);
                    if ($currentValue !== $baseValue) {
                        if ($incomingComparable === $baseValue) {
                            unset($item['data'][$field]);
                            continue;
                        }
                        $choiceKey = $item['row'].':'.$field;
                        $conflict = array('key' => $choiceKey, 'field' => $field, 'label' => isset($columns[$field]['label']) ? $columns[$field]['label'] : $field, 'base' => $baseValue, 'current' => $currentValue, 'incoming' => $incomingComparable);
                        $item['conflicts'][] = $conflict;
                        if (empty($overwriteChoices[$choiceKey])) {
                            unset($item['data'][$field]);
                        }
                    }
                }
                if (isset($item['data'][$field])) $item['changes'][$field] = array('label' => isset($columns[$field]['label']) ? $columns[$field]['label'] : $field, 'current' => $currentValue, 'incoming' => $incomingComparable);
            }
            if (count($item['data']) === 1 && isset($item['data']['date_updated'])) unset($item['data']['date_updated']);

            /* Diff cột "Mô tả dài (khối)": so nội dung hiện tại với ô Excel. */
            if ($item['sections_raw'] !== '' && !empty($existing['id'])) {
                $curMd = '';
                $curSec = $GLOBALS['d']->rawQueryOne("select mode, blocksvi from #_content_section where scope = 'product' and scope_id = ? limit 0,1", array((int) $existing['id']));
                if ($curSec) $curMd = ($curSec['mode'] === 'hide') ? '[ẩn]' : productBlocksToMarkdown(productSectionsDecode($curSec['blocksvi']));
                $incMd = ($item['sections_raw'] === '__CLEAR__') ? '' : $item['sections_raw'];
                if (trim($curMd) === trim($incMd)) {
                    $item['sections_raw'] = '';
                } else {
                    $item['changes']['sectionsvi'] = array(
                        'label' => 'Mô tả dài (khối)',
                        'current' => mb_substr($curMd, 0, 4000, 'UTF-8'),
                        'incoming' => mb_substr($incMd, 0, 4000, 'UTF-8'),
                    );
                }
            }
        }
        if ($isCreate && $item['sections_raw'] !== '' && $item['sections_raw'] !== '__CLEAR__') {
            $item['changes']['sectionsvi'] = array('label' => 'Mô tả dài (khối)', 'current' => '(chưa có)', 'incoming' => mb_substr($item['sections_raw'], 0, 4000, 'UTF-8'));
        }
        if ($isCreate || isset($item['data']['regular_price']) || isset($item['data']['sale_price'])) {
            $item['data']['discount'] = $effectivePricing['values']['discount'];
        }
        if ($isCreate) {
            $item['data'] += array('id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 0, 'numb' => 0, 'status' => 'hienthi', 'type' => $type, 'date_created' => time(), 'date_updated' => 0, 'view' => 0);
        } else {
            if ($item['pending_brand_key'] !== '' || !empty($item['data'])) $item['data']['date_updated'] = time();
        }
        if ($isCreate) {
            $createdStatuses = array_filter(array_map('trim', explode(',', (string) $item['data']['status'])));
            if (!in_array('hienthi', $createdStatuses, true)) $result['counts']['draft_create']++;
        } elseif ($existing && array_key_exists('status', $item['data'])) {
            $currentStatuses = array_filter(array_map('trim', explode(',', (string) $existing['status'])));
            $incomingStatuses = array_filter(array_map('trim', explode(',', (string) $item['data']['status'])));
            $currentlyVisible = in_array('hienthi', $currentStatuses, true);
            $willBeVisible = in_array('hienthi', $incomingStatuses, true);
            if ($currentlyVisible && !$willBeVisible) $result['counts']['hide_update']++;
            elseif (!$currentlyVisible && $willBeVisible) $result['counts']['show_update']++;
        }
        if (!$isCreate && empty($item['data']) && $item['sections_raw'] === '') $result['counts']['unchanged']++;
        else $result['counts'][$resolvedAction]++;
        $result['errors'] += count($item['errors']);
        $result['warnings'] += count($item['warnings']);
        $result['conflicts'] += count($item['conflicts']);
        $result['rows'][] = $item;
    }
    return $result;
}

function productBulkPreview()
{
    global $bulkPreview, $bulkMessage;
    $bulkPreview = null;
    if (!productBulkCheckCsrf()) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        return;
    }
    $parsed = productBulkReadUpload(isset($_FILES['product_bulk_file']) ? $_FILES['product_bulk_file'] : null);
    if (!empty($parsed['errors'])) {
        $bulkMessage = array('type' => 'danger', 'text' => implode(' ', $parsed['errors']));
        return;
    }
    global $type;
    $bulkPreview = productBulkValidateRows($parsed['rows'], $type, $parsed['baseline']);
    if ($bulkPreview['errors'] === 0) {
        $token = bin2hex(random_bytes(24));
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'pts-product-bulk-'.$token.'.json';
        $payload = array('type' => $type, 'created_at' => time(), 'rows' => $parsed['rows'], 'baseline' => $parsed['baseline']);
        if (file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE)) === false) {
            $bulkMessage = array('type' => 'danger', 'text' => 'Không thể tạo dữ liệu xem trước trên máy chủ.');
            return;
        }
        @chmod($path, 0600);
        $_SESSION['product_bulk_preview'][$token] = array('path' => $path, 'type' => $type, 'created_at' => time());
        $bulkPreview['token'] = $token;
    }
}

function productBulkPrepareDataForSave($data)
{
    global $func;
    $kinds = array();
    foreach (productBulkColumns() as $meta) {
        if (!empty($meta['db'])) $kinds[$meta['db']] = $meta['kind'];
    }
    foreach ($data as $field => $value) {
        if ($value === null || !isset($kinds[$field])) continue;
        if (in_array($kinds[$field], array('money', 'decimal', 'integer', 'category'), true)) continue;
        if ($kinds[$field] === 'html') $data[$field] = htmlspecialchars($func->sanitize($value, 'iframe'));
        else $data[$field] = htmlspecialchars($func->sanitize($value));
    }
    return $data;
}

function productBulkCreateBrands($newBrands, $brandChoices, $type, &$appliedBrands)
{
    global $d, $func;
    $ids = array();
    $slugOwners = productBulkSlugOwners();
    $reservedSlugs = array();
    foreach ($newBrands as $key => $brand) {
        if (empty($brandChoices[$key]) || $brandChoices[$key] !== 'create') continue;
        $name = trim(preg_replace('/\s+/u', ' ', (string) $brand['name']));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 255) throw new Exception('Tên thương hiệu mới không hợp lệ');
        $existingId = productBulkFindCategoryIdByName(productBulkCategoryMaps($type)['brand'], $name);
        if ($existingId > 0) {
            $ids[$key] = $existingId;
            continue;
        }
        $baseSlug = $func->changeTitle($name);
        if ($baseSlug === '') throw new Exception('Không thể tạo slug cho thương hiệu “'.$name.'”');
        $slug = $baseSlug;
        $suffix = 1;
        while (!empty($slugOwners[$slug]) || isset($reservedSlugs[$slug])) {
            $suffix++;
            $slug = $baseSlug.'-thuong-hieu'.($suffix > 2 ? '-'.$suffix : '');
        }
        $safeName = htmlspecialchars($func->sanitize($name));
        $data = array('namevi' => $safeName, 'nameen' => $safeName, 'slugvi' => $slug, 'slugen' => $slug, 'numb' => 0, 'status' => 'hienthi', 'type' => $type, 'date_created' => time(), 'date_updated' => 0);
        if (!$d->insert('product_brand', $data)) throw new Exception('Không thể tạo thương hiệu “'.$name.'”');
        $id = (int) $d->getLastInsertId();
        $appliedBrands[] = $id;
        $ids[$key] = $id;
        $reservedSlugs[$slug] = true;
        if (!$d->insert('seo', array('id_parent' => $id, 'com' => 'product', 'act' => 'man_brand', 'type' => $type))) throw new Exception('Không thể tạo SEO cho thương hiệu “'.$name.'”');
    }
    return $ids;
}

function productBulkRollback($applied, $type, $appliedBrands = array())
{
    global $d;
    for ($index = count($applied) - 1; $index >= 0; $index--) {
        $entry = $applied[$index];
        if ($entry['action'] === 'create') {
            $d->rawQuery('delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?', array($entry['id'], 'product', 'man', $type));
            $d->rawQuery('delete from #_product where id = ? and type = ?', array($entry['id'], $type));
        } elseif (!empty($entry['before'])) {
            $restore = $entry['before'];
            unset($restore['id']);
            $d->where('id', $entry['id']);
            $d->where('type', $type);
            $d->update('product', $restore);
        }
    }
    for ($index = count($appliedBrands) - 1; $index >= 0; $index--) {
        $brandId = (int) $appliedBrands[$index];
        $d->rawQuery('delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?', array($brandId, 'product', 'man_brand', $type));
        $d->rawQuery('delete from #_product_brand where id = ? and type = ?', array($brandId, $type));
    }
}

function productBulkCommit()
{
    global $d, $cache, $type, $bulkPreview, $bulkMessage, $bulkResult, $productBulkTestFailAfter;
    if (!productBulkCheckCsrf()) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        return;
    }
    if (empty($_POST['confirm_bulk_apply'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Hãy xác nhận đã kiểm tra bản xem trước trước khi áp dụng.');
        return;
    }
    $token = isset($_POST['preview_token']) ? preg_replace('/[^a-f0-9]/', '', $_POST['preview_token']) : '';
    $state = !empty($_SESSION['product_bulk_preview'][$token]) ? $_SESSION['product_bulk_preview'][$token] : null;
    if (!$state || $state['type'] !== $type || time() - (int) $state['created_at'] > 1800 || !is_file($state['path'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Bản xem trước đã hết hạn hoặc không hợp lệ.');
        return;
    }
    $payload = json_decode(file_get_contents($state['path']), true);
    if (empty($payload['rows'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Không đọc được dữ liệu xem trước.');
        return;
    }
    $overwriteChoices = array();
    foreach (!empty($_POST['overwrite_conflict']) && is_array($_POST['overwrite_conflict']) ? $_POST['overwrite_conflict'] : array() as $choice) {
        if (preg_match('/^\d+:[a-zA-Z0-9_]+$/', $choice)) $overwriteChoices[$choice] = true;
    }
    $brandChoices = productBulkBrandChoicesFromPost();
    $productChoices = productBulkProductChoicesFromPost();
    $bulkPreview = productBulkValidateRows($payload['rows'], $type, !empty($payload['baseline']) ? $payload['baseline'] : array(), $overwriteChoices, $brandChoices, true, $productChoices, true);
    if ($bulkPreview['errors'] > 0) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Dữ liệu hoặc database đã thay đổi sau bước xem trước. Chưa có dữ liệu nào được ghi.');
        return;
    }
    $hiddenProductCount = (int) $bulkPreview['counts']['draft_create'] + (int) $bulkPreview['counts']['hide_update'] + (int) $bulkPreview['counts']['archive'];
    if ($hiddenProductCount > 0 && empty($_POST['confirm_hidden_products'])) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Có '.$hiddenProductCount.' sản phẩm sẽ ở trạng thái Tạm ẩn. Hãy đánh dấu xác nhận riêng rồi áp dụng lại; chưa có dữ liệu nào được ghi.');
        return;
    }
    $applied = array();
    $appliedBrands = array();
    $transactionStarted = false;
    $writesApplied = 0;
    try {
        $d->startTransaction();
        $transactionStarted = true;
        $createdBrandIds = productBulkCreateBrands($bulkPreview['new_brands'], $brandChoices, $type, $appliedBrands);
        $bulkPreview['counts']['brand_create'] = count($appliedBrands);
        foreach ($bulkPreview['rows'] as $row) {
            if ($row['action'] === 'skip' || (empty($row['data']) && empty($row['sections_raw']))) continue;
            $saveData = productBulkPrepareDataForSave($row['data']);
            if (!empty($row['pending_brand_key'])) {
                if (empty($createdBrandIds[$row['pending_brand_key']])) throw new Exception('Không xác định được thương hiệu mới tại dòng '.$row['row']);
                $saveData['id_brand'] = (int) $createdBrandIds[$row['pending_brand_key']];
            }
            if ($row['action'] === 'create') {
                if (!$d->insert('product', $saveData)) throw new Exception('Không thể tạo sản phẩm tại dòng '.$row['row']);
                $id = (int) $d->getLastInsertId();
                $applied[] = array('action' => 'create', 'id' => $id, 'before' => null);
                $seo = array('id_parent' => $id, 'com' => 'product', 'act' => 'man', 'type' => $type);
                if (!$d->insert('seo', $seo)) throw new Exception('Không thể tạo SEO cho sản phẩm tại dòng '.$row['row']);
            } elseif (!empty($saveData)) {
                $applied[] = array('action' => $row['action'], 'id' => $row['id'], 'before' => $row['existing']);
                $d->where('id', $row['id']);
                $d->where('type', $type);
                if (!$d->update('product', $saveData)) throw new Exception('Không thể cập nhật sản phẩm tại dòng '.$row['row']);
            }
            if (!empty($row['sections_raw'])) {
                $sectionPid = ($row['action'] === 'create') ? (int) $id : (int) $row['id'];
                $sraw = $row['sections_raw'];
                if ($sraw === '[ẩn]' || $sraw === '[an]') {
                    productContentSectionSetMode($d, $sectionPid, 'hide');
                } elseif ($sraw === '__CLEAR__') {
                    productContentSectionApplyBlocks($d, $sectionPid, array());
                } else {
                    productContentSectionApplyBlocks($d, $sectionPid, productMarkdownToBlocks($sraw));
                }
            }
            $writesApplied++;
            if(isset($productBulkTestFailAfter) && $productBulkTestFailAfter > 0 && $writesApplied >= $productBulkTestFailAfter)
                throw new Exception('Lỗi mô phỏng để kiểm tra rollback');
        }
        $d->commit();
        $transactionStarted = false;
        $cache->delete();
        $bulkResult = $bulkPreview['counts'];
        $bulkMessage = array('type' => 'success', 'text' => 'Đã áp dụng dữ liệu hàng loạt thành công.');
        @unlink($state['path']);
        unset($_SESSION['product_bulk_preview'][$token]);
        $bulkPreview = null;
    } catch (Exception $e) {
        if($transactionStarted) $d->rollback();
        // Phục hồi thủ công khi database engine không hỗ trợ transaction.
        productBulkRollback($applied, $type, $appliedBrands);
        $bulkMessage = array('type' => 'danger', 'text' => $e->getMessage().'. Hệ thống đã phục hồi các dòng đã xử lý trong lượt này.');
    }
}

function productBulkExportValue($key, $row, $categories)
{
    if ($key === 'action') return 'update';
    if ($key === 'expected_updated_at') return isset($row['date_updated']) ? (int) $row['date_updated'] : 0;
    if ($key === '_list_name') return !empty($categories['list'][(int) $row['id_list']]['namevi']) ? $categories['list'][(int) $row['id_list']]['namevi'] : '';
    if ($key === '_cat_name') return !empty($categories['cat'][(int) $row['id_cat']]['namevi']) ? $categories['cat'][(int) $row['id_cat']]['namevi'] : '';
    if ($key === '_brand_name') return !empty($categories['brand'][(int) $row['id_brand']]['namevi']) ? $categories['brand'][(int) $row['id_brand']]['namevi'] : '';
    if ($key === 'is_visible') return in_array('hienthi', array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))), true) ? 1 : 0;
    if ($key === 'is_featured') return in_array('banchay', array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))), true) ? 1 : 0;
    if ($key === 'requires_quote') return in_array('baogia', array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))), true) ? 1 : 0;
    if ($key === 'is_promo') return in_array('khuyenmai', array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))), true) ? 1 : 0;
    if ($key === 'status') {
        $labels = array('hienthi' => 'Hiển thị', 'banchay' => 'Bán chạy');
        $values = array();
        foreach (array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))) as $status) {
            $values[] = isset($labels[$status]) ? $labels[$status] : $status;
        }
        return empty($values) ? 'Tạm ẩn' : implode(', ', $values);
    }
    if ($key === 'additional_specs' && !empty($row[$key])) {
        $lines = array();
        $labelCounts = array();
        $specs = json_decode(htmlspecialchars_decode((string) $row[$key], ENT_QUOTES), true);
        if (is_array($specs)) foreach ($specs as $spec) {
            if (!empty($spec['label']) && isset($spec['value'])) {
                $specKey = mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $spec['label'])), 'UTF-8');
                if ($specKey === '') continue;
                $labelCounts[$specKey] = isset($labelCounts[$specKey]) ? $labelCounts[$specKey] + 1 : 1;
                $label = trim((string) $spec['label']);
                if($labelCounts[$specKey] > 1) $label .= ' ('.$labelCounts[$specKey].')';
                $lines[] = $label.' | '.trim((string) $spec['value']);
            }
        }
        return implode("\n", $lines);
    }
    if (in_array($key, array('highlightsvi', 'applicationsvi', 'installationvi'), true))
        return productContentBlockLines(isset($row[$key]) ? $row[$key] : '', $key === 'highlightsvi');
    if ($key === 'sectionsvi')
        return isset($row['_sections_md']) ? (string) $row['_sections_md'] : '';
    $columns = productBulkColumns();
    if(isset($columns[$key]) && $columns[$key]['kind'] === 'html')
        return isset($row[$key]) ? productBulkHtmlToText($row[$key]) : '';
    return isset($row[$key]) ? htmlspecialchars_decode((string) $row[$key], ENT_QUOTES) : '';
}

function productBulkCsvSafe($value)
{
    $value = (string) $value;
    if ($value !== '' && in_array($value[0], array('=', '+', '-', '@'), true)) return "'".$value;
    return $value;
}

function productBulkExport($templateOnly = false)
{
    global $d, $type, $setting;
    $format = !empty($_GET['format']) ? strtolower($_GET['format']) : 'xlsx';
    if (!in_array($format, array('xlsx', 'csv'), true)) $format = 'xlsx';
    $allColumns = productBulkColumns();
    $columns = productBulkVisibleColumns();
    $headers = array_keys($columns);
    $displayHeaders = array_map(function ($column) { return $column['label']; }, $columns);
    $categories = productBulkCategoryMaps($type);
    $dataStatus = !$templateOnly && !empty($_GET['data_status']) ? (string)$_GET['data_status'] : '';
    $dataStatusDefinition = adminProductDataStatus($dataStatus);
    if(!$dataStatusDefinition) $dataStatus = '';
    $products = array();
    if(!$templateOnly)
    {
        $where = $dataStatusDefinition ? ' and '.$dataStatusDefinition['sql'] : '';
        $products = $d->rawQuery("select p.* from #_product p where p.type = ?$where order by p.numb,p.id desc", array($type));
        /* Đính kèm mô tả khối (chỉ bản riêng của sản phẩm) cho cột sectionsvi. */
        $sectionsByProduct = array();
        foreach($d->rawQuery("select scope_id, mode, blocksvi from #_content_section where scope='product'") as $sec)
            $sectionsByProduct[(int) $sec['scope_id']] = $sec;
        foreach($products as &$productRef)
        {
            $sec = isset($sectionsByProduct[(int) $productRef['id']]) ? $sectionsByProduct[(int) $productRef['id']] : null;
            if($sec && $sec['mode'] === 'own') $productRef['_sections_md'] = productBlocksToMarkdown(productSectionsDecode($sec['blocksvi']));
            elseif($sec && $sec['mode'] === 'hide') $productRef['_sections_md'] = '[ẩn]';
            else $productRef['_sections_md'] = '';
        }
        unset($productRef);
    }
    $suffix = $templateOnly ? 'mau-nhap-san-pham' : 'san-pham'.($dataStatus !== '' ? '-'.$dataStatus : '').'-'.date('Ymd-His');
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$suffix.'.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $displayHeaders);
        foreach ($products as $product) {
            $values = array();
            foreach ($headers as $key) $values[] = productBulkCsvSafe(productBulkExportValue($key, $product, $categories));
            fputcsv($output, $values);
        }
        fclose($output);
        exit;
    }
    require_once LIBRARIES.'PHPExcel.php';
    require_once LIBRARIES.'PHPExcel/IOFactory.php';
    // Bổ sung writer part khi PHPExcel không khai báo lớp xử lý comment.
    if (!class_exists('PHPExcel_Writer_Excel2007_Comments', true)) {
        class PHPExcel_Writer_Excel2007_Comments extends PHPExcel_Writer_Excel2007_WriterPart
        {
        }
    }

    $excel = new PHPExcel();
    $excel->getProperties()->setCreator(!empty($setting['namevi']) ? $setting['namevi'] : 'Phuc Thinh Solar')->setTitle('Quản lý sản phẩm');
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle('SanPham');
    foreach ($headers as $columnIndex => $key) {
        $coordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).'1';
        $sheet->setCellValueExplicit($coordinate, $columns[$key]['label'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($columnIndex))->setWidth(in_array($key, array('descvi', 'contentvi', 'highlightsvi', 'applicationsvi', 'installationvi', 'chinhsachmuahangvi', 'certificatevi', 'additional_specs'), true) ? 45 : 18);
    }
    $lastColumn = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
    $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray(array('font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')), 'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '0B2A5B')), 'alignment' => array('wrap' => true)));
    foreach($headers as $columnIndex => $key) {
        if(empty($columns[$key]['required_create'])) continue;
        $letter = PHPExcel_Cell::stringFromColumnIndex($columnIndex);
        $sheet->getStyle($letter.'1')->applyFromArray(array(
            'font' => array('bold' => true, 'color' => array('rgb' => '1F2937')),
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'FDE68A'))
        ));
    }
    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:'.$lastColumn.'1');
    $rowIndex = 2;
    foreach ($products as $product) {
        foreach ($headers as $columnIndex => $key) {
            $coordinate = PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowIndex;
            $value = productBulkExportValue($key, $product, $categories);
            if (in_array($columns[$key]['kind'], array('money', 'decimal', 'integer'), true) && $value !== '') $sheet->setCellValue($coordinate, (float) $value);
            else $sheet->setCellValueExplicit($coordinate, (string) $value, PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $rowIndex++;
    }
    $sheet->getProtection()->setSheet(true);
    foreach ($headers as $columnIndex => $key) {
        $letter = PHPExcel_Cell::stringFromColumnIndex($columnIndex);
        $locked = in_array($key, array('id', 'photo'), true);
        $sheet->getStyle($letter.'2:'.$letter.'501')->getProtection()->setLocked($locked ? PHPExcel_Style_Protection::PROTECTION_PROTECTED : PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
        if ($locked) $sheet->getStyle($letter.'1:'.$letter.'501')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');
    }
    $guide = $excel->createSheet();
    $guide->setTitle('HuongDan');
    $guide->fromArray(array(
        array('HƯỚNG DẪN NHANH', 'Chỉ nhập những ô cần thiết; không cần điền hết các cột.'),
        array('Tạo sản phẩm mới', 'Chỉ bắt buộc Mã sản phẩm và Tên sản phẩm (hai cột màu vàng). Các ô khác có thể để trống.'),
        array('Sửa sản phẩm', 'Giữ nguyên ID hoặc Mã sản phẩm. Chỉ sửa các ô muốn thay đổi; ô trống sẽ giữ dữ liệu đang có trên website.'),
        array('Hệ thống tự làm', 'Tự nhận biết tạo mới/cập nhật, tạo đường dẫn từ tên, tính chiết khấu, giữ ngày hệ thống và dùng mặc định cho ô không nhập.'),
        array('Muốn xóa một ô', 'Nhập đúng __CLEAR__ vào ô đó. Không dùng __CLEAR__ cho ID hoặc Mã sản phẩm.'),
        array('1. TẠO MỚI', 'Ví dụ tối thiểu: Mã sản phẩm = JD-T200; Tên sản phẩm = Đèn pha JD-T200. Có thể nhập thêm giá, danh mục, trạng thái và thông số nếu đã có.'),
        array('Mã sản phẩm', 'Mỗi sản phẩm dùng một mã duy nhất, tối đa 30 ký tự. Không dùng lại mã của sản phẩm khác.'),
        array('Giá sản phẩm', 'Giá gốc và Giá bán/khuyến mãi nhập bằng số, không nhập chữ “đ” hoặc “VNĐ”. Giá khuyến mãi không được lớn hơn giá gốc.'),
        array('Tình trạng mặc định', 'Các cột Hiển thị, Bán chạy và Liên hệ báo giá dùng 1 = bật, 0 = tắt. Nếu tạo mới và để trống, sản phẩm mặc định hiển thị.'),
        array('2. CẬP NHẬT', 'Nên dùng file vừa xuất từ website. Giữ nguyên ID/Mã; chỉ thay những ô cần sửa. Không cần xóa dữ liệu cũ trong các ô khác.'),
        array('Ô để trống', 'Khi sửa sản phẩm: giữ nguyên giá trị trên website. Khi tạo mới: hệ thống dùng mặc định hoặc để trống nếu trường không bắt buộc.'),
        array('Xóa giá trị cũ', 'Nhập __CLEAR__ vào ô cần xóa. Ví dụ nhập __CLEAR__ ở cột Bảo hành để xóa nội dung bảo hành hiện tại.'),
        array('Xung đột dữ liệu', 'Nếu website đã được người khác sửa sau lúc xuất Excel, màn hình xem trước sẽ báo từng ô. Chỉ chọn ghi đè khi chắc chắn dùng dữ liệu Excel.'),
        array('3. DANH MỤC', 'Chọn Danh mục cấp 1, cấp 2 và Thương hiệu từ danh sách có sẵn. Không tự gõ tên mới nếu tên đó chưa có trong hệ thống.'),
        array('Quan hệ danh mục', 'Danh mục cấp 2 phải thuộc đúng Danh mục cấp 1. Nếu không dùng danh mục cấp 2 hoặc thương hiệu thì có thể để trống.'),
        array('4. THÔNG SỐ', 'Thông số phổ biến nhập vào đúng cột riêng như Công suất, Pin, Chất liệu, Chống nước, Kích thước, Bảo hành…'),
        array('Thông số khác', "Thông số chưa có cột riêng nhập mỗi dòng theo mẫu Tên | Giá trị. Ví dụ:\nModel | JD-T200\nGóc chiếu | 120°"),
        array('Quy tắc thông số', 'Không lặp tên thông số trong cùng một sản phẩm. Không dán bảng HTML. Nội dung giải thích dài nên nhập ở cột Nội dung.'),
        array('5. TRẠNG THÁI', 'Hiển thị điều khiển công khai; Bán chạy điều khiển nhóm nổi bật; Liên hệ báo giá ẩn giá và nút mua riêng sản phẩm. Đặt Hiển thị = 0 mới là tạm ẩn.'),
        array('Archive', 'Dùng thao tác archive khi cần ẩn hàng loạt. Archive không xóa vĩnh viễn sản phẩm, hình ảnh hoặc lịch sử liên quan.'),
        array('6. QUY TRÌNH NHẬP', 'Bước 1: Xuất file mới nhất. Bước 2: Sửa dữ liệu. Bước 3: Tải file lên. Bước 4: đọc màn hình Xem trước. Bước 5: đánh dấu đã kiểm tra và áp dụng.'),
        array('Khi có lỗi', 'Không dòng nào được ghi nếu file còn lỗi. Sửa đúng dòng/cột được báo rồi tải lại file; không cần chia file trừ khi vượt giới hạn.'),
        array('Giới hạn', 'Tối đa 500 sản phẩm và 5 MB mỗi lần. Hỗ trợ XLSX, XLS và CSV; nên dùng XLSX để giữ danh sách chọn và dữ liệu chống xung đột.'),
        array('Màu cột', 'Vàng: bắt buộc khi tạo mới. Xám: chỉ đọc, không sửa. Xanh: tùy chọn.'),
        array('KHÔNG NÊN LÀM', 'Không xóa/đổi tiêu đề cột, không sửa sheet ẩn _HeThong, không đổi ID, không sao chép ID sang sản phẩm khác và không nhập công thức Excel.'),
        array('Hình ảnh', 'Cột ảnh chỉ để đối chiếu. Thêm, đổi hoặc xóa hình tại trang chi tiết sản phẩm trong admin.'),
        array('An toàn', 'Luôn kiểm tra số dòng Tạo mới/Cập nhật/Tạm ẩn trên màn hình xem trước trước khi xác nhận.')
    ));
    $guide->getStyle('A1:B1')->getFont()->setBold(true);
    $guide->getStyle('A1:B1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FDE68A');
    $guide->getColumnDimension('A')->setWidth(24);
    $guide->getColumnDimension('B')->setWidth(95);
    $guide->getStyle('A1:B30')->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
    foreach(array(1, 6, 10, 14, 16, 19, 21, 25) as $guideHeadingRow) {
        $guide->getStyle('A'.$guideHeadingRow.':B'.$guideHeadingRow)->applyFromArray(array(
            'font' => array('bold' => true, 'color' => array('rgb' => '1F2937')),
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'FDE68A'))
        ));
    }
    for($guideRow = 1; $guideRow <= 27; $guideRow++) $guide->getRowDimension($guideRow)->setRowHeight($guideRow === 17 ? 45 : 30);
    $reference = $excel->createSheet();
    $reference->setTitle('DanhMuc');
    $reference->fromArray(array(array('ID cấp 1', 'Danh mục cấp 1', '', 'ID cấp 2', 'ID cấp 1 cha', 'Danh mục cấp 2', '', 'ID thương hiệu', 'Thương hiệu')));
    $listRow = $catRow = $brandRow = 2;
    foreach ($categories['list'] as $row) {
        $reference->fromArray(array(array((int) $row['id'], $row['namevi'])), null, 'A'.$listRow++);
    }
    foreach ($categories['cat'] as $row) {
        $reference->fromArray(array(array((int) $row['id'], (int) $row['id_list'], $row['namevi'])), null, 'D'.$catRow++);
    }
    foreach ($categories['brand'] as $row) {
        $reference->fromArray(array(array((int) $row['id'], $row['namevi'])), null, 'H'.$brandRow++);
    }
    $reference->getStyle('A1:I1')->getFont()->setBold(true);
    foreach (range('A', 'I') as $column) $reference->getColumnDimension($column)->setWidth(in_array($column, array('B', 'F', 'I'), true) ? 40 : 18);
    $dropdownRanges = array(
        '_list_name' => '$B$2:$B$'.max(2, $listRow - 1),
        '_cat_name' => '$F$2:$F$'.max(2, $catRow - 1),
        '_brand_name' => '$I$2:$I$'.max(2, $brandRow - 1)
    );
    foreach ($headers as $columnIndex => $key) {
        $letter = PHPExcel_Cell::stringFromColumnIndex($columnIndex);
        if (isset($dropdownRanges[$key])) {
            $validation = $sheet->getCell($letter.'2')->getDataValidation();
            $validation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_STOP)->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Giá trị không hợp lệ')->setError('Hãy chọn một giá trị có trong sheet DanhMuc.')->setShowDropDown(true)->setFormula1("DanhMuc!".$dropdownRanges[$key]);
            for ($validationRow = 3; $validationRow <= 501; $validationRow++) $sheet->getCell($letter.$validationRow)->setDataValidation(clone $validation);
        } elseif (in_array($key, array('is_visible', 'is_featured', 'requires_quote', 'is_promo'), true)) {
            $validation = $sheet->getCell($letter.'2')->getDataValidation();
            $validation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Giá trị không hợp lệ')->setError('Chỉ chọn 1 (bật) hoặc 0 (tắt).')->setFormula1('"1,0"');
            for ($validationRow = 3; $validationRow <= 501; $validationRow++) $sheet->getCell($letter.$validationRow)->setDataValidation(clone $validation);
        }
    }
    $system = $excel->createSheet();
    $system->setTitle('_HeThong');
    $systemHeaders = array_merge(array('id', 'date_updated'), productBulkEditableFields());
    foreach ($systemHeaders as $columnIndex => $key) $system->setCellValueExplicitByColumnAndRow($columnIndex, 1, $key, PHPExcel_Cell_DataType::TYPE_STRING);
    $systemRow = 2;
    foreach ($products as $product) {
        foreach ($systemHeaders as $columnIndex => $key) {
            $system->setCellValueExplicitByColumnAndRow($columnIndex, $systemRow, isset($product[$key]) ? (string) $product[$key] : '', PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $systemRow++;
    }
    $system->setSheetState(PHPExcel_Worksheet::SHEETSTATE_VERYHIDDEN);
    $system->getProtection()->setSheet(true);
    $excel->setActiveSheetIndex(0);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$suffix.'.xlsx"');
    header('Cache-Control: max-age=0');
    $previousErrorReporting = error_reporting();
    error_reporting($previousErrorReporting & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
    try {
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
    } finally {
        error_reporting($previousErrorReporting);
    }
    exit;
}
