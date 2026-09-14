<?php
/* Local-only fixtures; exact created IDs are cleaned in finally. */
$_SERVER['SERVER_NAME']='localhost';
define('LIBRARIES','/var/www/html/libraries/');
require LIBRARIES.'config.php';
if(getenv('APP_ENV') !== 'local') { fwrite(STDERR,"Local only.\n"); exit(1); }
require LIBRARIES.'autoload.php';
new AutoLoad();
$d=new PDODb($config['database']);
define('SOURCES','/var/www/html/sources/');
define('TEMPLATE','/var/www/html/templates/');
if(!defined('UPLOAD_NEWS_L')) define('UPLOAD_NEWS_L','upload/news/');
if(!defined('ASSET')) define('ASSET','http://localhost:8080/');
chdir('/var/www/html');
$cache=new Cache($d); $func=new Functions($d,$cache);
require_once LIBRARIES.'public_watermark.php';
function nhAssert($ok,$label) { if(!$ok) throw new RuntimeException($label); echo "PASS: ".$label."\n"; }
$ids=array(); $suffix='NHFIX-'.bin2hex(random_bytes(5));
$lang='vi'; $template='news/news'; $newsList=array(); $optsetting=array();
try {
    for($i=0;$i<14;$i++) {
        $ok=$d->insert('news',array('type'=>'tin-tuc','namevi'=>$suffix.' article '.$i,'nameen'=>'','slugvi'=>strtolower($suffix).'-'.$i,'descvi'=>'Literal 100% _test_ ! and <b>safe</b>','contentvi'=>str_repeat('năng lượng ',230),'status'=>'hienthi','numb'=>0,'date_created'=>time()+$i,'photo'=>'missing-image.png'));
        nhAssert($ok,'Create isolated article '.$i);
        $ids[]=(int)$d->getLastInsertId();
    }
    $_GET=array('q'=>$suffix);
    require SOURCES.'news_hub.php';
    nhAssert($nhTotal===14 && count($nhArticles)===6 && $nhPages===3,'Search counts/pagination cover all matching articles');
    $seen=array_column($nhArticles,'id');
    for($p=2;$p<=3;$p++) {
        $_GET=array('q'=>$suffix,'p'=>$p); require SOURCES.'news_hub.php';
        $seen=array_merge($seen,array_column($nhArticles,'id'));
    }
    nhAssert(count(array_unique($seen))===14,'No duplicate or skipped article across pages');
    $_GET=array('q'=>$suffix,'p'=>999); require SOURCES.'news_hub.php';
    nhAssert($nhPage===3 && count($nhArticles)===2,'Out-of-range page clamps to last valid page');
    ob_start(); require TEMPLATE.'news/news_tpl.php'; $html=ob_get_clean();
    nhAssert(strpos($html,'missing-image.png')===false && strpos($html,'<b>safe</b>')===false,'Missing images and metadata are safe');
    nhAssert(strpos($html,'3 phút đọc')!==false,'Reading time derives from Vietnamese content');
    foreach(array("' OR 1=1 --",'<script>alert(1)</script>','NHFIX-not-found') as $q) {
        $_GET=array('q'=>$q); require SOURCES.'news_hub.php';
        nhAssert($nhTotal===0,'Adversarial/unmatched search returns no rows');
    }
    $_GET=array('q'=>'100% _test_ !'); require SOURCES.'news_hub.php';
    nhAssert($nhTotal===14,'LIKE wildcard characters match literally');
    $lang='en'; $_GET=array('q'=>$suffix); require SOURCES.'news_hub.php';
    nhAssert($nhTotal===14,'English search uses Vietnamese fallback for untranslated articles');
    $lang='vi';
    foreach(array('news-intro','news-cta') as $block) {
        $d->insert('news',array('type'=>$block,'namevi'=>$suffix.' '.$block,'status'=>'hienthi,hero','numb'=>-99999,'date_created'=>time()));
        $ids[]=(int)$d->getLastInsertId();
    }
    $_GET=array(); require SOURCES.'news_hub.php';
    nhAssert($nhTitle===$suffix.' news-intro' && $nhCtaTitle===$suffix.' news-cta','CMS blocks override defaults');
    $d->where('id',$ids[14]); $d->update('news',array('status'=>'hienthi'));
    require SOURCES.'news_hub.php';
    nhAssert(!$nhShowFeatured,'Admin hero toggle disables featured section');
    $d->where('id',$ids[15]); $d->update('news',array('status'=>''));
    require SOURCES.'news_hub.php';
    nhAssert(!$nhBlocks['news-cta']['visible'],'Hidden CTA does not resurrect fallback');
} finally {
    foreach($ids as $fixtureId) $d->rawQuery('delete from #_news where id=?',array($fixtureId));
}
echo "News hub regression completed.\n";
