<?php
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_projects.php';

	/* Kiểm tra active news */
	if(isset($config['news']))
	{
		$arrCheck = array();
		foreach($config['news'] as $k => $v) $arrCheck[] = $k;
		if(!count($arrCheck) || !in_array($type,$arrCheck)) $func->transfer("Trang không tồn tại", "index.php", false);
	}
	else
	{
		$func->transfer("Trang không tồn tại", "index.php", false);
	}

	/* Cấu hình đường dẫn trả về */
	$strUrl = "";
	$arrUrl = array('id_list','id_cat','id_item','id_sub','id_brand');
	if(isset($_POST['data']))
	{
		$dataUrl = isset($_POST['data']) ? $_POST['data'] : null;
		if($dataUrl)
		{
			foreach($arrUrl as $k => $v)
			{
				if(isset($dataUrl[$arrUrl[$k]])) $strUrl .= "&".$arrUrl[$k]."=".htmlspecialchars($dataUrl[$arrUrl[$k]]);
			}
		}
	}
	else
	{
		foreach($arrUrl as $k => $v)
		{
			if(isset($_REQUEST[$arrUrl[$k]])) $strUrl .= "&".$arrUrl[$k]."=".htmlspecialchars($_REQUEST[$arrUrl[$k]]);
		}

		if(!empty($_REQUEST['comment_status'])) $strUrl .= "&comment_status=".htmlspecialchars($_REQUEST['comment_status']);
		if(isset($_REQUEST['keyword']) && is_scalar($_REQUEST['keyword'])) $strUrl .= "&keyword=".urlencode((string)$_REQUEST['keyword']);
	}
	$adminPerPageOptions = array(10, 20, 50, 100);
	$adminPerPage = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 20;
	if(!in_array($adminPerPage, $adminPerPageOptions, true)) $adminPerPage = 20;
	$strUrl .= "&per_page=".$adminPerPage;

	switch($act)
	{
		/* Man */
		case "man":
			viewMans();
			$template = "news/man/mans";
			break;
		case "add":
			$template = "news/man/man_add";
			break;
		case "edit":
		case "copy":
			if((!isset($config['news'][$type]['copy']) || $config['news'][$type]['copy'] == false) && $act=='copy')
			{
				$template = "404";
				return false;
			}
			editMan();
			$template = "news/man/man_add";
			break;
		case "save":
		case "save_copy":
			saveMan();
			break;
		case "delete":
			deleteMan();
			break;

		/* List */
		case "man_list":
			viewLists();
			$template = "news/list/lists";
			break;
		case "add_list":
			$template = "news/list/list_add";
			break;
		case "edit_list":
			editList();
			$template = "news/list/list_add";
			break;
		case "save_list":
			saveList();
			break;
		case "delete_list":
			deleteList();
			break;

		/* Cat */
		case "man_cat":
			viewCats();
			$template = "news/cat/cats";
			break;
		case "add_cat":
			$template = "news/cat/cat_add";
			break;
		case "edit_cat":
			editCat();
			$template = "news/cat/cat_add";
			break;
		case "save_cat":
			saveCat();
			break;
		case "delete_cat":
			deleteCat();
			break;

		/* Item */
		case "man_item":
			viewItems();
			$template = "news/item/items";
			break;
		case "add_item":
			$template = "news/item/item_add";
			break;
		case "edit_item":
			editItem();
			$template = "news/item/item_add";
			break;
		case "save_item":
			saveItem();
			break;
		case "delete_item":
			deleteItem();
			break;

		/* Sub */
		case "man_sub":
			viewSubs();
			$template = "news/sub/subs";
			break;
		case "add_sub":
			$template = "news/sub/sub_add";
			break;
		case "edit_sub":
			editSub();
			$template = "news/sub/sub_add";
			break;
		case "save_sub":
			saveSub();
			break;
		case "delete_sub":
			deleteSub();
			break;
		
		/* Gallery */
		case "man_photo":
		case "add_photo":
		case "edit_photo":
		case "save_photo":
		case "delete_photo":
			include "gallery.php";
			break;

		default:
			$template = "404";
	}

	$projectProducts = array();
	$projectProductIds = array();
	if($type === 'du-an' && in_array($act, array('add', 'edit'), true))
	{
		$projectProducts = $d->rawQuery("select id, code, namevi, status from #_product where type = ? order by namevi,id", array('san-pham'));
		if(!empty($item['id'])) $projectProductIds = productProjectIdsForProject($d, $item['id']);
	}

	/* View man */
	function viewMans()
	{
		global $d, $func, $comment, $strUrl, $curPage, $items, $paging, $type, $adminPerPage;

		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$idcat = isset($_REQUEST['id_cat']) ? (int)$_REQUEST['id_cat'] : 0;
		$iditem = isset($_REQUEST['id_item']) ? (int)$_REQUEST['id_item'] : 0;
		$idsub = isset($_REQUEST['id_sub']) ? (int)$_REQUEST['id_sub'] : 0;
		$comment_status = (!empty($_REQUEST['comment_status'])) ? htmlspecialchars($_REQUEST['comment_status']) : '';

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if($idcat > 0) { $where .= " and id_cat = ?"; $params[] = $idcat; }
		if($iditem > 0) { $where .= " and id_item = ?"; $params[] = $iditem; }
		if($idsub > 0) { $where .= " and id_sub = ?"; $params[] = $idsub; }
		if($comment_status == 'new')
		{
			$comment = $d->rawQuery("select distinct id_variant from #_comment where type = ? and find_in_set('new-admin',status)", array($type));
			$idcomment = (!empty($comment)) ? $func->joinCols($comment, 'id_variant') : 0;
			$where .= " and id in ($idcomment)";
		}
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = $adminPerPage;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_news where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=news&act=man".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* Comment */
		$comment = new Comments($d, $func);
	}

	/* Edit man */
	function editMan()
	{
		global $d, $strUrl, $func, $curPage, $item, $gallery, $type, $com, $act;

		if(!empty($_GET['id'])) $id = htmlspecialchars($_GET['id']);
		else if(!empty($_GET['id_copy'])) $id = htmlspecialchars($_GET['id_copy']);
		else $id = 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_news where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
			}
			else
			{
				/* Get gallery */
				if($act != 'copy')
				{
					$gallery = $d->rawQuery("select * from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc",array($id,$com,$type,'man',$type));
				}
			}
		}
	}

	/* Save man */
	function saveMan()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $act, $type, $id_user;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$savehere = (isset($_POST['save-here'])) ? true : false;
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = !empty($_POST['data']) ? $_POST['data'] : null;
		$dataTags = (!empty($_POST['dataTags'])) ? $_POST['dataTags'] : null;
		$dataProjectProducts = ($type === 'du-an') ? productProjectNormalizeIds(isset($_POST['project_products']) ? $_POST['project_products'] : array()) : array();

		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false || strpos($column, 'map_iframe') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(!empty($config['news'][$type]['slug']))
			{
				if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
				else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
				if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
				else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['news'][$type]['seo']) && $config['news'][$type]['seo'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		if($type === 'du-an' && !productProjectIdsExist($d, $dataProjectProducts))
			$response['messages'][] = 'Danh sách sản phẩm liên kết có dữ liệu không hợp lệ';
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['news'][$type]['slug']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = ($act == 'save_copy') ? true : false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
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

			if($id || $act == 'save_copy')
			{
				if($act == 'save_copy')
				{
					$func->redirect("index.php?com=news&act=copy&type=".$type."&p=".$curPage.$strUrl."&id_copy=".$id.adminReturnSuffix());
				}
				else
				{
					$func->redirect("index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix());
				}
			}
			else
			{
				$func->redirect("index.php?com=news&act=add&type=".$type."&p=".$curPage.$strUrl.adminReturnSuffix());
			}
		}

		/* Save data */
		if($id && $act!='save_copy')
		{
			$data['date_updated'] = time();

			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('news',$data))
			{
				if($type === 'du-an' && !productProjectSync($d, $id, $dataProjectProducts))
					$func->transfer("Dự án đã lưu nhưng liên kết sản phẩm bị lỗi", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix(), false);

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);
					
					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type'], UPLOAD_NEWS, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_news where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('news', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file1"))
				{
					$photoUpdate = array();
					$file_name1 = $func->uploadName($_FILES["file1"]["name"]);
					
					if($photo1 = $func->uploadImage("file1", $config['news'][$type]['img_type1'], UPLOAD_NEWS, $file_name1))
					{
						$row = $d->rawQueryOne("select id, photo1 from #_news where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo1']);
						}

						$photoUpdate['photo1'] = $photo1;
						$d->where('id', $id);
						$d->update('news', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo']) && $config['news'][$type]['seo'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Tags */
				if(isset($config['news'][$type]['tags']) && $config['news'][$type]['tags'] == true)
				{
					if($dataTags)
					{
						$d->rawQuery("delete from #_news_tags where id_parent = ?",array($id));
						foreach($dataTags as $v)
						{
							$dataTag = array();
							$dataTag['id_parent'] = $id;
							$dataTag['id_tags'] = $v;
							$d->insert('news_tags',$dataTag);
						}
					}
					else
					{
						$d->rawQuery("delete from #_news_tags where id_parent = ?",array($id));
					}
				}

				if($savehere)
				{
					$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix());
				}
				else
				{
					$func->transfer("Cập nhật dữ liệu thành công", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl));
				}
			}
			else
			{
				if($savehere)
				{
					$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix(), false);
				}
				else
				{
					$func->transfer("Cập nhật dữ liệu bị lỗi", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
				}
			}
		}
		else
		{
			$data['date_created'] = time();
			$data['id_user'] = (int) $id_user;

			if($d->insert('news',$data))
			{
				$id_insert = $d->getLastInsertId();
				if($type === 'du-an' && !productProjectSync($d, $id_insert, $dataProjectProducts))
					$func->transfer("Dự án đã lưu nhưng liên kết sản phẩm bị lỗi", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix(), false);

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type'], UPLOAD_NEWS, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('news', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file1"))
				{
					$photoUpdate = array();
					$file_name1 = $func->uploadName($_FILES['file1']["name"]);

					if($photo1 = $func->uploadImage("file1", $config['news'][$type]['img_type1'], UPLOAD_NEWS, $file_name1))
					{
						$photoUpdate['photo1'] = $photo1;
						$d->where('id', $id_insert);
						$d->update('news', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo']) && $config['news'][$type]['seo'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Tags */
				if(isset($config['news'][$type]['tags']) && $config['news'][$type]['tags'] == true)
				{
					if($dataTags)
					{
						foreach($dataTags as $v)
						{
							$dataTag = array();
							$dataTag['id_parent'] = $id_insert;
							$dataTag['id_tags'] = $v;
							$d->insert('news_tags',$dataTag);
						}
					}
				}

				/* Cập nhật hash khi upload multi */
				$hash = (isset($_POST['hash']) && $_POST['hash'] != '') ? addslashes($_POST['hash']) : null;
				if($hash)
				{
					$d->rawQuery("update #_gallery set hash = ?, id_parent = ? where hash = ?",array('',$id_insert,$hash));
				}

				if($act=='save_copy')
				{
					if($savehere)
					{
						$func->transfer("Sao chép dữ liệu thành công", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix());
					}
					else
					{
						$func->transfer("Sao chép dữ liệu thành công", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl));
					}
				}
				else
				{
					if($savehere)
					{
						$func->transfer("Lưu dữ liệu thành công", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix());
					}
					else
					{
						$func->transfer("Lưu dữ liệu thành công", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl));
					}
				}
			}
			else
			{
				if($act=='save_copy')
				{
					if($savehere)
					{
						$func->transfer("Sao chép dữ liệu bị lỗi", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix(), false);
					}
					else
					{
						$func->transfer("Sao chép dữ liệu bị lỗi", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
					}
				}
				else
				{
					if($savehere)
					{
						$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=news&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix(), false);
					}
					else
					{
						$func->transfer("Lưu dữ liệu bị lỗi", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
					}
				}
			}
		}
	}

	/* Delete man */
	function deleteMan()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_news where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_NEWS.$row['photo']);
				$d->rawQuery("delete from #_news where id = ?",array($id));
				if($type === 'du-an') $d->rawQuery("delete from #_product_project where id_project = ?", array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

				/* Xóa Tags */
				$d->rawQuery("delete from #_news_tags where id_parent = ?",array($id));

				/* Xóa gallery */
				$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));

				if(count($row))
				{
					foreach($row as $v)
					{
						$func->deleteFile(UPLOAD_NEWS.$v['photo']);
						$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
					}

					$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));
				}

				/* Xóa comment */
				$rowComment = $d->rawQuery("select id, id_parent from #_comment where id_variant = ? and type = ?",array($id,$type));

				if(!empty($rowComment))
				{
					foreach($rowComment as $v)
					{
						if($v['id_parent'] == 0)
						{
							/* Xóa comment photo */
							$rowCommentPhoto = $d->rawQuery("select photo from #_comment_photo where id_parent = ?",array($v['id']));

							if(!empty($rowCommentPhoto))
							{
								/* Xóa image */
								foreach($rowCommentPhoto as $v_photo)
								{
									$func->deleteFile(UPLOAD_PHOTO.$v_photo['photo']);
								}

								/* Xóa photo */
								$d->rawQuery("delete from #_comment_photo where id_parent = ?",array($v['id']));
							}

							/* Xóa comment video */
							$rowCommentVideo = $d->rawQueryOne("select photo, video from #_comment_video where id_parent = ? limit 0,1",array($v['id']));

							if(!empty($rowCommentVideo))
							{
								$func->deleteFile(UPLOAD_PHOTO.$rowCommentVideo['photo']);
								$func->deleteFile(UPLOAD_VIDEO.$rowCommentVideo['video']);
								$d->rawQuery("delete from #_comment_video where id_parent = ?",array($v['id']));
							}

							/* Xóa child */
							$d->rawQuery("delete from #_comment where id_parent = ? and type = ?",array($v['id'],$type));
						}

						/* Xóa comment main */
						$d->rawQuery("delete from #_comment where id = ? and type = ?",array($v['id'],$type));
					}
				}

				$func->transfer("Xóa dữ liệu thành công", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl));
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_news where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_NEWS.$row['photo']);
					$d->rawQuery("delete from #_news where id = ?",array($id));
					if($type === 'du-an') $d->rawQuery("delete from #_product_project where id_project = ?", array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

					/* Xóa Tags */
					$d->rawQuery("delete from #_news_tags where id_parent = ?",array($id));
					
					/* Xóa gallery */
					$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));

					if(count($row))
					{
						foreach($row as $v)
						{
							$func->deleteFile(UPLOAD_NEWS.$v['photo']);
							$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
						}

						$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));
					}

					/* Xóa comment */
					$rowComment = $d->rawQuery("select id, id_parent from #_comment where id_variant = ? and type = ?",array($id,$type));

					if(!empty($rowComment))
					{
						foreach($rowComment as $v)
						{
							if($v['id_parent'] == 0)
							{
								/* Xóa comment photo */
								$rowCommentPhoto = $d->rawQuery("select photo from #_comment_photo where id_parent = ?",array($v['id']));

								if(!empty($rowCommentPhoto))
								{
									/* Xóa image */
									foreach($rowCommentPhoto as $v_photo)
									{
										$func->deleteFile(UPLOAD_PHOTO.$v_photo['photo']);
									}

									/* Xóa photo */
									$d->rawQuery("delete from #_comment_photo where id_parent = ?",array($v['id']));
								}

								/* Xóa comment video */
								$rowCommentVideo = $d->rawQueryOne("select photo, video from #_comment_video where id_parent = ? limit 0,1",array($v['id']));

								if(!empty($rowCommentVideo))
								{
									$func->deleteFile(UPLOAD_PHOTO.$rowCommentVideo['photo']);
									$func->deleteFile(UPLOAD_VIDEO.$rowCommentVideo['video']);
									$d->rawQuery("delete from #_comment_video where id_parent = ?",array($v['id']));
								}

								/* Xóa child */
								$d->rawQuery("delete from #_comment where id_parent = ? and type = ?",array($v['id'],$type));
							}

							/* Xóa comment main */
							$d->rawQuery("delete from #_comment where id = ? and type = ?",array($v['id'],$type));
						}
					}
				}
			}

			$func->transfer("Xóa dữ liệu thành công", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl));
		} 
		else
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=news&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}
	}

	/* View list */
	function viewLists()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;
		
		$where = "";
		$params = array($type);

		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_news_list where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news_list where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=news&act=man_list&type=".$type.$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit list */
	function editList()
	{
		global $d, $strUrl, $func, $curPage, $item, $gallery, $type, $com;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_news_list where id = ? and type = ? limit 0,1",array($id,$type));
		
			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
			else
			{
				/* Get gallery */
				$gallery = $d->rawQuery("select * from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc",array($id,$com,$type,'man_list',$type));
			}
		}
	}

	/* Save list */
	function saveList()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

		    if(!empty($config['news'][$type]['slug_list']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }

			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['news'][$type]['seo_list']) && $config['news'][$type]['seo_list'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['news'][$type]['slug_list']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
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

			if($id)
			{
				$func->redirect("index.php?com=news&act=edit_list&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=news&act=add_list&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('news_list',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_list'], UPLOAD_NEWS, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_news_list where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('news_list', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_list']) && $config['news'][$type]['seo_list'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_list';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{		
			$data['date_created'] = time();
			
			if($d->insert('news_list',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_list'], UPLOAD_NEWS, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('news_list', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_list']) && $config['news'][$type]['seo_list'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_list';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Cập nhật hash khi upload multi */
				$hash = (isset($_POST['hash']) && $_POST['hash'] != '') ? addslashes($_POST['hash']) : null;
				if($hash)
				{
					$d->rawQuery("update #_gallery set hash = ?, id_parent = ? where hash = ?",array('',$id_insert,$hash));
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete list */
	function deleteList()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_news_list where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_NEWS.$row['photo']);
				$d->rawQuery("delete from #_news_list where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

				/* Xóa gallery */
				$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));

				if(count($row))
				{
					foreach($row as $v)
					{
						$func->deleteFile(UPLOAD_NEWS.$v['photo']);
						$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
					}

					$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));
				}

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_news_list where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_NEWS.$row['photo']);
					$d->rawQuery("delete from #_news_list where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

					/* Xóa gallery */
					$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));

					if(count($row))
					{
						foreach($row as $v)
						{
							$func->deleteFile(UPLOAD_NEWS.$v['photo']);
							$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
						}

						$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));
					}
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* View cat */
	function viewCats()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;
		
		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_news_cat where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news_cat where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=news&act=man_cat".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit cat */
	function editCat()
	{
		global $d, $strUrl, $func, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_news_cat where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save cat */
	function saveCat()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

		    if(!empty($config['news'][$type]['slug_cat']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }

			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['news'][$type]['seo_cat']) && $config['news'][$type]['seo_cat'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['news'][$type]['slug_cat']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
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

			if($id)
			{
				$func->redirect("index.php?com=news&act=edit_cat&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=news&act=add_cat&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('news_cat',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_cat'], UPLOAD_NEWS, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_news_cat where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('news_cat', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_cat']) && $config['news'][$type]['seo_cat'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_cat';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('news_cat',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_cat'], UPLOAD_NEWS, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('news_cat', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_cat']) && $config['news'][$type]['seo_cat'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_cat';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}
				$func->transfer("Lưu dữ liệu thành công", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete cat */
	function deleteCat()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_news_cat where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_NEWS.$row['photo']);
				$d->rawQuery("delete from #_news_cat where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_news_cat where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_NEWS.$row['photo']);
					$d->rawQuery("delete from #_news_cat where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* View item */
	function viewItems()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;
		
		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$idcat = isset($_REQUEST['id_cat']) ? (int)$_REQUEST['id_cat'] : 0;

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if($idcat > 0) { $where .= " and id_cat = ?"; $params[] = $idcat; }
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_news_item where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news_item where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=news&act=man_item".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit item */
	function editItem()
	{
		global $d, $strUrl, $func, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_news_item where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save item */
	function saveItem()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

		    if(!empty($config['news'][$type]['slug_item']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }

			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['news'][$type]['seo_item']) && $config['news'][$type]['seo_item'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['news'][$type]['slug_item']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
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

			if($id)
			{
				$func->redirect("index.php?com=news&act=edit_item&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=news&act=add_item&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('news_item',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_item'], UPLOAD_NEWS, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_news_item where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('news_item', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_item']) && $config['news'][$type]['seo_item'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_item';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else $func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('news_item',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_item'], UPLOAD_NEWS, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('news_item', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_item']) && $config['news'][$type]['seo_item'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_item';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else $func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* Delete item */
	function deleteItem()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_news_item where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_NEWS.$row['photo']);
				$d->rawQuery("delete from #_news_item where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else $func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_news_item where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_NEWS.$row['photo']);
					$d->rawQuery("delete from #_news_item where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl);
		}
		else $func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
	}

	/* View sub */
	function viewSubs()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;

		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$idcat = isset($_REQUEST['id_cat']) ? (int)$_REQUEST['id_cat'] : 0;
		$iditem = isset($_REQUEST['id_item']) ? (int)$_REQUEST['id_item'] : 0;

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if($idcat > 0) { $where .= " and id_cat = ?"; $params[] = $idcat; }
		if($iditem > 0) { $where .= " and id_item = ?"; $params[] = $iditem; }
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_news_sub where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news_sub where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=news&act=man_sub".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit sub */
	function editSub()
	{
		global $d, $strUrl, $func, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_news_sub where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save sub */
	function saveSub()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
		
		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

		    if(!empty($config['news'][$type]['slug_sub']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }
			
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['news'][$type]['seo_sub']) && $config['news'][$type]['seo_sub'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['news'][$type]['slug_sub']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
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

			if($id)
			{
				$func->redirect("index.php?com=news&act=edit_sub&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=news&act=add_sub&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('news_sub',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_sub'], UPLOAD_NEWS, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_news_sub where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_NEWS.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('news_sub', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_sub']) && $config['news'][$type]['seo_sub'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_sub';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('news_sub',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['news'][$type]['img_type_sub'], UPLOAD_NEWS, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('news_sub', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['news'][$type]['seo_sub']) && $config['news'][$type]['seo_sub'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_sub';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete sub */
	function deleteSub()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_news_sub where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_NEWS.$row['photo']);
				$d->rawQuery("delete from #_news_sub where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else $func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_news_sub where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_NEWS.$row['photo']);
					$d->rawQuery("delete from #_news_sub where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=news&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}
?>
