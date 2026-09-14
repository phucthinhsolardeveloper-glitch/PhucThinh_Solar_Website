<?php
if(!defined('SOURCES')) die('Error');
require_once LIBRARIES.'solution_hub.php';

$projectUi = $lang === 'en' ? array(
    'eyebrow' => '',
    'title' => 'Completed projects',
    'intro' => 'Phuc Thinh Solar is proud to partner with customers in creating clean, sustainable energy systems.',
    'all' => 'All', 'filters' => 'Filter projects', 'view' => 'View project',
    'empty' => 'There are no projects in this category yet.',
    'cta_eyebrow' => 'Build with clean energy', 'cta_title' => 'Do you have a project that needs advice?',
    'cta_desc' => 'Our team is ready to recommend the right solar solution for your needs.',
    'cta_button' => 'Get free advice', 'contact' => 'Contact now'
) : array(
    'eyebrow' => '',
    'title' => 'Dự án đã thực hiện',
    'intro' => 'Phúc Thịnh Solar tự hào đồng hành cùng khách hàng kiến tạo những công trình năng lượng sạch, bền vững cho tương lai.',
    'all' => 'Tất cả', 'filters' => 'Lọc dự án', 'view' => 'Xem dự án',
    'empty' => 'Chưa có dự án trong nhóm này.',
    'cta_eyebrow' => 'Đồng hành cùng năng lượng sạch', 'cta_title' => 'Bạn có công trình cần tư vấn?',
    'cta_desc' => 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
    'cta_button' => 'Nhận tư vấn miễn phí', 'contact' => 'Liên hệ ngay'
);

// Include hidden records so an explicit Admin hide action suppresses fallback.
$projectIntroRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,status from #_news where type=? order by numb,id desc', array('project-intro'));
$projectCtaRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,content1vi,content1en,photo,status from #_news where type=? order by numb,id desc', array('project-cta'));
$projectFilterRows = $d->rawQuery("select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_news_list where type=? and find_in_set('hienthi',status) order by numb,id desc", array('du-an'));

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
$projectHotline = !empty($optsetting['hotline']) ? $optsetting['hotline'] : '';
$projectHotlineTel = preg_replace('/[^0-9+]/', '', $projectHotline);
