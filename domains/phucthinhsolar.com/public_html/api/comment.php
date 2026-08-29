<?php 
    include "config.php";

    $routes = array('limitLists' => ['limitLists', 'GET'], 'limitReplies' => ['limitReplies', 'GET'], 'add' => ['add', 'POST']);
    $route = (!empty($_GET['get']) && !empty($routes[$_GET['get']])) ? $_GET['get'] : false;

    if(!empty($route))
    {
		if($route === 'add' && (!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '') || !formRateLimit('review', 5, 600)))
		{
			http_response_code(403);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('errors' => array('Phiên gửi nhận xét không hợp lệ hoặc thao tác quá nhanh.')));
			exit();
		}
        $comment = new Comments($d, $func);
        $method = $routes[$route][0];
        $requestType = $routes[$route][1];

        if(method_exists($comment, $method) && $_SERVER['REQUEST_METHOD'] == $requestType)
        {
            print $comment->$method();
        }
    }
?>
