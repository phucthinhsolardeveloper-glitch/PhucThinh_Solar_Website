<?php
if(!function_exists('adminCsrfToken'))
{
    function adminCsrfToken()
    {
        if(empty($_SESSION['admin_csrf_token']))
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['admin_csrf_token'];
    }

    function adminCsrfValid($token)
    {
        return is_string($token) && $token !== '' && hash_equals(adminCsrfToken(), $token);
    }

    function adminCsrfField()
    {
        return '<input type="hidden" name="admin_csrf" value="'.htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8').'">';
    }

    function adminApiEnvelope($ok, $message = '', $code = '', $data = array(), $legacy = array())
    {
        return array_merge(array('ok'=>(bool)$ok, 'message'=>(string)$message, 'code'=>(string)$code, 'data'=>is_array($data) ? $data : array()), is_array($legacy) ? $legacy : array());
    }

    function adminApiExceptionEnvelope()
    {
        return adminApiEnvelope(false, 'Không thể xử lý yêu cầu. Vui lòng thử lại.', 'INTERNAL_ERROR', array(), array('success'=>false));
    }

    function adminKeywordLikeClause($keyword, $columns, &$params)
    {
        if(!is_scalar($keyword) || !is_array($columns) || empty($columns)) return '';

        $keyword = (string)$keyword;
        if($keyword === '') return '';

        $conditions = array();
        foreach($columns as $column)
        {
            if(!is_string($column) || !preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\.)?[A-Za-z_][A-Za-z0-9_]*$/D', $column))
                throw new InvalidArgumentException('Invalid Admin search column');
            $conditions[] = $column.' LIKE ?';
            $params[] = '%'.$keyword.'%';
        }

        return '('.implode(' or ', $conditions).')';
    }

    function adminInstallApiExceptionHandler()
    {
        set_exception_handler(function(Throwable $exception) {
            if(isset($GLOBALS['d']) && is_object($GLOBALS['d']))
            {
                try { $GLOBALS['d']->rollback(); }
                catch(Throwable $rollbackException) {}
            }

            $detail = $exception instanceof PDODbException
                ? get_class($exception).' operation='.$exception->getOperation().' sqlstate='.$exception->getSqlState()
                : get_class($exception);
            error_log('Admin API unhandled exception: '.$detail.' at '.$exception->getFile().':'.$exception->getLine());

            while(ob_get_level() > 0) ob_end_clean();

            if(!headers_sent())
            {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(adminApiExceptionEnvelope());
            exit();
        });
    }

    function adminUpdateSortOrder($d, $table, $order)
    {
        $tables = array('sortable', 'sortable_index', 'sortable_detail');
        if(!in_array($table, $tables, true)) throw new InvalidArgumentException('Invalid sortable table');
        $rawIds = explode(',', (string)$order);
        if(count($rawIds) < 1 || count($rawIds) > 500) throw new InvalidArgumentException('Invalid sortable order size');
        $ids = array();
        foreach($rawIds as $rawId)
        {
            if(!preg_match('/^[1-9][0-9]*$/', trim($rawId))) throw new InvalidArgumentException('Invalid sortable ID');
            $ids[] = (int)trim($rawId);
        }
        if(count(array_unique($ids)) !== count($ids)) throw new InvalidArgumentException('Duplicate sortable ID');

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $d->rawQuery("select id from #_{$table} where id in ({$placeholders})", $ids);
        if(count($rows) !== count($ids)) throw new InvalidArgumentException('Unknown sortable ID');

        $d->startTransaction();
        try
        {
            foreach($ids as $position => $id)
                $d->rawQuery("update #_{$table} set display_order = ? where id = ?", array($position, $id));
            $d->commit();
        }
        catch(Throwable $e)
        {
            $d->rollback();
            throw $e;
        }
        return count($ids);
    }

    function adminLegacyMutationNeedsCsrf($com, $act, $method, $post = array())
    {
        if(strtoupper((string)$method) !== 'POST') return preg_match('/^(?:delete|save|upload|sync$|create$)/i', (string)$act) === 1;
        if(preg_match('/^(?:delete|save|upload)/i', (string)$act)) return true;
        if(in_array($act, array('sync', 'create'), true)) return true;
        return $com === 'newsletter' && $act === 'man' && !empty($post['listemail']);
    }

    function adminRequirePostCsrf($json = false)
    {
        $valid = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
            && adminCsrfValid(isset($_POST['admin_csrf']) ? $_POST['admin_csrf'] : '');
        if($valid) return;
        http_response_code(403);
        if($json) {
            header('Content-Type: application/json; charset=UTF-8');
            $message = 'Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.';
            echo json_encode(adminApiEnvelope(false, $message, 'INVALID_CSRF', array(), array('success'=>false, 'msg'=>$message)));
        }
        exit();
    }

    function adminAllowedMutationTable($table, $capability)
    {
        $tables = array(
            'status' => array('product','product_list','product_cat','product_item','product_sub','product_brand','news','news_list','news_cat','news_item','news_sub','gallery','photo','tags','size','color','user','member','permission_group','city','district','ward','contact','newsletter'),
            'numb' => array('product','product_list','product_cat','product_item','product_sub','product_brand','news','news_list','news_cat','news_item','news_sub','gallery','photo','tags','size','color','user','member','permission_group','city','district','ward','contact','newsletter','order','excel','pushonesignal'),
            'copy' => array('product','news')
        );
        return isset($tables[$capability]) && in_array($table, $tables[$capability], true);
    }

    function adminAllowedCategoryTable($table)
    {
        return in_array($table, array('product_cat','product_item','product_sub','news_cat','news_item','news_sub'), true);
    }

    function adminAllowedPlaceTable($table)
    {
        return in_array($table, array('district','ward'), true);
    }

    function adminGalleryUploadFolder($com)
    {
        $folders = array('product' => 'product', 'news' => 'news');
        return isset($folders[$com]) ? $folders[$com] : null;
    }

    function adminSafeUploadBasename($filename)
    {
        return is_string($filename) && $filename !== '' && basename($filename) === $filename;
    }

    function adminAllowedDatabaseAction($action)
    {
        return in_array(strtoupper((string)$action), array('ANALYZE', 'OPTIMIZE', 'CHECK', 'REPAIR'), true);
    }

    function adminMutationPermissionKey($table, $capability, $type = '', $com = '')
    {
        $type = is_string($type) && preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $type) ? $type : '';
        if($table === 'sortable' && in_array($capability, array('numb', 'delete'), true)) return 'sortable_update';
        if($table === 'comment' && $type !== '')
        {
            if(in_array($capability, array('add', 'status'), true)) return 'product_edit_'.$type;
            if($capability === 'delete') return 'product_delete_'.$type;
        }
        $actions = array('status' => 'edit', 'numb' => 'edit', 'copy' => 'add', 'database' => 'update');
        $suffixes = array(
            'product' => array('product', ''), 'product_list' => array('product', 'list'),
            'product_cat' => array('product', 'cat'), 'product_item' => array('product', 'item'),
            'product_sub' => array('product', 'sub'), 'product_brand' => array('product', 'brand'),
            'size' => array('product', 'size'), 'color' => array('product', 'color'),
            'news' => array('news', ''), 'news_list' => array('news', 'list'),
            'news_cat' => array('news', 'cat'), 'news_item' => array('news', 'item'),
            'news_sub' => array('news', 'sub'), 'tags' => array('tags', ''),
            'newsletter' => array('newsletter', ''), 'photo' => array('photo', 'photo'),
            'city' => array('places', 'city'), 'district' => array('places', 'district'),
            'ward' => array('places', 'ward'), 'order' => array('order', ''),
            'pushonesignal' => array('pushOnesignal', ''), 'setting' => array('setting', '')
        );

        if($table === 'gallery' && in_array($com, array('product', 'news'), true))
        {
            $action = in_array($capability, array('delete', 'delete-all'), true) ? 'delete' : ($capability === 'upload' ? 'add' : 'edit');
            return $type !== '' ? $com.'_'.$action.'_photo_'.$type : null;
        }
        if(!isset($suffixes[$table])) return null;
        $action = isset($actions[$capability]) ? $actions[$capability] : null;
        if($action === null) return null;

        $module = $suffixes[$table][0];
        $suffix = $suffixes[$table][1];
        $parts = array($module, $action);
        if($suffix !== '') $parts[] = $suffix;
        if(!in_array($module, array('order', 'places', 'pushOnesignal', 'setting'), true))
        {
            if($type === '') return null;
            $parts[] = $type;
        }
        return implode('_', $parts);
    }

    function adminPermissionGranted($active, $role, $debugDeveloper, $permission, $permissions)
    {
        if(!$active || (int)$role === 3 || $debugDeveloper) return true;
        return is_string($permission) && $permission !== '' && is_array($permissions) && in_array($permission, $permissions, true);
    }

    function adminLoadPermissions($d, $idUser)
    {
        $permissions = array();
        $group = (int)$idUser > 0 ? $d->rawQueryOne("select id_permission from #_user where id = ? and find_in_set('hienthi',status) limit 0,1", array((int)$idUser)) : array();
        if(!empty($group['id_permission']))
        {
            $activeGroup = $d->rawQueryOne("select id from #_permission_group where id = ? and find_in_set('hienthi',status) limit 0,1", array((int)$group['id_permission']));
            if(!empty($activeGroup['id']))
            {
                $rows = $d->rawQuery("select permission from #_permission where id_permission_group = ?", array((int)$activeGroup['id']));
                foreach($rows as $row) if(!empty($row['permission'])) $permissions[] = $row['permission'];
            }
        }
        return array_values(array_unique($permissions));
    }

    function adminRequireMutationPermission($table, $capability, $type = '', $com = '', $json = false)
    {
        global $config, $loginAdmin, $d;
        $active = !empty($config['permission']['active']);
        $role = isset($_SESSION[$loginAdmin]['role']) ? (int)$_SESSION[$loginAdmin]['role'] : 0;
        $debugDeveloper = !empty($config['website']['debug-developer']);
        $permission = adminMutationPermissionKey($table, $capability, $type, $com);
        $permissions = array();

        if($active && $role !== 3 && !$debugDeveloper)
        {
            $idUser = isset($_SESSION[$loginAdmin]['id']) ? (int)$_SESSION[$loginAdmin]['id'] : 0;
			$permissions = adminLoadPermissions($d, $idUser);
        }

        if(adminPermissionGranted($active, $role, $debugDeveloper, $permission, $permissions)) return;
        http_response_code(403);
        if($json)
        {
            header('Content-Type: application/json; charset=UTF-8');
            $message = 'Tài khoản không có quyền thực hiện thao tác này.';
            echo json_encode(adminApiEnvelope(false, $message, 'FORBIDDEN', array(), array('success'=>false, 'msg'=>$message)));
        }
        exit();
    }

    function adminFileManagerContextPermission($request)
    {
        $request = is_array($request) ? $request : array();
        $fields = array();
        foreach(array('fm_com', 'fm_act', 'fm_type', 'fm_variant') as $field)
        {
            $value = isset($request[$field]) ? (string)$request[$field] : '';
            if($value !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $value)) return null;
            $fields[$field] = $value;
        }

        if($fields['fm_com'] === '' || $fields['fm_act'] === '') return null;
        $hasId = !empty($request['fm_has_id']) && (string)$request['fm_has_id'] === '1';
        $permission = adminLegacyScreenPermission(
            $fields['fm_com'],
            $fields['fm_act'],
            $fields['fm_variant'],
            $fields['fm_type'],
            $hasId
        );

        /* File manager thay đổi filesystem: quyền chỉ-xem (man) không đủ. */
        if(!is_string($permission) || $permission === '' || $permission === 'SUPERADMIN_ONLY') return null;
        if(!preg_match('/(?:^|_)(?:add|edit|update)(?:_|$)/D', $permission)) return null;
        return $permission;
    }

    function adminFileManagerAccessGranted($active, $role, $debugDeveloper, $permission, $permissions)
    {
        if(!$active || (int)$role === 3 || $debugDeveloper) return true;
        return is_string($permission) && $permission !== ''
            && is_array($permissions) && in_array($permission, $permissions, true);
    }

    /**
     * Quyền cần có cho một màn hình Admin theo com/act, dùng cho cổng chặn tập
     * trung trong requick.php.
     *
     * Trả về một trong ba giá trị:
     *   ''                  - ai đăng nhập cũng dùng được (đăng xuất, hồ sơ cá nhân)
     *   'SUPERADMIN_ONLY'   - chỉ tài khoản role 3, không nhóm quyền nào cấp được
     *   'chuoi_quyen'       - phải có đúng quyền này trong nhóm
     *
     * Hai chỗ trước đây bị bỏ trống hoàn toàn:
     *
     * 1) Toàn bộ com=user được loại khỏi kiểm tra, nên bất kỳ tài khoản nào đăng
     *    nhập được cũng vào thẳng được man_admin/add_admin/save_admin/
     *    delete_admin/permission_group — tức tự tạo cho mình một tài khoản role 3.
     *    Giờ chỉ role 3 mới quản lý được tài khoản và nhóm quyền; các màn hình tự
     *    phục vụ (đăng xuất, xem hồ sơ của chính mình) vẫn mở cho mọi người.
     *
     * 2) Các act ghi dữ liệu (save, save_list, saveImages, uploadExcel...) cũng
     *    được loại khỏi kiểm tra, vì khoá quyền sinh ra là "product_save_..." mà
     *    danh mục quyền không hề có khoá đó. Hệ quả: một tài khoản không được
     *    xem sản phẩm vẫn POST thẳng act=save để ghi. Nay mỗi act ghi được quy
     *    về đúng quyền add hoặc edit tương ứng, chọn theo việc có id hay không.
     */
    function adminLegacyScreenPermission($com, $act, $variant, $type, $hasId)
    {
        $com = (string)$com;
        $act = (string)$act;

        if($com === '' || $act === '') return '';
        if($com === 'index') return '';

        if($com === 'user')
        {
            /* Tự phục vụ: không đụng tới tài khoản người khác. */
            if(in_array($act, array('login', 'logout', 'info_admin'), true)) return '';
            return 'SUPERADMIN_ONLY';
        }

        /* act ghi -> quyền add/edit tương ứng. Giá trị là hậu tố sau tên module. */
        $writeActions = array(
            'save'        => array('add', 'edit'),
            'save_list'   => array('add_list', 'edit_list'),
            'save_cat'    => array('add_cat', 'edit_cat'),
            'save_item'   => array('add_item', 'edit_item'),
            'save_sub'    => array('add_sub', 'edit_sub'),
            'save_brand'  => array('add_brand', 'edit_brand'),
            'save_color'  => array('add_color', 'edit_color'),
            'save_size'   => array('add_size', 'edit_size'),
            'save_photo'  => array('add_photo', 'edit_photo'),
            'saveImages'  => array('edit_photo', 'edit_photo')
        );

        if(isset($writeActions[$act]))
        {
            $pair = $writeActions[$act];
            $suffix = $hasId ? $pair[1] : $pair[0];
            $permission = $com.'_'.$suffix;
            if($variant !== '') $permission .= '_'.$variant;
            if($type !== '') $permission .= '_'.$type;
            return $permission;
        }

        /* Nhập Excel dùng chung quyền với module import. */
        if($act === 'uploadExcel') return $type !== '' ? 'import_man_'.$type : 'SUPERADMIN_ONLY';
        /* Lưu nội dung trang tĩnh dùng quyền update của chính nó. */
        if($act === 'save_static') return $type !== '' ? 'static_update_'.$type : 'SUPERADMIN_ONLY';

        /* Khối nội dung mô tả (content_section) không có khái niệm "thêm mới"
           riêng — chỉ soạn theo scope/scope_id có sẵn (upsert), và act=save
           không gửi kèm 'id' nên $hasId luôn false, khiến quy tắc write mặc
           định ở trên sẽ đòi quyền "add" cho một màn hình không hề có nút
           thêm. Các thao tác phụ trong composer (tải ảnh, nhập nội dung cũ,
           xem khối kế thừa) cũng chỉ là một phần của việc soạn nội dung, nên
           gộp chung một quyền "edit" thay vì tách vụn theo từng endpoint nội
           bộ mà người dùng không hề thấy là hành động riêng. */
        if($com === 'content_section' && in_array($act, array('edit', 'save', 'upload_image', 'import_legacy', 'inherited_blocks'), true))
            return $type !== '' ? 'content_section_edit_'.$type : 'SUPERADMIN_ONLY';

        /* Mặc định giữ nguyên quy ước cũ: com_act[_variant][_type]. */
        $permission = $com.'_'.$act;
        if($variant !== '') $permission .= '_'.$variant;
        if($type !== '') $permission .= '_'.$type;
        return $permission;
    }

    /**
     * Người đang đăng nhập có dùng được thao tác này không — dùng để ẩn nút
     * Thêm/Sửa/Xoá trong template.
     *
     * Vì sao cần: cổng chặn ở requick.php chặn đúng, nhưng template vẫn vẽ đủ
     * nút. Một nhân viên chỉ có quyền XEM sản phẩm vẫn thấy 1 nút "Thêm mới",
     * 80 link "Sửa" và 21 nút "Xoá"; bấm vào là bị đá về trang chủ kèm thông
     * báo từ chối. Đúng về bảo mật nhưng rất ức chế khi dùng.
     *
     * Hàm này gọi lại CHÍNH adminLegacyScreenPermission() mà cổng đang dùng,
     * nên nút hiện ra luôn đúng bằng thứ server cho phép. Nếu tự dựng lại chuỗi
     * quyền ở đây thì hai bên sẽ lệch nhau theo thời gian, và lệch kiểu nào
     * cũng tệ: hoặc thấy nút rồi bị từ chối, hoặc mất nút mình có quyền dùng.
     *
     * $is_permission do requick.php đặt, và chỉ bằng true khi cổng thật sự
     * đang áp dụng. Role 3, cờ debug-developer, hay khi tắt phân quyền đều
     * không đặt cờ này — khi đó hàm trả về true và không ẩn gì cả.
     */
    function adminUiCan($com, $act, $variant = '', $type = '', $hasId = false)
    {
        global $is_permission, $loginAdmin;

        if(empty($is_permission)) return true;

        $permission = adminLegacyScreenPermission($com, $act, $variant, $type, $hasId);
        if($permission === '') return true;
        if($permission === 'SUPERADMIN_ONLY') return false;

        $granted = isset($_SESSION[$loginAdmin]['permissions']) && is_array($_SESSION[$loginAdmin]['permissions'])
            ? $_SESSION[$loginAdmin]['permissions']
            : array();

        return in_array($permission, $granted, true);
    }

    /**
     * Trả về 'd-none' khi KHÔNG có quyền, để gắn thẳng vào class của nút.
     * Giữ đúng lối viết sẵn có của menu.php thay vì bỏ hẳn thẻ khỏi DOM: một số
     * nút được JS bắt theo id (#delete-all, #delete-item), giữ lại thẻ thì
     * không phải rà lại toàn bộ script.
     */
    function adminHideUnless($com, $act, $variant = '', $type = '', $hasId = false)
    {
        return adminUiCan($com, $act, $variant, $type, $hasId) ? '' : ' d-none';
    }
}
