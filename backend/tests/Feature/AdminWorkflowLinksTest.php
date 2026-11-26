<?php

use App\Domain\Admin\DataQuality;
use App\Domain\Admin\WorkQueue;
use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Pages\PageLayouts;
use App\Filament\Pages\PageLayoutsPage;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\PageItems\Pages\ManagePageItems;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PageSectionSources;
use App\Models\Lead;
use App\Models\PageItem;
use App\Models\Product;
use App\Models\User;
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
