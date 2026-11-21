<?php
/**
 * Phúc Thịnh Solar / ORIANA photo-only batch, 2026-11-17.
 * Exact source provenance: backend/resources/product-gallery/2026-11-17/batch-p0-real-photo/approved-six.json
 * Manifest is copied into container /tmp/pts-oriana-approved-six-20261117.json.
 * Default dry-run. Set PTS_PHOTOS_APPLY=YES only after preapply snapshot and sha256 checks.
 * Does not alter Shopping images, gallery, prices, specs, slug or any other products.
 */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Audit\Audit;

$manifestFile='/tmp/pts-oriana-approved-six-20261117.json';
$items=json_decode((string)file_get_contents($manifestFile),true,512,JSON_THROW_ON_ERROR);
$allowed=[510,513,519,522,526,687];
if(!is_array($items)||count($items)!==6)throw new RuntimeException('Expected 6 selected products');
$seen=[];
foreach($items as $m){
 $id=(int)($m['product_id']??0);
 if(!in_array($id,$allowed,true) || isset($seen[$id]))throw new RuntimeException('ID scope mismatch');
 $seen[$id]=true;
}
if(count($seen)!==6)throw new RuntimeException('Incomplete scope');
$db=DB::connection();
$media=app(ProductMediaUrls::class);
$apply=getenv('PTS_PHOTOS_APPLY')==='YES';
$checks=[];
foreach($items as $m){
 $id=(int)$m['product_id'];
 $p=$db->table('products')->where('id',$id)->first(['id','code','image','image_secondary','is_visible']);
 $file=(string)$m['output_file'];
 $safe= (bool) preg_match('/^pts-oriana-[a-z0-9-]+-photo-20261117\\.jpg$/',$file);
 if(!$safe || !$p || !(bool)$p->is_visible || (string)$p->code!==(string)$m['sku'])throw new RuntimeException('Product changed: '.$id);
 if(trim((string)$p->image_secondary)!=='')throw new RuntimeException('Has secondary photo already: '.$id);
 if($db->table('product_images')->where('product_id',$id)->exists())throw new RuntimeException('Unexpected gallery rows: '.$id);
 $existing=trim((string)$p->image);
 if($existing!=='' && $existing!==$file)throw new RuntimeException('Do not overwrite avatar: '.$id);
 $absolute=$media->absolute('upload/product/'.$file);
 if(!is_file($absolute) || hash_file('sha256',$absolute)!==(string)$m['output_sha256'])throw new RuntimeException('Photo missing or SHA mismatch: '.$file);
 if($media->product($file)!=='/upload/product/'.$file)throw new RuntimeException('Unresolvable photo: '.$file);
 $checks[]=['product_id'=>$id,'sku'=>$p->code,'filename'=>$file,'action'=>$existing===$file?'SKIP':'ADD'];
}
if($apply){
 $db->transaction(function()use($db,$items){
  foreach($items as $m){
   $id=(int)$m['product_id'];$file=(string)$m['output_file'];
   $p=$db->table('products')->where('id',$id)->lockForUpdate()->first(['id','code','image','image_secondary','is_visible']);
   if(!$p || !(bool)$p->is_visible || (string)$p->code!==(string)$m['sku']
     || trim((string)$p->image_secondary)!==''
     || $db->table('product_images')->where('product_id',$id)->exists()){
    throw new RuntimeException('Concurrent product change: '.$id);
   }
   if($p->image===$file)continue;
   if(trim((string)$p->image)!=='')throw new RuntimeException('Refuse overwrite: '.$id);
   $db->table('products')->where('id',$id)->whereNull('image')->update(['image'=>$file,'updated_at'=>now()]);
   Audit::record('product.photo_batch_add','products',$id,['image'=>null],[
     'image'=>$file,
     'source'=>'internal supplier workbook',
     'source_sha256'=>(string)$m['source_sha256'],
     'output_sha256'=>(string)$m['output_sha256']
   ],'system:pts-photo-audit-20261117');
  }
 });
}
echo json_encode(['mode'=>$apply?'APPLY':'DRY_RUN','total'=>count($checks),'checks'=>$checks],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
