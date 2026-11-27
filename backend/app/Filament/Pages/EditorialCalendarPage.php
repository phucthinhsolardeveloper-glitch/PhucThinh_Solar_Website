<?php

namespace App\Filament\Pages;

use App\Domain\Content\EditorialBoard;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\EditorialPlanItems\EditorialPlanItemResource;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Filament\Support\AdminActor;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use UnitEnum;

/** "Lịch bài viết": the one screen for planning, reading, approving, scheduling and moving articles. */
class EditorialCalendarPage extends Page
{
    protected static ?string $title = 'Lịch bài viết';

    protected static ?string $slug = 'lich-bien-tap';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected string $view = 'filament.pages.editorial-calendar';

    #[Url]
    public string $month = '';

    /** Show one state only; empty shows everything. */
    #[Url]
    public string $state = '';

    /** The item open in the side panel, as "post:12" or "plan:34". */
    public string $open = '';

    /** Value of the "hẹn giờ" field in the side panel. */
    public string $when = '';

    public const KINDS = ['article' => 'Bài viết', 'project' => 'Dự án', 'solution' => 'Giải pháp', 'policy' => 'Chính sách', 'plan' => 'Đề tài'];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('content');
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = app(EditorialBoard::class)->totals()[EditorialBoard::WAITING];

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Bài đã viết, chờ duyệt';
    }

    public function start(): Carbon
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) ? Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay() : now()->startOfMonth();
    }

    public function shift(int $months): void
    {
        $this->month = $this->start()->addMonthsNoOverflow($months)->format('Y-m');
    }

    public function today(): void
    {
        $this->month = '';
    }

    public function filter(string $state): void
    {
        $this->state = $this->state === $state || ! isset(EditorialBoard::STATES[$state]) ? '' : $state;
    }

    /** @return list<list<array{date: Carbon, inMonth: bool, items: list<array<string, mixed>>}>> weeks, Monday first */
    public function weeks(): array
    {
        $start = $this->start();
        $from = $start->copy()->startOfWeek(Carbon::MONDAY);
        $to = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $byDay = [];
        foreach (app(EditorialBoard::class)->items($from, $to) as $item) {
            if ($this->state === '' || $item['state'] === $this->state) {
                $byDay[$item['at']->toDateString()][] = $item;
            }
        }
        $weeks = [];
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $weeks[intdiv((int) $from->diffInDays($day), 7)][] = ['date' => $day->copy(), 'inMonth' => $day->month === $start->month, 'items' => $byDay[$day->toDateString()] ?? []];
        }

        return $weeks;
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        return app(EditorialBoard::class)->totals();
    }

    public function show(string $type, int $id): void
    {
        $this->resetValidation();
        $this->open = ($type === 'plan' ? 'plan:' : 'post:').$id;
        $record = $this->selected();
        $at = $record instanceof Post ? ($record->published_at ? Carbon::parse($record->published_at) : null)
            : ($record instanceof EditorialPlanItem ? Carbon::parse($record->scheduled_for) : null);
        $this->when = ($at && $at->isFuture() ? $at : now()->addDay()->setTime(8, 0))->format('Y-m-d\TH:i');
    }

    public function close(): void
    {
        $this->open = '';
    }

    public function selected(): Post|EditorialPlanItem|null
    {
        [$type, $id] = array_pad(explode(':', $this->open, 2), 2, '');

        return match ($type) {
            'post' => Post::query()->find((int) $id),
            'plan' => EditorialPlanItem::query()->find((int) $id),
            default => null,
        };
    }

    public function publishNow(): void
    {
        $this->onPost(fn (Post $post, EditorialBoard $board) => $board->publish($post, null, AdminActor::current()), 'Bài đã lên website');
    }

    public function schedule(): void
    {
        $this->validate(['when' => ['required', 'date_format:Y-m-d\TH:i']], attributes: ['when' => 'giờ hẹn']);
        $at = Carbon::createFromFormat('Y-m-d\TH:i', $this->when);
        $this->onPost(fn (Post $post, EditorialBoard $board) => $board->publish($post, $at, AdminActor::current()), 'Đã hẹn giờ đăng '.$at->format('H:i d/m/Y'));
    }

    public function hidePost(): void
    {
        $this->onPost(fn (Post $post, EditorialBoard $board) => $board->hide($post, AdminActor::current()), 'Đã ẩn bài khỏi website');
    }

    public function write(): mixed
    {
        $plan = $this->selected();
        abort_unless($plan instanceof EditorialPlanItem, 404);
        $post = app(EditorialBoard::class)->createDraft($plan);

        return redirect(ArticleResource::getUrl('edit', ['record' => $post]));
    }

    public function move(string $type, int $id, string $date): void
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1, 422);
        try {
            app(EditorialBoard::class)->move($type, $id, Carbon::parse($date), AdminActor::current());
            Notification::make()->success()->title('Đã dời sang '.Carbon::parse($date)->format('d/m'))->send();
        } catch (ValidationException $e) {
            Notification::make()->warning()->title($e->getMessage())->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requeue')->label('Xếp lại đề tài chưa viết')->icon('heroicon-o-arrow-path')->color('gray')
                ->visible(fn () => AdminActor::current()->canPerform('content_publish') && $this->totals()[EditorialBoard::LATE] > 0)
                ->modalDescription('Mọi đề tài chưa có bài được xếp lại nối tiếp nhau từ ngày chọn, giữ nguyên thứ tự. Bài đã viết không bị đụng tới.')
                ->schema([
                    DatePicker::make('from')->label('Bắt đầu từ ngày')->default(now()->addDay())->minDate(today())->required(),
                    Select::make('per_day')->label('Số bài mỗi ngày')->options([1 => '1 bài', 2 => '2 bài', 3 => '3 bài'])->default(3)->required()->native(false),
                ])
                ->action(function (array $data): void {
                    $moved = app(EditorialBoard::class)->requeue(Carbon::parse($data['from']), (int) $data['per_day']);
                    Notification::make()->success()->title('Đã xếp lại '.$moved.' đề tài')->send();
                }),
            Action::make('plans')->label('Danh sách đề tài')->icon('heroicon-o-clipboard-document-list')->color('gray')
                ->url(fn () => EditorialPlanItemResource::getUrl()),
        ];
    }

    /** @return list<array{title: string, state: string, note: string, url: string}> edits of existing posts waiting to be published */
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

    /** @param callable(Post, EditorialBoard): void $do */
    private function onPost(callable $do, string $done): void
    {
        $post = $this->selected();
        abort_unless($post instanceof Post, 404);
        $do($post, app(EditorialBoard::class));
        $this->open = '';
        Notification::make()->success()->title($done)->send();
    }
}
