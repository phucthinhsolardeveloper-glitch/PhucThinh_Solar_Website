<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\Listing\CatalogFilters;
use App\Domain\Catalog\Listing\ProductCard;
use App\Domain\Catalog\PricingPolicy;
use App\Domain\Media\MediaLibrary;
use App\Domain\Pages\PageSupport;
use App\Legacy\Support\LegacyText;
use App\Support\FormToken;
use App\Support\SiteSettings;

/**
 * Product page extras (api/pts_v1_product_extras.php): related products, warranty links, reviews with
 * photos, videos and replies (paged), commitments, consultation needs and a form token.
 */
class ProductExtras extends PageSupport
{
    private const OFFSET = '/^(?:0|[1-9][0-9]{0,5})$/';

    private const VIDEO_EXTENSIONS = ['mp4', 'mkv'];

    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly ProductSpecs $specs, private readonly ProductCard $cards)
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

        $row = $this->row("select id,namevi,slugvi,id_list,pdp_profile,video_url,warranty,chinhsachmuahangvi,status,type from table_product where slugvi=? and type='san-pham' and find_in_set('hienthi',status) limit 1", [$slug]);
        if (! $row) {
            return [404, ['error' => ['code' => 'PRODUCT_NOT_FOUND']]];
        }
        $id = (int) $row['id'];
        $list = $this->row("select id,slugvi,pdp_profile from table_product_list where id=? and type='san-pham' limit 1", [(int) $row['id_list']]);
        $profileKey = $this->specs->profileKey($row, $list);
        $profile = $this->specs->profile($profileKey);
        $pricesVisible = $this->settings->productPricesVisible();

        $pricing = new PricingPolicy($pricesVisible);
        $related = [];
        foreach ($this->rows("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from table_product p where p.id<>? and p.id_list=? and p.type='san-pham' and find_in_set('hienthi',p.status)"
            .' order by '.CatalogFilters::defaultOrder(false).' limit 8', [$id, (int) $row['id_list']]) as $product) {
            if ($dto = $this->cards->dto($product, $pricing)) {
                $related[] = $dto;
            }
        }

        $override = LegacyText::plain($row['chinhsachmuahangvi'] ?? '');
        $policies = [];
        if ($override === '') {
            foreach ($this->rows("select namevi,slugvi,status from table_news where type='chinh-sach' and find_in_set('hienthi',status) order by numb,id desc") as $policy) {
                $name = LegacyText::plain($policy['namevi']);
                $policySlug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $policy['slugvi']) ? $policy['slugvi'] : '';
                if ($name !== '' && $policySlug !== '' && mb_stripos($name, 'bảo hành') !== false) {
                    $policies[] = ['label' => $name, 'href' => '/ho-tro/'.$policySlug.'/'];
                }
            }
        }

        $visibleReview = "id_variant=? and type='san-pham' and find_in_set('hienthi',status)";
        $totals = $this->row("select count(id) total,coalesce(sum(star),0) total_star from table_comment where id_parent=0 and {$visibleReview}", [$id]);
        $reviewTotal = (int) ($totals['total'] ?? 0);
        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($this->rows("select star,count(id) num from table_comment where id_parent=0 and {$visibleReview} group by star", [$id]) as $starRow) {
            if ((int) $starRow['star'] >= 1 && (int) $starRow['star'] <= 5) {
                $counts[(int) $starRow['star']] = (int) $starRow['num'];
            }
        }
        $reviews = [];
        foreach ($this->rows("select id,title,content,fullname,star,status,date_posted from table_comment where id_parent=0 and {$visibleReview} order by date_posted desc limit {$reviewOffset},10", [$id]) as $review) {
            $reviewId = (int) $review['id'];
            $photos = [];
            foreach ($this->rows('select photo from table_comment_photo where id_parent=? limit 3', [$reviewId]) as $photo) {
                if (($url = $this->photo($photo['photo'])) !== '') {
                    $photos[] = $url;
                }
            }
            $reviews[] = [
                'id' => $reviewId,
                'title' => LegacyText::plain($review['title']),
                'content' => LegacyText::plain($review['content']),
                'name' => LegacyText::plain($review['fullname']),
                'star' => max(1, min(5, (int) $review['star'])),
                'verified' => str_contains(','.$review['status'].',', ',verified-purchase,'),
                'date' => (int) $review['date_posted'],
                'photos' => $photos,
                'video' => $this->reviewVideo($this->row('select photo,video from table_comment_video where id_parent=? limit 1', [$reviewId])),
                'replies' => $this->replies($reviewId, $id, 0),
                'repliesTotal' => (int) ($this->row("select count(id) total from table_comment where id_parent=? and {$visibleReview}", [$reviewId, $id])['total'] ?? 0),
            ];
        }

        $replyPage = null;
        if ($replyParent > 0) {
            if (! $this->row("select id from table_comment where id=? and id_parent=0 and {$visibleReview} limit 1", [$replyParent, $id])) {
                return [404, ['error' => ['code' => 'REVIEW_NOT_FOUND']]];
            }
            $replyTotal = (int) ($this->row("select count(id) total from table_comment where id_parent=? and {$visibleReview}", [$replyParent, $id])['total'] ?? 0);
            $items = $this->replies($replyParent, $id, $replyOffset);
            $replyPage = ['parentId' => $replyParent, 'offset' => $replyOffset, 'total' => $replyTotal, 'hasMore' => $replyOffset + count($items) < $replyTotal, 'items' => $items];
        }

        $commitments = [];
        foreach ($this->rows("select namevi,photo,status from table_news where type='cam-ket' and find_in_set('hienthi',status) order by numb,id desc limit 8") as $commitment) {
            $name = LegacyText::plain($commitment['namevi']);
            if ($name !== '') {
                $commitments[] = ['name' => $name, 'image' => $this->photo($commitment['photo'])];
            }
        }
        $needs = [];
        foreach ((array) ($profile['needs'] ?? []) as $code => $label) {
            if (preg_match('/^[a-z0-9_-]{1,64}$/', (string) $code) && LegacyText::plain($label) !== '') {
                $needs[] = ['code' => (string) $code, 'label' => LegacyText::plain($label)];
            }
        }

        $payload = [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'productId' => $id,
            'profileKey' => $profileKey,
            'csrfToken' => FormToken::issue(),
            'videoEmbedUrl' => ($video = MediaLibrary::youtubeId($row['video_url'] ?? '', requireScheme: false)) !== '' ? 'https://www.youtube-nocookie.com/embed/'.$video : '',
            'warranty' => ['summary' => LegacyText::plain($row['warranty'] ?? ''), 'override' => $override, 'policies' => $policies],
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
        foreach ($this->rows("select fullname,content,poster,date_posted from table_comment where id_parent=? and id_variant=? and type='san-pham' and find_in_set('hienthi',status) order by date_posted desc limit {$offset},5", [$parent, $productId]) as $reply) {
            $content = LegacyText::plain($reply['content']);
            if ($content !== '') {
                $items[] = ['name' => LegacyText::plain($reply['fullname']), 'content' => $content, 'isAdmin' => $reply['poster'] === 'admin', 'date' => (int) $reply['date_posted']];
            }
        }

        return $items;
    }

    private function reviewVideo(array $row): ?array
    {
        if (! $row) {
            return null;
        }
        $file = is_scalar($row['video'] ?? null) ? basename(trim((string) $row['video'])) : '';
        if ($file === '' || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file) || ! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS, true)
            || ! $this->urls->exists('upload/video/'.$file)) {
            return null;
        }

        return ['url' => '/upload/video/'.$file, 'poster' => $this->photo($row['photo'] ?? '')];
    }

    private function photo(mixed $value): string
    {
        $file = is_scalar($value) ? basename(trim((string) $value)) : '';

        return $file !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file) && $this->urls->exists('upload/photo/'.$file) ? '/upload/photo/'.$file : '';
    }
}
