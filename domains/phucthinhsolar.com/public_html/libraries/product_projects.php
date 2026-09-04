<?php
if(!function_exists('productProjectNormalizeIds'))
{
    function productProjectNormalizeIds($ids)
    {
        if(!is_array($ids)) return array();
        $normalized = array_values(array_unique(array_filter(array_map('intval', $ids), function($id) { return $id > 0; })));
        return array_slice($normalized, 0, 500);
    }

    function productProjectSync($d, $projectId, $productIds)
    {
        $projectId = (int)$projectId;
        $productIds = productProjectNormalizeIds($productIds);
        if($projectId <= 0) return false;

        if(!productProjectIdsExist($d, $productIds)) return false;

        $transactionStarted = false;
        try {
            $d->startTransaction();
            $transactionStarted = true;
            $d->rawQuery('delete from #_product_project where id_project = ?', array($projectId));
            foreach($productIds as $position => $productId)
            {
                $d->insert('product_project', array(
                    'id_product' => $productId,
                    'id_project' => $projectId,
                    'numb' => $position + 1,
                    'status' => 'hienthi'
                ));
            }
            $d->commit();
            return true;
        } catch(Throwable $error) {
            if($transactionStarted) $d->rollback();
            return false;
        }
    }

    function productProjectIdsForProject($d, $projectId)
    {
        $rows = $d->rawQuery('select id_product from #_product_project where id_project = ? order by numb,id', array((int)$projectId));
        return array_map(function($row) { return (int)$row['id_product']; }, $rows);
    }

    function productProjectIdsExist($d, $productIds)
    {
        $productIds = productProjectNormalizeIds($productIds);
        if(!$productIds) return true;
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $validRows = $d->rawQuery("select id from #_product where type = ? and id in ($placeholders)", array_merge(array('san-pham'), $productIds));
        $validIds = array_map(function($row) { return (int)$row['id']; }, $validRows);
        sort($validIds);
        sort($productIds);
        return $validIds === $productIds;
    }
}
