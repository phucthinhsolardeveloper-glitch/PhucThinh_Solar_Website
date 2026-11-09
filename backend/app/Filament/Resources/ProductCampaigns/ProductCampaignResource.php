<?php

namespace App\Filament\Resources\ProductCampaigns;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\Options;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCampaign;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Promotion frames shown over product card pictures for a period. */
class ProductCampaignResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'catalog';

    protected static ?string $model = ProductCampaign::class;

    protected static ?string $modelLabel = 'chiến dịch khung';

    protected static ?string $pluralModelLabel = 'Khung khuyến mãi';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function form(Schema $schema): Schema
    {
        $scoped = fn (Get $get) => ! $get('applies_to_all');

        return $schema->columns(2)->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Tên chiến dịch')->required()->maxLength(255)->columnSpanFull(),
                Fields::image('frame_image', 'photo', 'Khung (PNG nền trong, vuông)')->required()->acceptedFileTypes(['image/png'])
                    ->helperText('Khung phủ lên ảnh sản phẩm trên thẻ; phần giữa để trong suốt.'),
                Section::make()->schema([
                    DateTimePicker::make('starts_at')->label('Bắt đầu')->helperText('Để trống là chạy ngay.'),
                    DateTimePicker::make('ends_at')->label('Kết thúc')->after('starts_at')->helperText('Để trống là không hết hạn.'),
                    TextInput::make('priority')->label('Độ ưu tiên')->numeric()->default(0)->required()
                        ->helperText('Nhiều chiến dịch cùng áp cho một sản phẩm thì số lớn hơn thắng.'),
                    Fields::visible('Đang bật'),
                ]),
            ]),
            Section::make('Áp dụng cho')->columnSpanFull()->schema([
                Toggle::make('applies_to_all')->label('Tất cả sản phẩm')->live(),
                Select::make('category_ids')->label('Danh mục (gồm cả danh mục con)')->multiple()->searchable()
                    ->options(fn () => Options::categoryPaths())->visible($scoped),
                Select::make('brand_ids')->label('Thương hiệu')->multiple()->searchable()
                    ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all())->visible($scoped),
                Select::make('product_ids')->label('Sản phẩm')->multiple()->searchable()->visible($scoped)
                    ->getSearchResultsUsing(fn (string $search) => Product::query()->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                    ->getOptionLabelsUsing(fn (array $values) => Product::query()->whereKey($values)->pluck('name', 'id')->all())
                    ->rules([fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get) {
                        if (! $get('applies_to_all') && empty($value) && empty($get('category_ids')) && empty($get('brand_ids'))) {
                            $fail('Chọn ít nhất một danh mục, thương hiệu hoặc sản phẩm, hoặc bật Tất cả sản phẩm.');
                        }
                    }]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('priority', 'desc')
            ->columns([
                ImageColumn::make('frame_image')->label('Khung')->disk('upload_photo')->height(56)->square(),
                TextColumn::make('name')->label('Chiến dịch')->searchable(),
                TextColumn::make('state')->label('Tình trạng')->badge()
                    ->state(fn (ProductCampaign $record) => self::state($record))
                    ->color(fn (string $state) => match ($state) {
                        'Đang chạy' => 'success', 'Sắp chạy' => 'warning', default => 'gray',
                    }),
                TextColumn::make('window')->label('Thời gian')
                    ->state(fn (ProductCampaign $record) => ($record->starts_at?->format('d/m/Y H:i') ?? 'Ngay').' → '.($record->ends_at?->format('d/m/Y H:i') ?? 'Không hết hạn')),
                TextColumn::make('scope')->label('Áp dụng')->state(fn (ProductCampaign $record) => self::scopeSummary($record)),
                TextColumn::make('priority')->label('Ưu tiên')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function state(ProductCampaign $record): string
    {
        return match (true) {
            ! $record->is_visible => 'Đang tắt',
            $record->starts_at && $record->starts_at->isFuture() => 'Sắp chạy',
            $record->ends_at && $record->ends_at->isPast() => 'Đã hết hạn',
            default => 'Đang chạy',
        };
    }

    private static function scopeSummary(ProductCampaign $record): string
    {
        if ($record->applies_to_all) {
            return 'Tất cả sản phẩm';
        }
        $parts = array_filter([
            count($record->category_ids ?? []) ? count($record->category_ids).' danh mục' : null,
            count($record->brand_ids ?? []) ? count($record->brand_ids).' thương hiệu' : null,
            count($record->product_ids ?? []) ? count($record->product_ids).' sản phẩm' : null,
        ]);

        return $parts === [] ? '—' : implode(', ', $parts);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProductCampaigns::route('/')];
    }
}
