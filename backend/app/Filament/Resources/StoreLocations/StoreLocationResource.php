<?php

namespace App\Filament\Resources\StoreLocations;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\InlineEdit;
use App\Filament\Support\PublicLink;
use App\Models\Province;
use App\Models\StoreLocation;
use App\Models\Ward;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
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
            Section::make('Kho gửi hàng')->columns(2)->columnSpanFull()
                ->description('Bật nếu chi nhánh này là nơi giữ hàng và xuất hàng. Tên và số điện thoại bên dưới là người gửi in trên vận đơn Nhất Tín.')
                ->schema([
                    Toggle::make('is_warehouse')->label('Là kho gửi hàng')->live()->columnSpanFull(),
                    TextInput::make('sender_name')->label('Người liên hệ / quản lý kho')->maxLength(255)->visible(fn ($get) => (bool) $get('is_warehouse')),
                    TextInput::make('sender_phone')->label('Điện thoại người gửi')->tel()->maxLength(30)->visible(fn ($get) => (bool) $get('is_warehouse')),
                    Select::make('province_id')->label('Tỉnh / thành của kho')->searchable()->live()->dehydrated(false)
                        ->afterStateHydrated(fn (Select $component, $record) => $component->state($record?->ward_id ? Ward::query()->whereKey($record->ward_id)->value('province_id') : null))
                        ->options(fn () => Province::query()->where('is_visible', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn ($get) => (bool) $get('is_warehouse')),
                    Select::make('ward_id')->label('Phường / xã của kho')->searchable()
                        ->options(fn ($get) => $get('province_id') ? Ward::query()->where('province_id', $get('province_id'))->orderBy('name')->pluck('name', 'id')->all() : [])
                        ->helperText('Cần để tính cước và tạo vận đơn từ kho này.')
                        ->visible(fn ($get) => (bool) $get('is_warehouse')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $edit = InlineEdit::on($table, static::class);

        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->columns([
                $edit ? TextInputColumn::make('name')->label('Chi nhánh')->rules(['required', 'string', 'max:255']) : TextColumn::make('name')->label('Chi nhánh'),
                $edit ? TextInputColumn::make('address')->label('Địa chỉ')->rules(['required', 'string', 'max:500']) : TextColumn::make('address')->label('Địa chỉ')->limit(60),
                TextColumn::make('is_warehouse')->label('Kho gửi hàng')->formatStateUsing(fn ($state, StoreLocation $record) => $state ? ($record->sender_name ? 'Kho · '.$record->sender_name : 'Kho · chưa có người liên hệ') : '')->color(fn ($state, StoreLocation $record) => $state && ! $record->ward_id ? 'warning' : null),
                ToggleColumn::make('is_visible')->label('Hiển thị'),
            ])
            ->recordActions([PublicLink::row(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStoreLocations::route('/')];
    }
}
