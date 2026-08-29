<?php
if (!defined('SOURCES')) die('Error');

function productBulkColumns()
{
    return array(
        'action' => array('label' => 'Thao tác', 'kind' => 'control', 'export' => false),
        'id' => array('label' => 'ID sản phẩm', 'kind' => 'control'),
        'expected_updated_at' => array('label' => 'Mốc cập nhật', 'kind' => 'control', 'export' => false),
        'code' => array('label' => 'Mã sản phẩm', 'kind' => 'text', 'db' => 'code', 'max' => 30),
        'namevi' => array('label' => 'Tên sản phẩm', 'kind' => 'text', 'db' => 'namevi', 'max' => 255),
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
        'status' => array('label' => 'Tình trạng website', 'kind' => 'status', 'db' => 'status'),
        'numb' => array('label' => 'Thứ tự', 'kind' => 'integer', 'db' => 'numb', 'export' => false),
        'descvi' => array('label' => 'Mô tả ngắn', 'kind' => 'html', 'db' => 'descvi'),
        'contentvi' => array('label' => 'Nội dung', 'kind' => 'html', 'db' => 'contentvi'),
        'thongsokythuatvi' => array('label' => 'Thông số kỹ thuật', 'kind' => 'html', 'db' => 'thongsokythuatvi'),
        'chinhsachmuahangvi' => array('label' => 'Chính sách mua hàng', 'kind' => 'html', 'db' => 'chinhsachmuahangvi'),
        'video_url' => array('label' => 'Video YouTube', 'kind' => 'youtube', 'db' => 'video_url', 'max' => 500),
        'battery_capacity' => array('label' => 'Dung lượng pin', 'kind' => 'text', 'db' => 'battery_capacity', 'max' => 100),
        'panel_power' => array('label' => 'Công suất tấm pin', 'kind' => 'text', 'db' => 'panel_power', 'max' => 100),
        'material' => array('label' => 'Chất liệu', 'kind' => 'text', 'db' => 'material', 'max' => 255),
        'charging_time' => array('label' => 'Thời gian sạc', 'kind' => 'text', 'db' => 'charging_time', 'max' => 100),
        'lighting_time' => array('label' => 'Thời gian chiếu sáng', 'kind' => 'text', 'db' => 'lighting_time', 'max' => 100),
        'waterproof_rating' => array('label' => 'Chuẩn chống nước', 'kind' => 'text', 'db' => 'waterproof_rating', 'max' => 50),
        'led_chip' => array('label' => 'Chip LED', 'kind' => 'text', 'db' => 'led_chip', 'max' => 100),
        'lighting_area' => array('label' => 'Diện tích chiếu sáng', 'kind' => 'text', 'db' => 'lighting_area', 'max' => 100),
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
        // Giữ tương thích với file kỹ thuật đã tải trước đây.
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
        } catch (Exception $e) {
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

function productBulkComparableValue($value, $kind = '')
{
    if ($value === null) return '';
    $value = trim(htmlspecialchars_decode((string) $value, ENT_QUOTES));
    if (in_array($kind, array('money', 'decimal', 'integer', 'category'), true) && $value !== '' && is_numeric($value)) {
        return rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');
    }
    return $value;
}

function productBulkValidateRows($rawRows, $type, $baseline = array(), $overwriteChoices = array())
{
    global $func, $config;
    $result = array('rows' => array(), 'errors' => 0, 'warnings' => 0, 'conflicts' => 0, 'counts' => array('create' => 0, 'update' => 0, 'archive' => 0, 'skip' => 0, 'unchanged' => 0));
    $products = productBulkProductMaps($type);
    $categories = productBulkCategoryMaps($type);
    $slugOwners = productBulkSlugOwners();
    $databaseColumns = productBulkDatabaseColumns();
    $columns = productBulkColumns();
    $seenIds = array();
    $seenCodes = array();
    $seenSlugs = array();
    $allowedStatus = !empty($config['product'][$type]['check']) ? array_keys($config['product'][$type]['check']) : array('hienthi');
    foreach ($rawRows as $raw) {
        $requestedAction = productBulkNormalizeAction(isset($raw['action']) ? $raw['action'] : '');
        $item = array('row' => (int) $raw['_row_number'], 'action' => $requestedAction, 'id' => 0, 'code' => '', 'name' => '', 'data' => array(), 'existing' => null, 'errors' => array(), 'warnings' => array(), 'conflicts' => array(), 'changes' => array());
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
        if ($item['action'] === 'auto') {
            $item['action'] = $existing ? 'update' : 'create';
            if (!$existingById && $existingByCode) $item['warnings'][] = 'Đã nhận diện sản phẩm hiện có theo mã và sẽ cập nhật.';
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
        foreach (array('_list_name' => array('list', 'id_list'), '_cat_name' => array('cat', 'id_cat'), '_brand_name' => array('brand', 'id_brand')) as $sourceKey => $definition) {
            if (!array_key_exists($sourceKey, $raw) || trim((string) $raw[$sourceKey]) === '') continue;
            $resolvedId = productBulkFindCategoryIdByName($categories[$definition[0]], $raw[$sourceKey]);
            if ($resolvedId === -1) $item['errors'][] = productBulkColumns()[$sourceKey]['label'].' bị trùng tên trong hệ thống; hãy chọn lại trong file mới.';
            elseif ($resolvedId === 0) $item['errors'][] = productBulkColumns()[$sourceKey]['label'].' không tồn tại.';
            else $raw[$definition[1]] = $resolvedId;
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
                    if (!($owner['table'] === 'product' && $existing && $owner['id'] === (int) $existing['id'])) {
                        $item['errors'][] = 'Slug đã được sử dụng trong hệ thống.';
                        break;
                    }
                }
            }
        }
        $effectiveList = isset($item['data']['id_list']) ? $item['data']['id_list'] : ($existing ? (int) $existing['id_list'] : 0);
        $effectiveCat = isset($item['data']['id_cat']) ? $item['data']['id_cat'] : ($existing ? (int) $existing['id_cat'] : 0);
        $effectiveBrand = isset($item['data']['id_brand']) ? $item['data']['id_brand'] : ($existing ? (int) $existing['id_brand'] : 0);
        if ($effectiveList > 0 && empty($categories['list'][$effectiveList])) $item['errors'][] = 'ID danh mục cấp 1 không tồn tại.';
        if ($effectiveCat > 0 && empty($categories['cat'][$effectiveCat])) $item['errors'][] = 'ID danh mục cấp 2 không tồn tại.';
        if ($effectiveCat > 0 && !empty($categories['cat'][$effectiveCat]) && $effectiveList > 0 && (int) $categories['cat'][$effectiveCat]['id_list'] !== $effectiveList) $item['errors'][] = 'Danh mục cấp 2 không thuộc danh mục cấp 1 đã chọn.';
        if ($effectiveBrand > 0 && empty($categories['brand'][$effectiveBrand])) $item['errors'][] = 'ID thương hiệu không tồn tại.';
        $regular = isset($item['data']['regular_price']) ? (float) $item['data']['regular_price'] : ($existing ? (float) $existing['regular_price'] : 0);
        $sale = isset($item['data']['sale_price']) ? (float) $item['data']['sale_price'] : ($existing ? (float) $existing['sale_price'] : 0);
        if ($regular > 0 && $sale > $regular) $item['errors'][] = 'Giá bán/khuyến mãi không được lớn hơn giá gốc.';
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
        }
        if ($isCreate || isset($item['data']['regular_price']) || isset($item['data']['sale_price'])) {
            $effectiveRegular = isset($item['data']['regular_price']) ? (float) $item['data']['regular_price'] : ($existing ? (float) $existing['regular_price'] : 0);
            $effectiveSale = isset($item['data']['sale_price']) ? (float) $item['data']['sale_price'] : ($existing ? (float) $existing['sale_price'] : 0);
            $item['data']['discount'] = ($effectiveRegular > 0 && $effectiveSale > 0) ? max(0, min(100, round((($effectiveRegular - $effectiveSale) / $effectiveRegular) * 100))) : 0;
        }
        if ($isCreate) {
            $item['data'] += array('id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 0, 'numb' => 0, 'status' => 'hienthi', 'type' => $type, 'date_created' => time(), 'date_updated' => 0, 'view' => 0);
        } else {
            if (!empty($item['data'])) $item['data']['date_updated'] = time();
        }
        if (!$isCreate && empty($item['data'])) $result['counts']['unchanged']++;
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

function productBulkRollback($applied, $type)
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
}

function productBulkCommit()
{
    global $d, $cache, $type, $bulkPreview, $bulkMessage, $bulkResult;
    if (!productBulkCheckCsrf()) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        return;
    }
    if (empty($_POST['confirm_bulk']) || trim($_POST['confirm_bulk']) !== 'CAP_NHAT') {
        $bulkMessage = array('type' => 'danger', 'text' => 'Bạn chưa nhập đúng CAP_NHAT để xác nhận.');
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
    $bulkPreview = productBulkValidateRows($payload['rows'], $type, !empty($payload['baseline']) ? $payload['baseline'] : array(), $overwriteChoices);
    if ($bulkPreview['errors'] > 0) {
        $bulkMessage = array('type' => 'danger', 'text' => 'Dữ liệu hoặc database đã thay đổi sau bước xem trước. Chưa có dữ liệu nào được ghi.');
        return;
    }
    $applied = array();
    try {
        foreach ($bulkPreview['rows'] as $row) {
            if ($row['action'] === 'skip' || empty($row['data'])) continue;
            $saveData = productBulkPrepareDataForSave($row['data']);
            if ($row['action'] === 'create') {
                if (!$d->insert('product', $saveData)) throw new Exception('Không thể tạo sản phẩm tại dòng '.$row['row']);
                $id = (int) $d->getLastInsertId();
                $applied[] = array('action' => 'create', 'id' => $id, 'before' => null);
                $seo = array('id_parent' => $id, 'com' => 'product', 'act' => 'man', 'type' => $type);
                if (!$d->insert('seo', $seo)) throw new Exception('Không thể tạo SEO cho sản phẩm tại dòng '.$row['row']);
            } else {
                $applied[] = array('action' => $row['action'], 'id' => $row['id'], 'before' => $row['existing']);
                $d->where('id', $row['id']);
                $d->where('type', $type);
                if (!$d->update('product', $saveData)) throw new Exception('Không thể cập nhật sản phẩm tại dòng '.$row['row']);
            }
        }
        $cache->delete();
        $bulkResult = $bulkPreview['counts'];
        $bulkMessage = array('type' => 'success', 'text' => 'Đã áp dụng dữ liệu hàng loạt thành công.');
        @unlink($state['path']);
        unset($_SESSION['product_bulk_preview'][$token]);
        $bulkPreview = null;
    } catch (Exception $e) {
        productBulkRollback($applied, $type);
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
    if ($key === 'status') {
        $labels = array('hienthi' => 'Hiển thị', 'banchay' => 'Bán chạy');
        $values = array();
        foreach (array_filter(array_map('trim', explode(',', isset($row['status']) ? $row['status'] : ''))) as $status) {
            $values[] = isset($labels[$status]) ? $labels[$status] : $status;
        }
        return empty($values) ? 'Tạm ẩn' : implode(', ', $values);
    }
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
    $products = $templateOnly ? array() : $d->rawQuery('select * from #_product where type = ? order by numb,id desc', array($type));
    $suffix = $templateOnly ? 'mau-nhap-san-pham' : 'san-pham-'.date('Ymd-His');
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
    // Source bàn giao có Comments.php rỗng; writer vẫn cần class này dù workbook không dùng comment.
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
        $sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($columnIndex))->setWidth(in_array($key, array('descvi', 'contentvi', 'thongsokythuatvi', 'chinhsachmuahangvi', 'certificatevi'), true) ? 45 : 18);
    }
    $lastColumn = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
    $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray(array('font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')), 'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '0B2A5B')), 'alignment' => array('wrap' => true)));
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
        array('Quy tắc', 'Chi tiết'),
        array('Tạo mới / cập nhật', 'Có ID hoặc mã đã tồn tại: cập nhật. ID trống và mã chưa tồn tại: tạo mới. Sản phẩm mới bắt buộc có mã và tên.'),
        array('Ô trống', 'Khi cập nhật: giữ nguyên dữ liệu hiện tại. Khi tạo mới: dùng giá trị mặc định.'),
        array('Chống trùng', 'Hệ thống đối chiếu ID trước, sau đó đến mã sản phẩm. ID và mã chỉ đến hai sản phẩm khác nhau sẽ bị từ chối.'),
        array('Xung đột', 'Sheet ẩn _HeThong lưu dữ liệu lúc export. Nếu website và Excel cùng sửa một ô, hệ thống sẽ yêu cầu chọn giữ website hoặc dùng Excel.'),
        array('Giá sale', 'regular_price là giá gốc; sale_price là giá bán/khuyến mãi và không được lớn hơn giá gốc.'),
        array('Trạng thái', 'hienthi,banchay. archive sẽ gỡ hienthi và banchay nhưng không xóa vĩnh viễn.'),
        array('Hình ảnh', 'Cột photo chỉ để đối chiếu. Hình vẫn quản lý tại trang chi tiết sản phẩm.'),
        array('Giới hạn', 'Tối đa 500 dòng và 5 MB mỗi lần import. Luôn xem trước trước khi xác nhận.')
    ));
    $guide->getStyle('A1:B1')->getFont()->setBold(true);
    $guide->getColumnDimension('A')->setWidth(22);
    $guide->getColumnDimension('B')->setWidth(100);
    $guide->getStyle('A1:B20')->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
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
        } elseif ($key === 'status') {
            $validation = $sheet->getCell($letter.'2')->getDataValidation();
            $validation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowErrorMessage(true)->setFormula1('"Hiển thị,Bán chạy,Tạm ẩn"');
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
