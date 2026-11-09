<?php

use App\Domain\Pages\PageLayouts;
use App\Filament\Pages\PageLayoutsPage;
use App\Models\User;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function layoutUser(array $permissions = []): User
{
    return User::query()->create(['name' => 'Bố cục', 'username' => 'layout-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

it('keeps the built order until a layout is published, previews drafts and rolls back', function () {
    $owner = layoutUser();
    $this->actingAs($owner, 'admin');
    $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('layout', array_map(fn ($r) => ['type' => $r['type'], 'variant' => $r['variant']], PageLayouts::defaults('home')));

    $layouts = app(PageLayouts::class);
    $draft = $layouts->draft('home', $owner);
    $sections = PageLayouts::defaults('home');
    [$sections[1], $sections[4]] = [$sections[4], $sections[1]]; // solutions before catalog
    $sections[2]['visible'] = false; // hide the energy illustration
    $sections[0]['visible'] = false; // hero is required: stays shown
    $sections[] = ['type' => 'unknown', 'visible' => true];
    $layouts->save($draft->id, $sections, $owner);

    $live = collect($this->getJson('/api/v1/home')->json('layout'))->pluck('type')->all();
    expect($live)->toBe(array_keys(PageLayouts::PAGES['home']['sections']));

    $url = $layouts->previewUrl($draft);
    expect(parse_url($url, PHP_URL_PATH))->toBe('/xem-truoc/trang-chu/'.$draft->id);
    $api = '/api/v1/page-layout-preview/'.$draft->id.'?'.parse_url($url, PHP_URL_QUERY);
    $preview = collect($this->getJson($api)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->json('layout'))->pluck('type')->all();
    expect($preview)->toBe(['hero', 'solutions', 'campaigns', 'catalog', 'process', 'dealer', 'about', 'contact']);
    $this->getJson($api.'&x=1')->assertForbidden();

    $layouts->publish($draft->id, $owner);
    expect(collect($this->getJson('/api/v1/home')->json('layout'))->pluck('type')->all())->toBe($preview);

    $second = $layouts->draft('home', $owner);
    $layouts->save($second->id, PageLayouts::defaults('home'), $owner);
    $layouts->publish($second->id, $owner);
    expect($this->getJson('/api/v1/home')->json('layout'))->toHaveCount(9);

    $restored = $layouts->restoreAsDraft($draft->id, $owner);
    expect($this->getJson('/api/v1/home')->json('layout'))->toHaveCount(9);
    $layouts->publish($restored->id, $owner);
    expect(collect($this->getJson('/api/v1/home')->json('layout'))->pluck('type')->all())->toBe($preview);
    $this->getJson('/api/v1/about')->assertOk()->assertJsonCount(8, 'layout');
});

it('lets content editors draft but only publishers publish, from the Admin page', function () {
    $editor = layoutUser(['content']);
    $this->actingAs($editor, 'admin');
    $page = Livewire::test(PageLayoutsPage::class, ['page' => 'about']);
    $rows = array_values($page->get('data.sections'));
    $rows[6]['visible'] = false; // certificates
    $page->set('data.sections', $rows)->call('saveDraft')->assertHasNoErrors();
    expect(DB::table('page_layouts')->where('page', 'about')->where('state', 'draft')->count())->toBe(1);
    $page->assertActionHidden('publish')->assertActionVisible('preview');
    expect(fn () => app(PageLayouts::class)->publish((int) DB::table('page_layouts')->where('state', 'draft')->value('id'), $editor))
        ->toThrow(HttpException::class);

    $this->actingAs(layoutUser(), 'admin');
    Livewire::test(PageLayoutsPage::class, ['page' => 'about'])->callAction('publish')->assertHasNoErrors();
    expect(collect($this->getJson('/api/v1/about')->json('layout'))->pluck('type')->all())->not->toContain('certificates');

    $this->actingAs(layoutUser(['catalog']), 'admin');
    $this->get(PageLayoutsPage::getUrl())->assertForbidden();
});
