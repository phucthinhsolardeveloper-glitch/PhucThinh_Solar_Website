<?php

namespace App\Domain\Catalog\Listing;

/**
 * The validated query of GET /api/v1/catalog. Unknown keys,
 * arrays, empty values and malformed lists are refused (BAD_QUERY) rather than silently ignored.
 */
final class CatalogRequest
{
    public const NEEDS = ['du-an-cong-trinh', 'chieu-sang-gia-dinh', 'san-vuon-canh-quan'];

    private const KEYS = ['category', 'page', 'per_page', 'brand', 'price', 'subcategory', 'sort', 'keyword', 'is_need'];

    private const ID_LIST = '/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/';

    /**
     * @param  list<int>  $brandIds
     * @param  list<int>  $subcategoryIds
     * @param  list<string>  $needKeys
     */
    private function __construct(
        public readonly string $categoryPath,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $brandIds,
        public readonly array $subcategoryIds,
        public readonly string $price,
        public readonly string $sort,
        public readonly string $keyword,
        public readonly array $needKeys,
    ) {}

    /** @return self|null null means BAD_QUERY */
    public static function fromQuery(array $query): ?self
    {
        foreach ($query as $key => $value) {
            if (! in_array($key, self::KEYS, true) || ! is_string($value) || trim($value) === '') {
                return null;
            }
        }
        $get = fn (string $key) => $query[$key] ?? null;
        $category = trim((string) $get('category'), '/');
        if ($category !== '' && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/', $category)) {
            return null;
        }
        foreach (['page', 'per_page'] as $key) {
            if ($get($key) !== null && ! preg_match('/^[1-9][0-9]*$/', $get($key))) {
                return null;
            }
        }
        $page = (int) ($get('page') ?? 1);
        $perPage = (int) ($get('per_page') ?? 20);
        if ($page > 10000 || $perPage > 48) {
            return null;
        }
        if (($get('brand') !== null && ! preg_match(self::ID_LIST, $get('brand')))
            || ($get('subcategory') !== null && ! preg_match(self::ID_LIST, $get('subcategory')))
            || ($get('price') !== null && ! preg_match('/^[123](?:,[123])*$/', $get('price')))
            || ($get('sort') !== null && ! preg_match('/^[a-z-]+$/', $get('sort')))
            || ($get('keyword') !== null && mb_strlen(trim($get('keyword'))) > 100)) {
            return null;
        }
        $needs = [];
        if ($get('is_need') !== null) {
            $needs = explode(',', $get('is_need'));
            if (array_diff($needs, self::NEEDS) || count(array_unique($needs)) !== count($needs)) {
                return null;
            }
        }
        $ids = fn (?string $value) => $value === null ? [] : array_values(array_unique(array_map('intval', explode(',', $value))));

        return new self(
            categoryPath: $category,
            page: $page,
            perPage: $perPage,
            brandIds: $ids($get('brand')),
            subcategoryIds: $ids($get('subcategory')),
            price: (string) $get('price'),
            sort: in_array($get('sort'), ['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'], true) ? $get('sort') : 'default',
            keyword: trim((string) $get('keyword')),
            needKeys: $needs,
        );
    }
}
