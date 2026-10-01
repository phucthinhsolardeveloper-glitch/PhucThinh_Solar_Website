<?php
/**
 * cloneRow()
 *
 * Copyright (c) 2013 Platonov Pavel (http://www.leng.ru)
 *
 * Extension for PHPWord_Template
 * New public method cloneRow() for clone rows in tables
 *
 *
 * @category   PHPWord Extension
 * @copyright  Copyright (c) 2013 Platonov Pavel (http://www.leng.ru)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    Beta 0.2, 25.12.2013
 *
 *
 * modified method setValue()
 * new pattern for replace: {MyPattern}
 * fixed problem with tags inside pattern: "{<tags...>My<tags...>Pattern<tags...>}"
 *
 * Copyright (c) 2013 Platonov Pavel (http://www.leng.ru)
 *
 *
 * PHPWord
 *
 * Copyright (c) 2011 PHPWord
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 010 PHPWord
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    Beta 0.6.3, 08.07.2011
 */


/**
 * PHPWord_DocumentProperties
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 2009 - 2011 PHPWord (http://www.codeplex.com/PHPWord)
 */
class PHPWord_Template {

	/**
	* ZipArchive
	*
	* @var ZipArchive
	*/
	private $_objZip;

	/**
	* Temporary Filename
	*
	* @var string
	*/
	private $_tempFileName;

	/**
	* Document XML
	*
	* @var string
	*/
	private $_documentXML;


	/**
	* Create a new Template Object
	*
	* @param string $strFilename
	*/
	public function __construct($strFilename) {
		/* File tạm phải nằm ở thư mục tạm của hệ thống, không phải cạnh file mẫu.
		   Trước đây dùng dirname($strFilename), tức ghi thẳng vào libraries/sample/
		   — thư mục source mà user chạy web (www-data) không có quyền ghi. copy()
		   thất bại, ZipArchive mở một file không tồn tại, rồi close() trả false và
		   ném "Could not close zip file". Đó là lý do xuất Word danh sách đơn hàng
		   luôn hỏng. Dùng tempnam() cũng an toàn hơn: tên file không đoán trước
		   được và không đụng nhau khi hai request chạy cùng giây. */
		$this->_tempFileName = tempnam(sys_get_temp_dir(), 'phpword-');
		if ($this->_tempFileName === false) {
			throw new Exception('Không tạo được file tạm để dựng tài liệu Word.');
		}

		if (!copy($strFilename, $this->_tempFileName)) { // Copy the source File to the temp File
			throw new Exception('Không chép được file mẫu Word: '.$strFilename);
		}

		$this->_objZip = new ZipArchive();
		if ($this->_objZip->open($this->_tempFileName) !== true) {
			throw new Exception('Không mở được file mẫu Word: '.$strFilename);
		}

		$this->_documentXML = $this->_objZip->getFromName('word/document.xml');
	}

	/**
	* Set a Template value
	*
	* @param mixed $search
	* @param mixed $replace
	*/
	public function setValue($search, $replace, $limit=-1) {
		/* PHP 8.1 deprecate truyền null cho $replacement (string|array) của
		   preg_replace(); giá trị cột setting trống (vd hotline_company khi
		   chưa cấu hình) tới đây là null. Ép về chuỗi rỗng, giữ đúng hành vi
		   cũ (preg_replace tự coi null như '' trước PHP 8.1). */
		if($replace === null) $replace = '';
		if(substr($search, 0, 1) !== '{' && substr($search, -1) !== '}') {
			$search = '{'.$search.'}';
		}
		preg_match_all('/\{[^}]+\}/', $this->_documentXML, $matches);
		foreach ($matches[0] as $k => $match) {
			$no_tag = strip_tags($match);
			if ($no_tag == $search) {
				$match = '{'.$match.'}';
				$this->_documentXML = preg_replace($match, $replace, $this->_documentXML, $limit);
				if ($limit == 1) {
					break;
				}			
			}
		}
	}

