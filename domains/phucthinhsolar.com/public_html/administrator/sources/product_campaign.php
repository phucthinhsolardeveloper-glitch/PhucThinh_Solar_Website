<?php
if(!defined('SOURCES')) die('Error');

require_once LIBRARIES.'product_promo.php';

if(empty($type) || empty($config['product'][$type]))
    $func->transfer('Loại sản phẩm không hợp lệ.', 'index.php', false);

if(!function_exists('productCampaignTablesReady'))
{
    function productCampaignTablesReady($d)
    {
        $schema = $d->rawQueryOne('select database() as name');
        if(empty($schema['name'])) return false;
        $row = $d->rawQueryOne(
            "select count(*) as total from information_schema.tables where table_schema = ? and table_name in (?, ?)",
            array($schema['name'], 'table_product_campaign', 'table_product_campaign_scope')
        );
        return !empty($row['total']) && (int)$row['total'] === 2;
    }

    function productCampaignPermission($action)
    {
        global $config, $loginAdmin, $d;
        $permission = 'product_campaign_'.$action.'_san-pham';
        $role = isset($_SESSION[$loginAdmin]['role']) ? (int)$_SESSION[$loginAdmin]['role'] : 0;
        $permissions = (!empty($config['permission']['active']) && $role !== 3 && empty($config['website']['debug-developer']))
            ? adminLoadPermissions($d, isset($_SESSION[$loginAdmin]['id']) ? (int)$_SESSION[$loginAdmin]['id'] : 0)
            : array();
        if(adminPermissionGranted(!empty($config['permission']['active']), $role, !empty($config['website']['debug-developer']), $permission, $permissions)) return;
        http_response_code(403);
        exit('Bạn không có quyền thực hiện thao tác này.');
    }

    function productCampaignTimestamp($value)
    {
        $value = trim((string)$value);
        if($value === '') return 0;
        $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
        return ($date && $date->format('Y-m-d\TH:i') === $value) ? (int)$date->getTimestamp() : -1;
    }

    function productCampaignAllowedIds($d, $table, $ids, $type)
    {
        $allowedTables = array('list' => 'product_list', 'cat' => 'product_cat', 'brand' => 'product_brand', 'product' => 'product');
        if(!isset($allowedTables[$table])) return array();
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : array()))));
        if(!$ids || count($ids) > 5000) return array();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $d->rawQuery("select id from #_{$allowedTables[$table]} where type = ? and id in ($placeholders)", array_merge(array($type), $ids));
        $found = array();
        foreach($rows as $row) $found[] = (int)$row['id'];
        sort($found);
        sort($ids);
        return $found === $ids ? $ids : array();
    }

    function productCampaignScopesFromPost($d, $type, &$error)
    {
        if(!empty($_POST['scope_all'])) return array(array('scope_type' => 'all', 'scope_id' => 0));
        $posted = !empty($_POST['scopes']) && is_array($_POST['scopes']) ? $_POST['scopes'] : array();
        $scopes = array();
        foreach(array('list','cat','brand','product') as $scopeType)
        {
            $raw = isset($posted[$scopeType]) && is_array($posted[$scopeType]) ? $posted[$scopeType] : array();
            $ids = productCampaignAllowedIds($d, $scopeType, $raw, $type);
            $requested = array_values(array_unique(array_filter(array_map('intval', $raw))));
            if($requested && !$ids)
            {
                $error = 'Phạm vi chiến dịch chứa đối tượng không hợp lệ.';
                return array();
            }
            foreach($ids as $scopeId) $scopes[] = array('scope_type' => $scopeType, 'scope_id' => $scopeId);
        }
        if(!$scopes) $error = 'Hãy chọn ít nhất một danh mục, thương hiệu hoặc sản phẩm.';
        return $scopes;
    }

    function productCampaignList()
    {
        global $d, $campaigns;
        productCampaignPermission('man');
        $campaigns = $d->rawQuery(
            "select c.*, (select count(*) from #_product_campaign_scope sc where sc.id_campaign = c.id) as scope_count, (select group_concat(distinct st.scope_type order by field(st.scope_type,'product','cat','brand','list','all') separator ',') from #_product_campaign_scope st where st.id_campaign = c.id) as scope_types from #_product_campaign c order by c.priority desc,c.id desc"
        );
    }

    function productCampaignForm()
    {
        global $d, $func, $type, $campaign, $campaignScopes, $campaignLists, $campaignCats, $campaignBrands, $campaignProducts;
        productCampaignPermission(!empty($_GET['id']) ? 'edit' : 'add');
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
        $campaign = $id ? $d->rawQueryOne('select * from #_product_campaign where id = ? limit 1', array($id)) : array();
        if($id && !$campaign) $func->transfer('Không tìm thấy chiến dịch', 'index.php?com=product_campaign&act=man&type='.$type, false);
        $campaignScopes = $id ? $d->rawQuery('select scope_type, scope_id from #_product_campaign_scope where id_campaign = ? order by id', array($id)) : array();
        /* Keep hidden records selectable so editing a campaign never silently drops an existing scope. */
        $campaignLists = $d->rawQuery("select id, namevi, status from #_product_list where type = ? order by numb,id", array($type));
        $campaignCats = $d->rawQuery("select pc.id, pc.namevi, pc.status, pl.namevi as parent_name from #_product_cat pc left join #_product_list pl on pl.id = pc.id_list where pc.type = ? order by pl.numb,pc.numb,pc.id", array($type));
        $campaignBrands = $d->rawQuery("select id, namevi, status from #_product_brand where type = ? order by namevi,id", array($type));
        $campaignProducts = $d->rawQuery("select id, code, namevi, status from #_product where type = ? order by namevi,id", array($type));
    }

    function productCampaignSave()
    {
        global $d, $func, $config, $type;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        productCampaignPermission($id ? 'edit' : 'add');
        $back = 'index.php?com=product_campaign&act='.($id ? 'edit&id='.$id : 'add').'&type='.$type;
        $current = $id ? $d->rawQueryOne('select * from #_product_campaign where id = ? limit 1', array($id)) : array();
        if($id && !$current) $func->transfer('Không tìm thấy chiến dịch', 'index.php?com=product_campaign&act=man&type='.$type, false);

        $data = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : array();
        $name = trim(strip_tags(isset($data['name']) ? (string)$data['name'] : ''));
        $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
        $startAt = productCampaignTimestamp(isset($data['start_at']) ? $data['start_at'] : '');
        $endAt = productCampaignTimestamp(isset($data['end_at']) ? $data['end_at'] : '');
        $error = '';
        if($name === '' || mb_strlen($name) > 255) $error = 'Tên chiến dịch không hợp lệ.';
        elseif($priority < -1000 || $priority > 1000) $error = 'Mức ưu tiên chỉ nhận từ -1000 đến 1000.';
        elseif($startAt < 0 || $endAt < 0 || ($startAt && $endAt && $endAt < $startAt)) $error = 'Thời gian chiến dịch không hợp lệ.';
        $scopes = productCampaignScopesFromPost($d, $type, $error);
        if($error !== '') $func->transfer($error, $back, false);

        $photo = !empty($current['photo']) ? $current['photo'] : '';
        $uploadedPhoto = '';
        if($func->hasFile('file'))
        {
            $fileName = $func->uploadName($_FILES['file']['name']);
            $uploadedPhoto = $func->uploadImage('file', '.png', UPLOAD_PHOTO, $fileName);
            if(!$uploadedPhoto) $func->transfer('Khung phải là ảnh PNG hợp lệ và không vượt dung lượng cho phép.', $back, false);
            $photo = $uploadedPhoto;
        }
        if($photo === '') $func->transfer('Hãy upload khung PNG cho chiến dịch.', $back, false);

        $row = array(
            'name' => $name,
            'photo' => $photo,
            'priority' => $priority,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => !empty($_POST['enabled']) ? 'hienthi' : '',
            'date_updated' => time()
        );

        $d->startTransaction();
        try
        {
            if($id)
            {
                $d->where('id', $id);
                if(!$d->update('product_campaign', $row)) throw new RuntimeException('Không cập nhật được chiến dịch.');
            }
            else
            {
                $row['date_created'] = time();
                if(!$d->insert('product_campaign', $row)) throw new RuntimeException('Không tạo được chiến dịch.');
                $id = (int)$d->getLastInsertId();
            }
            $d->rawQuery('delete from #_product_campaign_scope where id_campaign = ?', array($id));
            foreach($scopes as $scope)
                if(!$d->insert('product_campaign_scope', array('id_campaign' => $id, 'scope_type' => $scope['scope_type'], 'scope_id' => $scope['scope_id'])))
                    throw new RuntimeException('Không lưu được phạm vi chiến dịch.');
            $d->commit();
        }
        catch(Throwable $exception)
        {
            $d->rollback();
            if($uploadedPhoto !== '') $func->deleteFile(UPLOAD_PHOTO.$uploadedPhoto);
            $func->transfer('Không thể lưu chiến dịch; dữ liệu cũ được giữ nguyên.', $back, false);
        }
        $func->transfer('Đã lưu chiến dịch và '.count($scopes).' phạm vi áp dụng.', 'index.php?com=product_campaign&act=man&type='.$type);
    }
}

if(!productCampaignTablesReady($d))
    $func->transfer('Chưa có bảng chiến dịch. Hãy chạy migration 022 trước khi dùng chức năng này.', 'index.php', false);

switch($act)
{
    case 'add':
    case 'edit':
        productCampaignForm();
        $template = 'product_campaign/form';
        break;
    case 'save':
        productCampaignSave();
        break;
    case 'man':
    default:
        productCampaignList();
        $template = 'product_campaign/list';
        break;
}
?>
