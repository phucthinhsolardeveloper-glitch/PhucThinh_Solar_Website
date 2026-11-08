<?php

namespace App\Filament\Resources\ArticleDrafts\Pages;

use App\Filament\Resources\ArticleDrafts\ArticleDraftResource;
use Filament\Resources\Pages\ListRecords;

class ListArticleDrafts extends ListRecords
{
    protected static string $resource = ArticleDraftResource::class;
}
