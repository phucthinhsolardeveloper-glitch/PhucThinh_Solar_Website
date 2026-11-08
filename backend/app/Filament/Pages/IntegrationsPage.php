<?php

namespace App\Filament\Pages;

use App\Domain\Integrations\IntegrationReadiness;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IntegrationsPage extends Page
{
    protected static ?string $title = 'Tích hợp';

    protected static ?string $navigationLabel = 'Tích hợp';

    protected static ?string $slug = 'tich-hop';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected string $view = 'filament.pages.integrations';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('settings');
    }

    /** @return list<array{label:string,state:string,detail:string}> */
    public function statuses(): array
    {
        return app(IntegrationReadiness::class)->all();
    }
}