	/**
	* Save Template
	*
	* @param string $strFilename
	*/
	public function save($strFilename) {
		if(file_exists($strFilename)) {
			unlink($strFilename);
		}

		$this->_objZip->addFromString('word/document.xml', $this->_documentXML);

		// Close zip file
		if($this->_objZip->close() === false) {
			throw new Exception('Could not close zip file.');
		}

		/* File tạm giờ nằm ở /tmp, có thể khác filesystem với thư mục đích nên
		   rename() sẽ thất bại. Chép rồi xoá là đường lui an toàn. */
		if(!@rename($this->_tempFileName, $strFilename)) {
			if(!@copy($this->_tempFileName, $strFilename)) {
				throw new Exception('Không ghi được file Word ra: '.$strFilename);
			}
			@unlink($this->_tempFileName);
		}
	}

	/**
	* Clone Rows in tables
	*
	* @param string $search
	* @param array $data
	*/
	public function cloneRow($search, $data=array()) {		
		// remove ooxml-tags inside pattern				
		foreach ($data as $nn => $fieldset) {
			foreach ($fieldset as $field => $val) {
				$key = '{'.$search.'.'.$field.'}';
				$this->setValue($key, $key, 1);
			}
		}
		// how many clons we need
		$numberOfClones = 0;
		if (is_array($data)) {
			foreach ($data as $colName => $dataArr) {
				if (is_array($dataArr)) {
					$c = count($dataArr);
					if ($c > $numberOfClones)
						$numberOfClones = $c;
				}
			}
		}
		if ($numberOfClones > 0) {
			// read document as XML
			$xml = new DOMDocument();
			$xml->loadXML($this->_documentXML, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

			// search for tables
			$tables = $xml->getElementsByTagName('tbl');
			foreach ($tables as $table) {
				$text = $table->textContent;
				// search for pattern. Like {TBL1.
				if (mb_strpos($text, '{'.$search.'.') !== false) {
					// search row for clone
					$patterns = array();
					$rows = $table->getElementsByTagName('tr');
					$isUpdate = false;
					$isFind = false;
					foreach ($rows as $row) {
						$text = $row->textContent;
						$TextWithTags = $xml->saveXML($row);
						if (
							mb_strpos($text, '{'.$search.'.') !== false // Pattern found in this row
							OR
							(mb_strpos($TextWithTags, '<w:vMerge/>') !== false AND $isFind) // This row is merged with upper row (Upper row have pattern)
						)
						{
							// This row need to clone
							$patterns[] = $row->cloneNode(true);
							$isFind = true;
						} else {
							// This row don't have any patterns. It's table header or footer
							if (!$isUpdate and $isFind) {
								// This is table footer
								// Insert new rows before footer								
								$this->InsertNewRows($table, $patterns, $row, $numberOfClones);
								$isUpdate = true;
							}
						}
					}
					// if table without footer					
					if (!$isUpdate and $isFind) {
						$this->InsertNewRows($table, $patterns, $row, $numberOfClones);
					}
				}
			}
			// save document
			$res_string = $xml->saveXML();
			$this->_documentXML = $res_string;
	
			// parsing data
			foreach ($data as $colName => $dataArr) {
				$pattern = '{' . $search . '.' . $colName . '}';
				foreach ($dataArr as $value) {
					$this->setValue($pattern, $value, 1);
				}
			}
		}
	}
	
	/**
	* Insert new rows in table
	*
	* @param object &$table
	* @param object $patterns
	* @param object $row
	* @param int $numberOfClones
	*/
	protected function InsertNewRows(&$table, $patterns, $row, $numberOfClones)	{
		for ($i = 1; $i < $numberOfClones; $i++) {
			foreach ($patterns as $pattern) {
				$new_row = $pattern->cloneNode(true);
				$table->insertBefore($new_row, $row);
			}
		}
	}
}
?>