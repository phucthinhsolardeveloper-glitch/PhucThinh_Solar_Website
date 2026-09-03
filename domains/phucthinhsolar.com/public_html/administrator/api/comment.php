<?php 
    include "config.php";

    $routes = array('limitLists' => ['limitLists', 'GET', ''], 'limitReplies' => ['limitReplies', 'GET', ''], 'addAdmin' => ['addAdmin', 'POST', 'add'], 'status' => ['status', 'POST', 'status'], 'delete' => ['delete', 'POST', 'delete']);
    $route = (!empty($_GET['get']) && !empty($routes[$_GET['get']])) ? $_GET['get'] : false;

    if(!empty($route))
    {
		if($routes[$route][1] === 'POST')
		{
			adminRequirePostCsrf(true);
			if($route === 'addAdmin')
			{
				$review = isset($_POST['dataReview']) && is_array($_POST['dataReview']) ? $_POST['dataReview'] : array();
				$parentId = isset($review['id_parent']) ? (int)$review['id_parent'] : 0;
				$parentRow = $parentId > 0 ? $d->rawQueryOne('select type from #_comment where id = ? limit 0,1', array($parentId)) : array();
				$type = isset($parentRow['type']) ? (string)$parentRow['type'] : (isset($review['type']) ? (string)$review['type'] : '');
			}
			else
			{
				$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
				$commentRow = $id > 0 ? $d->rawQueryOne('select type from #_comment where id = ? limit 0,1', array($id)) : array();
				$type = isset($commentRow['type']) ? (string)$commentRow['type'] : '';
			}
			adminRequireMutationPermission('comment', $routes[$route][2], $type, '', true);
		}
        $comment = new Comments($d, $func);
        $method = $routes[$route][0];
        $requestType = $routes[$route][1];

        if(method_exists($comment, $method) && $_SERVER['REQUEST_METHOD'] == $requestType)
        {
            print $comment->$method();
        }
		else
		{
			http_response_code(405);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(adminApiEnvelope(false, 'Phương thức request không hợp lệ.', 'METHOD_NOT_ALLOWED'));
		}
    }
	else
	{
		http_response_code(404);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(adminApiEnvelope(false, 'API bình luận không tồn tại.', 'COMMENT_ROUTE_NOT_FOUND'));
	}
?>
