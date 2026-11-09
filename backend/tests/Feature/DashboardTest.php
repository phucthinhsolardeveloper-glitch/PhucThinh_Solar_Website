<?php

use App\Domain\Analytics\PageViews;
use App\Filament\Widgets\AttentionWidget;
use App\Filament\Widgets\LeadsChartWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\TopPagesWidget;
use App\Filament\Widgets\TopProductsWidget;
use App\Filament\Widgets\TrafficChartWidget;
use App\Filament\Widgets\TrafficSourcesWidget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

it('counts public pages without keeping the address and leaves bots and admin out', function () {
    $hit = fn (array $body, string $agent = BROWSER) => $this->call('POST', '/api/v1/track', [], [], [], ['HTTP_USER_AGENT' => $agent, 'CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '203.0.113.9'], json_encode($body));

    $hit(['path' => '/den-nang-luong/', 'referrer' => 'https://www.google.com/search?q=den', 'campaign' => ''])->assertNoContent();
    $hit(['path' => '/lien-he?x=1', 'entry' => false, 'referrer' => ''])->assertNoContent();
    $hit(['path' => '/', 'referrer' => 'https://l.facebook.com/x', 'campaign' => 'fbclid'])->assertNoContent();
    $hit(['path' => '/admin/orders', 'referrer' => '']);
    $hit(['path' => '/'], 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    $hit(['path' => '/'], '');

    $rows = DB::table('page_views')->orderBy('id')->get();
    expect($rows->pluck('path')->all())->toBe(['/den-nang-luong', '/lien-he', '/'])
        ->and($rows->pluck('source')->all())->toBe(['google', 'internal', 'campaign'])
        ->and($rows->pluck('device')->unique()->all())->toBe(['mobile'])
        ->and($rows->pluck('visitor')->unique())->toHaveCount(1)
        ->and(json_encode($rows))->not->toContain('203.0.113.9');

    DB::table('page_views')->insert(['day' => now()->subDays(PageViews::KEEP_DAYS + 1)->toDateString(), 'path' => '/cu', 'visitor' => str_repeat('a', 16), 'source' => 'direct', 'device' => 'desktop']);
    expect(PageViews::prune())->toBe(1);
});

it('shows charts and only the waiting work on the dashboard, by permission', function () {
    DB::table('page_views')->insert(['day' => now()->toDateString(), 'path' => '/san-pham-thu', 'visitor' => str_repeat('b', 16), 'source' => 'zalo', 'device' => 'desktop']);
    DB::table('leads')->insert(['kind' => 'contact', 'name' => 'Khách', 'phone' => '0900000002', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'dash-owner', 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin')->get('/admin')->assertOk();
    Livewire::test(AttentionWidget::class)->assertSee(DB::table('leads')->where('status', 'new')->count().' liên hệ / yêu cầu tư vấn chưa gọi lại');
    Livewire::test(TopPagesWidget::class)->assertSee('/san-pham-thu');
    Livewire::test(TrafficSourcesWidget::class)->assertSee('Khách đến từ đâu');
    foreach ([TrafficChartWidget::class, SalesChartWidget::class, LeadsChartWidget::class, TopProductsWidget::class] as $widget) {
        Livewire::test($widget)->assertOk();
    }
    expect(SalesChartWidget::canView())->toBeTrue();

    $writer = User::query()->create(['name' => 'Biên tập', 'username' => 'dash-writer', 'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['posts'], 'is_active' => true]);
    $this->actingAs($writer, 'admin');
    expect(SalesChartWidget::canView())->toBeFalse()->and(LeadsChartWidget::canView())->toBeFalse();
    Livewire::test(AttentionWidget::class)->assertDontSee('chưa gọi lại');
});
