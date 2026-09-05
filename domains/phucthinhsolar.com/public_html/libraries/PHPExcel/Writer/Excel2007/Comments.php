<?php

/**
 * PHPExcel_Writer_Excel2007_Comments
 *
 * BẢN THAY THẾ TỐI THIỂU — không phải file gốc của PHPExcel.
 *
 * File Comments.php đi kèm dự án này rỗng 0 byte, và đã rỗng từ commit đầu tiên
 * của repo. PHPExcel_Writer_Excel2007 khởi tạo TẤT CẢ writer part ngay trong
 * constructor (Excel2007.php dòng ~146), nên chỉ riêng việc thiếu class này đã
 * làm mọi lần xuất .xlsx chết với "Class 'PHPExcel_Writer_Excel2007_Comments'
 * not found" — tức chức năng "Xuất Excel đơn hàng" chưa từng chạy được.
 *
 * Hai method dưới đây chỉ được gọi khi một sheet thật sự có comment ô
 * (Excel2007.php dòng 331 và 334, đều nằm sau điều kiện
 * count($sheet->getComments()) > 0). Các file xuất đơn hàng của dự án không tạo
 * comment ô nào, nên đường chạy thực tế chỉ cần class tồn tại để constructor
 * không chết.
 *
 * GIỚI HẠN CẦN BIẾT: bản này ghi ra phần comment RỖNG hợp lệ. Nếu sau này có
 * code thêm comment vào ô rồi xuất Excel, comment sẽ không xuất hiện trong file
 * (file vẫn mở được bình thường, không hỏng). Muốn đầy đủ thì lấy lại
 * Comments.php gốc từ bản phát hành PHPExcel 1.8.x.
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer_Excel2007
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 */
class PHPExcel_Writer_Excel2007_Comments extends PHPExcel_Writer_Excel2007_WriterPart
{
    /**
     * Write comments to XML format
     *
     * @param     PHPExcel_Worksheet    $pWorksheet
     * @return     string                 XML Output
     * @throws     PHPExcel_Writer_Exception
     */
    public function writeComments(PHPExcel_Worksheet $pWorksheet = null)
    {
        // Create XML writer
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_MEMORY);
        }

        // XML header
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        // comments
        $objWriter->startElement('comments');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        // authors
        $objWriter->startElement('authors');
        $objWriter->writeElement('author', 'Author');
        $objWriter->endElement();

        // commentList rỗng: xem ghi chú giới hạn ở đầu file.
        $objWriter->startElement('commentList');
        $objWriter->endElement();

        $objWriter->endElement();

        return $objWriter->getData();
    }

    /**
     * Write VML comments to XML format
     *
     * @param     PHPExcel_Worksheet    $pWorksheet
     * @return     string                 XML Output
     * @throws     PHPExcel_Writer_Exception
     */
    public function writeVMLComments(PHPExcel_Worksheet $pWorksheet = null)
    {
        // Create XML writer
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new PHPExcel_Shared_XMLWriter(PHPExcel_Shared_XMLWriter::STORAGE_MEMORY);
        }

        // XML header
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        // xml
        $objWriter->startElement('xml');
        $objWriter->writeAttribute('xmlns:v', 'urn:schemas-microsoft-com:vml');
        $objWriter->writeAttribute('xmlns:o', 'urn:schemas-microsoft-com:office:office');
        $objWriter->writeAttribute('xmlns:x', 'urn:schemas-microsoft-com:office:excel');

        // o:shapelayout
        $objWriter->startElement('o:shapelayout');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->startElement('o:idmap');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->writeAttribute('data', '1');
        $objWriter->endElement();
        $objWriter->endElement();

        // v:shapetype
        $objWriter->startElement('v:shapetype');
        $objWriter->writeAttribute('id', '_x0000_t202');
        $objWriter->writeAttribute('coordsize', '21600,21600');
        $objWriter->writeAttribute('o:spt', '202');
        $objWriter->writeAttribute('path', 'm,l,21600r21600,l21600,xe');
        $objWriter->startElement('v:stroke');
        $objWriter->writeAttribute('joinstyle', 'miter');
        $objWriter->endElement();
        $objWriter->startElement('v:path');
        $objWriter->writeAttribute('gradientshapeok', 't');
        $objWriter->writeAttribute('o:connecttype', 'rect');
        $objWriter->endElement();
        $objWriter->endElement();

        // Không ghi v:shape nào: bản thay thế này không xuất comment.
        $objWriter->endElement();

        return $objWriter->getData();
    }
}
