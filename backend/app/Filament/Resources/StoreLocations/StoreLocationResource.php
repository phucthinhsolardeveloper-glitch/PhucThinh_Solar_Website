<?php

namespace App\Filament\Resources\StoreLocations;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Models\StoreLocation;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Showrooms and branches listed in the footer and on the contact page. */
class StoreLocationResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'settings';

    protected static ?string $model = StoreLocation::class;

    protected static ?string $modelLabel = 'chi nhánh';

    protected static ?string $pluralModelLabel = 'Cửa hàng & chi nhánh';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Tên chi nhánh')->required(),
            TextInput::make('address')->label('Địa chỉ')->required(),
            TextInput::make('map_url')->label('Link Google Maps')->url()->columnSpanFull(),
            Textarea::make('map_embed')->label('Mã nhúng bản đồ (iframe)')->rows(3)->columnSpanFull(),
            Fields::visible(),
            Fields::sort(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Chi nhánh'),
                TextColumn::make('address')->label('Địa chỉ')->limit(60),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStoreLocations::route('/')];
    }
}
