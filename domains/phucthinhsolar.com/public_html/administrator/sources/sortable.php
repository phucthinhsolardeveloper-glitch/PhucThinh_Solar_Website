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
		adminRequireMutationPermission('sortable', 'numb');

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=sortable&act=update", false);
		}
		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$row = $d->rawQueryOne("select id, options from #_setting_sort where id = ? limit 0,1",array($id));
		$option = !empty($row['options']) ? json_decode($row['options'], true) : array();
		if(!is_array($option)) $option = array();
		$data = (!empty($_POST['data']) && is_array($_POST['data'])) ? array_intersect_key($_POST['data'], array_flip(array('hotline','phone','email','options'))) : array();
		if($data)
		{
			foreach($data as $column => $value)
			{
				if($column === 'options' && is_array($value))
				{
					foreach(array_intersect_key($value, array_flip(array('chinhsach','dangky','fanpage'))) as $k2 => $v2)
					{
						$option[$k2] = htmlspecialchars($func->sanitize($v2));
					}

					$data[$column] = json_encode($option);
				}
				else $data[$column] = !empty($value) ? 1 : 0;
			}
		}
		
		/* Form sortable không có trường SEO; không nhận tên cột tùy ý từ POST. */
		$dataSeo = array();

		

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
		$transactionStarted = false;
		try
		{
			$groups = sortableRequestGroups($_POST, $func);
			$d->startTransaction();
			$transactionStarted = true;
			foreach($groups as $table => $items) saveSortableGroup($d, $table, $items);
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
				if(!$d->insert('seo',$dataSeo)) throw new RuntimeException('Sortable SEO insert failed');

				$d->commit();
				$transactionStarted = false;
				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=sortable&act=update");
			}
			else
			{
				throw new RuntimeException('Sortable setting update failed');
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
				if(!$d->insert('seo',$dataSeo)) throw new RuntimeException('Sortable SEO insert failed');

				$d->commit();
				$transactionStarted = false;
				$func->transfer("Thêm dữ liệu thành công", "index.php?com=sortable&act=update");
			}
			else
			{
				throw new RuntimeException('Sortable setting insert failed');
			}
		}
		}
		catch(Throwable $e)
		{
			if($transactionStarted) $d->rollback();
			error_log('Admin sortable save failed: '.$e->getMessage());
			$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=sortable&act=update", false);
		}
	}

	function sortableRequestGroups($post, $func)
	{
		$result = array();
		foreach(array('menu'=>'sortable', 'index'=>'sortable_index', 'detail'=>'sortable_detail') as $group => $table)
		{
			$keys = array('id_item_'.$group, 'stt_item_'.$group, 'ten_item_'.$group, 'type_item_'.$group);
			$present = array_filter($keys, function($key) use ($post) { return isset($post[$key]); });
			if(empty($present)) { $result[$table] = array(); continue; }
			if(count($present) !== 4) throw new InvalidArgumentException('Incomplete sortable group');
			foreach($keys as $key) if(!is_array($post[$key]) || count($post[$key]) > 200) throw new InvalidArgumentException('Invalid sortable group');
			$count = count($post[$keys[0]]);
			foreach($keys as $key) if(count($post[$key]) !== $count) throw new InvalidArgumentException('Mismatched sortable group');
			$result[$table] = array();
			for($i = 0; $i < $count; $i++)
			{
				$id = filter_var($post[$keys[0]][$i], FILTER_VALIDATE_INT, array('options'=>array('min_range'=>0)));
				$order = filter_var($post[$keys[1]][$i], FILTER_VALIDATE_INT, array('options'=>array('min_range'=>-32768, 'max_range'=>32767)));
				$name = trim((string)$post[$keys[2]][$i]);
				$type = trim((string)$post[$keys[3]][$i]);
				if($name === '') continue;
				if($id === false || $order === false || strlen($name) > 255 || !preg_match('/^[a-z0-9-]{1,255}$/i', $type)) throw new InvalidArgumentException('Invalid sortable item');
				$result[$table][] = array('id'=>(int)$id, 'display_order'=>(int)$order, 'name'=>htmlspecialchars($func->sanitize($name)), 'type'=>$type);
			}
		}
		return $result;
	}

	function saveSortableGroup($d, $table, $items)
	{
		if(!in_array($table, array('sortable','sortable_index','sortable_detail'), true)) throw new InvalidArgumentException('Invalid sortable table');
		foreach($items as $item)
		{
			$now = date('Y-m-d H:i:s');
			$data = array('display_order'=>$item['display_order'], 'name'=>$item['name'], 'type'=>$item['type'], 'status'=>1, 'modified'=>$now);
			if($item['id'] > 0)
			{
				$existing = $d->rawQueryOne("select id from #_{$table} where id = ? limit 0,1", array($item['id']));
				if(empty($existing['id'])) throw new InvalidArgumentException('Unknown sortable item');
				$d->where('id', $item['id']);
				if(!$d->update($table, $data)) throw new RuntimeException('Sortable update failed');
			}
			else
			{
				$data['created'] = $now;
				if(!$d->insert($table, $data)) throw new RuntimeException('Sortable insert failed');
			}
		}
	}
