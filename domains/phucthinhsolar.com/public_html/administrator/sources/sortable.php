<?php
	if(!defined('SOURCES')) die("Error");

	switch($act)
	{
		case "update":
			viewSortable();
			$template = "sortable/man/man_add";
			break;
		case "save":
			saveSortable();
			break;
			
		default:
			$template = "404";
	}

	/* View sortable */
	function viewSortable()
	{
		global $d, $item;

		$item = $d->rawQueryOne("select * from #_setting_sort limit 0,1");
	}
	/* Save sortable */
	function saveSortable()
	{
		global $d, $func, $flash, $config, $com;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=sortable&act=update", false);
		}
		var_dump($_POST);
		//die('xxxx');
		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$row = $d->rawQueryOne("select id, options from #_setting_sort where id = ? limit 0,1",array($id));
		$option = json_decode($row['options'],true);
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(is_array($value))
				{
					foreach($value as $k2 => $v2)
					{
						if($k2 == 'coords_iframe')
						{
							$option[$k2] = $func->sanitize($v2, 'iframe');
						}
						else
						{
							$option[$k2] = $v2;
						}
					}

					$data[$column] = json_encode($option);
				}
				else
				{
					if($column == 'mastertool')
					{
						$data[$column] = htmlspecialchars($func->sanitize($value, 'meta'));
					}
					else if(in_array($column, array('headjs', 'bodyjs', 'analytics')))
					{
						$data[$column] = htmlspecialchars($func->sanitize($value, 'script'));
					}
					else
					{
						$data[$column] = htmlspecialchars($func->sanitize($value));
					}
				}
			}
		}
		
		/* Post Seo */
		$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
		if($dataSeo)
		{
			foreach($dataSeo as $column => $value)
			{
				$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
			}
		}

		

		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($option))
			{
				foreach($option as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);
			$func->redirect("index.php?com=sortable&act=update");
		}
		$data['hotline'] = isset($data['hotline']) ? 1 : 0;
		$data['phone'] = isset($data['phone']) ? 1 : 0;
		$data['email'] = isset($data['email']) ? 1 : 0;

			// phần menu
				for($i=0;$i<count($_POST['id_item_menu']);$i++){
					 if($_POST['ten_item_menu'][$i]!='')
					 {
						 if($_POST['id_item_menu'][$i]>0){
								$data2['display_order'] = $_POST['stt_item_menu'][$i];
								$data2['name'] = $_POST['ten_item_menu'][$i];
								$data2['type'] = $_POST['type_item_menu'][$i];
								$data2['created'] = time();
								$data2['status'] = 1;

								$d->where('id', $_POST['id_item_menu'][$i]);
								$d->update('sortable',$data2);
						 }
						 else{
								$data2['display_order'] = $_POST['stt_item_menu'][$i];
								$data2['name'] = $_POST['ten_item_menu'][$i];
								$data2['type'] = $_POST['type_item_menu'][$i];

								$data2['modified'] = time();
								$data2['status'] = 1;
								$d->insert('sortable',$data2);
						 }
					 }
				 }
			// phần index
				for($i=0;$i<count($_POST['id_item_index']);$i++){
					 if($_POST['ten_item_index'][$i]!='')
					 {
						 if($_POST['id_item_index'][$i]>0){
								$data3['display_order'] = $_POST['stt_item_index'][$i];
								$data3['name'] = $_POST['ten_item_index'][$i];
								$data3['type'] = $_POST['type_item_index'][$i];
								$data3['created'] = time();
								$data3['status'] = 1;

								$d->where('id', $_POST['id_item_index'][$i]);
								$d->update('sortable_index',$data3);
						 }
						 else{
								$data3['display_order'] = $_POST['stt_item_index'][$i];
								$data3['name'] = $_POST['ten_item_index'][$i];
								$data3['type'] = $_POST['type_item_index'][$i];

								$data3['modified'] = time();
								$data3['status'] = 1;
								$d->insert('sortable_index',$data3);
						 }
					 }
				 }

			// phần detail
				for($i=0;$i<count($_POST['id_item_detail']);$i++){
					 if($_POST['ten_item_detail'][$i]!='')
					 {
						 if($_POST['id_item_detail'][$i]>0){
								$data4['display_order'] = $_POST['stt_item_detail'][$i];
								$data4['name'] = $_POST['ten_item_detail'][$i];
								$data4['type'] = $_POST['type_item_detail'][$i];
								$data4['created'] = time();
								$data4['status'] = 1;

								$d->where('id', $_POST['id_item_detail'][$i]);
								$d->update('sortable_detail',$data4);
						 }
						 else{
								$data4['display_order'] = $_POST['stt_item_detail'][$i];
								$data4['name'] = $_POST['ten_item_detail'][$i];
								$data4['type'] = $_POST['type_item_detail'][$i];

								$data4['modified'] = time();
								$data4['status'] = 1;
								$d->insert('sortable_detail',$data4);
						 }
					 }
				 }
			// 
		/* Save data */
		if(!empty($row))
		{
			$d->where('id',$id);
			if($d->update('setting_sort',$data))
			{
				/* SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array(0,$com,'update',$com));
				$dataSeo['id_parent'] = 0;
				$dataSeo['com'] = $com;
				$dataSeo['act'] = 'update';
				$dataSeo['type'] = $com;
				$d->insert('seo',$dataSeo);

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=sortable&act=update");
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=sortable&act=update", false);
			}
		}
		else
		{
			if($d->insert('setting_sort',$data))
			{
				/* SEO */
				$dataSeo['id_parent'] = 0;
				$dataSeo['com'] = $com;
				$dataSeo['act'] = 'update';
				$dataSeo['type'] = $com;
				$d->insert('seo',$dataSeo);

				$func->transfer("Thêm dữ liệu thành công", "index.php?com=sortable&act=update");
			}
			else
			{
				$func->transfer("Thêm dữ liệu bị lỗi", "index.php?com=sortable&act=update", false);
			}
		}
	}
