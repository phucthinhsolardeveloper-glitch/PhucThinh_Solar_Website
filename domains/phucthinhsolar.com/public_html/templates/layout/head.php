<!-- Basehref -->
<base href="<?=$configBase?>"/>

<?php /* Đánh dấu "có JavaScript" NGAY trong <head>, trước lần vẽ đầu tiên.
   Cụm liên hệ nổi ở footer mặc định hiện cả 3 nút (gọi/Zalo/Facebook) để người
   không có JS vẫn liên hệ được; apps.js mới thêm class is-enhanced để thu gọn.
   Vì apps.js chạy sau khi trang đã vẽ, người dùng thấy 3 nút NHÁY rồi mới thu
   lại. Class này cho CSS thu gọn ngay từ đầu mà vẫn giữ nguyên fallback: không
   có JS thì không có class, dock giữ nguyên 3 nút. Xem design-tokens.css. */ ?>
<script>document.documentElement.className+=' pts-js';</script>

<!-- UTF-8 -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<!-- Title, Keywords, Description -->
<title><?=$seo->get('title')?></title>
<meta name="keywords" content="<?=$seo->get('keywords')?>"/>
<meta name="description" content="<?=$seo->get('description')?>"/>

<!-- Robots -->
<?php $robotsContent = in_array($com, array('tim-kiem', 'gio-hang', 'cam-on', 'account', 'ngon-ngu'), true) ? 'noindex,nofollow' : 'index,follow';
if(isset($nhQuery) && $nhQuery !== '') $robotsContent = 'noindex,follow'; ?>
<meta name="robots" content="<?=$robotsContent?>" />

<!-- Favicon -->
<link href="assets/images/favicon-pts.png?v=20261005" rel="icon" type="image/png" sizes="32x32" />
<link href="assets/images/favicon-pts.png?v=20261005" rel="shortcut icon" type="image/png" />

<!-- Webmaster Tool -->
<?=htmlspecialchars_decode($setting['mastertool'])?>

<!-- GEO -->
<meta name="geo.region" content="VN" />
<meta name="geo.placename" content="Hồ Chí Minh" />
<meta name="geo.position" content="10.823099;106.629664" />
<meta name="ICBM" content="10.823099, 106.629664" />

<!-- Author - Copyright -->
<meta name='revisit-after' content='1 days' />
<meta name="author" content="<?=$setting['name'.$lang]?>" />
<meta name="copyright" content="<?=$setting['name'.$lang]." - [".$optsetting['email']."]"?>" />

<!-- Facebook -->
<?php $publicBrandName = 'Phúc Thịnh Solar'; ?>
<meta property="og:type" content="<?=$seo->get('type')?>" />
<meta property="og:site_name" content="<?=$publicBrandName?>" />
<meta property="og:title" content="<?=$seo->get('title')?>" />
<meta property="og:description" content="<?=$seo->get('description')?>" />
<meta property="og:url" content="<?=$seo->get('url')?>" />
<meta property="og:image" content="<?=$seo->get('photo')?>" />
<meta property="og:image:alt" content="<?=$seo->get('title')?>" />
<meta property="og:image:type" content="<?=$seo->get('photo:type')?>" />
<meta property="og:image:width" content="<?=$seo->get('photo:width')?>" />
<meta property="og:image:height" content="<?=$seo->get('photo:height')?>" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?=$seo->get('title')?>" />
<meta name="twitter:description" content="<?=$seo->get('description')?>" />
<meta name="twitter:image" content="<?=$seo->get('photo')?>" />

<!-- Canonical -->
<?php
	/* Ưu tiên canonical suy ra từ entity đã resolve; nếu route không map tới
	   entity đã biết thì giữ nguyên hành vi cũ (theo URL đang truy cập). */
	$canonicalHref = '';
	if(function_exists('publicCanonicalEntityPath'))
	{
		$canonicalEntityPath = publicCanonicalEntityPath($d, isset($com) ? $com : '', $_GET, $lang);
		if($canonicalEntityPath !== '') $canonicalHref = $configBase.$canonicalEntityPath;
	}
	if($canonicalHref === '') $canonicalHref = $func->getCurrentPageURL_CANO();
?>
<link rel="canonical" href="<?=htmlspecialchars($canonicalHref, ENT_QUOTES, 'UTF-8')?>" />

<!-- Chống đổi màu trên IOS -->
<meta name="format-detection" content="telephone=no">

<!-- Viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
