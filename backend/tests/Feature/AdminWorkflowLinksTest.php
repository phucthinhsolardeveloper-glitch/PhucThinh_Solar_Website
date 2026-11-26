<?php

use App\Domain\Admin\DataQuality;
use App\Domain\Admin\WorkQueue;
use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Pages\PageLayouts;
use App\Filament\Pages\PageLayoutsPage;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\PageItems\PageItemResource;
use App\Filament\Resources\PageItems\Pages\ManagePageItems;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PageSectionSources;
use App\Filament\Widgets\AttentionWidget;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PageItem;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function flowUser(array $permissions = []): User
{
    return User::query()->create(['name' => 'Luồng', 'username' => 'flow-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

/** The query string of an Admin link, as Livewire receives it. */
function flowQuery(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return $query;
}

it('never writes the old Filament query names, which the list pages ignore', function () {
    $offenders = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() === 'php' && preg_match('/[?&\'](tableFilters|tableSearch)(\[|=|\' =>)/', (string) file_get_contents($file->getPathname()))) {
            $offenders[] = $file->getFilename();
        }
    }
    expect($offenders)->toBe([]);
});

it('opens the filtered enquiry list from "Việc cần làm"', function () {
    $this->actingAs($me = flowUser(['leads']), 'admin');
    Lead::query()->update(['status' => 'done']);
    $new = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách mới', 'phone' => '0900111222', 'status' => 'new']);
    $other = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách đã gọi', 'phone' => '0900333444', 'status' => 'contacted']);
    foreach (range(1, 10) as $i) {
        Lead::query()->create(['kind' => 'contact', 'name' => "Khách $i", 'phone' => "09005550$i", 'status' => 'new']);
    }

    $section = collect(app(WorkQueue::class)->sections($me))->firstWhere('key', 'leads');
    $filtered = Livewire::withQueryParams(flowQuery($section['more']))->test(ManageLeads::class);
    expect($filtered->instance()->getFilteredTableQuery()->count())->toBe(11);
    $filtered->assertCanNotSeeTableRecords([$other]);

    $item = collect($section['items'])->firstWhere('label', $new->name.' · '.$new->phone);
    Livewire::withQueryParams(flowQuery($item['url']))->test(ManageLeads::class)
        ->assertSet('tableSearch', $new->phone)->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$other]);
});

