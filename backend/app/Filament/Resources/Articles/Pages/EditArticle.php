<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    use ManagesPostPublication;
    use SavesAndStays;

    protected static string $resource = ArticleResource::class;
}
