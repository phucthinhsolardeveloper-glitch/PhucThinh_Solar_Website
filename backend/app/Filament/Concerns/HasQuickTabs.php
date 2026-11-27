<?php

namespace App\Filament\Concerns;

use Closure;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** One tap shows one kind of record, with how many there are. "Tất cả" stays first so filter links keep working. */
trait HasQuickTabs
{
    /**
     * @param  array<string, array{0: string, 1: Closure(Builder<*>): Builder<*>}>  $kinds
     * @return array<string, Tab>
     */
    protected function quickTabs(array $kinds): array
    {
        $tabs = ['all' => Tab::make('Tất cả')];
        foreach ($kinds as $key => [$label, $scope]) {
            $tabs[$key] = Tab::make($label)->modifyQueryUsing($scope)
                ->badge(fn () => $scope(static::getResource()::getEloquentQuery())->count() ?: null);
        }

        return $tabs;
    }
}
