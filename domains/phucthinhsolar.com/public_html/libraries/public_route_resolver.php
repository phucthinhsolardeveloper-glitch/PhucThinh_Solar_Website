<?php
function publicRouteIdentifier($identifier)
{
    $identifier = (string)$identifier;
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) ? $identifier : '';
}

function publicRouteResolveSlug($d, $slug, $slugField, array $routes)
{
    $slugField = publicRouteIdentifier($slugField);
    if($slug === '' || $slugField === '') return array();

    $parts = array();
    $params = array();
    foreach($routes as $priority => $route)
    {
        $table = !empty($route['tbl']) ? publicRouteIdentifier($route['tbl']) : '';
        $field = !empty($route['field']) ? publicRouteIdentifier($route['field']) : '';
        $type = isset($route['type']) ? (string)$route['type'] : '';
        $com = isset($route['com']) ? (string)$route['com'] : '';
        if($table === '' || $field === '' || in_array($table, array('static', 'photo'), true)) continue;

        $parts[] = "select id, ? as route_field, ? as route_com, ".(int)$priority." as route_priority from #_{$table} where {$slugField} = ? and type = ? and find_in_set('hienthi',status)";
        $params[] = $field;
        $params[] = $com;
        $params[] = $slug;
        $params[] = $type;
    }

    if(empty($parts)) return array();
    $sql = 'select id, route_field, route_com from ('.implode(' union all ', $parts).') as route_matches order by route_priority limit 0,1';
    $row = $d->rawQueryOne($sql, $params);
    return !empty($row) ? $row : array();
}
?>
