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

    function adminRequirePostCsrf($json = false)
    {
        $valid = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
            && adminCsrfValid(isset($_POST['admin_csrf']) ? $_POST['admin_csrf'] : '');
        if($valid) return;
        http_response_code(403);
        if($json) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('success' => false, 'msg' => 'Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.'));
        }
        exit();
    }

    function adminAllowedMutationTable($table, $capability)
    {
        $tables = array(
            'status' => array('product','product_list','product_cat','product_item','product_sub','product_brand','news','news_list','news_cat','news_item','news_sub','gallery','photo','tags','size','color','user','member','permission_group','city','district','ward','contact'),
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

    function adminMutationPermissionKey($table, $capability, $type = '', $com = '')
    {
        $type = is_string($type) && preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $type) ? $type : '';
        $actions = array('status' => 'edit', 'numb' => 'edit', 'copy' => 'add');
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
            'pushonesignal' => array('pushOnesignal', '')
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
        if(!in_array($module, array('order', 'places', 'pushOnesignal'), true))
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
            $group = $idUser > 0 ? $d->rawQueryOne("select id_permission from #_user where id = ? and find_in_set('hienthi',status) limit 0,1", array($idUser)) : array();
            if(!empty($group['id_permission']))
            {
                $rows = $d->rawQuery("select permission from #_permission where id_permission_group = ?", array((int)$group['id_permission']));
                foreach($rows as $row) if(!empty($row['permission'])) $permissions[] = $row['permission'];
            }
        }

        if(adminPermissionGranted($active, $role, $debugDeveloper, $permission, $permissions)) return;
        http_response_code(403);
        if($json)
        {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('success' => false, 'msg' => 'Tài khoản không có quyền thực hiện thao tác này.'));
        }
        exit();
    }
}
