<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        // Products with orders or reviews are kept for history; hide them instead.
        return [DeleteAction::make()->hidden(fn (Product $record) => $record->reviews()->exists() || DB::table('order_items')->where('product_id', $record->id)->exists())];
    }
}
