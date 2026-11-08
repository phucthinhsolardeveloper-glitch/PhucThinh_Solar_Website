<?php

namespace App\Filament\Support;

use App\Filament\Concerns\RequiresPermission;
use App\Models\Post;
use App\Rules\FreePublicSlug;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shared screen for the four kinds of post (article, solution, project, policy). Each kind is its
 * own menu entry; the fields that only one kind uses appear only there.
 */
abstract class PostResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = Post::class;

    protected static ?string $recordTitleAttribute = 'title';

    /** article | solution | project | policy */
    protected static string $kind;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', static::$kind);
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowed() && (! $record instanceof Post || ! $record->is_visible || AdminActor::current()->canPerform('content_publish'));
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $kind = static::$kind;

        return $schema->components(array_values(array_filter([
            Hidden::make('kind')->default($kind),
            Section::make()->columns(2)->schema(array_values(array_filter([
                Fields::title('title', 'Tiêu đề'),
                Fields::slug()->rules([fn (?Model $record) => self::slugRule($kind, $record)]),
                $kind === 'solution' ? TextInput::make('nav_title')->label('Tên ngắn trên menu') : null,
                in_array($kind, ['article', 'project', 'solution'], true)
                    ? Select::make('category_id')->label('Nhóm')->relationship('category', 'name', fn (Builder $query) => $query->where('kind', $kind))->preload()
                    : null,
                Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
            ]))),
            $kind === 'project' ? Section::make('Thông tin công trình')->columns(2)->schema([
                TextInput::make('client_name')->label('Chủ đầu tư / khách hàng'),
                TextInput::make('address')->label('Địa điểm'),
                DatePicker::make('started_on')->label('Khởi công'),
                DatePicker::make('finished_on')->label('Hoàn thành'),
            ]) : null,
            Section::make('Nội dung')->schema(array_filter([
                Fields::rich('body', 'Nội dung chính')
                    ->helperText('Dùng Tiêu đề 2/3 cho các mục; mục lục được tạo tự động từ các tiêu đề này.'),
                $kind === 'solution' ? Fields::rich('extra_body', 'Nội dung bổ sung') : null,
            ])),
            Section::make('Hình ảnh')->columns(2)->schema(array_values(array_filter([
                Fields::image('image', 'news', 'Ảnh đại diện'),
                in_array($kind, ['solution', 'project'], true) ? Fields::image('cover_image', 'news', 'Ảnh bìa') : null,
                in_array($kind, ['project', 'article', 'solution'], true)
                    ? Repeater::make('media')->label('Thư viện ảnh / video')->relationship()->orderColumn('sort_order')->reorderable()
                        ->schema([
                            Select::make('kind')->label('Loại')->options(['image' => 'Ảnh', 'video' => 'Video YouTube'])->default('image')->live(),
                            Fields::image('path', 'news', 'Ảnh (với video: ảnh bìa)'),
                            TextInput::make('video_url')->label('Link YouTube')->url()->visible(fn (Get $get) => $get('kind') === 'video'),
                            TextInput::make('caption')->label('Chú thích'),
                            Fields::visible('Hiển thị'),
                        ])->grid(2)->collapsible()->defaultItems(0)->columnSpanFull()->addActionLabel('Thêm ảnh / video')
                    : null,
            ]))),
            in_array($kind, ['article', 'project'], true) ? Section::make($kind === 'article' ? 'Sản phẩm liên quan' : 'Thiết bị đã dùng')->schema([
                Select::make('products')->label('Sản phẩm')->relationship('products', 'name')->multiple()->searchable()
                    ->maxItems($kind === 'article' ? 4 : null)
                    ->helperText($kind === 'article' ? 'Chọn sản phẩm phù hợp để liên kết trực tiếp từ bài viết tới trang bán hàng. Tối đa 4 sản phẩm sẽ hiện trên bài.' : null),
            ]) : null,
            Section::make()->columns(4)->schema(array_values(array_filter([
                Fields::visible()->default($kind !== 'article'),
                $kind !== 'policy' ? Toggle::make('is_featured')->label('Nổi bật') : null,
                in_array($kind, ['article', 'solution'], true) ? Toggle::make('in_hub')->label($kind === 'solution' ? 'Hiện ở trang Giải pháp' : 'Đưa lên đầu trang Tin tức') : null,
                Fields::sort(),
                DateTimePicker::make('published_at')->label('Ngày đăng')->default(now())
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? Carbon::createFromTimestamp((int) $state)->toDateTimeString() : $state)
                    ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->timestamp : null),
            ]))),
            Fields::seo('news'),
        ])));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_news')->height(44),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->sortable()->limit(70),
                TextColumn::make('category.name')->label('Nhóm')->toggleable()->visible(static::$kind !== 'policy'),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('published_at')->label('Ngày đăng')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::createFromTimestamp((int) $state)->format('d/m/Y') : ''),
            ])
            ->filters(array_values(array_filter([
                static::$kind !== 'policy' ? SelectFilter::make('category_id')->label('Nhóm')->relationship('category', 'name', fn (Builder $query) => $query->where('kind', static::$kind)) : null,
                TernaryFilter::make('is_visible')->label('Hiển thị'),
            ])))
            ->recordActions([EditAction::make()]);
    }

    /** Articles and projects answer on "/<slug>"; solutions and policies only need a slug free in their kind. */
    private static function slugRule(string $kind, ?Model $record): Closure|FreePublicSlug
    {
        if (in_array($kind, ['article', 'project'], true)) {
            return new FreePublicSlug('posts', $record?->getKey());
        }

        return function (string $attribute, mixed $value, Closure $fail) use ($kind, $record) {
            $taken = DB::table('posts')->where('kind', $kind)->where('slug', $value)
                ->when($record, fn ($q) => $q->where('id', '!=', $record->getKey()))->exists();
            if ($taken) {
                $fail('Đã có trang khác dùng đường dẫn này.');
            }
        };
    }
}
