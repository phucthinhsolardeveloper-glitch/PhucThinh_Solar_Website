<?php
// Read-only production catalog image audit. Run via: ssh pts-prod 'docker exec -i pts-laravel13-backend php /dev/stdin' < this_file.php
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;

$media = app(ProductMediaUrls::class);
$rows = DB::table('products as p')
  ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
  ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
  ->where('p.is_visible', 1)
  ->select('p.*', 'c.name as category_name', 'b.name as brand_name')
  ->orderBy('p.id')->get();
$gallery = DB::table('product_images')->where('is_visible', 1)->orderBy('sort_order')->orderBy('id')
  ->get(['product_id','path','caption','sort_order'])->groupBy('product_id');
$specFields = ['power_watts','battery_capacity','panel_power','light_source','lamp_dimensions','color_temperature',
    'light_color','material','charging_time','lighting_time','waterproof_rating','led_chip','lighting_area',
    'installation_height','panel_dimensions','carton_quantity','carton_dimensions','weight','warranty','package_contents'];
$all = [];
$groups = [];
foreach ($rows as $p) {
    $raw = [];
    foreach (['image','image_secondary'] as $field) {
        $v = trim((string)($p->{$field} ?? ''));
        if ($v !== '') $raw[] = ['field'=>$field, 'filename'=>$v];
    }
    foreach ($gallery->get($p->id, collect()) as $g) {
        $v = trim((string)$g->path);
        if ($v !== '') $raw[] = ['field'=>'gallery', 'filename'=>$v, 'caption'=>$g->caption];
    }
    $valid = []; $broken = [];
    foreach ($raw as $item) {
        $url = $media->product($item['filename']);
        if ($url !== '') $valid[$url] ??= $item;
        else $broken[] = $item;
    }
    $count = count($valid);
    $numSpecs = 0; $missingSpecFields = [];
    foreach ($specFields as $field) {
       $v = trim((string)($p->{$field} ?? ''));
       if ($v !== '' && $v !== '0') $numSpecs++;
       else $missingSpecFields[] = $field;
    }
    $extra = json_decode((string)($p->additional_specs ?? ''), true);
    $extraCount = is_array($extra) ? count($extra) : 0;
    $category = (string)($p->category_name ?? '(không danh mục)');
    if (!isset($groups[$category])) $groups[$category] = ['total'=>0,'zero'=>0,'one'=>0,'two'=>0,'three_plus'=>0];
    $groups[$category]['total']++;
    $key = $count === 0 ? 'zero' : ($count === 1 ? 'one' : ($count === 2 ? 'two' : 'three_plus'));
    $groups[$category][$key]++;
    $all[] = [
        'id'=>(int)$p->id, 'code'=>(string)$p->code, 'name'=>(string)$p->name,
        'slug'=>(string)$p->slug,'category'=>$category,'brand'=>(string)($p->brand_name??''),
        'image_count'=>$count,'valid_images'=>array_values(array_map(fn($v,$k)=>['url'=>$k,'source'=>$v['field'],'filename'=>$v['filename']], array_values($valid), array_keys($valid))),
        'broken_images'=>$broken, 'raw_image_rows'=>count($raw), 'spec_fields_filled'=>$numSpecs,
        'additional_specs_count'=>$extraCount,'specs_html_length'=>mb_strlen((string)($p->specs_html ?? '')),
        'missing_spec_fields'=>$missingSpecFields
    ];
}
$summary = ['generated_at'=>date(DATE_ATOM),'scope'=>'production-visible-products','visible'=>count($all),
    'zero_images'=>count(array_filter($all,fn($r)=>$r['image_count']===0)),
    'one_image'=>count(array_filter($all,fn($r)=>$r['image_count']===1)),
    'two_images'=>count(array_filter($all,fn($r)=>$r['image_count']===2)),
    'three_plus'=>count(array_filter($all,fn($r)=>$r['image_count']>=3)),
    'has_broken_references'=>count(array_filter($all,fn($r)=>count($r['broken_images'])>0))];
echo json_encode(['summary'=>$summary,'by_category'=>$groups,'products'=>$all], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_INVALID_UTF8_SUBSTITUTE);
