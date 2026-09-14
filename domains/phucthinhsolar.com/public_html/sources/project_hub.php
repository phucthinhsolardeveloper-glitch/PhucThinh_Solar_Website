<?php
if(!defined('SOURCES')) die('Error');
require_once LIBRARIES.'solution_hub.php';

/* 3 gạch đầu dòng lợi ích + 3 mục tin cậy CTA (2026-09-14, theo mockup mới) -
   CỐ Ý là câu khẩu hiệu chung chung, không phải số liệu/cam kết riêng cho
   từng dự án (mockup có ghi số cụ thể kiểu "giảm 70% chi phí", "bền 25 năm"
   cho 1 dự án minh hoạ - không dùng nguyên văn vì không có bằng chứng cho
   từng dự án thật). */
$projectUi = $lang === 'en' ? array(
    'eyebrow' => '',
    'title' => 'Completed projects',
    'intro' => 'Phuc Thinh Solar is proud to partner with customers in creating clean, sustainable energy systems.',
    'all' => 'All', 'filters' => 'Filter projects', 'view' => 'View project',
    'empty' => 'There are no projects in this category yet.',
    'featured_eyebrow' => 'Featured project', 'featured_cta' => 'View project details',
    'featured_benefits' => array('Free survey and design consultation', 'Genuine equipment, installed to spec', 'Clear warranty, long-term operating support'),
    'cta_eyebrow' => 'Build with clean energy', 'cta_title' => 'Do you have a project that needs advice?',
    'cta_desc' => 'Our team is ready to recommend the right solar solution for your needs.',
    'cta_button' => 'Get free advice', 'contact' => 'Contact now',
    'cta_trust' => array(
        array('icon' => 'fa-headset', 'label' => 'Dedicated consulting'),
        array('icon' => 'fa-cogs', 'label' => 'Optimized solutions'),
        array('icon' => 'fa-shield-alt', 'label' => 'Long-term partnership'),
    ),
) : array(
    'eyebrow' => '',
    'title' => 'Dự án đã thực hiện',
    'intro' => 'Phúc Thịnh Solar tự hào đồng hành cùng khách hàng kiến tạo những công trình năng lượng sạch, bền vững cho tương lai.',
    'all' => 'Tất cả', 'filters' => 'Lọc dự án', 'view' => 'Xem dự án',
    'empty' => 'Chưa có dự án trong nhóm này.',
    'featured_eyebrow' => 'Dự án nổi bật', 'featured_cta' => 'Xem chi tiết dự án',
    'featured_benefits' => array('Khảo sát và tư vấn thiết kế miễn phí', 'Thiết bị chính hãng, thi công đúng kỹ thuật', 'Bảo hành rõ ràng, hỗ trợ vận hành lâu dài'),
    'cta_eyebrow' => 'Đồng hành cùng năng lượng sạch', 'cta_title' => 'Bạn có công trình cần tư vấn?',
    'cta_desc' => 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
    'cta_button' => 'Nhận tư vấn miễn phí', 'contact' => 'Liên hệ ngay',
    'cta_trust' => array(
        array('icon' => 'fa-headset', 'label' => 'Tư vấn tận tâm'),
        array('icon' => 'fa-cogs', 'label' => 'Giải pháp tối ưu'),
        array('icon' => 'fa-shield-alt', 'label' => 'Đồng hành lâu dài'),
    ),
);

// Include hidden records so an explicit Admin hide action suppresses fallback.
$projectIntroRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,status from #_news where type=? order by numb,id desc', array('project-intro'));
$projectCtaRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,content1vi,content1en,photo,status from #_news where type=? order by numb,id desc', array('project-cta'));
$projectFilterRows = $d->rawQuery("select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_news_list where type=? and find_in_set('hienthi',status) order by numb,id desc", array('du-an'));
$projectBenefitRows = $d->rawQuery("select namevi,nameen,photo from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc limit 0,3", array('project-benefit'));
$projectTrustRows = $d->rawQuery("select namevi,nameen,photo from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc limit 0,3", array('project-trust'));

$projectIntroVisible = solutionHubSelect($projectIntroRows, '', 1);
$projectCtaVisible = solutionHubSelect($projectCtaRows, '', 1);
$projectIntro = $projectIntroVisible ? $projectIntroVisible[0] : array();
$projectCta = $projectCtaVisible ? $projectCtaVisible[0] : array();
$projectCurrentCategory = array();
$projectCategoryById = array();
foreach($projectFilterRows as $projectFilterRow) {
    $projectCategoryById[(int)$projectFilterRow['id']] = $projectFilterRow;
    if(!empty($newsList['id']) && (int)$newsList['id'] === (int)$projectFilterRow['id']) $projectCurrentCategory = $projectFilterRow;
}

