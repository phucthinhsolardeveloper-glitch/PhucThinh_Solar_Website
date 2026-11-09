<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\Listing\CatalogFilters;
use App\Domain\Catalog\Listing\ProductCard;
use App\Domain\Catalog\PricingPolicy;
use App\Domain\Media\MediaLibrary;
use App\Domain\Pages\PageSupport;
use App\Support\FormToken;
use App\Support\SiteSettings;
use App\Support\Text;

/**
 * Product page extras: related products, warranty links, reviews with
 * photos, videos and replies (paged), commitments, consultation needs and a form token.
 */
class ProductExtras extends PageSupport
{
    private const OFFSET = '/^(?:0|[1-9][0-9]{0,5})$/';

    private const VIDEO_EXTENSIONS = ['mp4', 'mkv'];

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly ProductSpecs $specs, private readonly ProductCard $cards, private readonly CategoryTree $tree)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (! isset($get['slug']) || ! is_string($get['slug']) || array_diff(array_keys($get), ['slug', 'reviewOffset', 'replyParent', 'replyOffset'])) {
            return $bad;
        }
        $slug = trim($get['slug'], '/');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $bad;
        }
        $reviewOffset = 0;
        if (isset($get['reviewOffset'])) {
            if (! is_string($get['reviewOffset']) || ! preg_match(self::OFFSET, $get['reviewOffset'])) {
                return $bad;
            }
            $reviewOffset = (int) $get['reviewOffset'];
        }
        $replyParent = 0;
        $replyOffset = 0;
        if (isset($get['replyParent']) || isset($get['replyOffset'])) {
            if (! isset($get['replyParent'], $get['replyOffset']) || ! is_string($get['replyParent']) || ! preg_match('/^[1-9][0-9]{0,9}$/', $get['replyParent'])
                || ! is_string($get['replyOffset']) || ! preg_match(self::OFFSET, $get['replyOffset']) || isset($get['reviewOffset'])) {
                return $bad;
            }
            $replyParent = (int) $get['replyParent'];
            $replyOffset = (int) $get['replyOffset'];
        }

        $product = $this->row('select * from products where slug=? and is_visible=1 limit 1', [$slug]);
        if (! $product) {
            return [404, ['error' => ['code' => 'PRODUCT_NOT_FOUND']]];
        }
        $row = ProductRecord::from($product, $this->tree);
        $id = (int) $row['id'];
        $root = $row['top_category_id'] ? $this->tree->get($row['top_category_id']) : null;
        $list = $root ? ['id' => $root['id'], 'slug' => $root['slug'], 'pdp_profile' => $root['pdp_profile'] ?? ''] : [];
        $profileKey = $this->specs->profileKey($row, $list);
        $profile = $this->specs->profile($profileKey);
        $pricesVisible = $this->settings->productPricesVisible();

        $pricing = new PricingPolicy($pricesVisible);
        $related = [];
        $siblings = $row['top_category_id'] ? $this->tree->descendantIds($row['top_category_id']) : [0];
        foreach ($this->rows('select '.CatalogFilters::CARD_BASE.' from products p where p.id<>? and p.is_visible=1 and p.category_id in ('.implode(',', array_fill(0, count($siblings), '?')).')'
            .' order by '.CatalogFilters::defaultOrder().' limit 8', [$id, ...$siblings]) as $product) {
            if ($dto = $this->cards->dto($product, $pricing)) {
                $related[] = $dto;
            }
        }

        $override = Text::plain($row['purchase_policy']);
        $policies = [];
        if ($override === '') {
            foreach ($this->db->table('posts')->where('kind', 'policy')->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get(['title', 'slug']) as $policy) {
                $name = Text::plain($policy->title);
                $policySlug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $policy->slug) ? $policy->slug : '';
                if ($name !== '' && $policySlug !== '' && mb_stripos($name, 'bảo hành') !== false) {
                    $policies[] = ['label' => $name, 'href' => '/ho-tro/'.$policySlug.'/'];
                }
            }
        }

        $visibleReview = "product_id=? and status='approved'";
        $totals = $this->row("select count(id) total,coalesce(sum(stars),0) total_star from reviews where parent_id is null and {$visibleReview}", [$id]);
        $reviewTotal = (int) ($totals['total'] ?? 0);
        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($this->rows("select stars as star,count(id) num from reviews where parent_id is null and {$visibleReview} group by stars", [$id]) as $starRow) {
            if ((int) $starRow['star'] >= 1 && (int) $starRow['star'] <= 5) {
                $counts[(int) $starRow['star']] = (int) $starRow['num'];
            }
        }
        $reviews = [];
        foreach ($this->rows("select id, coalesce(title,'') as title, content, name, stars, verified_purchase, created_at from reviews where parent_id is null and {$visibleReview} order by created_at desc, id desc limit {$reviewOffset},10", [$id]) as $review) {
            $reviewId = (int) $review['id'];
            $photos = [];
            foreach ($this->rows("select path from review_media where review_id=? and kind='photo' order by id limit 3", [$reviewId]) as $photo) {
                if (($url = $this->photo($photo['path'])) !== '') {
                    $photos[] = $url;
                }
            }
            $reviews[] = [
                'id' => $reviewId,
                'title' => Text::plain($review['title']),
                'content' => Text::plain($review['content']),
                'name' => Text::plain($review['name']),
                'star' => max(1, min(5, (int) $review['stars'])),
                'verified' => (bool) $review['verified_purchase'],
                'date' => (int) strtotime((string) $review['created_at']),
                'photos' => $photos,
                'video' => $this->reviewVideo($this->row("select coalesce(poster,'') as poster, path from review_media where review_id=? and kind='video' order by id limit 1", [$reviewId])),
                'replies' => $this->replies($reviewId, $id, 0),
                'repliesTotal' => (int) ($this->row("select count(id) total from reviews where parent_id=? and {$visibleReview}", [$reviewId, $id])['total'] ?? 0),
            ];
        }

        $replyPage = null;
        if ($replyParent > 0) {
            if (! $this->row("select id from reviews where id=? and parent_id is null and {$visibleReview} limit 1", [$replyParent, $id])) {
                return [404, ['error' => ['code' => 'REVIEW_NOT_FOUND']]];
            }
            $replyTotal = (int) ($this->row("select count(id) total from reviews where parent_id=? and {$visibleReview}", [$replyParent, $id])['total'] ?? 0);
            $items = $this->replies($replyParent, $id, $replyOffset);
            $replyPage = ['parentId' => $replyParent, 'offset' => $replyOffset, 'total' => $replyTotal, 'hasMore' => $replyOffset + count($items) < $replyTotal, 'items' => $items];
        }

        $commitments = [];
        foreach ($this->section('product.commitments', 8, true) as $commitment) {
            $name = Text::plain($commitment['title']);
            if ($name !== '') {
                $commitments[] = ['name' => $name, 'image' => $this->photo($commitment['image'])];
            }
        }
        $needs = [];
        foreach ((array) ($profile['needs'] ?? []) as $code => $label) {
            if (preg_match('/^[a-z0-9_-]{1,64}$/', (string) $code) && Text::plain($label) !== '') {
                $needs[] = ['code' => (string) $code, 'label' => Text::plain($label)];
            }
        }

        $payload = [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'productId' => $id,
            'profileKey' => $profileKey,
            'csrfToken' => FormToken::issue(),
            'videoEmbedUrl' => ($video = MediaLibrary::youtubeId($row['video_url'] ?? '', requireScheme: false)) !== '' ? 'https://www.youtube-nocookie.com/embed/'.$video : '',
            'warranty' => ['summary' => Text::plain($row['warranty'] ?? ''), 'override' => $override, 'policies' => $policies],
            'related' => $related,
            'reviews' => [
                'total' => $reviewTotal,
                'average' => $reviewTotal > 0 ? round(((float) ($totals['total_star'] ?? 0)) / $reviewTotal, 1) : 0,
                'counts' => $counts,
                'requireOrderCode' => (string) ($this->settings->options()['review_require_order_code'] ?? '') === '1',
                'offset' => $reviewOffset,
                'hasMore' => $reviewOffset + count($reviews) < $reviewTotal,
                'items' => $reviews,
            ],
            'consultation' => ['needs' => $needs],
            'commitments' => $commitments,
        ];
        if ($replyPage !== null) {
            $payload['replyPage'] = $replyPage;
        }

        return [200, $payload];
    }

    /** Up to five published replies, newest first, admin answers flagged. */
    private function replies(int $parent, int $productId, int $offset): array
    {
        $items = [];
        foreach ($this->rows("select name as fullname, content, if(by_staff,'admin','') as poster, created_at as date_posted from reviews where parent_id=? and product_id=? and status='approved' order by created_at desc, id desc limit {$offset},5", [$parent, $productId]) as $reply) {
            $content = Text::plain($reply['content']);
            if ($content !== '') {
                $items[] = ['name' => Text::plain($reply['fullname']), 'content' => $content, 'isAdmin' => $reply['poster'] === 'admin', 'date' => (int) strtotime((string) $reply['date_posted'])];
            }
        }

        return $items;
    }

    private function reviewVideo(array $row): ?array
    {
        if (! $row) {
            return null;
        }
        $file = is_scalar($row['path'] ?? null) ? basename(trim((string) $row['path'])) : '';
        if ($file === '' || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file) || ! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS, true)
            || ! $this->urls->exists('upload/video/'.$file)) {
            return null;
        }

        return ['url' => '/upload/video/'.$file, 'poster' => $this->photo($row['poster'] ?? '')];
    }

    private function photo(mixed $value): string
    {
        $file = is_scalar($value) ? basename(trim((string) $value)) : '';

        return $file !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file) && $this->urls->exists('upload/photo/'.$file) ? '/upload/photo/'.$file : '';
    }
}
