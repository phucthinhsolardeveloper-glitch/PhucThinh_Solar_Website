<?php

namespace App\Filament\Resources\EditorialPlanItems;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\EditorialPlanItem;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EditorialPlanItemResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = EditorialPlanItem::class;

    protected static ?string $modelLabel = 'lịch bài viết';

    protected static ?string $pluralModelLabel = 'Lịch 180 bài viết';

    protected static ?string $navigationLabel = 'Kế hoạch bài đăng';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false; // The source-controlled, deduplicated plan is imported by an explicit command.
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Never delete editorial history as a side-effect of a scheduling change.
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lịch và trạng thái')->columns(2)->schema([
                TextInput::make('plan_key')->label('Mã kế hoạch')->disabled()->dehydrated(false),
                Select::make('stage')->label('Tiến độ biên tập')->options(EditorialPlanItem::STAGES)->required()->native(false),
                TextInput::make('title')->label('Tiêu đề dự kiến')->required()->maxLength(255)->columnSpanFull(),
                DateTimePicker::make('scheduled_for')->label('Ngày giờ dự kiến (giờ Việt Nam)')->required()
                    ->helperText('Chỉ là lịch biên tập; không tự bật bài lên website.'),
                Select::make('category_slug')->label('Chuyên mục')->options([
                    'kien-thuc-san-pham' => 'Kiến thức sản phẩm',
                    'tin-moi-nhat' => 'Tin mới nhất',
                    'tin-truyen-thong' => 'Tin truyền thông',
                ])->required(),
                TextInput::make('stream')->label('Luồng nội dung')->disabled()->dehydrated(false),
                Select::make('priority')->label('Ưu tiên')->options(['P1' => 'P1 — Cao', 'P2' => 'P2 — Bình thường', 'P3' => 'P3 — Thấp'])->required(),
            ]),
            Section::make('Định hướng biên tập và kiểm chứng')->columns(2)->schema([
                Textarea::make('angle')->label('Góc tiếp cận / dàn ý')->rows(4)->columnSpanFull(),
                TextInput::make('primary_source_name')->label('Nguồn chính / cơ quan')->maxLength(120),
                TextInput::make('primary_source_url')->label('Link tài liệu nguồn')->url(),
                TextInput::make('secondary_source_url')->label('Link kiểm chứng bổ sung')->url()->columnSpanFull(),
                Textarea::make('editor_notes')->label('Ghi chú và người phụ trách')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Ảnh và nhận diện thương hiệu')->columns(2)->schema([
                TextInput::make('cover_file')->label('Ảnh bìa trong kho media')->helperText('URL ảnh local: /upload/news/{tên file}')->columnSpanFull(),
                Select::make('image_status')->label('Trạng thái ảnh')->options([
                    'mockup' => 'Bản bìa thương hiệu — cần duyệt ảnh',
                    'approved' => 'Ảnh đã được kiểm tra quyền sử dụng',
                    'needs_photo' => 'Cần bổ sung ảnh thật / hình kỹ thuật',
                ]),
                Select::make('logo_variant')->label('Dùng logo')->options(['wide' => 'Logo dài', 'square' => 'Logo đứng']),
                Textarea::make('image_brief')->label('Yêu cầu hình minh họa')->rows(3)->columnSpanFull(),
                TextInput::make('image_search_url')->label('Nguồn tìm ảnh')->url()->columnSpanFull(),
            ]),
            Section::make('Liên kết bài viết đã soạn')->description('Đầu mục kế hoạch có thể liên kết bài thực trong CMS. Đổi trạng thái ở đây không xuất bản bài.')
                ->schema([
                    Select::make('post_id')->label('Bài viết trong Admin')->relationship('post', 'title', modifyQueryUsing: fn (Builder $query) => $query->where('kind', 'article'))
                        ->searchable()->preload(false)->helperText('Chỉ chọn sau khi bài đã viết và kiểm tra nội dung.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('scheduled_for')
            ->columns([
                ImageColumn::make('cover_file')->label('Bìa')->disk('upload_news')->height(44)->checkFileExistence(false),
                TextColumn::make('scheduled_for')->label('Lịch dự kiến')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(68)->wrap(),
                TextColumn::make('category_slug')->label('Nhóm')->formatStateUsing(fn ($v) => [
                    'kien-thuc-san-pham' => 'Kiến thức',
                    'tin-moi-nhat' => 'Tin ngành',
                    'tin-truyen-thong' => 'Truyền thông',
                ][$v] ?? $v)->badge(),
                TextColumn::make('stage')->label('Tiến độ')->badge()
                    ->formatStateUsing(fn ($v) => EditorialPlanItem::STAGES[$v] ?? $v)
                    ->color(fn ($v) => match ($v) {
                        'published' => 'success', 'scheduled' => 'info', 'ready', 'review' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('post.title')->label('Bài đã viết')->placeholder('Chưa soạn')->limit(22)
                    ->url(fn (EditorialPlanItem $record) => $record->post_id
                        ? ArticleResource::getUrl('edit', ['record' => $record->post_id]) : null),
            ])
            ->filters([
                SelectFilter::make('stage')->label('Tiến độ')->options(EditorialPlanItem::STAGES),
                SelectFilter::make('category_slug')->label('Chuyên mục')->options([
                    'kien-thuc-san-pham' => 'Kiến thức sản phẩm', 'tin-moi-nhat' => 'Tin mới nhất',
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEditorialPlanItems::route('/'),
            'edit' => Pages\EditEditorialPlanItem::route('/{record}/edit')];
    }
}
