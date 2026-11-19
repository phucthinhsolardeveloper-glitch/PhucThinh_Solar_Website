<?php

use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Filament\Resources\SeoPages\Pages\ManageSeoPages;
use App\Filament\Resources\Videos\Pages\ManageVideos;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function inlineOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'il-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

it('shows the pencil on the CMS lists and swaps in editable columns only while it is on', function () {
    $this->actingAs(inlineOwner(), 'admin');
    foreach ([[ListBrands::class, 'sort_order'], [ManageBanners::class, 'sort_order'], [ManageVideos::class, 'url'], [ManageRedirects::class, 'to_path'], [ManageSeoPages::class, 'description'], [ManageLeads::class, 'follow_up_at']] as [$page, $column]) {
        Livewire\Livewire::test($page)->assertActionVisible('inlineEdit');
        Livewire\Livewire::withQueryParams(['sua' => 1])->test($page)->assertTableColumnExists($column);
    }
});

it('saves brand name and order from the list and refuses a bad order', function () {
    $this->actingAs(inlineOwner(), 'admin');
    $brand = Brand::query()->firstOrFail();
    $key = (string) $brand->getKey();
    $page = Livewire\Livewire::withQueryParams(['sua' => 1])->test(ListBrands::class);
    $page->call('updateTableColumnState', 'name', $key, 'Hãng sửa nhanh')->call('updateTableColumnState', 'sort_order', $key, '7');
    expect($brand->fresh()->name)->toBe('Hãng sửa nhanh')->and((int) $brand->fresh()->sort_order)->toBe(7);
    $page->call('updateTableColumnState', 'sort_order', $key, '-3')->call('updateTableColumnState', 'name', $key, '');
    expect((int) $brand->fresh()->sort_order)->toBe(7)->and($brand->fresh()->name)->toBe('Hãng sửa nhanh');
});

it('refuses a video link that is not a URL and a redirect target without a leading slash', function () {
    $this->actingAs(inlineOwner(), 'admin');
    $video = DB::table('videos')->first();
    if ($video) {
        Livewire\Livewire::withQueryParams(['sua' => 1])->test(ManageVideos::class)->call('updateTableColumnState', 'url', (string) $video->id, 'khong-phai-link');
        expect(DB::table('videos')->where('id', $video->id)->value('url'))->toBe($video->url);
    }
    $id = DB::table('redirects')->insertGetId(['from_path' => '/cu-thu-sua-nhanh/', 'to_path' => '/moi-thu/', 'is_prefix' => false, 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
    $page = Livewire\Livewire::withQueryParams(['sua' => 1])->test(ManageRedirects::class);
    $page->call('updateTableColumnState', 'to_path', (string) $id, 'khong-co-gach-cheo');
    expect(DB::table('redirects')->where('id', $id)->value('to_path'))->toBe('/moi-thu/');
    $page->call('updateTableColumnState', 'to_path', (string) $id, '/trang-dich-moi/');
    expect(DB::table('redirects')->where('id', $id)->value('to_path'))->toBe('/trang-dich-moi/');
});

it('changes a lead status and follow-up time from the list through the same audited service', function () {
    $this->actingAs(inlineOwner(), 'admin');
    $lead = Lead::query()->firstOrFail();
    $lead->update(['status' => 'new', 'follow_up_at' => null]);
    $key = (string) $lead->getKey();
    $page = Livewire\Livewire::withQueryParams(['sua' => 1])->test(ManageLeads::class);
    $page->call('updateTableColumnState', 'follow_up_at', $key, '20/12/2026 09:30');
    expect(Illuminate\Support\Carbon::parse($lead->fresh()->follow_up_at)->format('Y-m-d H:i'))->toBe('2026-12-20 09:30');
    $page->call('updateTableColumnState', 'status', $key, 'contacted');
    expect($lead->fresh()->status)->toBe('contacted');
    $page->call('updateTableColumnState', 'status', $key, 'done');
    expect($lead->fresh()->status)->toBe('done')->and($lead->fresh()->follow_up_at)->toBeNull();
});
