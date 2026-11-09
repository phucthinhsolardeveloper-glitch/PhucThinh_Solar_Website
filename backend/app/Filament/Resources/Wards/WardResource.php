<?php

namespace App\Filament\Resources\Wards;

use App\Filament\Concerns\RequiresPermission;
use App\Models\Ward;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Delivery fee per ward (province → ward). Edited straight in the list; a fee of 0 means free or agreed later. */
class WardResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'orders';

    protected static ?string $model = Ward::class;

    protected static ?string $modelLabel = 'phường/xã';

    protected static ?string $pluralModelLabel = 'Phí giao hàng theo phường/xã';

    protected static ?string $navigationLabel = 'Phí giao hàng';

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function canCreate(): bool
    {
        return static::allowed();
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
        return $schema->columns(2)->components([
            Select::make('province_id')->label('Tỉnh/TP')->relationship('province', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Phường/Xã')->required()->maxLength(255),
            TextInput::make('code')->label('Mã địa phương')->maxLength(255),
            TextInput::make('shipping_fee')->label('Phí giao (đ)')->numeric()->minValue(0)->default(0)->required(),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->integer()->default(0)->required(),
            Toggle::make('is_visible')->label('Cho chọn khi đặt hàng')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('province.name')->label('Tỉnh/TP')->sortable(),
                TextColumn::make('name')->label('Phường/Xã')->searchable()->sortable(),
                TextColumn::make('tax_code')->label('Mã phường/xã')->searchable()->toggleable(),
                TextInputColumn::make('shipping_fee')->label('Phí giao (đ)')->type('number')->rules(['numeric', 'min:0']),
                ToggleColumn::make('is_visible')->label('Cho chọn khi đặt hàng'),
            ])
            ->filters([SelectFilter::make('province_id')->label('Tỉnh/TP')->relationship('province', 'name')->searchable()->preload()])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('fee')->label('Đặt phí cho các phường/xã đã chọn')
                        ->schema([TextInput::make('fee')->label('Phí giao (đ)')->numeric()->minValue(0)->required()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['shipping_fee' => (int) $data['fee']]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageWards::route('/')];
    }
}
