<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\SeoMeta;
use App\Rules\FreePublicSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductDuplicator
{
    public function duplicate(Product $source): Product
    {
        return DB::transaction(function () use ($source): Product {
            $source = Product::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();
            $copy = $source->replicate();
            $copy->name = mb_substr($source->name, 0, 240).' (bản sao)';
            $copy->slug = $this->newSlug($source->slug);
            $copy->code = null;
            $copy->is_visible = false;
            $copy->is_bestseller = false;
            $copy->views = 0;
            $copy->save();

            foreach ($source->images as $image) {
                $copy->images()->save($image->replicate());
            }
            foreach ($source->documents as $document) {
                $copy->documents()->save($document->replicate());
            }
            if ($section = $source->contentSection) {
                $copy->contentSection()->save($section->replicate());
            }
            if ($seo = $source->seo) {
                /** @var SeoMeta $seoCopy */
                $seoCopy = $seo->replicate();
                $seoCopy->page = null;
                $copy->seo()->save($seoCopy);
            }

            return $copy;
        });
    }

    private function newSlug(string $source): string
    {
        $stem = rtrim(substr($source, 0, 225), '-');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $slug = $stem.'-ban-sao-'.bin2hex(random_bytes(8));
            if (Validator::make(['slug' => $slug], ['slug' => [new FreePublicSlug('products')]])->passes()) {
                return $slug;
            }
        }

        throw ValidationException::withMessages(['slug' => 'Không tạo được đường dẫn bản sao duy nhất. Vui lòng thử lại.']);
    }
}
