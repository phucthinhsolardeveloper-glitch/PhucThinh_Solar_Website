<?php

use App\Filament\Pages\EditorialCalendarPage;
use App\Filament\Resources\EditorialPlanItems\EditorialPlanItemResource;
use App\Filament\Resources\EditorialPlanItems\Pages\EditEditorialPlanItem;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('imports 180 distinct scheduled slots for 60 days with sources and branded images', function () {
    Storage::fake('upload_news');
    expect(Artisan::call('pts:editorial-batch', ['--apply' => true]))->toBe(0);
    expect(Artisan::call('pts:editorial-calendar', ['--apply' => true]))->toBe(0);

    expect(EditorialPlanItem::query()->count())->toBe(180)
        ->and(EditorialPlanItem::query()->distinct()->count('scheduled_for'))->toBe(180)
        ->and(EditorialPlanItem::query()->where('stage', 'review')->count())->toBe(24)
        ->and(EditorialPlanItem::query()->where('stage', 'idea')->count())->toBe(156)
        ->and(EditorialPlanItem::query()->min('scheduled_for'))->toBe('2026-11-13 08:00:00')
        ->and(EditorialPlanItem::query()->max('scheduled_for'))->toBe('2027-01-11 19:30:00');

    $first = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-001')->sole();
    $fourth = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-004')->sole();
    $last = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-180')->sole();
    $newDraft = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-021')->sole();
    $tenth = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-010')->sole();
    expect($tenth->stage)->toBe('review')
        ->and($tenth->post)->toBeInstanceOf(Post::class)
        ->and($tenth->post->is_visible)->toBeFalse()
        ->and($tenth->post->body)->toContain('/upload/news/pts-explainer-grid-outage.webp')
        ->and(RichText::sanitize((string) $tenth->post->body))->toContain('/upload/news/pts-explainer-grid-outage.webp')
        ->and($tenth->post->body)->not->toContain('<h1>')
        ->and(Storage::disk('upload_news')->exists('pts-explainer-grid-outage.webp'))->toBeTrue();
    expect($newDraft->stage)->toBe('review')
        ->and($newDraft->post)->toBeInstanceOf(Post::class)
        ->and($newDraft->post->is_visible)->toBeFalse()
        ->and($newDraft->post->body)->not->toContain('Gợi ý infographic')
        ->and($newDraft->image_status)->toBe('approved')
        ->and($newDraft->cover_file)->toBe('pts-photo-021.webp')
        ->and($newDraft->post->image)->toBe('pts-photo-021.webp')
        ->and(Storage::disk('upload_news')->exists('pts-photo-021.webp'))->toBeTrue();
    foreach (['PTS-KT-022', 'PTS-KT-023', 'PTS-KT-024'] as $key) {
        $prepared = EditorialPlanItem::query()->where('plan_key', $key)->sole();
        expect($prepared->stage)->toBe('review')
            ->and($prepared->post)->toBeInstanceOf(Post::class)
            ->and($prepared->post->is_visible)->toBeFalse()
            ->and(mb_strlen(strip_tags((string) $prepared->post->body)))->toBeGreaterThan(1200);
    }
    expect($first->post)->toBeInstanceOf(Post::class)
        ->and($first->post->is_visible)->toBeFalse()
        ->and($first->post->published_at)->toBe('2026-11-13 08:00:00')
        ->and($fourth->post)->toBeInstanceOf(Post::class)
        ->and($fourth->post->is_visible)->toBeFalse()
        ->and($fourth->post->body)->toContain('<h2>')
        ->and($fourth->cover_file)->toBe('pts-photo-004.webp')
        ->and(Storage::disk('upload_news')->exists($fourth->cover_file))->toBeTrue()
        ->and($last->post_id)->toBeNull()
        ->and($last->primary_source_url)->not->toBeEmpty();

    $this->getJson('/api/v1/news-detail?slug='.$fourth->post->slug)->assertNotFound();
    $this->get('/upload/news/pts-photo-004.webp')->assertOk();
    expect(Artisan::call('pts:editorial-calendar', ['--apply' => true]))->toBe(0)
        ->and(EditorialPlanItem::query()->count())->toBe(180);
});

it('preserves editorial changes on repeated imports and displays the planning calendar in admin', function () {
    Storage::fake('upload_news');
    Artisan::call('pts:editorial-batch', ['--apply' => true]);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);

    // PTS-KT-001..024 are written/linked articles; use the first idea-only plan slot.
    $item = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-025')->sole();
    $item->update(['title' => 'Tên đã sửa thủ công', 'stage' => 'research', 'editor_notes' => 'Đang kiểm chứng nguồn']);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);
    expect($item->fresh()->title)->toBe('Tên đã sửa thủ công')
        ->and($item->fresh()->stage)->toBe('research')
        ->and($item->fresh()->editor_notes)->toBe('Đang kiểm chứng nguồn');

    $user = User::query()->create([
        'name' => 'Biên tập kế hoạch', 'username' => 'editorial-calendar-'.bin2hex(random_bytes(3)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR,
        'permissions' => ['content'], 'is_active' => true,
    ]);
    $this->actingAs($user, 'admin');
    expect(EditorialPlanItemResource::canViewAny())->toBeTrue();
    Livewire::test(EditorialCalendarPage::class)->set('month', '2026-11')
        ->assertSee('Tên đã sửa thủ công')->assertSee('Quản lý kế hoạch 180 bài');
    $this->actingAs(User::query()->create([
        'name' => 'Nhân viên bán hàng', 'username' => 'sales-calendar-'.bin2hex(random_bytes(3)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR,
        'permissions' => ['orders'], 'is_active' => true,
    ]), 'admin');
    expect(EditorialPlanItemResource::canViewAny())->toBeFalse();
});

it('reschedules only a hidden CMS article when an editor changes its planning slot', function () {
    Storage::fake('upload_news');
    Artisan::call('pts:editorial-batch', ['--apply' => true]);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);

    $item = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-004')->sole();
    $post = $item->post;
    expect($post)->not->toBeNull()->and($post->is_visible)->toBeFalse();

    $user = User::query()->create([
        'name' => 'Editor reschedule', 'username' => 'rs-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR,
        'permissions' => ['content'], 'is_active' => true,
    ]);
    $this->actingAs($user, 'admin');

    Livewire::test(EditEditorialPlanItem::class,
        ['record' => $item->getKey()])
        ->fillForm(['scheduled_for' => '2026-11-16 11:00:00'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh()->scheduled_for->format('Y-m-d H:i'))->toBe('2026-11-16 11:00')
        ->and((string) $post->fresh()->published_at)->toBe('2026-11-16 11:00:00')
        ->and($post->fresh()->is_visible)->toBeFalse();
});

it('keeps an editor-selected cover instead of restoring the curated default', function () {
    Storage::fake('upload_news');
    Artisan::call('pts:editorial-batch', ['--apply' => true]);
    Artisan::call('pts:editorial-calendar', ['--apply' => true]);

    $plan = EditorialPlanItem::query()->where('plan_key', 'PTS-KT-005')->sole();
    $post = $plan->post;
    expect($post)->not->toBeNull()->and($post->is_visible)->toBeFalse();

    $plan->update(['cover_file' => 'custom-editorial-cover.webp', 'image_status' => 'approved']);
    $post->update(['image' => 'custom-editorial-cover.webp']);
    expect(Artisan::call('pts:editorial-calendar', ['--apply' => true]))->toBe(0)
        ->and($plan->fresh()->cover_file)->toBe('custom-editorial-cover.webp')
        ->and($post->fresh()->image)->toBe('custom-editorial-cover.webp');
});
