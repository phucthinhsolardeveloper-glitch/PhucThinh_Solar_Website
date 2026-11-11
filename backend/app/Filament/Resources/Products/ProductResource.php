<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\ProductSalesReadiness;
use App\Domain\Catalog\SpecFormat;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\Options;
use App\Filament\Support\PublicLink;
use App\Filament\Support\PublicStatusColumn;
use App\Filament\Support\PublishGate;
use App\Models\Product;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'catalog';

    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'sản phẩm';

    protected static ?string $pluralModelLabel = 'Sản phẩm';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    /** Typed specification columns and their labels on the product page. */
    public const SPECS = [
        'light_source' => 'Nguồn sáng / quang thông', 'battery_capacity' => 'Dung lượng pin', 'panel_power' => 'Công suất tấm pin',
        'lamp_dimensions' => 'Kích thước đèn', 'color_temperature' => 'Nhiệt độ màu (CCT)', 'light_color' => 'Màu ánh sáng',
        'material' => 'Chất liệu', 'charging_time' => 'Thời gian sạc', 'lighting_time' => 'Thời gian chiếu sáng',
        'waterproof_rating' => 'Chỉ số chống nước', 'led_chip' => 'Loại chip LED', 'lighting_area' => 'Diện tích chiếu sáng',
        'installation_height' => 'Độ cao lắp đặt', 'panel_dimensions' => 'Kích thước tấm pin', 'carton_quantity' => 'Số lượng / thùng',
        'carton_dimensions' => 'Kích thước thùng', 'weight' => 'Trọng lượng', 'warranty' => 'Bảo hành',
    ];

    public const DOCUMENT_KINDS = ['datasheet' => 'Thông số kỹ thuật', 'manual' => 'Hướng dẫn sử dụng', 'warranty' => 'Bảo hành', 'certificate' => 'Chứng nhận', 'other' => 'Khác'];

    public static function canBulkImport(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->canPerform('catalog_bulk');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Thông tin chung')->columns(2)->schema([
                    Fields::title(),
                    Fields::publicSlug('products'),
                    TextInput::make('code')->label('Mã sản phẩm'),
                    Select::make('category_id')->label('Danh mục')->options(fn () => Options::categoryPaths())->searchable()->required(),
                    Select::make('brand_id')->label('Thương hiệu')->relationship('brand', 'name')->searchable()->preload(),
                    Select::make('pdp_profile')->label('Kiểu trang sản phẩm')->options(Options::pdpProfiles())
                        ->placeholder('Theo danh mục')->helperText('Để trống thì dùng kiểu của danh mục.'),
                    Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
                    Grid::make(4)->columnSpanFull()->schema([
                        Fields::visible()->default(false)
                            ->helperText('Sản phẩm mới mặc định ẩn. Bật khi đã có ảnh, mô tả, giá hoặc báo giá và SEO.'),
                        Toggle::make('is_bestseller')->label('Bán chạy'),
                    ]),
                ]),
                Tab::make('Giá')->columns(2)->schema([
                    Toggle::make('quote_only')->label('Chỉ báo giá (ẩn giá, hiện "Liên hệ báo giá")')->live()->columnSpanFull(),
                    TextInput::make('regular_price')->label('Giá bán (đ)')->numeric()->minValue(0)->hidden(fn (Get $get) => $get('quote_only')),
                    TextInput::make('sale_price')->label('Giá khuyến mãi (đ)')->numeric()->minValue(0)->hidden(fn (Get $get) => $get('quote_only'))
                        ->lte('regular_price')->validationMessages(['lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá bán.']),
                    TextInput::make('weight_kg')->label('Cân nặng khi đóng gói (kg)')->numeric()->minValue(0.01)->maxValue(5000)
                        ->helperText('Dùng để tính phí Nhất Tín lúc khách đặt hàng. Để trống thì nhân viên báo phí giao hàng sau.'),
                ]),
                Tab::make('Ảnh & video')->schema([
                    Grid::make(2)->schema([
                        Fields::image('image', 'product', 'Ảnh chính'),
                        Fields::image('image_secondary', 'product', 'Ảnh phụ'),
                        Fields::image('feed_image', 'product', 'Ảnh Google Shopping')->helperText('Nền sạch, không chữ, không logo, tối thiểu 500×500 px. Sản phẩm thiếu ảnh này không lên feed quảng cáo.'),
                    ]),
                    Repeater::make('images')->label('Thư viện ảnh')->relationship()->orderColumn('sort_order')->reorderable()
                        ->schema([Fields::image('path', 'product')->required(), TextInput::make('caption')->label('Chú thích'), Fields::visible('Hiển thị')])
                        ->grid(3)->collapsible()->defaultItems(0)->addActionLabel('Thêm ảnh'),
                    TextInput::make('video_url')->label('Video YouTube')->url()->placeholder('https://www.youtube.com/watch?v=…'),
                ]),
                Tab::make('Thông số')->schema([
                    TextInput::make('power_watts')->label('Công suất (W)')->numeric()->minValue(0),
                    // Measured specs are rewritten one way as soon as the field is left ("4-6H" → "4–6 giờ"); the model does it again on save.
                    Grid::make(3)->schema(collect(self::SPECS)->map(fn ($label, $column) => isset(SpecFormat::COLUMNS[$column])
                        ? TextInput::make($column)->label($label)->live(onBlur: true)->placeholder(SpecFormat::EXAMPLES[SpecFormat::COLUMNS[$column]])
                            ->afterStateUpdated(fn ($state, $set) => is_string($state) && $state !== '' ? $set($column, SpecFormat::apply($state, SpecFormat::COLUMNS[$column])) : null)
                        : TextInput::make($column)->label($label))->values()->all()),
                    Textarea::make('package_contents')->label('Bộ sản phẩm gồm')->rows(2),
                    Repeater::make('additional_specs')->label('Thông số thêm')->schema([
                        TextInput::make('label')->label('Tên thông số')->required(),
                        TextInput::make('value')->label('Giá trị')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $get, $set) => is_string($state) && ($kind = SpecFormat::kindOfLabel((string) $get('label'))) !== null ? $set('value', SpecFormat::apply($state, $kind)) : null),
                    ])->columns(2)->defaultItems(0)->addActionLabel('Thêm thông số')
                        ->dehydrateStateUsing(fn ($state) => array_values(array_map(fn ($row) => ['key' => '', 'label' => (string) $row['label'], 'value' => (string) $row['value']], (array) $state))),
                    Fields::rich('specs_html', 'Bảng thông số (dạng bảng tự soạn)'),
                ]),
                Tab::make('Mô tả')->schema([
                    Repeater::make('highlights')->label('Điểm nổi bật')->schema([
                        TextInput::make('title')->label('Ý chính')->required(),
                        Textarea::make('text')->label('Giải thích')->rows(2),
                    ])->columns(2)->defaultItems(0)->collapsible(),
                    Fields::lines('applications', 'Ứng dụng phù hợp'),
                    Fields::lines('installation', 'Lưu ý lắp đặt'),
                    Textarea::make('body')->label('Ghi chú tư vấn (dùng cho tìm kiếm, không hiện trên trang)')->rows(4),
                    Fields::rich('purchase_policy', 'Chính sách mua hàng'),
                    Section::make('Khối mô tả')->relationship('contentSection', Fields::keepRelation('contentSection', fn (array $state) => filled($state['blocks'] ?? null) || ! in_array($state['mode'] ?? 'inherit', ['inherit', null], true) || filled($state['inherit_source'] ?? null)))
                        ->schema([
                            Select::make('mode')->label('Cách hiển thị')->default('inherit')->required()->live()->options([
                                'own' => 'Chỉ dùng khối của sản phẩm', 'both' => 'Khối của sản phẩm, sau đó khối kế thừa',
                                'inherit' => 'Kế thừa từ thương hiệu / danh mục', 'hide' => 'Không hiện khối mô tả',
                            ]),
                            Select::make('inherit_source')->label('Kế thừa từ')->placeholder('Tự động: thương hiệu, rồi danh mục')
                                ->options(['brand' => 'Thương hiệu', 'category' => 'Danh mục', 'stack' => 'Gộp danh mục con, danh mục cha, thương hiệu'])
                                ->visible(fn (Get $get) => in_array($get('mode'), ['inherit', 'both'], true)),
                            Fields::visible('Dùng phần này'),
                            Fields::contentBlocks()->visible(fn (Get $get) => in_array($get('mode'), ['own', 'both'], true)),
                        ]),
                ]),
                Tab::make('Tài liệu')->schema([
                    Repeater::make('documents')->label('Tài liệu tải về')->relationship()->orderColumn('sort_order')->reorderable()
                        ->schema([
                            Select::make('kind')->label('Loại')->options(self::DOCUMENT_KINDS)->default('datasheet')->required(),
                            TextInput::make('title')->label('Tên hiển thị')->required(),
                            FileUpload::make('path')->label('Tệp (PDF, Word, Excel)')->disk('uploads')->directory('file')->required()
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->maxSize(25 * 1024)
                                ->getUploadedFileNameForStorageUsing(fn ($file) => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension()))
                                // The site reads "upload/file/<name>"; the disk works relative to upload/.
                                ->formatStateUsing(fn ($state) => is_string($state) ? preg_replace('#^upload/#', '', $state) : $state)
                                ->dehydrateStateUsing(fn ($state) => is_string($state) && $state !== '' ? 'upload/'.ltrim($state, '/') : $state),
                            Fields::visible('Hiển thị'),
                        ])->columns(2)->defaultItems(0)->collapsible()->addActionLabel('Thêm tài liệu'),
                ]),
                Tab::make('SEO')->schema([Fields::seo('product')]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['seo', 'category', 'brand'])->withCount('visibleArticles'))
            ->defaultSort('id', 'desc')
            ->reorderable('sort_order', fn () => self::canBulkImport())->paginatedWhileReordering(false)
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_product')->square()->height(48),
                TextColumn::make('name')->label('Sản phẩm')->searchable(['name', 'code', 'slug'])->sortable()->limit(60)
                    ->description(fn (Product $record) => $record->code),
                TextColumn::make('category.name')->label('Danh mục / thương hiệu')->toggleable()
                    ->description(fn (Product $record) => data_get($record, 'brand.name')),
                TextColumn::make('price')->label('Giá')
                    ->state(fn (Product $record) => match (true) {
                        $record->quote_only => 'Báo giá',
                        (float) ($record->sale_price ?: $record->regular_price) > 0 => number_format((float) ($record->sale_price ?: $record->regular_price), 0, ',', '.').'đ',
                        default => 'Chưa có giá',
                    }),
                TextColumn::make('sales_readiness')->label('Cần bổ sung')
                    ->state(function (Product $record): string {
                        if (! $record->is_visible) {
                            return 'Đang ẩn';
                        }

                        $issues = app(ProductSalesReadiness::class)->issues($record);

                        return $issues === [] ? 'Đủ dữ liệu cơ bản' : implode(', ', $issues);
                    })->wrap()->toggleable(),
                ToggleColumn::make('is_visible')->label('Hiển thị')->disabled(fn () => ! self::canBulkImport())
                    ->updateStateUsing(function (Product $record, bool $state): bool {
                        if ($state && ! PublishGate::allowSwitchOn($record)) {
                            return false;
                        }
                        $record->update(['is_visible' => $state]);

                        return $state;
                    }),
                PublicStatusColumn::make(),
                ToggleColumn::make('quote_only')->label('Báo giá')->disabled(fn () => ! self::canBulkImport())->toggleable(),
                ToggleColumn::make('is_bestseller')->label('Bán chạy')->disabled(fn () => ! self::canBulkImport())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Danh mục')->options(fn () => Options::categoryPaths())->searchable(),
                SelectFilter::make('brand_id')->label('Thương hiệu')->relationship('brand', 'name')->preload(),
                TernaryFilter::make('is_visible')->label('Hiển thị'),
                TernaryFilter::make('quote_only')->label('Chỉ báo giá'),
                SelectFilter::make('sales_readiness')->label('Cần cải thiện')->options(ProductSalesReadiness::FILTERS + ProductSalesReadiness::DATA_FILTERS)
                    ->query(fn (Builder $query, array $data) => app(ProductSalesReadiness::class)->filter($query, (string) ($data['value'] ?? ''))),
            ])
            ->recordActions([PublicLink::action(), EditAction::make()])
            ->toolbarActions(ProductBulkActions::groups());
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'slug'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
