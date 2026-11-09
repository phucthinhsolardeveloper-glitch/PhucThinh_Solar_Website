<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\PricingPolicy;
use App\Models\Product;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * Builds the /api/v1/product-detail payload (frontend/shared/contracts/public-product-detail.ts).
 */
class ProductDetailAssembler
{
    public function __construct(
        private readonly ProductContentResolver $contentResolver,
        private readonly ProductContentPresenter $contentPresenter,
        private readonly ProductMediaUrls $urls,
        private readonly SiteSettings $settings,
        private readonly ProductSpecs $specs,
        private readonly ProductApplications $applications,
        private readonly CategoryTree $tree,
    ) {}

    /** @return array<string, mixed>|null null when no visible product has this slug */
    public function forSlug(string $slug): ?array
    {
        $db = DB::connection();
        $product = $db->table('products')->where('slug', $slug)->where('is_visible', true)->first();
        if (! $product) {
            return null;
        }
        $row = ProductRecord::from((array) $product, $this->tree);
        $id = (int) $row['id'];
        $name = Text::plain($row['name']);
        $slug = Text::slug($row['slug']);
        if ($name === '' || $slug === '') {
            return null;
        }

        $root = $row['top_category_id'] ? $this->tree->get($row['top_category_id']) : null;
        $list = $root && $root['is_visible'] ? ['id' => $root['id'], 'slug' => $root['slug'], 'pdp_profile' => $root['pdp_profile'] ?? ''] : [];
        $brand = $row['brand_id'] > 0 ? $db->table('brands')->where('id', $row['brand_id'])->where('is_visible', true)->value('name') : '';
        $seo = (array) ($db->table('seo_metas')->where('owner_type', Product::class)->where('owner_id', $id)->first(['title', 'description', 'keywords']) ?? []);

        $profile = $this->specs->profileKey($row, $list);
        $sections = $this->contentResolver->resolve($row);
        $applications = $this->applications->cards($sections['blocks'], $row, $list, $profile);
        $content = $this->contentPresenter->present($sections, ! empty($applications));
        $content['highlights'] = $this->specs->highlights($row, $profile);

        return [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'product' => [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'url' => '/san-pham/'.$slug,
                'code' => Text::plain($row['code'] ?? ''),
                'summary' => Text::plain($row['summary']),
                'brand' => Text::plain($brand),
                'profile' => $profile,
                'media' => $this->media($row),
                'specifications' => $this->specs->table($row, $profile),
                'packageContents' => Text::plain($this->specs->packageContents($row['package_contents'] ?? '')),
                'decisionMetrics' => $this->specs->decisionMetrics($row, $profile),
            ],
            'breadcrumbs' => $this->breadcrumbs($row),
            'pricing' => (new PricingPolicy($this->settings->productPricesVisible()))->forProduct($row),
            'content' => $content,
            'applications' => $applications,
            'buyingGuides' => $this->applications->buyingGuides($row, $profile),
            'documents' => $this->applications->documents($db->table('product_documents')->where('product_id', $id)
                ->where('is_visible', true)->where('path', '<>', '')->orderBy('sort_order')->orderBy('id')
                ->get(['kind as document_type', 'title', 'path as file_path'])->map(fn ($r) => (array) $r)),
            'canonical' => ['url' => '/san-pham/'.$slug],
            'seo' => $this->seo($name, $seo),
        ];
    }

    /** @return list<string> main photo, second photo, then gallery (numb, newest first), files that exist */
    private function media(array $row): array
    {
        $media = [];
        foreach ([$row['image'], $row['image_secondary']] as $photo) {
            $media[] = $this->urls->product($photo);
        }
        $gallery = DB::table('product_images')->where('product_id', (int) $row['id'])->where('is_visible', true)
            ->orderBy('sort_order')->orderByDesc('id')->pluck('path');
        foreach ($gallery as $photo) {
            $media[] = $this->urls->product($photo);
        }

        return array_values(array_unique(array_filter($media)));
    }

    /** @return list<array{label: string, url: string}> visible categories from the top down to the product's own */
    private function breadcrumbs(array $row): array
    {
        $crumbs = [['label' => 'Sản phẩm', 'url' => '/san-pham/']];
        foreach ($row['category_id'] ? $this->tree->chain($row['category_id']) : [] as $node) {
            $label = Text::plain($node['name']);
            if ($node['is_visible'] && $label !== '') {
                $crumbs[] = ['label' => $label, 'url' => '/'.$this->tree->path((int) $node['id'])];
            }
        }

        return $crumbs;
    }

    /** @return array{title: string, description: string, keywords: string} */
    private function seo(string $name, array $seo): array
    {
        $title = ! empty($seo['title']) ? Text::seo($seo['title']) : Text::seo($name);
        if ($title !== '' && mb_stripos($title, 'phúc thịnh s') === false) {
            $title .= ' | Phúc Thịnh Solar';
        }

        return [
            'title' => $title,
            'description' => ! empty($seo['description']) ? Text::seo($seo['description']) : '',
            'keywords' => ! empty($seo['keywords']) ? Text::seo($seo['keywords']) : '',
        ];
    }
}
