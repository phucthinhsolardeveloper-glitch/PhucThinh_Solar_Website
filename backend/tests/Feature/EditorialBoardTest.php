<?php

use App\Domain\Content\EditorialBoard;
use App\Filament\Pages\EditorialCalendarPage;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-27 09:00:00');
    EditorialPlanItem::query()->delete();
    $this->owner = User::query()->create(['name' => 'Chủ', 'username' => 'eb-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($this->owner, 'admin');
});

function topic(string $key, string $at): EditorialPlanItem
{
    return EditorialPlanItem::query()->create(['plan_key' => $key, 'scheduled_for' => $at, 'title' => 'Đề tài thử '.$key,
        'suggested_slug' => 'de-tai-thu-'.strtolower($key), 'category_slug' => 'kien-thuc-san-pham', 'stream' => 'x', 'cover_file' => 'pts-plan-022.webp']);
}

function boardItems(): array
{
    return app(EditorialBoard::class)->items(Carbon::parse('2026-11-01'), Carbon::parse('2026-12-31'));
}

it('shows a topic with an article once, even when the post was published at another time', function () {
    $plan = topic('T-1', '2026-11-19 12:00:00');
    $post = app(EditorialBoard::class)->createDraft($plan);
    $post->update(['published_at' => '2026-11-19 08:19:00', 'is_visible' => true]);

    $mine = array_values(array_filter(boardItems(), fn (array $i) => str_contains($i['title'], 'T-1')));
    expect($mine)->toHaveCount(1)->and($mine[0]['type'])->toBe('post')->and($mine[0]['state'])->toBe('live');
});

it('creates one hidden draft per topic and keeps unwritten topics late or upcoming', function () {
    $late = topic('T-2', '2026-11-20 08:00:00');
    $soon = topic('T-3', '2026-11-28 08:00:00');
    expect(collect(boardItems())->where('type', 'plan')->pluck('state', 'id')->all())->toMatchArray([$late->id => 'late', $soon->id => 'todo']);

    $first = app(EditorialBoard::class)->createDraft($late);
    expect(app(EditorialBoard::class)->createDraft($late)->id)->toBe($first->id)
        ->and($first->is_visible)->toBeFalse()->and(EditorialBoard::stateOf($first))->toBe('waiting');
});

it('approves from the calendar: schedule, publish now, hide', function () {
    $plan = topic('T-4', '2026-11-20 08:00:00');
    $post = app(EditorialBoard::class)->createDraft($plan, ['body' => '<p>'.str_repeat('Nội dung bài thử. ', 40).'</p>', 'summary' => 'Tóm tắt']);

    $page = Livewire::test(EditorialCalendarPage::class)->call('show', 'post', $post->id)->assertSee('Nội dung bài thử')
        ->set('when', '2026-11-30T12:00')->call('schedule')->assertHasNoErrors();
    expect(EditorialBoard::stateOf($post->fresh()))->toBe('scheduled')->and($plan->fresh()->stage)->toBe('scheduled');

    $page->call('show', 'post', $post->id)->call('publishNow');
    expect(EditorialBoard::stateOf($post->fresh()))->toBe('live')->and((string) $post->fresh()->published_at)->toStartWith('2026-11-27 09:00');

    $page->call('show', 'post', $post->id)->call('hidePost');
    expect($post->fresh()->is_visible)->toBeFalse();
});

it('refuses to publish an empty article and a time in the past', function () {
    $post = app(EditorialBoard::class)->createDraft(topic('T-5', '2026-11-28 08:00:00'));
    Livewire::test(EditorialCalendarPage::class)->call('show', 'post', $post->id)->call('publishNow')->assertHasErrors('when');
    Livewire::test(EditorialCalendarPage::class)->call('show', 'post', $post->id)->set('when', '2026-11-01T08:00')->call('schedule')->assertHasErrors('when');
    expect($post->fresh()->is_visible)->toBeFalse();
});

it('moves topics and drafts to a future day but never a live post', function () {
    $plan = topic('T-6', '2026-11-28 12:00:00');
    $board = app(EditorialBoard::class);
    $board->move('plan', $plan->id, Carbon::parse('2026-12-02'), $this->owner);
    expect($plan->fresh()->scheduled_for->format('Y-m-d H:i'))->toBe('2026-12-02 12:00');

    $live = Post::query()->create(['kind' => 'article', 'title' => 'Bài đang hiện thử', 'slug' => 'bai-dang-hien-thu', 'body' => 'x', 'is_visible' => true, 'published_at' => '2026-11-10 08:00:00']);
    Livewire::test(EditorialCalendarPage::class)->call('move', 'post', $live->id, '2026-12-03');
    Livewire::test(EditorialCalendarPage::class)->call('move', 'plan', $plan->id, '2026-11-01');
    expect((string) $live->fresh()->published_at)->toStartWith('2026-11-10')->and($plan->fresh()->scheduled_for->format('m-d'))->toBe('12-02');
});

it('requeues unwritten topics from a day, skipping hours already past', function () {
    $a = topic('T-7', '2026-11-20 08:00:00');
    $b = topic('T-8', '2026-11-20 12:00:00');
    $c = topic('T-9', '2026-11-21 08:00:00');
    expect(app(EditorialBoard::class)->requeue(Carbon::parse('2026-11-27'), 3))->toBe(3)
        ->and(collect([$a, $b, $c])->map(fn ($p) => $p->fresh()->scheduled_for->format('d H:i'))->all())->toBe(['27 12:00', '27 19:30', '28 08:00']);
});

it('imports written articles as hidden drafts once, without links to unpublished later articles', function () {
    $earlier = topic('PTS-KT-022', '2026-11-28 08:00:00');
    $later = topic('PTS-KT-023', '2026-11-28 12:00:00');
    $this->artisan('pts:editorial-drafts', ['batch' => '2026-11-27'])->assertSuccessful();
    expect($earlier->fresh()->post_id)->toBeNull();

    $this->artisan('pts:editorial-drafts', ['batch' => '2026-11-27', '--apply' => true])->assertSuccessful();
    $first = Post::query()->findOrFail($earlier->fresh()->post_id);
    $second = Post::query()->findOrFail($later->fresh()->post_id);
    expect($first->is_visible)->toBeFalse()->and($first->body)->not->toContain('[[')->not->toContain($second->slug)
        ->and($second->body)->not->toContain('[[')->toContain('/'.$first->slug.'/')
        ->and($first->summary)->not->toBe('');

    $this->artisan('pts:editorial-drafts', ['batch' => '2026-11-27', '--apply' => true])->assertSuccessful();
    expect($earlier->fresh()->post_id)->toBe($first->id);
});
