<?php
	class Seo
	{
		private $d;
		private $data;

		function __construct($d)
		{
			$this->d = $d;
		}

		public function set($key='', $value='')
		{
			if(!empty($key) && !empty($value))
			{
				/* Chuẩn hoá title: mọi trang chưa có SEO title tự nhập trong
				   Admin (rơi về fallback tên thô - danh mục/sản phẩm/bài
				   viết...) đều thiếu hậu tố thương hiệu, không nhất quán với
				   các trang đã có SEO title tự viết kèm sẵn "| Phúc Thịnh
				   Solar". Thêm đúng 1 lần tại đây, không sửa 21 điểm gọi rời
				   rạc trong sources/*.php. Không thêm nếu title đã có brand
				   (tránh lặp "... | Phúc Thịnh Solar | Phúc Thịnh Solar"). */
				/* Kiểm tra "phúc thịnh s" (không phải "phúc thịnh solar" đầy
				   đủ): một số SEO title cũ trong DB bị cắt ngắn giữa chừng
				   (vd "...| Phúc Thịnh So", thiếu "lar") - check chuỗi dài đủ
				   sẽ không nhận ra và nối thêm lần 2 thành "...So | Phúc
				   Thịnh Solar". */
				if($key === 'title' && stripos($value, 'phúc thịnh s') === false)
				{
					$value = trim($value).' | Phúc Thịnh Solar';
				}
				$this->data[$key] = $value;
			}
		}

		public function get($key)
		{
			return (!empty($this->data[$key])) ? $this->data[$key] : '';
		}

		public function getOnDB($id=0, $com='', $act='', $type='')
		{
			if($id || $act=='update')
			{
				if($id) $row = $this->d->rawQueryOne("select * from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 0,1",array($id,$com,$act,$type));
				else $row = $this->d->rawQueryOne("select * from #_seo where com = ? and act = ? and type = ? limit 0,1",array($com,$act,$type));

				return $row;
			}
		}

		public function updateSeoDB($json = '', $table = '', $id = 0)
		{
			if($table && $id) $this->d->rawQuery("update #_$table set options = ? where id = ?",array($json,$id));
		}
	}
?>