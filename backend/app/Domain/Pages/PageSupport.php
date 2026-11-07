<?php

namespace App\Domain\Pages;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Support\LegacyText;
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

    /** SEO of a fixed page from table_seopage (ptsPublicSeoDto with the seopage folder). */
    protected function pageSeo(string $type, callable $text): array
    {
        $row = $this->row("select coalesce(title,'') as titlevi, coalesce(description,'') as descriptionvi, coalesce(keywords,'') as keywordsvi, coalesce(image,'') as photo from seo_metas where page=? limit 0,1", [$type]);

        return [
            'title' => $text(LegacyText::flat($row['titlevi'] ?? '')),
            'description' => $text(LegacyText::flat($row['descriptionvi'] ?? '')),
            'keywords' => $text(LegacyText::flat($row['keywordsvi'] ?? '')),
            'image' => $this->urls->visual($row['photo'] ?? '', 'upload/seopage/'),
        ];
    }

    /** File name that must start with a letter or digit (ptsDealerMedia, ptsAboutImage). */
    protected function strictImage(mixed $value, string $folder): string
    {
        $file = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file)) {
            return '';
        }

        return $this->urls->exists($folder.$file) ? '/'.$folder.$file : '';
    }

    /** Any file name with an image extension (ptsContactAsset, ptsHomePath). */
    protected function looseImage(mixed $value, string $folder): string
    {
        $file = is_scalar($value) ? trim((string) $value) : '';
        if ($file === '' || basename($file) !== $file || ! preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $file)) {
            return '';
        }

        return $this->urls->exists($folder.$file) ? '/'.$folder.$file : '';
    }

    protected static function visible(array $row): bool
    {
        return in_array('hienthi', array_filter(array_map('trim', explode(',', (string) ($row['status'] ?? '')))), true);
    }
}
