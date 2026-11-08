<?php

use App\Domain\Commerce\ManualPayment;
use App\Filament\Pages\SiteSettingsPage;
use App\Filament\Resources\Albums\Pages\CreateAlbum;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Policies\Pages\CreatePolicy;
use App\Filament\Resources\Policies\Pages\EditPolicy;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Solutions\Pages\CreateSolution;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Models\Album;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    foreach (['post-categories', 'page-items', 'banners', 'videos', 'wards', 'provinces', 'payment-methods', 'store-locations', 'leads', 'reviews', 'users', 'seo-pages', 'orders', 'thong-tin-website', 'tich-hop', ''] as $slug) {
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
        ->fillForm(['status' => 'confirmed', 'notes' => 'Đã gọi khách', 'customer_name' => 'Sửa trộm', 'payment_status' => 'paid'])
        ->call('save')->assertHasNoFormErrors();

    $order = DB::table('orders')->find($id);
    expect($order->status)->toBe('confirmed')->and($order->notes)->toBe('Đã gọi khách')->and($order->customer_name)->toBe('Khách thử')
        ->and($order->payment_status)->toBe('unpaid')
        ->and(DB::table('order_items')->where('order_id', $id)->value('quantity'))->toBe(2);
});

it('records a manual payment with staff reference and forbids changing a VNPay order from Admin', function () {
    $actor = admin(User::ROLE_EDITOR, ['orders', 'orders_payment']);
    $this->actingAs($actor, 'admin');
    $base = ['customer_name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'x',
        'subtotal' => 100000, 'total' => 100000, 'status' => 'new', 'payment_status' => 'unpaid',
        'created_at' => now(), 'updated_at' => now()];
    $manualId = DB::table('orders')->insertGetId($base + ['code' => 'ADMIN-CASH', 'payment_code' => 'manual']);
    $gatewayId = DB::table('orders')->insertGetId($base + ['code' => 'ADMIN-VNP', 'payment_code' => 'vnpay']);

    Livewire\Livewire::test(EditOrder::class, ['record' => $manualId])
        ->callAction('confirmManualPayment', ['reference' => 'PHIEU-001'])
        ->assertHasNoActionErrors();
    $manual = DB::table('orders')->find($manualId);
    $transaction = DB::table('payment_transactions')->where('order_id', $manualId)->first();
    expect($manual->payment_status)->toBe('paid')->and($manual->payment_reference)->toBe('PHIEU-001')
        ->and($transaction->provider)->toBe('manual')->and($transaction->amount)->toBe('100000')
        ->and($transaction->provider_reference)->toBe('PHIEU-001');
    expect(json_decode($transaction->payload, true)['confirmed_by'])->toBe($actor->id);
    expect(fn () => app(ManualPayment::class)->confirm(Order::findOrFail($manualId), $actor, 'PHIEU-002'))
        ->toThrow(ValidationException::class);
    expect(DB::table('payment_transactions')->where('order_id', $manualId)->count())->toBe(1);

    Livewire\Livewire::test(EditOrder::class, ['record' => $manualId])
        ->assertActionVisible('recordManualRefund')
        ->callAction('recordManualRefund', ['reference' => 'HOAN-001'])
        ->assertHasNoActionErrors();
    $refund = DB::table('payment_transactions')->where('order_id', $manualId)->where('status', 'refunded')->first();
    expect(DB::table('orders')->where('id', $manualId)->value('payment_status'))->toBe('refunded')
        ->and($refund->amount)->toBe('100000')->and($refund->provider_reference)->toBe('HOAN-001')
        ->and(json_decode($refund->payload, true)['recorded_by'])->toBe($actor->id);
    expect(fn () => app(ManualPayment::class)->recordRefund(Order::findOrFail($manualId), $actor, 'HOAN-002'))
        ->toThrow(ValidationException::class);
    expect(DB::table('payment_transactions')->where('order_id', $manualId)->count())->toBe(2);

    Livewire\Livewire::test(EditOrder::class, ['record' => $gatewayId])
        ->assertActionHidden('confirmManualPayment')->assertActionHidden('recordManualRefund')
        ->fillForm(['payment_status' => 'paid', 'status' => 'confirmed'])
        ->call('save')->assertHasNoFormErrors();
    expect(DB::table('orders')->where('id', $gatewayId)->value('payment_status'))->toBe('unpaid')
        ->and(DB::table('payment_transactions')->where('order_id', $gatewayId)->count())->toBe(0);
    expect(fn () => app(ManualPayment::class)->recordRefund(Order::findOrFail($gatewayId), $actor, 'HOAN-003'))
        ->toThrow(ValidationException::class);

    $viewer = admin(User::ROLE_EDITOR, ['orders']);
    $this->flushSession();
    $this->actingAs($viewer, 'admin');
    $this->get('/admin/orders/'.$gatewayId.'/edit')->assertOk();
    Livewire\Livewire::test(EditOrder::class, ['record' => $gatewayId])
        ->assertActionHidden('confirmManualPayment')->assertActionHidden('recordManualRefund');
    expect(fn () => app(ManualPayment::class)->confirm(Order::findOrFail($gatewayId), $viewer, 'PHIEU-003'))
        ->toThrow(HttpException::class);
    expect(fn () => app(ManualPayment::class)->recordRefund(Order::findOrFail($manualId), $viewer, 'HOAN-004'))
        ->toThrow(HttpException::class);
});

