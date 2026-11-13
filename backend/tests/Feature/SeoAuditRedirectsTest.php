<?php

use App\Domain\Routing\Redirects;
use App\Domain\Solutions\SolutionHub;
use Illuminate\Support\Facades\DB;

it('sends the old PHP short addresses and the empty Trụ đèn shelf somewhere that answers', function () {
    $migration = require database_path('migrations/2026_11_13_000100_redirect_old_paths_found_in_seo_audit.php');
    $migration->up();

    expect(Redirects::find('/lienhe'))->toBe('/lien-he/')
        ->and(Redirects::find('/lienhe/'))->toBe('/lien-he/')
        ->and(Redirects::find('/index.php'))->toBe('/')
        ->and(Redirects::find('/danh-muc-san-pham/tru-den/'))->toBe('/danh-muc-san-pham/den-nlmt/den-duong/')
        ->and(Redirects::find('/thanh-toan-tai-cong-ty'))->toBe('/ho-tro/huong-dan-dat-hang-va-thanh-toan/');

    // An older address that pointed at the empty shelf now goes straight to the live one (one hop).
    Redirects::record('/tru-den-thu', '/danh-muc-san-pham/tru-den/');
    $migration->up();
    expect(Redirects::find('/tru-den-thu'))->toBe('/danh-muc-san-pham/den-nlmt/den-duong/');
});

it('offers only product groups whose page exists as shortcuts on the solution pages', function () {
    $empty = (int) DB::table('categories')->insertGetId(['name' => 'Nhóm rỗng', 'slug' => 'nhom-rong-'.bin2hex(random_bytes(3)), 'depth' => 1, 'sort_order' => 0, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()]);

    $ids = array_column(SolutionHub::liveProductGroups(DB::connection()), 'id');
    expect($ids)->not->toContain($empty)
        ->and(count($ids))->toBeGreaterThan(0);
});