it('links product problems to the matching filtered list and never opens another record as a product', function () {
    $this->actingAs($owner = flowUser(), 'admin');
    $slug = 'trung-'.bin2hex(random_bytes(3));
    $product = Product::query()->where('is_visible', true)->orderBy('id')->firstOrFail();
    $product->update(['slug' => $slug, 'regular_price' => 100000, 'sale_price' => 150000, 'quote_only' => false]);
    // An album on the same address: its id is not a product id.
    $albumId = DB::table('albums')->insertGetId(['id' => 987654, 'title' => 'Album trùng', 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
    DataQuality::forget();

    $health = app(CatalogHealth::class);
    expect($health->productIds('duplicate_slug'))->toContain($product->id)->not->toContain($albumId)
        ->and($health->productIds('sale_above_regular'))->toContain($product->id)
        ->and($health->productIds('errors'))->toContain($product->id)
        ->and($health->productIds('unknown'))->toBe([]);

    $wrong = ProductResource::getUrl('edit', ['record' => $albumId]);
    $queue = collect(app(WorkQueue::class)->sections($owner))->firstWhere('key', 'products');
    expect(array_column($queue['items'], 'url'))->not->toContain($wrong)
        ->and($queue['count'])->toBe(count($health->productIds('errors')));
    $report = collect(app(DataQuality::class)->report(true));
    expect($report->flatMap(fn ($c) => array_column($c['items'], 'url'))->all())->not->toContain($wrong);

    $check = $report->firstWhere('key', 'product.sale_above_regular');
    $list = Livewire::withQueryParams(flowQuery($check['list_url']))->test(ListProducts::class);
    expect($list->instance()->getFilteredTableQuery()->pluck('id')->sort()->values()->all())
        ->toBe(collect($health->productIds('sale_above_regular'))->sort()->values()->all());
    $list->assertActionVisible('quality');
});

it('names where every layout block gets its content', function () {
    foreach (PageLayouts::PAGES as $page => $config) {
        foreach (array_keys($config['sections']) as $type) {
            expect(PageSectionSources::note($page, $type))->not->toBe('', "$page.$type");
        }
    }
    // The energy rows feed the catalog cards, not the fixed 3D "energy" block.
    expect(PageSectionSources::itemSections('home')['home.energy_chain'])->toBe(['catalog'])
        ->and(PageSectionSources::links('home', 'energy'))->toBe([]);
});

it('links layout blocks to their editors only for people who may open them', function () {
    $this->actingAs(flowUser(), 'admin');
    $links = PageSectionSources::links('about', 'contact');
    expect($links)->toHaveCount(2)->and(array_filter(array_column($links, 'url')))->toHaveCount(2);
    Livewire::test(PageLayoutsPage::class, ['page' => 'home'])->assertSee('Cảnh minh họa 3D cố định')
        ->assertSee('Nội dung: Trang chủ · Quy trình làm việc')->assertActionVisible('seo');

    $this->actingAs(flowUser(['content']), 'admin');
    expect(array_column(PageSectionSources::links('about', 'contact'), 'url'))->toBe([null, null]);
    Livewire::test(PageLayoutsPage::class, ['page' => 'home'])->assertSee('tài khoản chưa có quyền')->assertActionHidden('seo');

    // The link of one block shows that section only, and a new row starts in it.
    $url = PageSectionSources::links('home', 'process')[0]['url'];
    $about = PageItem::query()->create(['section' => 'about.team', 'title' => 'Dòng khác', 'is_visible' => true]);
    $step = PageItem::query()->create(['section' => 'home.process', 'title' => 'Bước thử', 'is_visible' => true, 'sort_order' => 0]);
    Livewire::withQueryParams(flowQuery($url))->test(ManagePageItems::class)
        ->assertCanSeeTableRecords([$step])->assertCanNotSeeTableRecords([$about])
        ->assertActionVisible('layout')
        ->mountAction('create')->assertSchemaStateSet(['section' => 'home.process']);
});

it('offers the live block only while the published layout shows it', function () {
    $owner = flowUser();
    expect(PageSectionSources::liveUrl('home', 'process'))->toEndWith('/#home-process-title')
        ->and(PageSectionSources::liveUrl('home', 'energy'))->toBeNull();

    $layouts = app(PageLayouts::class);
    $draft = $layouts->draft('home', $owner);
    $sections = array_map(fn ($row) => ['visible' => $row['type'] !== 'process'] + $row, PageLayouts::defaults('home'));
    $layouts->save($draft->id, $sections, $owner);
    expect(PageSectionSources::liveUrl('home', 'process'))->not->toBeNull();
    $layouts->publish($draft->id, $owner);
    expect(PageSectionSources::liveUrl('home', 'process'))->toBeNull();
});

it('explains every editable section and reaches each one from the page directory', function () {
    $this->actingAs(flowUser(), 'admin');
    $reachable = collect(PageSectionSources::directory())->flatMap(fn ($p) => $p['blocks'])->flatMap(fn ($b) => $b['links'])
        ->map(fn ($link) => flowQuery((string) $link['url'])['filters']['section']['value'] ?? null)->filter()->unique();
    foreach (['dealer', 'solutions', 'news', 'projects', 'product', 'home', 'about'] as $prefix) {
        foreach (array_keys(PageItemResource::SECTIONS) as $section) {
            if (str_starts_with($section, $prefix.'.') && $section !== 'home.energy_chain') {
                expect(PageSectionSources::sectionHelp($section))->not->toBeNull($section)
                    ->and($reachable->all())->toContain($section);
            }
        }
    }
    expect(PageSectionSources::sectionHelp('home.energy_chain'))->not->toBeNull()
        ->and(PageSectionSources::sectionHelp('site.footer'))->toBeNull();
});

it('shows the rule and the other editors on a filtered section list, then leads back', function () {
    $this->actingAs(flowUser(), 'admin');
    $url = PageItemResource::getUrl('index', ['filters' => ['section' => ['value' => 'dealer.overview']]]);
    Livewire::withQueryParams(flowQuery($url))->test(ManagePageItems::class)
        ->assertSee('Tiêu đề và mô tả lấy từ dòng Tổng quan')->assertSee('Ảnh: Đại lý · Hình ảnh')->assertSee('SEO của trang')
        ->assertActionHidden('layout')->assertActionVisible('byPage')
        ->mountAction('byPage')->assertActionMounted('byPage');
    // The slide-over body is not part of the component HTML in tests, so the view is rendered on its own.
    expect(view('filament.pages.page-directory', ['pages' => PageSectionSources::directory()])->render())
        ->toContain('Trang sản phẩm')->toContain('Nội dung: Dự án · Lợi ích')->not->toContain('target="_blank"');

    $banners = collect(PageSectionSources::sectionHelp('dealer.overview')['links'])->firstWhere('label', 'Ảnh: Đại lý · Hình ảnh')['url'];
    Livewire::withQueryParams(flowQuery($banners))->test(ManageBanners::class)->assertActionVisible('dealerText')->assertActionHidden('layout');

    // A content editor sees which screens exist but gets no link into those without permission.
    $this->actingAs(flowUser(['content']), 'admin');
    expect(array_column(PageSectionSources::sectionHelp('dealer.overview')['links'], 'url'))->toBe([null, null]);
    Livewire::test(ManagePageItems::class)->assertActionVisible('byPage')->assertActionVisible('layout')->assertDontSee('Tiêu đề và mô tả lấy từ');
});

it('asks for the solution button text in the field the page reads', function () {
    $this->actingAs(flowUser(), 'admin');
    $row = PageItem::query()->create(['section' => 'solutions.cta', 'title' => 'Mời', 'subtitle' => 'Gọi tư vấn', 'extra' => 'chữ cũ', 'is_visible' => true, 'sort_order' => 0]);
    Livewire::test(ManagePageItems::class)->mountTableAction('edit', $row)
        ->assertFormFieldExists('subtitle', checkFieldUsing: fn (TextInput $field) => $field->getLabel() === 'Chữ trên nút')
        ->assertFormFieldIsHidden('extra')
        ->callMountedTableAction()->assertHasNoTableActionErrors();
    // The unused old value is left as it was.
    expect($row->fresh()->only(['subtitle', 'extra']))->toBe(['subtitle' => 'Gọi tư vấn', 'extra' => 'chữ cũ']);
});

it('counts the same new orders in the work queue and on the dashboard', function () {
    $this->actingAs($owner = flowUser(), 'admin');
    DB::table('orders')->where('status', 'new')->update(['status' => 'cancelled']);
    foreach (['cod' => 'unpaid', 'vnpay' => 'unpaid'] as $method => $payment) {
        flowOrder($method, $payment);
    }
    $paid = flowOrder('vnpay', 'paid');

    $section = collect(app(WorkQueue::class)->sections($owner))->firstWhere('key', 'orders');
    $dashboard = collect((new AttentionWidget)->items())->first(fn ($item) => str_contains($item['text'], 'đơn hàng mới'));
    expect($section['count'])->toBe(2)->and($dashboard['text'])->toStartWith('2 đơn hàng mới')
        ->and(collect($section['items'])->pluck('label')->implode('|'))->toContain($paid->code);
});

/** A new order paid one way or another; only the columns the counting rule reads matter. */
function flowOrder(string $method, string $payment): Order
{
    $id = DB::table('orders')->insertGetId(['code' => 'FL'.strtoupper(bin2hex(random_bytes(3))), 'customer_name' => 'Khách thử', 'phone' => '0900000002', 'address' => '1 Đường Thử',
        'payment_code' => $method, 'payment_status' => $payment, 'subtotal' => 1000, 'shipping_fee' => 0, 'total' => 1000, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);

    return Order::query()->findOrFail($id);
}
