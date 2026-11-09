<?php

namespace App\Filament\Resources\Provinces;

use App\Filament\Concerns\RequiresPermission;
use App\Models\Province;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProvinceResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'orders';

    protected static ?string $model = Province::class;

    protected static ?string $modelLabel = 'tỉnh/thành phố';

    protected static ?string $pluralModelLabel = 'Tỉnh/thành phố';

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

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
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Tỉnh/TP')->required()->maxLength(255),
            TextInput::make('code')->label('Mã địa phương')->maxLength(255),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->integer()->default(0)->required(),
            Toggle::make('is_visible')->label('Cho chọn khi đặt hàng')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make('name')->label('Tỉnh/TP')->searchable()->sortable(),
            TextColumn::make('code')->label('Mã tỉnh')->searchable(),
            TextColumn::make('tax_code')->label('Mã thuế')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('code')->label('Mã')->searchable(),
            TextColumn::make('wards_count')->label('Phường/Xã')->counts('wards')->sortable(),
            IconColumn::make('is_visible')->label('Cho chọn')->boolean(),
            TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProvinces::route('/'),
            'create' => Pages\CreateProvince::route('/create'),
            'edit' => Pages\EditProvince::route('/{record}/edit'),
        ];
    }
}
