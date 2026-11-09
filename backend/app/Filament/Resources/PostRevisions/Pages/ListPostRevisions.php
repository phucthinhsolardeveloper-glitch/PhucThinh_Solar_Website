<?php

namespace App\Filament\Resources\PostRevisions\Pages;

use App\Filament\Resources\PostRevisions\PostRevisionResource;
use Filament\Resources\Pages\ListRecords;

class ListPostRevisions extends ListRecords
{
    protected static string $resource = PostRevisionResource::class;
}
