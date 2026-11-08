<?php

namespace App\Filament\Resources\ArticleDrafts;

use App\Domain\Content\ArticleWorkflow;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
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

class ArticleDraftResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = PostRevision::class;

    protected static ?string $modelLabel = 'phiên bản bài viết';

    protected static ?string $pluralModelLabel = 'Bản nháp & lịch sử bài viết';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('post', fn (Builder $query) => $query->where('kind', 'article'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowed() && $record instanceof PostRevision && $record->state === 'draft' && $record->post?->kind === 'article';
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
            Section::make('Bản nháp')->description('Lưu bản nháp không đổi bài đang hiển thị. Đường dẫn, ảnh và thư viện hiện lấy từ bài gốc.')
                ->schema([
                    TextInput::make('payload.title')->label('Tiêu đề')->required()->maxLength(255),
                    Textarea::make('payload.summary')->label('Mô tả ngắn')->rows(3)->maxLength(2000),
                    self::draftRich('body', 'Nội dung chính'),
                    self::draftRich('extra_body', 'Nội dung phụ'),
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
                TextColumn::make('post.title')->label('Bài viết')->limit(65)->searchable(),
                TextColumn::make('version')->label('Phiên bản')->sortable(),
                TextColumn::make('state')->label('Tình trạng')->badge()->formatStateUsing(fn ($state) => $state === 'draft' ? 'Bản nháp' : 'Đã xuất bản')
                    ->color(fn ($state) => $state === 'draft' ? 'warning' : 'success'),
                TextColumn::make('editor.name')->label('Người sửa gần nhất')->toggleable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([SelectFilter::make('state')->label('Tình trạng')->options(['draft' => 'Bản nháp', 'published' => 'Đã xuất bản'])])
            ->recordActions([
                EditAction::make()->visible(fn (PostRevision $record) => $record->state === 'draft'),
                Action::make('preview')->label('Xem trước')->icon(Heroicon::OutlinedEye)
                    ->url(fn (PostRevision $record) => app(ArticleWorkflow::class)->previewUrl($record))->openUrlInNewTab(),
                Action::make('restore')->label('Khôi phục thành bản nháp')->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn (PostRevision $record) => $record->state === 'published' && AdminActor::current()->canPerform('content_publish'))->requiresConfirmation()
                    ->action(function (PostRevision $record) {
                        $draft = app(ArticleWorkflow::class)->restoreAsDraft($record, AdminActor::current());

                        return redirect(static::getUrl('edit', ['record' => $draft]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleDrafts::route('/'),
            'edit' => Pages\EditArticleDraft::route('/{record}/edit'),
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
