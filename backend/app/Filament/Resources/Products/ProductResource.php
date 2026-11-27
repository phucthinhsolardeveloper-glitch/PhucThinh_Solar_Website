<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Catalog\MerchantIdentifier;
use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Domain\Catalog\ProductSalesReadiness;
use App\Domain\Catalog\SpecFormat;
use App\Domain\Routing\PublicStatus;
use App\Domain\Routing\PublicUrls;
use App\Domain\Shipping\Warehouses;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\NewFeatures;
use App\Filament\Support\Options;
use App\Filament\Support\PublicStatusColumn;
use App\Filament\Support\PublishGate;
use App\Filament\Support\TypedSpecsForm;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Support\SiteSettings;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
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
                    TextInput::make('code')->label('Mã sản phẩm')->maxLength(30)
                        ->helperText('Để trống để tự sinh mã PTS-000123 theo ID. Có thể đổi lại mã này sau khi lưu.'),
                    Select::make('category_id')->label('Danh mục')->options(fn () => Options::categoryPaths())->searchable()->required()->live(),
                    Select::make('brand_id')->label('Thương hiệu')->relationship('brand', 'name')->searchable()->preload(),
                    Select::make('pdp_profile')->label('Kiểu trang sản phẩm')->options(Options::pdpProfiles())->live()
                        ->placeholder('Theo danh mục')->helperText('Để trống thì dùng kiểu của danh mục.'),
                    Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
                    Grid::make(4)->columnSpanFull()->schema([
                        Fields::visible()->default(false)
                            ->helperText('Sản phẩm mới mặc định ẩn. Bật khi đã có ảnh, mô tả, giá hoặc báo giá và SEO.'),
                        Toggle::make('is_bestseller')->label('Nổi bật')->helperText('Hiện nhãn "Nổi bật" trên thẻ sản phẩm và đưa lên đầu khi khách chọn sắp xếp Nổi bật. Chỉ nên chọn một ít sản phẩm.'),
                    ]),
                ]),
                Tab::make('Giá')->columns(2)->schema([
                    self::quoteToggle()->columnSpanFull(),
                    ...self::priceInputs(),
                    TextInput::make('weight_kg')->label('Cân nặng khi đóng gói (kg)')->numeric()->minValue(0.01)->maxValue(5000)
                        ->helperText('Dùng để tính phí Nhất Tín lúc khách đặt hàng. Để trống thì nhân viên báo phí giao hàng sau.'),
                    Select::make('warehouse_id')->label(NewFeatures::label('Kho giữ hàng', 'kho-san-pham'))->options(fn () => app(Warehouses::class)->options())->placeholder('Chưa gán kho')
                        ->helperText('Kho nào đang giữ sản phẩm này. Đơn có sản phẩm này sẽ xuất từ kho đó; phí giao hàng cũng tính từ kho đó.'),
                ]),
                Tab::make('Google Merchant')->columns(2)->schema([
                    Select::make('merchant_availability')->label('Tình trạng Merchant')->options([
                        'in_stock' => 'Sẵn sàng giao ngay (đã xác minh)',
                    ])->placeholder('Chưa xác minh')->helperText('Chỉ chọn sau khi kiểm tra khả năng giao ngay. Trạng thái trống vẫn giữ dữ liệu feed cũ cho tới khi bật release gate; không đồng nghĩa đã xác minh.'),
                    TextInput::make('merchant_gtin')->label('GTIN nhà sản xuất')->maxLength(32)
                        ->helperText('Nhập GTIN-8/12/13/14 từ nhãn hoặc tài liệu hãng. Mã được giữ dạng chuỗi để bảo toàn số 0 đầu.')
                        ->rules([fn () => function (string $attribute, mixed $value, Closure $fail): void {
                            if (filled($value) && MerchantIdentifier::gtin((string) $value) === '') {
                                $fail('GTIN phải đúng độ dài, checksum và không thuộc dải hạn chế/coupon.');
                            }
                        }])->dehydrateStateUsing(fn ($state) => filled($state) ? MerchantIdentifier::gtin((string) $state) : null),
                    TextInput::make('merchant_mpn')->label('MPN nhà sản xuất')->maxLength(70)
                        ->helperText('Nhập đúng mã part number của hãng; không dùng mã sản phẩm nội bộ.')
                        ->rules([fn () => function (string $attribute, mixed $value, Closure $fail): void {
                            if (filled($value) && MerchantIdentifier::mpn((string) $value) === '') {
                                $fail('MPN phải có từ 1 đến 70 ký tự và không chứa ký tự điều khiển.');
                            }
                        }])->dehydrateStateUsing(fn ($state) => filled($state) ? MerchantIdentifier::mpn((string) $state) : null),
                    Toggle::make('merchant_identifiers_absent')->label('Đã xác minh không có GTIN/MPN')
                        ->helperText('Chỉ đánh dấu sau khi kiểm tra với hãng hoặc nhà cung cấp. Feed dùng yes khi có GTIN hợp lệ hoặc có cả MPN và thương hiệu; MPN thiếu thương hiệu vẫn chưa kết luận.')
                        ->rules([fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ($value && (filled($get('merchant_gtin')) || filled($get('merchant_mpn')))) {
                                $fail('Hãy xóa GTIN và MPN trước khi xác nhận sản phẩm không có mã.');
                            }
                        }]),
                ]),
                Tab::make('Ảnh & video')->schema([
                    Grid::make(2)->schema([
                        Fields::image('image', 'product', 'Ảnh chính'),
                        Fields::image('image_secondary', 'product', 'Ảnh phụ'),
                        Fields::image('feed_image', 'product', 'Ảnh Google Shopping')->helperText('Nền sạch, không chữ, không logo, tối thiểu 500×500 px. Không có ảnh này thì feed quảng cáo dùng ảnh chính (cần từ 500×500 px); ảnh sạch giúp Google ít từ chối hơn.'),
                    ]),
                    Fields::bulkImages('product', 'images'),
                    Repeater::make('images')->label('Thư viện ảnh')->relationship()->orderColumn('sort_order')->reorderable()
                        ->schema([Fields::image('path', 'product')->required(), TextInput::make('caption')->label('Chú thích'), Fields::visible('Hiển thị')])
                        ->grid(3)->collapsible()->defaultItems(0)->addActionLabel('Thêm ảnh'),
                    TextInput::make('video_url')->label('Video YouTube')->url()->placeholder('https://www.youtube.com/watch?v=…'),
                ]),
                Tab::make('Thông số')->schema([
                    ...TypedSpecsForm::components(),
                    Textarea::make('package_contents')->label('Bộ sản phẩm gồm')->rows(2),
                    Repeater::make('additional_specs')->label('Thông số thêm (ngoài các ô trên)')->schema([
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

    /** The pencil on the list: name, category, brand and prices turn into boxes that save as soon as you leave them. */
    private static function inlineEditing(Table $table): bool
    {
        $page = $table->getLivewire();

        return $page instanceof Pages\ListProducts && $page->inlineEdit && self::canBulkImport();
    }

    public static function table(Table $table): Table
    {
        $edit = self::inlineEditing($table);
        // Read once: a closure would run again for every row of a 200-row page.
        $categoryOptions = $edit ? Options::categoryPaths() : [];
        $brandOptions = $edit ? Brand::query()->orderBy('name')->pluck('name', 'id')->all() : [];
        $warehouseOptions = app(Warehouses::class)->options();
        $pricesVisible = app(SiteSettings::class)->productPricesVisible();

        return $table
            ->paginationPageOptions([10, 25, 50, 100, 200])->defaultPaginationPageOption(50)
            ->description($edit ? 'Đang sửa trên bảng: sửa ô nào, ô đó được lưu ngay khi bạn bấm ra ngoài. Bấm "Xong sửa" để thoát.' : null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['seo', 'category', 'brand'])->withCount('visibleArticles'))
            ->defaultSort('id', 'desc')
            ->recordClasses(fn (Product $record) => $record->is_visible ? null : 'pts-row-hidden')
            ->reorderable('sort_order', fn () => self::canBulkImport())->paginatedWhileReordering(false)
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_product')->square()->height(48),
                ...($edit ? [
                    TextInputColumn::make('name')->label('Sản phẩm')->searchable(['name', 'code', 'slug'])->sortable()->rules(['required', 'string', 'max:255']),
                    TextColumn::make('code')->label('Mã')->searchable()->toggleable(),
                    SelectColumn::make('category_id')->label('Danh mục')->options($categoryOptions)->searchable()->selectablePlaceholder(false)->rules(['required']),
                    SelectColumn::make('brand_id')->label('Thương hiệu')->options($brandOptions)->searchable()->placeholder('Chưa có'),
                    TextInputColumn::make('regular_price')->label('Giá bán (đ)')->type('number')->extraInputAttributes(['min' => 0, 'step' => 'any'])
                        ->rules(['nullable', 'numeric', 'min:0']),
                    TextInputColumn::make('sale_price')->label('Giá khuyến mãi (đ)')->type('number')->extraInputAttributes(['min' => 0, 'step' => 'any'])
                        ->rules(fn (Product $record) => ['nullable', 'numeric', 'min:0', ...((float) $record->regular_price > 0 ? ['lte:'.(float) $record->regular_price] : [])]),
                ] : [
                    TextColumn::make('name')->label('Sản phẩm')->searchable(['name', 'code', 'slug'])->sortable()->limit(80)->wrap()
                        ->extraHeaderAttributes(['style' => 'min-width: 16.5rem'])->extraCellAttributes(['style' => 'min-width: 16.5rem'])
                        ->description(fn (Product $record) => implode(' · ', array_filter([$record->code, $record->is_bestseller ? '★ Nổi bật' : null]))),
                    TextColumn::make('category.name')->label('Danh mục / thương hiệu')->toggleable()
                        ->description(fn (Product $record) => data_get($record, 'brand.name')),
                    TextColumn::make('price')->label('Giá khách thấy')
                        ->state(fn (Product $record) => ProductPriceState::of($record, $pricesVisible)['text'])
                        ->description(fn (Product $record) => ProductPriceState::of($record, $pricesVisible)['detail'])
                        ->badge(fn (Product $record) => ProductPriceState::of($record, $pricesVisible)['quote'])
                        ->color(fn (Product $record) => ProductPriceState::of($record, $pricesVisible)['color'])
                        ->icon(fn (Product $record) => ProductPriceState::of($record, $pricesVisible)['icon'])
                        ->tooltip('Bấm để sửa giá')
                        ->action(self::quickEditAction('priceEdit')),
                ]),
                TextColumn::make('warehouse_id')->label('Kho giữ hàng')->formatStateUsing(fn ($state) => $warehouseOptions[(int) $state] ?? 'Chưa gán')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('merchant_availability')->label('Merchant')->formatStateUsing(fn ($state) => $state === 'in_stock' ? 'Đã xác minh giao ngay' : 'Chưa xác minh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sales_readiness')->label('Cần bổ sung')
                    ->state(function (Product $record): string {
                        $issues = app(ProductSalesReadiness::class)->issues($record, includeHidden: true);

                        return $issues === [] ? 'Đủ dữ liệu cơ bản' : implode(', ', $issues);
                    })->wrap()->toggleable(),
                ToggleColumn::make('is_visible')->label('Hiện / ẩn')->disabled(fn () => ! self::canBulkImport())
                    ->updateStateUsing(function (Product $record, bool $state): bool {
                        if ($state && ! PublishGate::allowSwitchOn($record)) {
                            return false;
                        }
                        $record->update(['is_visible' => $state]);
                        Notification::make()->success()->title($state ? 'Đã hiện trên website' : 'Đã ẩn khỏi website')->body($record->name)->send();

                        return $state;
                    }),
                self::statusColumn(),
                ToggleColumn::make('quote_only')->label('Ẩn giá')->disabled(fn () => ! self::canBulkImport())->toggleable(isToggledHiddenByDefault: ! $edit),
                ToggleColumn::make('is_bestseller')->label('Nổi bật')->disabled(fn () => ! self::canBulkImport())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Danh mục')->options(fn () => Options::categoryPaths())->searchable(),
                SelectFilter::make('brand_id')->label('Thương hiệu')->relationship('brand', 'name')->preload(),
                TernaryFilter::make('is_visible')->label('Trên website')->trueLabel('Đang hiện')->falseLabel('Đang ẩn'),
                TernaryFilter::make('quote_only')->label('Giá')->trueLabel('Đang ẩn giá')->falseLabel('Không ẩn giá'),
                SelectFilter::make('warehouse_id')->label('Kho giữ hàng')->options(fn () => app(Warehouses::class)->options())->placeholder('Tất cả'),
                TernaryFilter::make('weight_kg')->label('Cân nặng đóng gói')->trueLabel('Đã có')->falseLabel('Chưa có')
                    ->queries(true: fn (Builder $q) => $q->where('weight_kg', '>', 0), false: fn (Builder $q) => $q->where(fn ($q) => $q->whereNull('weight_kg')->orWhere('weight_kg', '<=', 0))),
                SelectFilter::make('sales_readiness')->label('Cần cải thiện')->options(ProductSalesReadiness::FILTERS + ProductSalesReadiness::DATA_FILTERS)
                    ->query(fn (Builder $query, array $data) => app(ProductSalesReadiness::class)->filter($query, (string) ($data['value'] ?? ''))),
                // The same checks as "Chất lượng dữ liệu" and "Việc cần làm", so their links land on this list.
                SelectFilter::make('health')->label('Lỗi trên website')
                    ->options(fn () => ['errors' => 'Mọi lỗi nghiêm trọng'] + array_map(fn (array $check) => $check[1], CatalogHealth::CHECKS))
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereKey(app(CatalogHealth::class)->productIds((string) $data['value'])) : $query),
            ])
            ->recordActions([self::quickEditAction()->iconButton()->tooltip('Sửa nhanh'), EditAction::make()->iconButton()->tooltip('Chỉnh sửa')])
            ->toolbarActions(ProductBulkActions::groups());
    }

    /** "Trên website" in words on every row, hidden ones too; a hidden product opens as a 15-minute preview. */
    private static function statusColumn(): TextColumn
    {
        $hidden = fn (Product $record): bool => PublicStatus::of($record)['key'] === PublicStatus::HIDDEN;

        return PublicStatusColumn::make()
            ->icon(fn (Product $record) => $hidden($record) ? 'heroicon-m-eye-slash' : 'heroicon-m-arrow-top-right-on-square')
            ->tooltip(fn (Product $record) => $hidden($record) ? 'Khách không thấy. Bấm để xem trước (liên kết dùng 15 phút).' : PublicStatus::of($record)['reason'])
            ->url(fn (Product $record) => $hidden($record) ? ProductDetailAssembler::previewUrl((int) $record->getKey()) : PublicUrls::url($record), shouldOpenInNewTab: true);
    }

    private static function quoteToggle(): Toggle
    {
        return Toggle::make('quote_only')->label('Ẩn giá, hiện "Liên hệ báo giá"')->live()
            ->helperText(fn (Get $get) => $get('quote_only') ? 'Khách đang không thấy giá và không đặt mua được. Giá bên dưới vẫn được lưu.' : 'Khách thấy giá bên dưới. Chưa nhập giá thì website tự hiện "Liên hệ báo giá".');
    }

    /** @return list<TextInput> */
    private static function priceInputs(): array
    {
        return [
            TextInput::make('regular_price')->label('Giá bán (đ)')->numeric()->minValue(0),
            TextInput::make('sale_price')->label('Giá khuyến mãi (đ)')->numeric()->minValue(0)
                ->lte('regular_price')->validationMessages(['lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá bán.']),
        ];
    }

    /** Visibility, price, category and the featured flag from the list; the same model save as the edit page. */
    public static function quickEditAction(string $name = 'quickEdit'): Action
    {
        return Action::make($name)->label('Sửa nhanh')->icon(Heroicon::OutlinedBolt)->link()
            ->modalHeading(fn (Product $record) => $record->name)->modalWidth('lg')->modalSubmitActionLabel('Lưu')
            ->fillForm(fn (Product $record) => $record->only(['is_visible', 'regular_price', 'sale_price', 'quote_only', 'category_id', 'is_bestseller']))
            ->schema([
                Toggle::make('is_visible')->label('Hiện trên website')->visible(fn () => self::canBulkImport()),
                self::quoteToggle(),
                Grid::make(2)->schema(self::priceInputs()),
                Select::make('category_id')->label('Danh mục')->options(fn () => Options::categoryPaths())->searchable()->required(),
                Toggle::make('is_bestseller')->label('Nổi bật')->helperText('Hiện nhãn "Nổi bật" trên thẻ sản phẩm và đưa lên đầu khi khách chọn sắp xếp Nổi bật. Chỉ nên chọn một ít sản phẩm.'),
            ])
            ->action(function (Product $record, array $data): void {
                $show = (bool) ($data['is_visible'] ?? false);
                $switchOn = $show && ! $record->is_visible;
                // Switching on is checked after the new price and category are in; the switch itself comes last.
                if ($switchOn) {
                    unset($data['is_visible']);
                }
                $record->update($data);
                if ($switchOn && PublishGate::allowSwitchOn($record)) {
                    $record->update(['is_visible' => true]);
                }
                Notification::make()->success()->title('Đã lưu '.$record->name)
                    ->body($record->is_visible ? 'Đang hiện trên website.' : 'Đang ẩn khỏi website.')->send();
            });
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
