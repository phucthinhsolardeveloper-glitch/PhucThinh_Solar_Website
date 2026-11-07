<?php

namespace App\Filament\Resources\Solutions;

use App\Filament\Support\PostResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SolutionResource extends PostResource
{
    protected static string $kind = 'solution';

    protected static ?string $modelLabel = 'giải pháp';

    protected static ?string $pluralModelLabel = 'Giải pháp';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolutions::route('/'),
            'create' => Pages\CreateSolution::route('/create'),
            'edit' => Pages\EditSolution::route('/{record}/edit'),
        ];
    }
}
