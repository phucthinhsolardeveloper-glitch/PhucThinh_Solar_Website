<?php

namespace App\Filament\Resources\Wards;

use App\Filament\Concerns\RequiresPermission;
use App\Models\Ward;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
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
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('province.name')->label('Tỉnh/TP')->sortable(),
                TextColumn::make('name')->label('Phường/Xã')->searchable()->sortable(),
                TextInputColumn::make('shipping_fee')->label('Phí giao (đ)')->type('number')->rules(['numeric', 'min:0']),
                ToggleColumn::make('is_visible')->label('Cho chọn khi đặt hàng'),
            ])
            ->filters([SelectFilter::make('province_id')->label('Tỉnh/TP')->relationship('province', 'name')->searchable()->preload()])
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
