<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\AdminActor;
use App\Filament\Support\PublishGate;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! AdminActor::current()->canPerform('content_publish')) {
            $data['is_visible'] = false;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        PublishGate::afterSave($this->getRecord(), false);
    }
}
