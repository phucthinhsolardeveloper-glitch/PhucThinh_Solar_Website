<?php

use App\Domain\Routing\Redirects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Old addresses that answered 404 in the 2026-11-13 audit (every public slug of the last PHP database tried in its
 * old forms against production, plus every internal link of the sitemap pages). Redirects are only consulted when a
 * page does not answer, so a category that gets products again simply shows itself.
 */
return new class extends Migration
{
    private const MOVES = [
        // "Trụ đèn" has no products, so its pages do not exist; street lights are the closest live shelf.
        '/danh-muc-san-pham/tru-den/' => '/danh-muc-san-pham/den-nlmt/den-duong/',
        '/danh-muc-san-pham/tru-den/tru-den-duong/' => '/danh-muc-san-pham/den-nlmt/den-duong/',
        '/danh-muc-san-pham/tru-den/tru-den-trang-tri/' => '/danh-muc-san-pham/den-nlmt/den-duong/',
        '/danh-muc-san-pham/tru-den/can-den-gia-do/' => '/danh-muc-san-pham/den-nlmt/den-duong/',
        '/danh-muc-san-pham/tru-den/mong-de-tru/' => '/danh-muc-san-pham/den-nlmt/den-duong/',
        // Short addresses of the PHP site.
        '/index.php' => '/',
        '/index.html' => '/',
        '/gioithieu' => '/gioi-thieu/',
        '/lienhe' => '/lien-he/',
        '/tuyen-dung' => '/tin-tuyen-dung',
        '/khuyen-mai' => '/san-pham/',
        '/chinh-sach' => '/ho-tro/dieu-kien-giao-dich-chung/',
        '/ho-tro' => '/ho-tro/dieu-kien-giao-dich-chung/',
        '/hinh-thuc-thanh-toan' => '/ho-tro/huong-dan-dat-hang-va-thanh-toan/',
        '/thanh-toan-bang-chuyen-khoan' => '/ho-tro/huong-dan-dat-hang-va-thanh-toan/',
        '/thanh-toan-tai-cong-ty' => '/ho-tro/huong-dan-dat-hang-va-thanh-toan/',
        '/thanh-toan-tai-diem-giao-hang' => '/ho-tro/huong-dan-dat-hang-va-thanh-toan/',
        // Links inside the Blue Carbon article.
        '/danh-muc-san-pham/den-nlmt/blue-carbon/' => '/thuong-hieu/blue-carbon/',
        '/tags-san-pham/blue-carbon/' => '/thuong-hieu/blue-carbon/',
    ];

    public function up(): void
    {
        foreach (self::MOVES as $from => $to) {
            Redirects::record($from, $to, false, 'manual');
        }
    }

    public function down(): void
    {
        DB::table('redirects')->whereIn('from_path', array_map(fn ($from) => Redirects::normalise($from), array_keys(self::MOVES)))->delete();
    }
};
