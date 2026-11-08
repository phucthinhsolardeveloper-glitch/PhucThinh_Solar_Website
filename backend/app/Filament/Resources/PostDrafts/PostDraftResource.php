<?php

namespace App\Filament\Resources\PostDrafts;

use App\Domain\Content\PostWorkflow;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PostDraftResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = PostRevision::class;

    protected static ?string $modelLabel = 'phiên bản nội dung';

    protected static ?string $pluralModelLabel = 'Bản nháp Giải pháp / Dự án / Chính sách';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('post', fn (Builder $query) => $query->whereIn('kind', ['solution', 'project', 'policy']));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowed() && $record instanceof PostRevision && $record->state === 'draft'
            && in_array($record->post?->kind, ['solution', 'project', 'policy'], true);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bản nháp')->description('Lưu tại đây không đổi trang công khai. Đường dẫn và ảnh/thư viện hiện lấy từ bản gốc.')
                ->schema([
                    TextInput::make('payload.title')->label('Tiêu đề')->required()->maxLength(255),
                    TextInput::make('payload.nav_title')->label('Tên ngắn trên menu')->maxLength(255)
                        ->visible(fn (?PostRevision $record) => $record?->post?->kind === 'solution'),
                    Textarea::make('payload.summary')->label('Mô tả ngắn')->rows(3)->maxLength(2000),
                    self::draftRich('body', 'Nội dung chính'),
                    self::draftRich('extra_body', 'Nội dung bổ sung')
                        ->visible(fn (?PostRevision $record) => $record?->post?->kind === 'solution'),
                ]),
            Section::make('Thông tin công trình')->columns(2)
                ->visible(fn (?PostRevision $record) => $record?->post?->kind === 'project')
                ->schema([
                    TextInput::make('payload.client_name')->label('Chủ đầu tư / khách hàng')->maxLength(255),
                    TextInput::make('payload.address')->label('Địa điểm')->maxLength(255),
                    DatePicker::make('payload.started_on')->label('Khởi công'),
                    DatePicker::make('payload.finished_on')->label('Hoàn thành'),
                    Select::make('payload.products')->label('Thiết bị đã dùng')->multiple()->searchable()->preload()
                        ->options(fn () => Product::query()->where('is_visible', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->columnSpanFull(),
                ]),
            Section::make('SEO')->collapsed()->schema([
                TextInput::make('payload.seo.title')->label('Tiêu đề SEO')->maxLength(255),
                Textarea::make('payload.seo.description')->label('Mô tả SEO')->maxLength(500),
                TextInput::make('payload.seo.keywords')->label('Từ khóa SEO')->maxLength(1000),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('post.kind')->label('Loại')->formatStateUsing(fn ($state) => match ($state) {
                    'solution' => 'Giải pháp', 'project' => 'Dự án', 'policy' => 'Chính sách', default => $state,
                }),
                TextColumn::make('post.title')->label('Nội dung')->limit(65)->searchable(),
                TextColumn::make('version')->label('Phiên bản')->sortable(),
                TextColumn::make('state')->label('Tình trạng')->badge()->formatStateUsing(fn ($state) => $state === 'draft' ? 'Bản nháp' : 'Đã xuất bản')
                    ->color(fn ($state) => $state === 'draft' ? 'warning' : 'success'),
                TextColumn::make('editor.name')->label('Người sửa gần nhất')->toggleable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([SelectFilter::make('state')->label('Tình trạng')->options(['draft' => 'Bản nháp', 'published' => 'Đã xuất bản'])])
            ->recordActions([
                EditAction::make()->visible(fn (PostRevision $record) => $record->state === 'draft'),
                Action::make('restore')->label('Khôi phục thành bản nháp')->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn (PostRevision $record) => $record->state === 'published' && AdminActor::current()->canPerform('content_publish'))
                    ->requiresConfirmation()
                    ->action(function (PostRevision $record) {
                        $draft = app(PostWorkflow::class)->restoreAsDraft($record, AdminActor::current());

                        return redirect(static::getUrl('edit', ['record' => $draft]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostDrafts::route('/'),
            'edit' => Pages\EditPostDraft::route('/{record}/edit'),
        ];
    }

    private static function draftRich(string $field, string $label): RichEditor
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
}
