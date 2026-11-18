<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Support\AdminActor;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(AdminActor::current()->canPerform('content_publish')), CreateAction::make()];
    }
}
