<?php
/**
 * Three SolaX X1-Lite-LV variants, official real product photo + two angles.
 * Defaults DRY_RUN. To apply set PTS_SOLAX_X1_PHOTOS_APPLY=YES.
 * Unrelated products, pricing, specifications, Shopping images, slugs remain unchanged.
 */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app=require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Audit\Audit;

$m=json_decode((string)file_get_contents('/tmp/pts-solax-x1-official-20261117.json'),true,512,JSON_THROW_ON_ERROR);
$files=$m['assets']??[];$products=$m['products']??[];
$expectedSku=[
 808=>'X1-LITE-8-LV Hybrid 8kw',
 809=>'X1-LITE-10-LV Hybrid 10kw',
 810=>'X1-LITE-12-LV Hybrid 12kw'
];
if(count($files)!==3 || count($products)!==3 || ($m['source_page']??'')!=='https://vn.solaxpower.com/products/x1-lite-lv.html')throw new RuntimeException('Source manifest mismatch');
$media=app(ProductMediaUrls::class);$db=DB::connection();
foreach($files as $j=>$a){
 $file=$a['output_file']??'';
 if($file!==sprintf('pts-solax-x1-lite-lv-%d-official-20261117.jpg',$j+1))throw new RuntimeException('Filename mismatch');
 if(!in_array($a['view']??'',['front','front-left','front-right'],true))throw new RuntimeException('Invalid view');
 $full=$media->absolute('upload/product/'.$file);
 if(!is_file($full)||hash_file('sha256',$full)!==($a['output_sha256']??''))throw new RuntimeException('File not found / hash mismatch: '.$file);
 if($media->product($file)!=='/upload/product/'.$file)throw new RuntimeException('Media inaccessible');
}
foreach($products as $p){
 $id=(int)($p['product_id']??0);
 if(!isset($expectedSku[$id]) || ($p['sku']??'')!==$expectedSku[$id])throw new RuntimeException('Unexpected product id/sku');
 $current=$db->table('products')->where('id',$id)->first(['id','code','image','image_secondary','is_visible']);
 if(!$current||!$current->is_visible||$current->code!==$p['sku']||trim((string)$current->image_secondary)!=='')throw new RuntimeException('Product is changed');
 if($current->image!==null && $current->image!==$files[0]['output_file'])throw new RuntimeException('Refuse to overwrite avatar');
 $gal=$db->table('product_images')->where('product_id',$id)->get(['path','is_visible']);
 foreach($gal as $g)if(!in_array($g->path,[$files[1]['output_file'],$files[2]['output_file']],true))throw new RuntimeException('Other gallery files exist for product');
}
$apply=getenv('PTS_SOLAX_X1_PHOTOS_APPLY')==='YES';
if($apply){
 $db->transaction(function()use($db,$products,$files,$expectedSku,$m){
  foreach($products as $p){
   $id=(int)$p['product_id'];$current=$db->table('products')->where('id',$id)->lockForUpdate()->first(['code','image','image_secondary','is_visible']);
   if(!$current || !$current->is_visible || $current->code!==$expectedSku[$id]||$current->image_secondary!==null && $current->image_secondary!=='' || ($current->image!==null && $current->image!==$files[0]['output_file']))throw new RuntimeException('Concurrent data change');
   if($current->image===null){
    $db->table('products')->where('id',$id)->whereNull('image')->update(['image'=>$files[0]['output_file'],'updated_at'=>now()]);
   }
   foreach([1,2] as $i){
    $file=$files[$i]['output_file'];
    $existing=$db->table('product_images')->where('product_id',$id)->where('path',$file)->first();
    if($existing){
     if(!(bool)$existing->is_visible)throw new RuntimeException('Existing gallery image is hidden: '.$file);
     continue;
    }
    if($db->table('product_images')->where('product_id',$id)->whereNotIn('path',[$files[1]['output_file'],$files[2]['output_file']])->exists())throw new RuntimeException('Concurrent gallery edit');
    $db->table('product_images')->insert([
       'product_id'=>$id,'path'=>$file,'caption'=>'Ảnh thiết bị góc nghiêng SolaX X1-Lite-LV — nguồn: SolaX Power',
       'is_visible'=>true,'sort_order'=>$i*10,'created_at'=>now(),'updated_at'=>now()
    ]);
   }
   Audit::record('product.photo_gallery_add','products',$id,['image'=>null,'gallery_count'=>0],[
     'image'=>$files[0]['output_file'],
     'gallery'=>[$files[1]['output_file'],$files[2]['output_file']],
     'source'=>$m['source_page'],
     'source_sha256'=>array_column($files,'source_sha256'),
     'photo_kind'=>'official manufacturer actual product photograph'
   ],'system:pts-product-photo-20261117');
  }
 });
}
echo json_encode(['mode'=>$apply?'APPLY':'DRY_RUN','models'=>$expectedSku,'files'=>array_column($files,'output_file'),'source'=>$m['source_page']],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
