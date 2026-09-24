<?php
$root = is_dir('/var/www/html/libraries/') ? '/var/www/html/' : dirname(__DIR__).'/domains/phucthinhsolar.com/public_html/';
require_once $root.'libraries/product_pdp_profiles.php';
require_once $root.'libraries/product_specs.php';
require_once $root.'libraries/product_content_blocks.php';

function pdpAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: '.$message."\n";
}

pdpAssert(productPdpProfileFromSlug('bien-tan-inverter') === 'inverter', 'Danh mục inverter nhận đúng profile');
pdpAssert(productPdpProfileFromSlug('quat-nang-luong-mat-troi') === 'fan', 'Danh mục quạt nhận đúng profile');
pdpAssert(productPdpResolveProfile(array('pdp_profile' => 'pump_hardware'), array('pdp_profile' => 'pump_controller')) === 'pump_hardware', 'Override sản phẩm ưu tiên hơn danh mục');

$inverter = array(
    'namevi' => 'Inverter thử nghiệm',
    'light_source' => 'LED sai dữ liệu legacy',
    'additional_specs' => json_encode(array(
        array('key' => 'ac_power', 'label' => 'Công suất AC định mức', 'value' => '10 kW'),
        array('key' => 'phase_count', 'label' => 'Số pha', 'value' => '3 pha'),
        array('label' => 'Khối lượng thùng', 'value' => '30 kg')
    ), JSON_UNESCAPED_UNICODE)
);
$specs = productStructuredSpecs($inverter, 'vi', 'inverter');
$labels = array_map(function($spec) { return $spec['label']; }, $specs['specs']);
pdpAssert(in_array('Công suất AC định mức', $labels, true), 'Thông số có mã của inverter được render');
pdpAssert(!in_array('Nguồn sáng', $labels, true), 'Profile inverter loại thông số đèn khỏi bảng');

$blocks = array(array('type' => 'faq', 'question' => 'Có phù hợp không?', 'answer' => 'Cần đối chiếu tải thực tế.'));
$markdown = productBlocksToMarkdown($blocks);
$roundTrip = productMarkdownToBlocks($markdown);
pdpAssert(isset($roundTrip[0]['type']) && $roundTrip[0]['type'] === 'faq', 'FAQ đi qua Excel sectionsvi không bị mất');

echo "Product PDP profile tests completed.\n";
