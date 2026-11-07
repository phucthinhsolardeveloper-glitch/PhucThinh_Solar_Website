<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\PricingPolicy;
use App\Legacy\Catalog\ProductContentResolver;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;
use App\Legacy\Support\StatusFlags;
use Illuminate\Support\Facades\DB;

/**
 * Builds the /api/v1/product-detail payload, same contract as api/pts_v1_product_detail.php
 * (frontend/shared/contracts/public-product-detail.ts). Sections still served only by PHP are marked
 * in PORTED so the parity report shows exactly what is left.
 */
class ProductDetailAssembler
{
    /** Sections not ported yet; they return empty values until they are. */
    public const NOT_PORTED = [];

    public function __construct(
        private readonly ProductContentResolver $contentResolver,
        private readonly ProductContentPresenter $contentPresenter,
        private readonly ProductMediaUrls $urls,
        private readonly LegacySettings $settings,
        private readonly ProductSpecs $specs,
        private readonly ProductApplications $applications,
    ) {}

    /** @return array<string, mixed>|null null when no visible product has this slug */
    public function forSlug(string $slug): ?array
    {
        $db = DB::connection('legacy');
        $row = $db->table('table_product')->where('slugvi', $slug)->where('type', 'san-pham')
            ->whereRaw("find_in_set('hienthi', status)")->first();
        if (! $row) {
            return null;
        }
        $row = (array) $row;
        $id = (int) $row['id'];
        $name = LegacyText::plain($row['namevi'] ?? '');
        $slug = LegacyText::slug($row['slugvi'] ?? '');
        if ($name === '' || $slug === '' || ! (new StatusFlags($row['status'] ?? ''))->visible()) {
            return null;
        }

        $list = (array) ($db->table('table_product_list')->where('id', (int) $row['id_list'])->where('type', 'san-pham')
            ->whereRaw("find_in_set('hienthi', status)")->first(['id', 'slugvi', 'pdp_profile']) ?? []);
        $brand = (int) $row['id_brand'] > 0 ? $db->table('table_product_brand')->where('id', (int) $row['id_brand'])
            ->where('type', 'san-pham')->whereRaw("find_in_set('hienthi', status)")->value('namevi') : '';
        $seo = (array) ($db->table('table_seo')->where('id_parent', $id)->where('com', 'product')->where('act', 'man')
            ->where('type', $row['type'])->first(['titlevi', 'descriptionvi', 'keywordsvi']) ?? []);

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
                'code' => LegacyText::plain($row['code'] ?? ''),
                'summary' => LegacyText::plain($row['descvi'] ?? ''),
                'brand' => LegacyText::plain($brand),
                'profile' => $profile,
                'media' => $this->media($row),
                'specifications' => $this->specs->table($row, $profile),
                'packageContents' => LegacyText::plain($this->specs->packageContents($row['package_contents'] ?? '')),
                'decisionMetrics' => $this->specs->decisionMetrics($row, $profile),
            ],
            'breadcrumbs' => $this->breadcrumbs($row),
            'pricing' => (new PricingPolicy($this->settings->productPricesVisible()))->forProduct($row),
            'content' => $content,
            'applications' => $applications,
            'buyingGuides' => $this->applications->buyingGuides($row, $profile),
            'documents' => $this->applications->documents($db->table('table_product_document')->where('id_product', $id)
                ->whereRaw("find_in_set('hienthi', status)")->where('file_path', '<>', '')->orderBy('numb')->orderBy('id')
                ->get(['document_type', 'titlevi as title', 'file_path'])->map(fn ($r) => (array) $r)),
            'canonical' => ['url' => '/san-pham/'.$slug, 'sources' => [
                'product' => 'publicProductEntityPath(product)',
                'routeResolver' => 'public_route_resolver.php',
                'categoryUrls' => 'product_category_urls.php',
            ]],
            'seo' => $this->seo($name, $seo),
            'parity' => [
                'quote' => ['included' => false, 'reason' => 'legacy_quote_flow_stays_php'],
                'reviews' => ['included' => false, 'reason' => 'legacy_comment_mutation_stays_php'],
                'related' => ['included' => false, 'reason' => 'legacy_merchandising_order_not_verified'],
                'jsonLd' => ['included' => false, 'reason' => 'php_structured_data_authority_not_delegated'],
            ],
        ];
    }

    /** @return list<string> main photo, second photo, then gallery (numb, newest first), files that exist */
    private function media(array $row): array
    {
        $media = [];
        foreach ([$row['photo'] ?? '', $row['photo2'] ?? ''] as $photo) {
            $media[] = $this->urls->product($photo);
        }
        $gallery = DB::connection('legacy')->table('table_gallery')->where('id_parent', (int) $row['id'])
            ->where('com', 'product')->where('kind', 'man')->where('val', 'san-pham')
            ->whereRaw("find_in_set('hienthi', status)")->orderBy('numb')->orderByDesc('id')->pluck('photo');
        foreach ($gallery as $photo) {
            $media[] = $this->urls->product($photo);
        }

        return array_values(array_unique(array_filter($media)));
    }

    /** @return list<array{label: string, url: string}> */
    private function breadcrumbs(array $row): array
    {
        $crumbs = [['label' => 'Sản phẩm', 'url' => '/san-pham/']];
        $db = DB::connection('legacy');
        $levels = ['id_list' => 'list', 'id_cat' => 'cat', 'id_item' => 'item', 'id_sub' => 'sub'];
        foreach ($levels as $column => $level) {
            $id = (int) ($row[$column] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = $db->table('table_product_'.$level)->where('id', $id)->where('type', 'san-pham')
                ->whereRaw("find_in_set('hienthi', status)")->value('namevi');
            if ($label === null) {
                continue;
            }
            $path = $this->categoryPath($level, $id);
            $label = LegacyText::plain($label);
            if ($label !== '' && $path !== '') {
                $crumbs[] = ['label' => $label, 'url' => '/'.$path.'/'];
            }
        }

        return $crumbs;
    }

    /** danh-muc-san-pham/<list>/<cat>/... for a category level, as publicProductEntityPath() builds it. */
    private function categoryPath(string $level, int $id): string
    {
        $chain = ['list' => [], 'cat' => ['list'], 'item' => ['list', 'cat'], 'sub' => ['list', 'cat', 'item']][$level];
        $db = DB::connection('legacy');
        $record = $db->table('table_product_'.$level)->where('id', $id)->where('type', 'san-pham')->first();
        if (! $record) {
            return '';
        }
        $parts = ['danh-muc-san-pham'];
        foreach ($chain as $parent) {
            $parentSlug = $db->table('table_product_'.$parent)->where('id', (int) ($record->{'id_'.$parent} ?? 0))->value('slugvi');
            if ($parentSlug === null) {
                return '';
            }
            if ($parentSlug !== '') {
                $parts[] = trim($parentSlug, '/');
            }
        }
        if (! empty($record->slugvi)) {
            $parts[] = trim($record->slugvi, '/');
        }

        return implode('/', $parts);
    }

    /** @return array{title: string, description: string, keywords: string} */
    private function seo(string $name, array $seo): array
    {
        $title = ! empty($seo['titlevi']) ? LegacyText::seo($seo['titlevi']) : LegacyText::seo($name);
        if ($title !== '' && mb_stripos($title, 'phúc thịnh s') === false) {
            $title .= ' | Phúc Thịnh Solar';
        }

        return [
            'title' => $title,
            'description' => ! empty($seo['descriptionvi']) ? LegacyText::seo($seo['descriptionvi']) : '',
            'keywords' => ! empty($seo['keywordsvi']) ? LegacyText::seo($seo['keywordsvi']) : '',
        ];
    }
}
