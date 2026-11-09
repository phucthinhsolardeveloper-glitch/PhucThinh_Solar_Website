<?php

namespace App\Filament\Pages;

use App\Domain\Pages\PageLayouts;
use App\Filament\Support\AdminActor;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Order, visibility and look of the sections of Home and About. Edits go to a draft; the public page
 * changes only on publish. Section texts and pictures are edited in "Nội dung các trang".
 *
 * @property-read Schema $form
 */
class PageLayoutsPage extends Page
{
    protected static ?string $title = 'Bố cục trang';

    protected static ?string $navigationLabel = 'Bố cục trang';

    protected static ?string $slug = 'bo-cuc-trang';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    #[Url]
    public string $page = 'home';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('content');
    }

    public function mount(): void
    {
        if (! isset(PageLayouts::PAGES[$this->page])) {
            $this->page = 'home';
        }
        $this->fillFromCurrent();
    }

    public function getSubheading(): ?string
    {
        $draft = $this->draftRow();
        $live = DB::table('page_layouts')->where('page', $this->page)->where('state', 'published')->orderByDesc('version')->first();

        return PageLayouts::PAGES[$this->page]['label'].': '.($live ? 'đang dùng bản '.$live->version : 'đang dùng bố cục gốc')
            .($draft ? ' · có bản nháp '.$draft->version.' chưa xuất bản' : '').'. Chữ và ảnh trong từng khối sửa ở "Nội dung các trang".';
    }

    public function form(Schema $schema): Schema
    {
        $sections = PageLayouts::PAGES[$this->page]['sections'];

        return $schema->statePath('data')->components([
            Repeater::make('sections')->hiddenLabel()->addable(false)->deletable(false)->reorderable()->reorderableWithButtons()
                ->itemLabel(fn (array $state) => $sections[$state['type'] ?? '']['label'] ?? '')
                ->columns(2)
                ->schema([
                    Hidden::make('type'),
                    Toggle::make('visible')->label('Hiện trên trang')
                        ->disabled(fn ($get) => ! empty($sections[$get('type')]['required']))
                        ->helperText(fn ($get) => ! empty($sections[$get('type')]['required']) ? 'Khối mở đầu: luôn hiện và luôn ở đầu trang.' : null),
                    Select::make('variant')->label('Kiểu hiển thị')->selectablePlaceholder(false)
                        ->options(fn ($get) => $sections[$get('type')]['variants'] ?? [])
                        ->visible(fn ($get) => count($sections[$get('type')]['variants'] ?? []) > 1),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('saveDraft')
                ->footer([Actions::make([Action::make('saveDraft')->label('Lưu bản nháp')->submit('saveDraft')->keyBindings(['mod+s'])])]),
            Section::make('Các phiên bản đã xuất bản')->collapsed()->schema([
                View::make('filament.pages.page-layout-history')->viewData(['versions' => $this->history(), 'canRestore' => AdminActor::current()->canPerform('content_publish')]),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        foreach (PageLayouts::PAGES as $key => $page) {
            $actions[] = Action::make('page_'.$key)->label($page['label'])->color($key === $this->page ? 'primary' : 'gray')
                ->url(self::getUrl(['page' => $key]));
        }
        $actions[] = Action::make('preview')->label('Xem trước')->icon(Heroicon::OutlinedEye)->color('gray')
            ->visible(fn () => $this->draftRow() !== null)->url(fn () => app(PageLayouts::class)->previewUrl($this->draftRow()))->openUrlInNewTab();
        $actions[] = Action::make('publish')->label('Xuất bản')->icon(Heroicon::OutlinedRocketLaunch)->requiresConfirmation()
            ->modalDescription('Trang công khai sẽ dùng bố cục của bản nháp ngay sau khi xuất bản.')
            ->visible(fn () => $this->draftRow() !== null && AdminActor::current()->canPerform('content_publish'))
            ->action(function (): void {
                $this->saveDraft(notify: false);
                app(PageLayouts::class)->publish((int) $this->draftRow()?->id, AdminActor::current());
                Notification::make()->success()->title('Đã xuất bản bố cục')->send();
                $this->fillFromCurrent();
            });
        $actions[] = Action::make('discard')->label('Bỏ bản nháp')->color('danger')->requiresConfirmation()
            ->visible(fn () => $this->draftRow() !== null)
            ->action(function (): void {
                DB::table('page_layouts')->where('page', $this->page)->where('state', 'draft')->delete();
                $this->fillFromCurrent();
            });

        return $actions;
    }

    public function saveDraft(bool $notify = true): void
    {
        $actor = AdminActor::current();
        $draft = app(PageLayouts::class)->draft($this->page, $actor);
        app(PageLayouts::class)->save((int) $draft->id, array_values($this->form->getState()['sections'] ?? []), $actor);
        if ($notify) {
            Notification::make()->success()->title('Đã lưu bản nháp')->body('Bấm "Xem trước" để kiểm tra, rồi "Xuất bản".')->send();
        }
    }

    public function restore(int $id): void
    {
        app(PageLayouts::class)->restoreAsDraft($id, AdminActor::current());
        Notification::make()->success()->title('Đã mở lại phiên bản thành bản nháp')->body('Xem trước rồi xuất bản để dùng lại.')->send();
        $this->fillFromCurrent();
    }

    /** @return list<array{id: int, version: int, state: string, at: string, by: string, hidden: string}> */
    private function history(): array
    {
        $labels = collect(PageLayouts::PAGES[$this->page]['sections'])->map(fn ($s) => $s['label']);

        return DB::table('page_layouts as l')->leftJoin('users as u', 'u.id', '=', 'l.published_by')->where('l.page', $this->page)
            ->whereIn('l.state', ['published', 'archived'])->orderByDesc('l.version')->limit(20)
            ->get(['l.id', 'l.version', 'l.state', 'l.published_at', 'l.sections', 'u.name'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'version' => (int) $r->version, 'state' => $r->state === 'published' ? 'Đang dùng' : 'Bản cũ',
                'at' => $r->published_at ? date('H:i d/m/Y', strtotime((string) $r->published_at)) : '', 'by' => (string) $r->name,
                'hidden' => collect(PageLayouts::normalise($this->page, json_decode((string) $r->sections, true)))->where('visible', false)
                    ->map(fn ($s) => $labels[$s['type']] ?? $s['type'])->implode(', ')])->all();
    }

    private function draftRow(): ?object
    {
        return DB::table('page_layouts')->where('page', $this->page)->where('state', 'draft')->first();
    }

    private function fillFromCurrent(): void
    {
        $row = $this->draftRow() ?? DB::table('page_layouts')->where('page', $this->page)->where('state', 'published')->orderByDesc('version')->first();
        $this->form->fill(['sections' => PageLayouts::normalise($this->page, $row ? json_decode((string) $row->sections, true) : null)]);
    }
}
