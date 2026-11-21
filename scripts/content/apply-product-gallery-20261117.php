<?php
/**
 * Idempotent, four-row production media attach (DRAFT: safe by default).
 * Run from work WSL via SSH docker exec -i php /dev/stdin.
 * Production DB writes require PTS_GALLERY_APPLY=YES; no other tables touched.
 * New asset files must already exist in the public /srv/upload/product volume.
 */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;

$images = [
  811 => ['code'=>'X3-NEO-10K-LV','main'=>'x3-neo-10k-lvhybrid10kw-2-7396.png','file'=>'pts-solax-x3-neo-10k-lv-spec-card-20261117.jpg','sha'=>'0246938127f002e5250f2c6af2382f95e0eacec98c66b41aa0ec9c68e136b7eb'],
  812 => ['code'=>'X3-NEO-12K-LV','main'=>'x3-neo-12k-lvhybrid12kw-2-1693.png','file'=>'pts-solax-x3-neo-12k-lv-spec-card-20261117.jpg','sha'=>'c4f0379956978ba414937914d8a70a2c5b565bda3212934a1e78e636dfbd203c'],
  813 => ['code'=>'X3-NEO-15K-LV','main'=>'x3-neo-15k-lvhybrid15kw-2-1156.png','file'=>'pts-solax-x3-neo-15k-lv-spec-card-20261117.jpg','sha'=>'63264512a46c181b2614ab47a5c29280d2f06bbab15be297b00bfc1cb0fd0f40'],
  814 => ['code'=>'X3-NEO-20K-LV','main'=>'x3-neo-20k-lvhybrid20kw-2-7972.png','file'=>'pts-solax-x3-neo-20k-lv-spec-card-20261117.jpg','sha'=>'2fe14c3f2315e774e0664d9d23a3eb68c5e62ee3ec269f04515b7bffa11c56ce'],
];
$apply = getenv('PTS_GALLERY_APPLY') === 'YES';
$db=DB::connection();
$upload=app(ProductMediaUrls::class);
$plan=[];
foreach($images as $id=>$a) {
 $p=$db->table('products')->where('id',$id)->first(['id','code','image','image_secondary','is_visible']);
 if(!$p || !$p->is_visible || !str_starts_with((string)$p->code, $a['code'])
   || $p->image !== $a['main'] || trim((string)$p->image_secondary) !== ''
   || $upload->product($p->image) === '') {
   throw new RuntimeException('Refusing: product changed since the 2026-11-17 audit: '.$id);
 }
 $filename=$a['file'];
 $full=$upload->absolute('upload/product/'.$filename);
 if(!is_file($full) || hash_file('sha256',$full) !== $a['sha']) {
   throw new RuntimeException('Missing/changed image: '.$filename);
 }
 $already=$db->table('product_images')->where('product_id',$id)->where('path',$filename)->exists();
 $other=$db->table('product_images')->where('product_id',$id)->where('is_visible',true)->where('path','<>',$filename)->count();
 if($other!==0) throw new RuntimeException('Product now has another visible gallery row: '.$id);
 $plan[]=['id'=>$id,'code'=>$p->code,'image_file'=>$filename,'action'=>$already?'SKIP_ALREADY_PRESENT':'INSERT'];
}
if($apply) {
 $db->transaction(function()use($images,$db){
   foreach($images as $id=>$a) {
     $p=$db->table('products')->where('id',$id)->lockForUpdate()->first(['code','image','image_secondary','is_visible']);
     if(!$p || !$p->is_visible || !str_starts_with((string)$p->code, $a['code'])
      || $p->image!==$a['main'] || trim((string)$p->image_secondary)!=='') {
       throw new RuntimeException('Concurrent update: '.$id);
     }
     $exists=$db->table('product_images')->where('product_id',$id)->where('path',$a['file'])->exists();
     if(!$exists){
       $other=$db->table('product_images')->where('product_id',$id)->where('is_visible',true)->count();
       if($other!==0)throw new RuntimeException('Concurrent gallery update: '.$id);
       $db->table('product_images')->insert([
          'product_id'=>$id,'path'=>$a['file'],
          'caption'=>'Thông số kỹ thuật '. $a['code'].' — nguồn: SolaX Power',
          'is_visible'=>true,'sort_order'=>10,
          'created_at'=>now(),'updated_at'=>now()
       ]);
     }
   }
 });
}
echo json_encode(['mode'=>$apply?'APPLY':'DRY_RUN','records'=>$plan],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
