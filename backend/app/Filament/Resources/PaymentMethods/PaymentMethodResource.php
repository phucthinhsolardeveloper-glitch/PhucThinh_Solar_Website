<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'orders';

    protected static ?string $model = PaymentMethod::class;

    protected static ?string $modelLabel = 'hình thức thanh toán';

    protected static ?string $pluralModelLabel = 'Hình thức thanh toán';

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public const KINDS = ['cash' => 'Tiền mặt tại công ty', 'bank_transfer' => 'Chuyển khoản (VietQR)', 'cod' => 'Thu tiền khi nhận hàng', 'other' => 'Khác'];

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Tên')->required(),
            Select::make('kind')->label('Loại')->required()->default('other')->options(self::KINDS)
                ->helperText('Chuyển khoản: khách thấy mã VietQR theo đơn. Thu khi nhận hàng: vận đơn Nhất Tín thu hộ đúng tổng tiền đơn.'),
            Textarea::make('description')->label('Hướng dẫn hiện khi khách chọn (số tài khoản, ghi chú…)')->rows(4)->columnSpanFull(),
            Fields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Hình thức'),
                ToggleColumn::make('is_visible')->label('Hiển thị'),
                TextColumn::make('sort_order')->label('Thứ tự'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePaymentMethods::route('/')];
    }
}
