<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\Text;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/** Small shared pieces of the editorial page APIs: queries, page SEO, safe file names. */
abstract class PageSupport
{
    protected Connection $db;

    public function __construct(protected readonly ProductMediaUrls $urls)
    {
        $this->db = DB::connection();
    }

    /** @return list<array<string, mixed>> */
    protected function rows(string $sql, array $bindings = []): array
    {
        return array_map(fn ($row) => (array) $row, $this->db->select($sql, $bindings));
    }

    /** @return array<string, mixed> */
    protected function row(string $sql, array $bindings = []): array
    {
        return (array) ($this->db->selectOne($sql, $bindings) ?? []);
    }

    /** SEO of a fixed page (seo_metas.page). */
    protected function pageSeo(string $type, callable $text): array
    {
        $row = $this->db->table('seo_metas')->where('page', $type)->first(['title', 'description', 'keywords', 'image']);

        return [
            'title' => $text(Text::flat((string) $row?->title)),
            'description' => $text(Text::flat((string) $row?->description)),
            'keywords' => $text(Text::flat((string) $row?->keywords)),
            'image' => $this->urls->visual((string) $row?->image, 'upload/seopage/'),
        ];
    }

    /** Bare file name that must start with a letter or digit, and must exist. */
    protected function strictImage(mixed $value, string $folder): string
    {
        $file = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file)) {
            return '';
        }

        return $this->urls->exists($folder.$file) ? '/'.$folder.$file : '';
    }

    /** Any bare file name with an image extension that exists. */
    protected function looseImage(mixed $value, string $folder): string
    {
        $file = is_scalar($value) ? trim((string) $value) : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $file)) {
            return '';
        }

        return $this->urls->exists($folder.$file) ? '/'.$folder.$file : '';
    }

    /**
     * Items of a page section in Admin order, with their own column names; text columns are never null.
     *
     * @return list<array{id: int, title: string, subtitle: string, summary: string, body: string, extra: string, figure: string, image: string, link: string, is_visible: bool, flags: list<string>}>
     */
    protected function section(string $section, int $limit = 0, bool $visibleOnly = false): array
    {
        $query = $this->db->table('page_items')->where('section', $section)->orderBy('sort_order')->orderByDesc('id');
        $visibleOnly && $query->where('is_visible', true);
        $limit > 0 && $query->limit($limit);

        return $query->get()->map(fn ($row) => [
            'id' => (int) $row->id, 'title' => (string) $row->title, 'subtitle' => (string) $row->subtitle, 'summary' => (string) $row->summary,
            'body' => (string) $row->body, 'extra' => (string) $row->extra, 'figure' => (string) $row->figure, 'image' => (string) $row->image,
            'link' => (string) $row->link, 'is_visible' => (bool) $row->is_visible, 'flags' => array_values((array) json_decode((string) $row->flags, true)),
        ])->all();
    }

    /**
     * Pictures of one placement in Admin order, with their own column names.
     *
     * @return list<array{id: int, title: string, summary: string, image: string, link: string, video_url: string, is_visible: bool, is_featured: bool, options: array<string, mixed>}>
     */
    protected function banners(string $placement, bool $visibleOnly = false): array
    {
        $query = $this->db->table('banners')->where('placement', $placement)->orderBy('sort_order')->orderByDesc('id');
        $visibleOnly && $query->where('is_visible', true);

        return $query->get()->map(fn ($row) => [
            'id' => (int) $row->id, 'title' => (string) $row->title, 'summary' => (string) $row->summary, 'image' => (string) $row->image,
            'link' => (string) $row->link, 'video_url' => (string) $row->video_url, 'is_visible' => (bool) $row->is_visible,
            'is_featured' => (bool) $row->is_featured, 'options' => (array) json_decode((string) $row->options, true),
        ])->all();
    }
}
