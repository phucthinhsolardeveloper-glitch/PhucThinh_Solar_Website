<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Support\PostResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProjectResource extends PostResource
{
    protected static string $kind = 'project';

    protected static ?string $modelLabel = 'dự án';

    protected static ?string $pluralModelLabel = 'Dự án';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