$projectShowIntro = !empty($newsList['id']) || !$projectIntroRows || (bool)$projectIntroVisible;
$projectHeroEyebrow = solutionHubField($projectIntro, 'title_index', $lang) ?: $projectUi['eyebrow'];
$projectHeroTitle = solutionHubField($projectIntro, 'name', $lang) ?: $projectUi['title'];
$projectHeroDesc = solutionHubField($projectIntro, 'desc', $lang) ?: $projectUi['intro'];
if($projectCurrentCategory) {
    $projectCategoryName = solutionHubField($projectCurrentCategory, 'name', $lang);
    $projectHeroTitle = $lang === 'en' ? 'Projects for '.$projectCategoryName : 'Dự án '.$projectCategoryName;
    $projectHeroDesc = solutionHubField($projectCurrentCategory, 'desc', $lang) ?: $projectHeroDesc;
}

$projectShowCta = !$projectCtaRows || (bool)$projectCtaVisible;
$projectCtaEyebrow = solutionHubField($projectCta, 'title_index', $lang) ?: $projectUi['cta_eyebrow'];
$projectCtaTitle = solutionHubField($projectCta, 'name', $lang) ?: $projectUi['cta_title'];
$projectCtaDesc = solutionHubField($projectCta, 'desc', $lang) ?: $projectUi['cta_desc'];
$projectCtaButton = solutionHubField($projectCta, 'content1', $lang) ?: $projectUi['cta_button'];
$projectCtaImage = solutionHubImage(isset($projectCta['photo']) ? $projectCta['photo'] : '', UPLOAD_NEWS_L);
/* Admin chưa nhập ảnh nền CTA thì tạm dùng 1 ảnh dự án thật đã có sẵn (không
   phải ảnh dựng) - vẫn ưu tiên ảnh Admin nhập ở trên nếu có. */
if($projectCtaImage === '') $projectCtaImage = solutionHubImage('anh-quan-gl-4-9449.jpg', UPLOAD_NEWS_L);
$projectHotline = !empty($optsetting['hotline']) ? $optsetting['hotline'] : '';
$projectHotlineTel = preg_replace('/[^0-9+]/', '', $projectHotline);

/* 3 lợi ích nổi bật + 3 tin cậy CTA (2026-09-14): ưu tiên record Admin
   (project-benefit/project-trust), rơi về đúng nội dung mặc định đã duyệt
   trong $projectUi khi chưa có record nào. */
$projectBenefitItems = array();
foreach($projectBenefitRows as $row) { $label = solutionHubField($row, 'name', $lang); if($label !== '') $projectBenefitItems[] = array('label' => $label, 'icon' => solutionHubImage(isset($row['photo']) ? $row['photo'] : '', UPLOAD_NEWS_L)); }
if(!$projectBenefitItems) foreach($projectUi['featured_benefits'] as $label) $projectBenefitItems[] = array('label' => $label, 'icon' => '');

$projectTrustItems = array();
foreach($projectTrustRows as $row) { $label = solutionHubField($row, 'name', $lang); if($label !== '') $projectTrustItems[] = array('label' => $label, 'icon' => solutionHubImage(isset($row['photo']) ? $row['photo'] : '', UPLOAD_NEWS_L)); }
if(!$projectTrustItems) foreach($projectUi['cta_trust'] as $item) $projectTrustItems[] = array('label' => $item['label'], 'icon' => '', 'faIcon' => $item['icon']);

/* Dự án nổi bật (2026-09-14, theo mockup mới): tách 1 dự án ra khối lớn đầu
   trang, phần còn lại xuống lưới. Chỉ áp dụng ở trang danh sách GỐC (chưa lọc
   danh mục, đúng trang 1) - ẩn khi đã lọc/sang trang sau, giống nguyên tắc đã
   dùng ở News Hub. Không dùng $curPage của news.php vì biến $getPage nó dựa
   vào chỉ được set cho type=tin-tuc (bug có sẵn, ngoài phạm vi task này) -
   tự đọc lại $_GET['p'] cho chắc. */
$projectFeatured = null;
$projectGridRows = $news;
$projectHubCurPage = isset($_GET['p']) && is_scalar($_GET['p']) ? max(1, min(1000000, (int)$_GET['p'])) : 1;
if(empty($newsList['id']) && $projectHubCurPage <= 1 && $news)
{
    $projectFeaturedIndex = null;
    foreach($news as $newsIndex => $newsRow) {
        if(solutionHubHasStatus($newsRow, 'projecthub')) { $projectFeaturedIndex = $newsIndex; break; }
    }
    if($projectFeaturedIndex === null) $projectFeaturedIndex = 0;
    $projectGridRows = $news;
    $projectFeaturedSlice = array_splice($projectGridRows, $projectFeaturedIndex, 1);
    $projectFeatured = $projectFeaturedSlice ? $projectFeaturedSlice[0] : null;
}
if($projectFeatured) {
    $projectFeaturedCategory = isset($projectCategoryById[(int)$projectFeatured['id_list']]) ? $projectCategoryById[(int)$projectFeatured['id_list']] : array();
    $projectFeaturedCategoryName = $projectFeaturedCategory ? solutionHubField($projectFeaturedCategory, 'name', $lang) : '';
    $projectFeaturedCategoryImage = $projectFeaturedCategory ? solutionHubImage($projectFeaturedCategory['photo'], UPLOAD_NEWS_L) : '';
}
