<?php

namespace App\Filament\Resources\Policies;

use App\Filament\Support\PostResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PolicyResource extends PostResource
{
    protected static string $kind = 'policy';

    protected static ?string $modelLabel = 'trang hỗ trợ';

    protected static ?string $pluralModelLabel = 'Chính sách & hỗ trợ';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
