<?php

use App\Domain\Admin\DataQuality;
use App\Domain\Admin\WorkQueue;
use App\Filament\Pages\DataQualityPage;
use App\Filament\Pages\EditorialCalendarPage;
use App\Filament\Pages\WorkQueuePage;
use App\Models\Lead;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function workUser(array $permissions, string $role = User::ROLE_EDITOR): User
{
    return User::query()->create(['name' => 'Nhân viên '.bin2hex(random_bytes(2)), 'username' => 'wq-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $role, 'permissions' => $permissions, 'is_active' => true]);
}

it('lists waiting work by permission, and "mine" keeps my enquiries and unassigned ones', function () {
    $me = workUser(['leads']);
    $colleague = workUser(['leads']);
    Lead::query()->update(['status' => 'done']);
    $mine = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách của tôi', 'phone' => '0900000011', 'status' => 'new', 'assigned_to' => $me->id]);
    $theirs = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách của đồng nghiệp', 'phone' => '0900000012', 'status' => 'new', 'assigned_to' => $colleague->id]);
    Lead::query()->create(['kind' => 'contact', 'name' => 'Khách quá hạn', 'phone' => '0900000013', 'status' => 'contacted', 'follow_up_at' => now()->subDay()]);

    $all = collect(app(WorkQueue::class)->sections($me));
    expect($all->pluck('key')->all())->toBe(['overdue', 'leads'])
        ->and($all->firstWhere('key', 'leads')['count'])->toBe(2);
    $only = collect(app(WorkQueue::class)->sections($me, true));
    expect(collect($only->firstWhere('key', 'leads')['items'])->pluck('label')->implode(' | '))->toContain($mine->name)->not->toContain($theirs->name);

    $this->actingAs($me, 'admin');
    Livewire::test(WorkQueuePage::class)->assertSee('Liên hệ quá hạn hẹn gọi lại')->assertSee('Khách của tôi')->assertDontSee('Đơn hàng mới');
    expect(WorkQueuePage::getNavigationBadge())->toBe('3');
});

it('shows the month of posts and drafts on the editorial calendar', function () {
    $this->actingAs(workUser(['content']), 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post->update(['published_at' => now()->startOfMonth()->addDays(9)->setTime(8, 30)]);

    Livewire::test(EditorialCalendarPage::class)->assertSee($post->title)->assertSee('Tháng '.now()->format('m/Y'))
        ->call('shift', 1)->assertSet('month', now()->addMonthNoOverflow()->format('Y-m'));
    expect(EditorialCalendarPage::canAccess())->toBeTrue();
    $this->actingAs(workUser(['orders']), 'admin');
    expect(EditorialCalendarPage::canAccess())->toBeFalse();
});

it('reports data quality with fix links and records the daily trend', function () {
    $this->actingAs(workUser([], User::ROLE_OWNER), 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $post->update(['summary' => '']);
    $post->seo()->updateOrCreate([], ['description' => '']);

    $report = collect(app(DataQuality::class)->report(true));
    $summary = $report->firstWhere('label', 'Chưa có tóm tắt (dùng cho danh sách bài và chia sẻ)');
    expect($summary)->not->toBeNull()->and(collect($summary['items'])->pluck('name')->all())->toContain($post->title);

    DB::table('data_quality_daily')->insert(['day' => now()->subDays(8)->toDateString(), 'check_key' => $summary['key'], 'count' => $summary['count'] + 3]);
    expect(app(DataQuality::class)->record())->toBeGreaterThan(1)
        ->and(app(DataQuality::class)->countsAgo(7)[$summary['key']])->toBe($summary['count'] + 3);

    Livewire::test(DataQualityPage::class)->assertSee('Chưa có tóm tắt')->assertSee('7 ngày trước')->callAction('refresh');
});
