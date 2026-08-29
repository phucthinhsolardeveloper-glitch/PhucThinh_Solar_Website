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
}
