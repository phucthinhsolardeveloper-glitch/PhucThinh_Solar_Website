<?php

namespace App\Filament\Resources\PostRevisions;

use App\Domain\Content\PostRevisions;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Filament\Support\AdminActor;
use App\Filament\Support\Fields;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Drafts and published versions of articles, solutions, projects and policies. A draft is edited
 * here without touching the public page; history rows can be compared and reopened as a draft.
 */
class PostRevisionResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = PostRevision::class;

    protected static ?string $modelLabel = 'phiên bản';

    protected static ?string $pluralModelLabel = 'Bản nháp & lịch sử';

    protected static ?string $slug = 'ban-nhap';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public const KIND_LABELS = ['article' => 'Bài viết', 'solution' => 'Giải pháp', 'project' => 'Dự án', 'policy' => 'Chính sách'];

    public static function getNavigationBadge(): ?string
    {
        $drafts = PostRevision::query()->where('state', 'draft')->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Bản nháp chờ xuất bản';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('post')->whereHas('post', fn (Builder $q) => $q->whereIn('kind', array_keys(PostRevisions::KINDS)));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowed() && $record instanceof PostRevision && $record->state === 'draft';
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowed() && $record instanceof PostRevision && $record->state === 'draft';
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $kind = fn (?PostRevision $record): string => (string) $record?->post?->kind;
        $has = fn (string $field) => fn (?PostRevision $record) => in_array($field, PostRevisions::KINDS[$kind($record)]['fields'] ?? [], true);

        return $schema->components([
            Section::make('Nội dung')->description('Lưu bản nháp không đổi trang đang hiển thị. Đường dẫn giữ nguyên khi xuất bản.')
                ->schema([
                    TextInput::make('payload.title')->label('Tiêu đề')->required()->maxLength(255),
                    TextInput::make('payload.nav_title')->label('Tên ngắn trên menu')->maxLength(255)->visible($has('nav_title')),
                    Textarea::make('payload.summary')->label('Mô tả ngắn')->rows(3)->maxLength(2000),
                    self::draftRich('body', 'Nội dung chính'),
                    self::draftRich('extra_body', fn (?PostRevision $record) => $kind($record) === 'solution' ? 'Nội dung bổ sung' : 'Nội dung phụ')->visible($has('extra_body')),
                ]),
            Section::make('Thông tin công trình')->columns(2)->visible($has('client_name'))->schema([
                TextInput::make('payload.client_name')->label('Chủ đầu tư / khách hàng')->maxLength(255),
                TextInput::make('payload.address')->label('Địa điểm')->maxLength(255),
                DatePicker::make('payload.started_on')->label('Khởi công'),
                DatePicker::make('payload.finished_on')->label('Hoàn thành'),
            ]),
            Section::make('Hình ảnh')->columns(2)->visible($has('image'))->schema([
                Fields::image('payload.image', 'news', 'Ảnh đại diện'),
                Fields::image('payload.cover_image', 'news', 'Ảnh bìa')->visible($has('cover_image')),
                Repeater::make('payload.media')->label('Thư viện ảnh / video')->reorderable()->collapsible()->grid(2)->defaultItems(0)->columnSpanFull()
                    ->visible(fn (?PostRevision $record) => PostRevisions::KINDS[$kind($record)]['media'] ?? false)
                    ->addActionLabel('Thêm ảnh / video')
                    ->schema([
                        Select::make('kind')->label('Loại')->options(['image' => 'Ảnh', 'video' => 'Video YouTube'])->default('image')->live(),
                        Fields::image('path', 'news', 'Ảnh (với video: ảnh bìa)'),
                        TextInput::make('video_url')->label('Link YouTube')->url()->visible(fn (Get $get) => $get('kind') === 'video'),
                        TextInput::make('caption')->label('Chú thích'),
                        Toggle::make('is_visible')->label('Hiển thị')->default(true),
                    ]),
            ]),
            Section::make(fn (?PostRevision $record) => $kind($record) === 'project' ? 'Thiết bị đã dùng' : 'Sản phẩm liên quan')
                ->visible(fn (?PostRevision $record) => (PostRevisions::KINDS[$kind($record)]['products'] ?? false) !== false)
                ->schema([
                    Select::make('payload.products')->label('Sản phẩm')->multiple()->searchable()->preload()
                        ->maxItems(fn (?PostRevision $record) => PostRevisions::KINDS[$kind($record)]['products'] ?: null)
                        ->options(fn () => Product::query()->where('is_visible', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText(fn (?PostRevision $record) => $kind($record) === 'article' ? 'Tối đa 4 sản phẩm hiện trên bài viết.' : null),
                ]),
            Section::make('SEO')->collapsed()->schema([
                TextInput::make('payload.seo.title')->label('Tiêu đề SEO')->maxLength(255),
                Textarea::make('payload.seo.description')->label('Mô tả SEO')->rows(3)->maxLength(500),
                TextInput::make('payload.seo.keywords')->label('Từ khóa')->maxLength(1000),
                Fields::image('payload.seo.image', 'news', 'Ảnh chia sẻ'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('post.kind')->label('Loại')->badge()->color('gray')->formatStateUsing(fn ($state) => self::KIND_LABELS[$state] ?? $state),
                TextColumn::make('post.title')->label('Nội dung')->limit(60)->searchable()->tooltip(fn (PostRevision $record) => $record->post?->title),
                TextColumn::make('version')->label('Bản')->sortable()->formatStateUsing(fn ($state) => $state === 0 ? 'Gốc' : '#'.$state),
                TextColumn::make('state')->label('Tình trạng')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'draft' ? 'Bản nháp' : 'Đã xuất bản')
                    ->color(fn ($state) => $state === 'draft' ? 'warning' : 'success'),
                TextColumn::make('editor.name')->label('Người sửa')->placeholder('Hệ thống')->toggleable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')->label('Tình trạng')->options(['draft' => 'Bản nháp', 'published' => 'Đã xuất bản']),
                SelectFilter::make('kind')->label('Loại')->options(self::KIND_LABELS)
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null) ? $q->whereHas('post', fn (Builder $p) => $p->where('kind', $data['value'])) : $q),
                SelectFilter::make('post_id')->label('Nội dung')->searchable()
                    ->options(fn () => Post::query()->whereHas('revisions')->orderBy('title')->pluck('title', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (PostRevision $record) => $record->state === 'draft'),
                self::compareAction(),
                self::previewAction(),
                Action::make('restore')->label('Mở lại thành bản nháp')->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn (PostRevision $record) => $record->state === 'published' && AdminActor::current()->canPerform('content_publish'))
                    ->requiresConfirmation()->modalDescription('Tạo bản nháp từ phiên bản này. Trang công khai chỉ đổi khi bạn xuất bản bản nháp đó.')
                    ->action(fn (PostRevision $record) => redirect(static::getUrl('edit', ['record' => app(PostRevisions::class)->restoreAsDraft($record, AdminActor::current())]))),
            ]);
    }

    public static function compareAction(): Action
    {
        return Action::make('compare')->label('So sánh')->icon(Heroicon::OutlinedArrowsRightLeft)->color('gray')
            ->modalHeading(fn (PostRevision $record) => 'So với trang đang hiển thị: '.$record->post?->title)
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng')->modalWidth('5xl')
            ->modalContent(fn (PostRevision $record) => view('filament.revision-changes', ['changes' => self::describeChanges($record)]));
    }

    public static function previewAction(): Action
    {
        return Action::make('preview')->label('Xem trước')->icon(Heroicon::OutlinedEye)->color('gray')
            ->visible(fn (PostRevision $record) => $record->post?->kind === 'article')
            ->url(fn (PostRevision $record) => app(PostRevisions::class)->previewUrl($record))->openUrlInNewTab();
    }

    /** @return list<array{label: string, live: string, revision: string}> */
    public static function describeChanges(PostRevision $record): array
    {
        $rows = [];
        foreach (app(PostRevisions::class)->changes($record) as $field => [$live, $revision]) {
            $rows[] = ['label' => PostRevisions::LABELS[$field] ?? $field, 'live' => self::readable($field, $live), 'revision' => self::readable($field, $revision)];
        }

        return $rows;
    }

    private static function readable(string $field, mixed $value): string
    {
        if ($field === 'products') {
            $names = Product::query()->whereIn('id', (array) $value)->pluck('name', 'id');

            return collect((array) $value)->map(fn ($id) => $names[$id] ?? '#'.$id)->implode("\n") ?: '—';
        }
        if ($field === 'media') {
            return collect((array) $value)->map(fn ($m) => ($m['kind'] === 'video' ? 'Video: '.$m['video_url'] : 'Ảnh: '.$m['path']).($m['caption'] ? ' — '.$m['caption'] : '').($m['is_visible'] ? '' : ' (ẩn)'))->implode("\n") ?: '—';
        }
        if ($field === 'seo') {
            return collect((array) $value)->filter(fn ($v) => filled($v))->map(fn ($v, $k) => $k.': '.$v)->implode("\n") ?: '—';
        }
        if ($field === 'published_at' && is_numeric($value)) {
            return date('d/m/Y H:i', (int) $value);
        }
        if (is_bool($value) || in_array($field, ['is_featured', 'in_hub', 'is_visible'], true)) {
            return $value ? 'Có' : 'Không';
        }
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return $text === '' ? '—' : mb_strimwidth($text, 0, 1200, '…');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostRevisions::route('/'),
            'edit' => Pages\EditPostRevision::route('/{record}/edit'),
        ];
    }

    /** Rich text that keeps the stored HTML byte for byte when the editor did not change it. */
    private static function draftRich(string $field, string|\Closure $label): RichEditor
    {
        $html = fn (mixed $content): string => filled($content) ? RichContentRenderer::make($content)->getEditor()->getHTML() : '';

        return RichEditor::make('payload.'.$field)->label($label)
            ->fileAttachmentsDisk('uploads')->fileAttachmentsDirectory('filemanager/editor')->fileAttachmentsVisibility('public')
            ->dehydrateStateUsing(function ($state, ?Model $record) use ($field, $html) {
                $original = $record instanceof PostRevision ? ($record->payload[$field] ?? null) : null;
                if (is_string($original) && $html($state) === $html($original)) {
                    return $original;
                }
                $out = is_string($state) ? $state : $html($state);

                return trim(strip_tags($out, '<img><iframe><table>')) === '' ? null : $out;
            });
    }

    /** @return class-string<resource> the edit screen of a post of this kind */
    public static function ownerResource(string $kind): string
    {
        return match ($kind) {
            'article' => ArticleResource::class,
            'solution' => SolutionResource::class,
            'project' => ProjectResource::class,
            default => PolicyResource::class,
        };
    }
}
