<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    use ManagesPostPublication;

    protected static string $resource = ArticleResource::class;
}
