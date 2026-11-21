<?php
/**
 * Replace 5 staged product images with lossless alpha variants (QA v2).
 * Dry-run by default; apply with PTS_PHOTO_QAV2_APPLY=YES.
 * Exact SKU, hero, 2 gallery paths, source hash and file must match.
 * Never touches Shopping image, prices, specs or other products.
 */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app=require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Audit\Audit;

$m=json_decode((string)file_get_contents('/tmp/pts-photo-qav2-five-20261117.json'),true,512,JSON_THROW_ON_ERROR);
$approvedIds=[510,513,808,809,810];
$items=$m['products']??[];
$assets=$m['source_files']??[];
if(count($items)!==5 || count($assets)!==5 || ($m['batch_name']??'')!=='photo_quality_rework_2026_11_17')
 throw new RuntimeException('Unexpected manifest');
$files=[];
$media=app(ProductMediaUrls::class);
$db=DB::connection();
foreach($assets as $asset){
 $fn=$asset['file']??'';
 if(!preg_match('/^pts-(?:solax|oriana)-[a-z0-9-]+-alpha-qav2-20261117\\.png$/',$fn)) throw new RuntimeException('Unexpected filename '.$fn);
 if(isset($files[$fn]))throw new RuntimeException('Duplicate source file');
 $full=$media->absolute('upload/product/'.$fn);
 if(!is_file($full) || hash_file('sha256',$full)!==($asset['sha256']??''))throw new RuntimeException('Source file missing/changed '.$fn);
 $files[$fn]=true;
 if($media->product($fn)!=='/upload/product/'.$fn)throw new RuntimeException('Product URL resolution failed');
}
$observed=[];
foreach($items as $item){
 $id=(int)($item['id']??0);
 if(!in_array($id,$approvedIds,true)||isset($observed[$id]))throw new RuntimeException('Unexpected scope');
 $observed[$id]=true;
 $p=$db->table('products')->where('id',$id)->first(['id','code','image','image_secondary','is_visible']);
 if(!$p || !(bool)$p->is_visible || $p->code!==$item['sku']
 || trim((string)$p->image_secondary)!=='')throw new RuntimeException('SKU changed '.$id);
 foreach([$item['new_image'],...$item['gallery_new']] as $fn){
   if(!isset($files[$fn]))throw new RuntimeException('New file not in hashed file set '.$fn);
 }
 $already=$p->image===$item['new_image'];
 if($p->image!==$item['previous_image'] && !$already)throw new RuntimeException('Different live hero image '.$id);
 $actual=$db->table('product_images')->where('product_id',$id)->orderBy('sort_order')->orderBy('id')->get(['path','is_visible']);
 $existing=$actual->pluck('path')->all();
 $expected=$already?$item['gallery_new']:$item['gallery_previous'];
 if($existing!==$expected || $actual->contains(fn($g)=>!(bool)$g->is_visible))
    throw new RuntimeException('Unexpected current gallery '.$id.' got '.json_encode($existing));
}
if($m['status']!=='STAGED_QA_VALIDATED_ALPHAS')throw new RuntimeException('No QA approval');
$apply=getenv('PTS_PHOTO_QAV2_APPLY')==='YES';
if($apply){
 $db->transaction(function() use($db,$items){
   foreach($items as $item){
     $id=(int)$item['id'];
     $p=$db->table('products')->where('id',$id)->lockForUpdate()->first(['code','image','image_secondary','is_visible']);
     if(!$p || !(bool)$p->is_visible || $p->code!==$item['sku']||trim((string)$p->image_secondary)!=='')
       throw new RuntimeException('Concurrent state change '.$id);
     if($p->image===$item['new_image'])continue;
     if($p->image!==$item['previous_image'])throw new RuntimeException('Concurrent hero edit '.$id);
     $gal=$db->table('product_images')->where('product_id',$id)->orderBy('sort_order')->orderBy('id')->get(['id','path','is_visible']);
     if($gal->pluck('path')->all()!==$item['gallery_previous']||$gal->contains(fn($x)=>!(bool)$x->is_visible))
       throw new RuntimeException('Concurrent gallery edit '.$id);
     $db->table('products')->where('id',$id)->where('image',$item['previous_image'])->update([
       'image'=>$item['new_image'],'updated_at'=>now()
     ]);
     foreach($gal as $k=>$g){
       $db->table('product_images')->where('id',$g->id)->update([
         'path'=>$item['gallery_new'][$k],'updated_at'=>now()
       ]);
     }
     Audit::record('product.photo_alpha_rework','products',$id,
       ['image'=>$item['previous_image'],'gallery'=>$item['gallery_previous']],
       ['image'=>$item['new_image'],'gallery'=>$item['gallery_new'],
        'method'=>'preserve original alpha or conservative true background cutout',
        'source_quality'=>'transparency QA passed; native source resolution limited',
        'old_files_retained'=>true],
       'system:pts-photo-qav2-20261117');
   }
 });
}
echo json_encode(['mode'=>$apply?'APPLY':'DRY_RUN','product_ids'=>array_keys($observed),
 'assets'=>array_keys($files),'all_passed'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