it('refuses an area an editor was not given', function () {
    $this->actingAs(admin(User::ROLE_EDITOR, ['content']), 'admin');
    $this->get('/admin/products')->assertForbidden();
});

it('opens the area an editor was given', function () {
    $this->actingAs(admin(User::ROLE_EDITOR, ['catalog']), 'admin');
    $this->get('/admin/products')->assertOk();
});

it('enforces each editor area on direct Admin routes', function () {
    $areas = [
        'catalog' => 'products', 'content' => 'articles', 'media' => 'banners',
        'orders' => 'orders', 'leads' => 'leads', 'reviews' => 'reviews',
        'settings' => 'seo-pages',
    ];

    foreach (array_keys($areas) as $granted) {
        $this->flushSession();
        $this->actingAs(admin(User::ROLE_EDITOR, [$granted]), 'admin');
        foreach ($areas as $area => $route) {
            $this->get('/admin/'.$route)->assertStatus($area === $granted ? 200 : 403);
        }
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/thong-tin-website')->assertStatus($granted === 'settings' ? 200 : 403);
    }

    $productId = DB::table('products')->value('id');
    $this->flushSession();
    $this->actingAs(admin(User::ROLE_EDITOR, ['content']), 'admin');
    $this->get('/admin/products/create')->assertForbidden();
    $this->get('/admin/products/'.$productId.'/edit')->assertForbidden();
    $this->flushSession();
    $this->actingAs(admin(User::ROLE_EDITOR, ['catalog']), 'admin');
    $this->get('/admin/products/create')->assertOk();
    $this->get('/admin/products/'.$productId.'/edit')->assertOk();
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

it('creates and deletes a product without leaving its related rows behind', function () {
    $this->actingAs(admin(), 'admin');
    $slug = 'admin-create-delete-'.bin2hex(random_bytes(4));
    $categoryId = DB::table('categories')->value('id');

    Livewire\Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Sản phẩm thử Admin', 'slug' => $slug, 'category_id' => $categoryId,
            'regular_price' => 100000, 'is_visible' => true])
        ->call('create')->assertHasNoFormErrors();

    $id = DB::table('products')->where('slug', $slug)->value('id');
    expect($id)->not->toBeNull();
    Livewire\Livewire::test(EditProduct::class, ['record' => $id])
        ->callAction('delete')->assertHasNoActionErrors();

    expect(DB::table('products')->where('id', $id)->exists())->toBeFalse()
        ->and(DB::table('content_sections')->where('owner_type', Product::class)->where('owner_id', $id)->exists())->toBeFalse()
        ->and(DB::table('seo_metas')->where('owner_type', Product::class)->where('owner_id', $id)->exists())->toBeFalse();
});

dataset('created admin records', [
    'category' => ['categories', CreateCategory::class, EditCategory::class, 'name'],
    'brand' => ['brands', CreateBrand::class, EditBrand::class, 'name'],
    'article' => ['posts', CreateArticle::class, EditArticle::class, 'title'],
    'solution' => ['posts', CreateSolution::class, EditSolution::class, 'title'],
    'project' => ['posts', CreateProject::class, EditProject::class, 'title'],
    'policy' => ['posts', CreatePolicy::class, EditPolicy::class, 'title'],
    'album' => ['albums', CreateAlbum::class, EditAlbum::class, 'title'],
]);

it('creates and deletes a record through its Admin form', function (string $table, string $createPage, string $editPage, string $titleField) {
    $this->actingAs(admin(), 'admin');
    $slug = 'admin-crud-'.bin2hex(random_bytes(4));

    Livewire\Livewire::test($createPage)->fillForm([$titleField => 'Nội dung thử Admin', 'slug' => $slug, 'is_visible' => true])
        ->call('create')->assertHasNoFormErrors();

    $id = DB::table($table)->where('slug', $slug)->value('id');
    expect($id)->not->toBeNull();
    Livewire\Livewire::test($editPage, ['record' => $id])->callAction('delete')->assertHasNoActionErrors();
    expect(DB::table($table)->where('id', $id)->exists())->toBeFalse();
})->with('created admin records');

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
