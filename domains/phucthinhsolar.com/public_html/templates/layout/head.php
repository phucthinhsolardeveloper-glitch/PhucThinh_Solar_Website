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
<link href="assets/images/favicon-pts.png?v=20261008" rel="icon" type="image/png" sizes="32x32" />
<link href="assets/images/favicon-pts-mobile.png?v=20261008" rel="icon" type="image/png" sizes="192x192" />
<link href="assets/images/favicon-pts-mobile.png?v=20261008" rel="icon" type="image/png" sizes="512x512" />
<link href="assets/images/favicon-pts-mobile.png?v=20261008" rel="apple-touch-icon" sizes="180x180" />
<link href="site.webmanifest?v=20261008" rel="manifest" />
<meta name="theme-color" content="#0a2251" />

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

<!-- Canonical (tính trước OG để og:url luôn có fallback hợp lệ) -->
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

<!-- Facebook -->
<?php
	$publicBrandName = 'Phúc Thịnh Solar';
	/* Fallback OG khi trang (search, cart...) chưa gọi $seo->set() cho
	   description/url/photo - không bịa nội dung riêng của trang, chỉ dùng
	   tên site tĩnh, canonical đã tính và logo chung (cùng ảnh dùng cho
	   Organization JSON-LD ở strucdata.php) để tránh OG rỗng. */
	$ogDescription = $seo->get('description');
	if($ogDescription === '') $ogDescription = isset($setting['name'.$lang]) ? $setting['name'.$lang] : $publicBrandName;
	$ogUrl = $seo->get('url');
	if($ogUrl === '') $ogUrl = $canonicalHref;
	$ogImage = $seo->get('photo');
	$ogImageType = $seo->get('photo:type');
	$ogImageWidth = $seo->get('photo:width');
	$ogImageHeight = $seo->get('photo:height');
	if($ogImage === '' && !empty($logo['photo']))
	{
		$ogImage = $configBase.UPLOAD_PHOTO_L.$logo['photo'];
		$ogImageType = '';
		$ogImageWidth = '';
		$ogImageHeight = '';
	}
?>
<meta property="og:type" content="<?=$seo->get('type')?>" />
<meta property="og:site_name" content="<?=$publicBrandName?>" />
<meta property="og:title" content="<?=$seo->get('title')?>" />
<meta property="og:description" content="<?=$ogDescription?>" />
<meta property="og:url" content="<?=$ogUrl?>" />
<meta property="og:image" content="<?=$ogImage?>" />
<meta property="og:image:alt" content="<?=$seo->get('title')?>" />
<meta property="og:image:type" content="<?=$ogImageType?>" />
<meta property="og:image:width" content="<?=$ogImageWidth?>" />
<meta property="og:image:height" content="<?=$ogImageHeight?>" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?=$seo->get('title')?>" />
<meta name="twitter:description" content="<?=$ogDescription?>" />
<meta name="twitter:image" content="<?=$ogImage?>" />

<!-- Chống đổi màu trên IOS -->
<meta name="format-detection" content="telephone=no">

<!-- Viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
