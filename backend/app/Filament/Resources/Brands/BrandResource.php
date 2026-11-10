<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\PublicLink;
use App\Models\Brand;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BrandResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'catalog';

    protected static ?string $model = Brand::class;

    protected static ?string $modelLabel = 'thương hiệu';

    protected static ?string $pluralModelLabel = 'Thương hiệu';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Fields::title(),
                Fields::publicSlug('brands'),
                Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
                Fields::rich('body', 'Giới thiệu')->columnSpanFull(),
                Fields::image('image', 'product', 'Logo'),
                Section::make()->schema([Fields::visible()]),
            ]),
            Section::make('Khối mô tả dùng chung')->relationship('contentSection', Fields::keepRelation('contentSection', fn (array $state) => filled($state['blocks'] ?? null)))->collapsed()
                ->description('Sản phẩm của thương hiệu kế thừa các khối này khi sản phẩm không có mô tả riêng.')
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => $data + ['mode' => 'own'])
                ->schema([Fields::visible('Dùng các khối này'), Fields::contentBlocks()]),
            Fields::seo('product'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Logo')->disk('upload_product')->height(36),
                TextColumn::make('name')->label('Thương hiệu')->searchable()->sortable(),
                TextColumn::make('products_count')->label('Sản phẩm')->counts('products')->sortable(),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->recordActions([PublicLink::action(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
