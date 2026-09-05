<?php
if(!function_exists('productPromoStatusEnabled'))
{
    function productPromoStatusEnabled($product)
    {
        $statuses = array_filter(array_map('trim', explode(',', isset($product['status']) ? (string)$product['status'] : '')));
        return in_array('khuyenmai', $statuses, true);
    }

    function productPromoCampaignSpecificity($campaign, $product, $now = null)
    {
        $now = $now === null ? time() : (int)$now;
        if(empty($campaign['photo']) || empty($campaign['enabled'])) return -1;
        if(!empty($campaign['start_at']) && (int)$campaign['start_at'] > $now) return -1;
        if(!empty($campaign['end_at']) && (int)$campaign['end_at'] < $now) return -1;

        $weights = array('all' => 0, 'list' => 10, 'brand' => 20, 'cat' => 30, 'product' => 40);
        $productValues = array(
            'all' => 0,
            'list' => isset($product['id_list']) ? (int)$product['id_list'] : 0,
            'cat' => isset($product['id_cat']) ? (int)$product['id_cat'] : 0,
            'brand' => isset($product['id_brand']) ? (int)$product['id_brand'] : 0,
            'product' => isset($product['id']) ? (int)$product['id'] : 0
        );
        $best = -1;
        foreach(isset($campaign['scopes']) && is_array($campaign['scopes']) ? $campaign['scopes'] : array() as $scope)
        {
            $scopeType = isset($scope['scope_type']) ? (string)$scope['scope_type'] : '';
            $scopeId = isset($scope['scope_id']) ? (int)$scope['scope_id'] : 0;
            if(!isset($weights[$scopeType])) continue;
            if($scopeType === 'all' || ($productValues[$scopeType] > 0 && $productValues[$scopeType] === $scopeId))
                $best = max($best, $weights[$scopeType]);
        }
        return $best;
    }

    function productPromoFrameResolve($product, $campaigns = array(), $now = null)
    {
        /* Ảnh riêng cũ là override có chủ ý và vẫn cần cờ Khuyến mãi/Sự kiện. */
        $productPhoto = trim(isset($product['photo_khuyenmai']) ? (string)$product['photo_khuyenmai'] : '');
        if($productPhoto !== '' && productPromoStatusEnabled($product))
            return array('photo' => $productPhoto, 'upload' => UPLOAD_PRODUCT_L, 'source' => 'product', 'campaign_id' => 0);

        $winner = array();
        $winnerPriority = PHP_INT_MIN;
        $winnerSpecificity = -1;
        $winnerId = 0;
        foreach(is_array($campaigns) ? $campaigns : array() as $campaign)
        {
            $specificity = productPromoCampaignSpecificity($campaign, $product, $now);
            if($specificity < 0) continue;
            $priority = isset($campaign['priority']) ? (int)$campaign['priority'] : 0;
            $campaignId = isset($campaign['id']) ? (int)$campaign['id'] : 0;
            if(
                $priority > $winnerPriority
                || ($priority === $winnerPriority && $specificity > $winnerSpecificity)
                || ($priority === $winnerPriority && $specificity === $winnerSpecificity && $campaignId > $winnerId)
            )
            {
                $winner = $campaign;
                $winnerPriority = $priority;
                $winnerSpecificity = $specificity;
                $winnerId = $campaignId;
            }
        }

        if(!empty($winner['photo']))
        {
            return array(
                'photo' => (string)$winner['photo'],
                'upload' => UPLOAD_PHOTO_L,
                'source' => 'campaign',
                'campaign_id' => $winnerId,
                'campaign_name' => isset($winner['name']) ? (string)$winner['name'] : ''
            );
        }

        return array();
    }

    function productPromoCampaigns($cache)
    {
        static $campaignSets = array();
        $cacheKey = is_object($cache) ? spl_object_hash($cache) : 'default';
        if(!array_key_exists($cacheKey, $campaignSets))
        {
            $campaigns = array();
            try
            {
                $rows = $cache->get(
                    "select c.id, c.name, c.photo, c.priority, c.start_at, c.end_at, c.status, s.scope_type, s.scope_id from #_product_campaign c inner join #_product_campaign_scope s on s.id_campaign = c.id where find_in_set('hienthi',c.status) and coalesce(trim(c.photo), '') <> '' order by c.priority desc,c.id desc,s.id",
                    null,
                    'result',
                    300
                );
                foreach(is_array($rows) ? $rows : array() as $row)
                {
                    $campaignId = (int)$row['id'];
                    if(!isset($campaigns[$campaignId]))
                    {
                        $campaigns[$campaignId] = array(
                            'id' => $campaignId,
                            'name' => $row['name'],
                            'photo' => $row['photo'],
                            'priority' => (int)$row['priority'],
                            'start_at' => (int)$row['start_at'],
                            'end_at' => (int)$row['end_at'],
                            'enabled' => true,
                            'scopes' => array()
                        );
                    }
                    $campaigns[$campaignId]['scopes'][] = array('scope_type' => $row['scope_type'], 'scope_id' => (int)$row['scope_id']);
                }
            }
            catch(Throwable $exception)
            {
                /* Cho phép source mới chạy cùng DB chưa migrate: card cũ vẫn hoạt động. */
                error_log('Product campaign tables are unavailable; using legacy product frames only.');
            }
            $campaignSets[$cacheKey] = array_values($campaigns);
        }
        return $campaignSets[$cacheKey];
    }

    function productPromoFrameForCard($product, $cache)
    {
        return productPromoFrameResolve($product, productPromoCampaigns($cache));
    }
}
?>
