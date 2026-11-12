<?php

namespace App\Filament\Support;

use App\Domain\News\RelatedProductSuggestions;
use App\Domain\Projects\ProjectSystem;
use App\Filament\Concerns\RequiresPermission;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Slugs;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

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
                Fields::slug('title', fn (?Model $record) => in_array($kind, ['article', 'project'], true)
                    ? Slugs::publicScope('posts', $record?->getKey())
                    : Slugs::scope('posts', ['kind' => $kind], $record?->getKey())),
                $kind === 'solution' ? TextInput::make('nav_title')->label('Tên ngắn trên menu') : null,
                in_array($kind, ['article', 'project', 'solution'], true)
                    ? Select::make('category_id')->label('Nhóm')->relationship('category', 'name', fn (Builder $query) => $query->where('kind', $kind))->preload()
                    : null,
                Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
            ]))),
            $kind === 'project' ? Section::make('Thông tin công trình')->columns(2)->schema([
                TextInput::make('client_name')->label('Chủ đầu tư / khách hàng')->columnSpanFull(),
                ...Fields::location(),
                DatePicker::make('started_on')->label('Khởi công'),
                DatePicker::make('finished_on')->label('Hoàn thành'),
            ]) : null,
            $kind === 'project' ? Section::make('Thông số hệ thống')->description('Hiện thành dải thông số trên trang dự án; ô để trống thì không hiện.')->columns(3)
                ->schema(ProjectSystem::fields()) : null,
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
                    ->maxItems($kind === 'article' ? RelatedProductSuggestions::MAX : null)
                    ->hintActions($kind === 'article' ? [self::suggestProductsAction()] : [])
                    ->helperText($kind === 'article' ? 'Chọn sản phẩm phù hợp để liên kết trực tiếp từ bài viết tới trang bán hàng. Tối đa 4 sản phẩm sẽ hiện trên bài.' : null),
            ]) : null,
            Section::make()->columns(3)->schema(array_values(array_filter([
                Fields::visible()->default($kind !== 'article'),
                $kind !== 'policy' ? Toggle::make('is_featured')->label('Nổi bật') : null,
                in_array($kind, ['article', 'solution'], true) ? Toggle::make('in_hub')->label($kind === 'solution' ? 'Hiện ở trang Giải pháp' : 'Đưa lên đầu trang Tin tức') : null,
                DateTimePicker::make('published_at')->label('Ngày đăng')->default(now())
                    ->helperText('Chọn ngày giờ trong tương lai để hẹn giờ: bài bật hiển thị sẽ tự lên website đúng lúc đó.'),
            ]))),
            Fields::seo('news'),
        ])));
    }

    /**
     * Opens the topic-based suggestions for the article being edited. Choosing adds to the current
     * selection only; existing (manual) choices are never removed or reordered, and nothing is saved
     * until the editor saves the form.
     */
    protected static function suggestProductsAction(): Action
    {
        return Action::make('suggestProducts')->label('Gợi ý sản phẩm phù hợp')->icon('heroicon-o-light-bulb')->link()
            ->modalHeading('Gợi ý sản phẩm phù hợp')->modalWidth('2xl')
            ->modalSubmitActionLabel('Thêm sản phẩm đã chọn')
            ->schema(function (Get $get, ?Model $record): array {
                $current = self::selectedProductIds($get);
                $free = RelatedProductSuggestions::MAX - count($current);
                $items = $free > 0 ? app(RelatedProductSuggestions::class)->suggest(self::articleState($get, $record), $current) : [];
                if ($items === []) {
                    return [Text::make($free > 0
                        ? 'Chưa tìm thấy sản phẩm phù hợp: bài chưa bàn rõ một nhóm thiết bị (tấm pin, inverter hòa lưới/hybrid, pin lưu trữ, đèn, bơm). Bài tin thị trường chung có thể không cần sản phẩm liên quan.'
                        : 'Bài đã có đủ '.RelatedProductSuggestions::MAX.' sản phẩm. Bỏ bớt một sản phẩm nếu muốn thêm gợi ý.')];
                }
                $options = [];
                $descriptions = [];
                foreach ($items as $item) {
                    $options[$item['id']] = $item['name'];
                    $descriptions[$item['id']] = new HtmlString('<span style="display:flex;gap:.6rem;align-items:flex-start">'
                        .'<img src="/thumbs/96x96x1'.e($item['image']).'" alt="" loading="lazy" style="width:48px;height:48px;object-fit:contain;flex:none;border-radius:4px;background:#fff">'
                        .'<span>'.e($item['reason']).' <a href="'.e($item['url']).'" target="_blank" rel="noopener" style="text-decoration:underline">Xem trang sản phẩm</a></span></span>');
                }

                return [
                    Text::make(count($current) > 0
                        ? 'Đang có '.count($current).' sản phẩm đã chọn, được giữ nguyên. Có thể thêm tối đa '.$free.' sản phẩm.'
                        : 'Chọn tối đa '.$free.' sản phẩm. Gợi ý chỉ gồm sản phẩm đang hiển thị, có ảnh và đường dẫn hợp lệ.'),
                    CheckboxList::make('suggested')->label('Sản phẩm gợi ý')->options($options)->descriptions($descriptions)
                        ->maxItems($free)->required()->validationMessages(['required' => 'Chọn ít nhất một sản phẩm hoặc bấm Hủy.']),
                ];
            })
            ->action(function (array $data, Get $get, Set $set): void {
                $current = self::selectedProductIds($get);
                $chosen = array_map('intval', (array) ($data['suggested'] ?? []));
                $live = array_keys(app(RelatedProductSuggestions::class)->eligible(array_values(array_diff($chosen, $current))));
                $merged = array_values(array_unique([...$current, ...$live]));
                if (count($merged) > RelatedProductSuggestions::MAX) {
                    Notification::make()->danger()->title('Tối đa '.RelatedProductSuggestions::MAX.' sản phẩm cho một bài viết')
                        ->body('Lựa chọn hiện có không bị thay đổi.')->send();

                    return;
                }
                $set('products', array_map('strval', $merged));
                Notification::make()->success()->title('Đã thêm '.count($live).' sản phẩm vào lựa chọn')
                    ->body('Bấm Lưu để áp dụng.')->send();
            });
    }

    /** @return list<int> */
    private static function selectedProductIds(Get $get): array
    {
        return array_values(array_unique(array_map('intval', array_filter((array) ($get('products') ?? []), 'is_numeric'))));
    }

    /** @return array{title: string, summary: string, keywords: string, body: string, category: string} */
    private static function articleState(Get $get, ?Model $record): array
    {
        $body = $get('body');
        if (! is_string($body)) {
            $body = filled($body) ? RichContentRenderer::make($body)->getEditor()->getHTML() : (string) ($record instanceof Post ? $record->body : '');
        }
        $categoryId = $get('category_id');

        return [
            'title' => (string) $get('title'),
            'summary' => (string) $get('summary'),
            'keywords' => (string) ($get('seo.keywords') ?? ($record instanceof Post ? $record->seo?->keywords : '')),
            'body' => $body,
            'category' => $categoryId ? (string) PostCategory::query()->whereKey($categoryId)->value('slug') : '',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->reorderable('sort_order', fn () => AdminActor::current()->canPerform('content_publish'))->paginatedWhileReordering(false)
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_news')->height(44)
                    ->checkFileExistence(static::$kind !== 'article'),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->sortable()->limit(70),
                TextColumn::make('category.name')->label('Nhóm')->toggleable()->visible(static::$kind !== 'policy'),
                PublicStatusColumn::make(),
                TextColumn::make('published_at')->label('Ngày đăng')->sortable()->date('d/m/Y'),
            ])
            ->filters(array_values(array_filter([
                static::$kind !== 'policy' ? SelectFilter::make('category_id')->label('Nhóm')->relationship('category', 'name', fn (Builder $query) => $query->where('kind', static::$kind)) : null,
                TernaryFilter::make('is_visible')->label('Hiển thị'),
                Filter::make('scheduled')->label('Đang hẹn giờ đăng')->query(fn (Builder $query) => $query->where('is_visible', true)->where('published_at', '>', now())),
            ])))
            ->recordActions([EditAction::make()]);
    }
}
