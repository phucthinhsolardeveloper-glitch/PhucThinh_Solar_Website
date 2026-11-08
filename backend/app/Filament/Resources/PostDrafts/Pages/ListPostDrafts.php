<?php

namespace App\Filament\Resources\PostDrafts\Pages;

use App\Filament\Resources\PostDrafts\PostDraftResource;
use Filament\Resources\Pages\ListRecords;

class ListPostDrafts extends ListRecords
{
    protected static string $resource = PostDraftResource::class;
}
