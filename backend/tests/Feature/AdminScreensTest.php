<?php

use App\Filament\Pages\SiteSettingsPage;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Models\Album;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Every Admin screen opens for an owner, and an editor only sees the areas they were given.

function admin(string $role = User::ROLE_OWNER, array $permissions = []): User
{
    return User::query()->create(['name' => 'Quản trị thử', 'username' => 'test-'.bin2hex(random_bytes(4)), 'password' => 'mat-khau-thu',
        'role' => $role, 'permissions' => $permissions, 'is_active' => true]);
}

it('opens every list, create and edit screen for an owner', function () {
    $this->actingAs(admin(), 'admin');
    $full = [
        'products' => DB::table('products')->value('id'),
        'categories' => DB::table('categories')->whereNotNull('parent_id')->value('id'),
        'brands' => DB::table('brands')->value('id'),
        'articles' => DB::table('posts')->where('kind', 'article')->value('id'),
        'solutions' => DB::table('posts')->where('kind', 'solution')->value('id'),
        'projects' => DB::table('posts')->where('kind', 'project')->value('id'),
        'policies' => DB::table('posts')->where('kind', 'policy')->value('id'),
        'albums' => DB::table('albums')->value('id'),
    ];
    foreach ($full as $slug => $id) {
        $this->get("/admin/{$slug}")->assertOk();
        $this->get("/admin/{$slug}/create")->assertOk();
        if ($id) {
            $this->get("/admin/{$slug}/{$id}/edit")->assertOk();
        }
    }
    foreach (['post-categories', 'page-items', 'banners', 'videos', 'wards', 'payment-methods', 'store-locations', 'leads', 'reviews', 'users', 'seo-pages', 'orders', 'thong-tin-website', ''] as $slug) {
        $this->get('/admin/'.$slug)->assertOk();
    }
});

it('keeps settings unchanged when the settings page is saved as it is', function () {
    $this->actingAs(admin(), 'admin');
    $before = DB::table('settings')->pluck('value', 'key')->map(fn ($v) => json_decode((string) $v, true))->all();

    Livewire\Livewire::test(SiteSettingsPage::class)->call('save')->assertHasNoFormErrors();

    $after = DB::table('settings')->pluck('value', 'key')->map(fn ($v) => json_decode((string) $v, true))->all();
    expect($after)->toBe($before);
});

it('changes only the handling fields of an order', function () {
    $this->actingAs(admin(), 'admin');
    $id = DB::table('orders')->insertGetId(['code' => 'ADMTEST', 'customer_name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'x',
        'subtotal' => 100000, 'total' => 100000, 'status' => 'new', 'payment_status' => 'unpaid', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('order_items')->insert(['order_id' => $id, 'name' => 'Đèn thử', 'quantity' => 2, 'regular_price' => 50000, 'created_at' => now(), 'updated_at' => now()]);

    Livewire\Livewire::test(EditOrder::class, ['record' => $id])
        ->fillForm(['status' => 'confirmed', 'notes' => 'Đã gọi khách', 'customer_name' => 'Sửa trộm'])
        ->call('save')->assertHasNoFormErrors();

    $order = DB::table('orders')->find($id);
    expect($order->status)->toBe('confirmed')->and($order->notes)->toBe('Đã gọi khách')->and($order->customer_name)->toBe('Khách thử')
        ->and(DB::table('order_items')->where('order_id', $id)->value('quantity'))->toBe(2);
});

it('refuses an area an editor was not given', function () {
    $this->actingAs(admin(User::ROLE_EDITOR, ['content']), 'admin');
    $this->get('/admin/products')->assertForbidden();
});

it('opens the area an editor was given', function () {
    $this->actingAs(admin(User::ROLE_EDITOR, ['catalog']), 'admin');
    $this->get('/admin/products')->assertOk();
});

it('saves a product unchanged when nothing was edited', function () {
    $this->actingAs(admin(), 'admin');
    $id = DB::table('products as p')->join('content_sections as c', fn ($j) => $j->on('c.owner_id', '=', 'p.id')->where('c.owner_type', Product::class))
        ->where('p.highlights', '<>', '')->whereRaw('json_length(c.blocks) > 3')->value('p.id');
    $columns = ['name', 'slug', 'highlights', 'applications', 'installation', 'additional_specs', 'regular_price', 'sale_price', 'image', 'category_id', 'brand_id'];
    $before = (array) DB::table('products')->where('id', $id)->first($columns);
    $blocksBefore = DB::table('content_sections')->where('owner_type', Product::class)->where('owner_id', $id)->value('blocks');
    $imagesBefore = DB::table('product_images')->where('product_id', $id)->orderBy('id')->pluck('path')->all();

    Livewire\Livewire::test(EditProduct::class, ['record' => $id])
        ->call('save')->assertHasNoFormErrors();

    $after = (array) DB::table('products')->where('id', $id)->first($columns);
    foreach (['highlights', 'applications', 'installation', 'additional_specs'] as $json) {
        expect(json_decode((string) $after[$json], true))->toBe(json_decode((string) $before[$json], true), $json);
        expect((string) $after[$json])->not->toContain('\\u');
        unset($before[$json], $after[$json]);
    }
    expect($after)->toEqual($before)
        ->and(json_decode((string) DB::table('content_sections')->where('owner_type', Product::class)->where('owner_id', $id)->value('blocks'), true))
        ->toBe(json_decode((string) $blocksBefore, true))
        ->and(DB::table('product_images')->where('product_id', $id)->orderBy('id')->pluck('path')->all())->toBe($imagesBefore);
});

dataset('editable records', [
    'category' => ['categories', Category::class, EditCategory::class, []],
    'brand' => ['brands', Brand::class, EditBrand::class, []],
    'article' => ['posts', Post::class, EditArticle::class, ['kind' => 'article']],
    'solution' => ['posts', Post::class, EditSolution::class, ['kind' => 'solution']],
    'project' => ['posts', Post::class, EditProject::class, ['kind' => 'project']],
    'album' => ['albums', Album::class, EditAlbum::class, []],
]);

it('saves a record unchanged when nothing was edited', function (string $table, string $model, string $page, array $where) {
    $this->actingAs(admin(), 'admin');
    $id = DB::table($table)->where($where)->orderByDesc('id')->value('id');
    if (! $id) {
        $this->markTestSkipped("No {$table} row in the test database.");
    }
    $snapshot = fn () => [
        'row' => collect((array) DB::table($table)->find($id))->except(['updated_at'])->all(),
        'seo' => collect((array) DB::table('seo_metas')->where('owner_type', $model)->where('owner_id', $id)->first())->except(['updated_at', 'created_at', 'id'])->all(),
        'blocks' => json_decode((string) DB::table('content_sections')->where('owner_type', $model)->where('owner_id', $id)->value('blocks'), true),
        'media' => DB::table('post_media')->where('post_id', $table === 'posts' ? $id : 0)->orderBy('id')->get(['path', 'video_url', 'kind', 'caption'])->toArray(),
    ];
    $before = $snapshot();

    Livewire\Livewire::test($page, ['record' => $id])->call('save')->assertHasNoFormErrors();

    expect($snapshot())->toEqual($before);
})->with('editable records');
