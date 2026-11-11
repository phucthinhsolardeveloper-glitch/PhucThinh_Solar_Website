<?php

namespace App\Filament\Support;

trait CreatesPostWithPublicationGate
{
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
