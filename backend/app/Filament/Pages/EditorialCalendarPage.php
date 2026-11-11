<?php

namespace App\Filament\Pages;

use App\Domain\Routing\PublicStatus;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use UnitEnum;

/** "Lịch biên tập": posts by publish day for one month (published, scheduled, hidden), plus drafts in review. */
class EditorialCalendarPage extends Page
{
    protected static ?string $title = 'Lịch biên tập';

    protected static ?string $slug = 'lich-bien-tap';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected string $view = 'filament.pages.editorial-calendar';

    #[Url]
    public string $month = '';

    public const KINDS = ['article' => 'Bài viết', 'project' => 'Dự án', 'solution' => 'Giải pháp', 'policy' => 'Chính sách'];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('content');
    }

    public function start(): Carbon
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) ? Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay() : now()->startOfMonth();
    }

    public function shift(int $months): void
    {
        $this->month = $this->start()->addMonthsNoOverflow($months)->format('Y-m');
    }

    /** @return list<list<array{date: Carbon, inMonth: bool, posts: list<array{title: string, kind: string, time: string, status: array<string, string>, url: string}>}>> weeks, Monday first */
    public function weeks(): array
    {
        $start = $this->start();
        $from = $start->copy()->startOfWeek(Carbon::MONDAY);
        $to = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $byDay = [];
        foreach (Post::query()->whereBetween('published_at', [$from, $to])->orderBy('published_at')->get() as $post) {
            $resource = PostRevisionResource::ownerResource((string) $post->kind);
            $byDay[Carbon::parse($post->published_at)->toDateString()][] = [
                'title' => (string) $post->title, 'kind' => self::KINDS[$post->kind] ?? (string) $post->kind,
                'time' => Carbon::parse($post->published_at)->format('H:i'), 'status' => PublicStatus::of($post),
                'url' => $resource::getUrl('edit', ['record' => $post]),
            ];
        }
        $weeks = [];
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $weeks[intdiv((int) $from->diffInDays($day), 7)][] = ['date' => $day->copy(), 'inMonth' => $day->month === $start->month, 'posts' => $byDay[$day->toDateString()] ?? []];
        }

        return $weeks;
    }

    /** @return list<array{title: string, state: string, note: string, url: string}> drafts being written or reviewed, not dated yet */
    public function drafts(): array
    {
        return PostRevision::query()->with('post')->where('state', 'draft')->latest('updated_at')->limit(30)->get()
            ->map(fn (PostRevision $r) => [
                'title' => (string) $r->post?->title,
                'state' => match ($r->review_state) {
                    'waiting' => 'Chờ duyệt', 'returned' => 'Bị trả lại', default => 'Đang viết'
                },
                'note' => (string) $r->review_note, 'url' => PostRevisionResource::getUrl('edit', ['record' => $r]),
            ])->all();
    }
}
