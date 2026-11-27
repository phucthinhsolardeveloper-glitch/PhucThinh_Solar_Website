<?php

use App\Domain\Admin\WorkQueue;
use App\Domain\Content\EditorialBoard;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\EditorialPlanItem;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->owner = User::query()->create(['name' => 'Chủ', 'username' => 'qt-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($this->owner, 'admin');
});

it('opens every list tab', function (string $page, array $tabs) {
    foreach ($tabs as $tab) {
        Livewire::withQueryParams(['tab' => $tab])->test($page)->assertOk()->assertSet('activeTab', $tab);
    }
})->with([
    [ListOrders::class, ['all', 'moi', 'bao-ck', 'da-xac-nhan', 'dang-giao', 'hoan-tat', 'da-huy']],
    [ManageLeads::class, ['all', 'moi', 'qua-han', 'da-lien-he', 'da-xong', 'rac']],
    [ManageReviews::class, ['all', 'cho-duyet', 'dang-hien', 'da-an']],
    [ListArticles::class, ['all', 'dang-hien', 'hen-gio', 'dang-an']],
]);

it('narrows the enquiry list to the tab', function () {
    $new = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách mới tab', 'phone' => '0900000091', 'status' => 'new']);
    $done = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách xong tab', 'phone' => '0900000092', 'status' => 'done']);
    Livewire::withQueryParams(['tab' => 'moi'])->test(ManageLeads::class)->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$done]);
});

it('lists written articles waiting for approval in the work queue', function () {
    $plan = EditorialPlanItem::query()->create(['plan_key' => 'QT-1', 'scheduled_for' => now()->addDay(), 'title' => 'Bài chờ duyệt tab', 'suggested_slug' => 'bai-cho-duyet-tab', 'category_slug' => 'kien-thuc-san-pham', 'stream' => 'x']);
    app(EditorialBoard::class)->createDraft($plan, ['body' => '<p>x</p>']);
    $section = collect(app(WorkQueue::class)->sections($this->owner))->firstWhere('key', 'waiting_articles');
    expect(collect($section['items'])->pluck('label'))->toContain('Bài chờ duyệt tab')->and($section['more'])->toContain('state=waiting');
});
