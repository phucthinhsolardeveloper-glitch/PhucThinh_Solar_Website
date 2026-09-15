<?php
if(!defined('SOURCES')) die('Error');
require_once LIBRARIES.'solution_hub.php';
$nhLang = $lang === 'en' ? 'en' : 'vi';
$nhEscape = static function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$nhUi = $nhLang === 'en' ? array(
'title'=>'Knowledge & Insights','intro'=>'Solar energy knowledge, product updates and stories from Phuc Thinh Solar.',
'all'=>'All','search'=>'Search articles','empty'=>'No matching articles found.','reset'=>'View all articles',
'read'=>'min read','featured'=>'Featured articles','results'=>'Articles','pages'=>'Article pages',
'cta'=>'Phuc Thinh Solar is ready to help','cta_desc'=>'Contact us for advice and the latest information.',
'cta_label'=>'Need advice or have a topic in mind?','button'=>'Get advice','phone'=>'Call our hotline'
) : array(
'title'=>'Kiến thức','intro'=>'Kiến thức năng lượng mặt trời, thông tin sản phẩm và câu chuyện từ Phúc Thịnh Solar.',
'all'=>'Tất cả','search'=>'Tìm kiếm bài viết','empty'=>'Chưa tìm thấy bài viết phù hợp.','reset'=>'Xem tất cả bài viết',
'read'=>'phút đọc','featured'=>'Bài viết nổi bật','results'=>'Bài viết','pages'=>'Trang bài viết',
'cta'=>'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ','cta_desc'=>'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.',
'cta_label'=>'Cần tư vấn hoặc có chủ đề bạn quan tâm?','button'=>'Nhận tư vấn ngay','phone'=>'Gọi ngay hotline'
);
$nhBlocks = array();
foreach(array('news-intro','news-cta') as $nhBlockType) {
    $nhRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,content1vi,content1en,photo,status from #_news where type=? order by numb,id desc', array($nhBlockType));
    $nhSelected = solutionHubSelect($nhRows, '', 1);
    $nhBlocks[$nhBlockType] = array('exists'=>(bool)$nhRows,'visible'=>!$nhRows || (bool)$nhSelected,'row'=>$nhSelected ? $nhSelected[0] : array());
}
$nhIntro = $nhBlocks['news-intro']['row'];
$nhCta = $nhBlocks['news-cta']['row'];
$nhTitle = solutionHubField($nhIntro,'name',$nhLang) ?: $nhUi['title'];
$nhDescription = solutionHubField($nhIntro,'desc',$nhLang) ?: $nhUi['intro'];
$nhCtaTitle = solutionHubField($nhCta,'name',$nhLang) ?: $nhUi['cta'];
$nhCtaLabel = solutionHubField($nhCta,'title_index',$nhLang) ?: $nhUi['cta_label'];
$nhCtaDesc = solutionHubField($nhCta,'desc',$nhLang) ?: $nhUi['cta_desc'];
$nhButton = solutionHubField($nhCta,'content1',$nhLang) ?: $nhUi['button'];
$nhCtaImage = solutionHubImage(isset($nhCta['photo']) ? $nhCta['photo'] : '',UPLOAD_NEWS_L);
$nhPhone = isset($optsetting['hotline']) ? $optsetting['hotline'] : '';
$nhTel = preg_replace('/[^0-9+]/','',$nhPhone);
if($template !== 'news/news') return;
$nhQuery = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']),0,120,'UTF-8') : '';
$nhPage = isset($_GET['p']) && is_scalar($_GET['p']) ? max(1,min(1000000,(int)$_GET['p'])) : 1;
$nhCategoryId = !empty($newsList['id']) ? (int)$newsList['id'] : 0;
$nhCategoryMap = array();
$nhCategories = $d->rawQuery("select l.id,l.namevi,l.nameen,l.descvi,l.descen,l.slugvi,l.photo from #_news_list l where l.type=? and find_in_set('hienthi',l.status) and exists(select 1 from #_news n where n.id_list=l.id and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'') order by l.numb,l.id desc",array('tin-tuc','tin-tuc'));
foreach($nhCategories as $nhCategory) $nhCategoryMap[(int)$nhCategory['id']] = $nhCategory;
if($nhCategoryId) {
    $nhTitle = solutionHubField($newsList,'name',$nhLang);
    $nhDescription = solutionHubField($newsList,'desc',$nhLang);
}
$nhPath = $nhCategoryId ? $newsList['slugvi'].'/' : 'tin-tuc/';
$nhUrl = static function($path,$query,$page=1) {
    $params=array();
    if($query !== '') $params['q']=$query;
    if($page>1) $params['p']=$page;
    return $path.($params ? '?'.http_build_query($params,'','&',PHP_QUERY_RFC3986) : '');
};
// Full content is fetched only for the visible cards to calculate reading time.
$nhFields='id,id_list,namevi,nameen,slugvi,photo,descvi,descen,contentvi,contenten,date_created';
$nhWhere="type=? and find_in_set('hienthi',status) and slugvi<>''";
$nhParams=array('tin-tuc');
$nhFeatured=array();
$nhHeroEnabled=!$nhBlocks['news-intro']['exists'] || solutionHubHasStatus($nhIntro,'hero');
if(!$nhCategoryId && $nhQuery === '' && $nhHeroEnabled) {
    $nhSelection=$d->rawQueryOne("select count(*) as num from #_news where type=? and find_in_set('newshub',status)",array('tin-tuc'));
    $nhHeroWhere=$nhWhere.(!empty($nhSelection['num']) ? " and find_in_set('newshub',status)" : '');
    $nhFeatured=$d->rawQuery('select '.$nhFields.' from #_news where '.$nhHeroWhere.' order by numb,date_created desc,id desc limit 3',$nhParams);
    // Exclude the same selected IDs on EVERY unfiltered page, avoiding skips/duplicates.
    if($nhFeatured) {
        $nhWhere.=' and id not in ('.implode(',',array_fill(0,count($nhFeatured),'?')).')';
        foreach($nhFeatured as $nhArticle) $nhParams[]=(int)$nhArticle['id'];
    }
}
if($nhCategoryId) { $nhWhere.=' and id_list=?'; $nhParams[]=$nhCategoryId; }
if($nhQuery !== '') {
    $nhPattern='%'.strtr($nhQuery,array('!'=>'!!','%'=>'!%','_'=>'!_')).'%';
    $nhSearchColumns = $nhLang === 'en'
        ? array("coalesce(nullif(nameen,''),namevi)","coalesce(nullif(descen,''),descvi)","coalesce(nullif(contenten,''),contentvi)")
        : array('namevi','descvi','contentvi');
    $nhWhere.=' and ('.implode(" like ? escape '!' or ",$nhSearchColumns)." like ? escape '!')";
    array_push($nhParams,$nhPattern,$nhPattern,$nhPattern);
}
$nhCount=$d->rawQueryOne('select count(*) as num from #_news where '.$nhWhere,$nhParams);
$nhTotal=(int)$nhCount['num'];
$nhPages=max(1,(int)ceil($nhTotal/6));
$nhPage=min($nhPage,$nhPages);
$nhArticles=$d->rawQuery('select '.$nhFields.' from #_news where '.$nhWhere.' order by date_created desc,id desc limit '.(($nhPage-1)*6).',6',$nhParams);
$nhShowFeatured=$nhPage===1 && (bool)$nhFeatured;
