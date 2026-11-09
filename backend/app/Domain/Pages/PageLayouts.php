<?php

namespace App\Domain\Pages;

use App\Domain\Audit\Audit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Section layout of Home and About: which fixed sections show, in what order and in which look.
 * Staff edit a draft, preview it, publish it; an older version comes back as a new draft.
 * With nothing published, the page uses the registry order (the page as it was built).
 */
final class PageLayouts
{
    /** Background of a light About section, so neighbours can be told apart after reordering. */
    public const TONES = ['default' => 'Theo thiết kế', 'white' => 'Nền trắng', 'muted' => 'Nền xám nhạt'];

    /**
     * Sections each page knows, in their default order. variants: first is the default.
     *
     * @var array<string, array{label: string, sections: array<string, array{label: string, variants: array<string, string>, required?: bool}>}>
     */
    public const PAGES = [
        'home' => ['label' => 'Trang chủ', 'sections' => [
            'hero' => ['label' => 'Ảnh đầu trang', 'variants' => ['default' => 'Mặc định'], 'required' => true],
            'catalog' => ['label' => 'Danh mục thiết bị', 'variants' => ['default' => 'Mặc định']],
            'energy' => ['label' => 'Chuỗi năng lượng (minh họa)', 'variants' => ['default' => 'Mặc định']],
            'campaigns' => ['label' => 'Chiến dịch nổi bật', 'variants' => ['default' => 'Mặc định']],
            'solutions' => ['label' => 'Giải pháp', 'variants' => ['default' => 'Mặc định']],
            'process' => ['label' => 'Quy trình làm việc', 'variants' => ['default' => 'Mặc định']],
            'dealer' => ['label' => 'Đăng ký đại lý', 'variants' => ['default' => 'Mặc định']],
            'about' => ['label' => 'Giới thiệu ngắn', 'variants' => ['default' => 'Mặc định']],
            'contact' => ['label' => 'Liên hệ', 'variants' => ['default' => 'Mặc định']],
        ]],
        'about' => ['label' => 'Giới thiệu', 'sections' => [
            'story' => ['label' => 'Về Phúc Thịnh', 'variants' => ['default' => 'Mặc định'], 'required' => true],
            'history' => ['label' => 'Hành trình phát triển', 'variants' => ['default' => 'Mặc định']],
            'people' => ['label' => 'Con người', 'variants' => self::TONES],
            'trust' => ['label' => 'Vì sao chọn chúng tôi', 'variants' => self::TONES],
            'brands' => ['label' => 'Thương hiệu đồng hành', 'variants' => ['default' => 'Mặc định']],
            'compass' => ['label' => 'Tầm nhìn, sứ mệnh', 'variants' => ['default' => 'Mặc định']],
            'certificates' => ['label' => 'Chứng nhận', 'variants' => self::TONES],
            'contact' => ['label' => 'Liên hệ', 'variants' => ['default' => 'Mặc định']],
        ]],
    ];

    /** @return list<array{type: string, visible: bool, variant: string}> registry order, everything shown */
    public static function defaults(string $page): array
    {
        return array_map(fn ($type) => ['type' => $type, 'visible' => true, 'variant' => array_key_first(self::PAGES[$page]['sections'][$type]['variants'])],
            array_keys(self::PAGES[$page]['sections']));
    }

    /**
     * Cleans a layout against the registry: unknown types dropped, missing ones added at the end
     * (shown), variants kept only when allowed, required sections always shown.
     *
     * @return list<array{type: string, visible: bool, variant: string}>
     */
    public static function normalise(string $page, mixed $sections): array
    {
        $known = self::PAGES[$page]['sections'];
        $out = [];
        foreach (is_array($sections) ? $sections : [] as $row) {
            $type = is_array($row) ? (string) ($row['type'] ?? '') : '';
            if (! isset($known[$type]) || isset($out[$type])) {
                continue;
            }
            $variant = (string) ($row['variant'] ?? '');
            $out[$type] = ['type' => $type, 'visible' => ! empty($known[$type]['required']) || (bool) ($row['visible'] ?? true),
                'variant' => isset($known[$type]['variants'][$variant]) ? $variant : (string) array_key_first($known[$type]['variants'])];
        }
        foreach (self::defaults($page) as $row) {
            $out[$row['type']] ??= $row;
        }
        // The opening section (hero, story) always stays first.
        uksort($out, fn ($a, $b) => (int) empty($known[$a]['required']) <=> (int) empty($known[$b]['required']));

        return array_values($out);
    }

    /** @return list<array{type: string, variant: string}> what the public page renders, in order */
    public function published(string $page): array
    {
        $row = DB::table('page_layouts')->where('page', $page)->where('state', 'published')->orderByDesc('version')->first();

        return self::visible($page, $row ? json_decode((string) $row->sections, true) : null);
    }

    /** @return list<array{type: string, variant: string}> */
    public static function visible(string $page, mixed $sections): array
    {
        return array_values(array_map(fn ($row) => ['type' => $row['type'], 'variant' => $row['variant']],
            array_filter(self::normalise($page, $sections), fn ($row) => $row['visible'])));
    }

    /** The open draft of a page, made from the published layout when there is none. */
    public function draft(string $page, User $actor): object
    {
        self::assertPage($page);
        abort_unless($actor->hasPermission('content'), 403);

        return DB::transaction(function () use ($page, $actor) {
            $open = DB::table('page_layouts')->where('page', $page)->where('state', 'draft')->lockForUpdate()->first();
            if ($open) {
                return $open;
            }
            $live = DB::table('page_layouts')->where('page', $page)->where('state', 'published')->orderByDesc('version')->first();
            $id = DB::table('page_layouts')->insertGetId(['page' => $page, 'version' => $this->nextVersion($page), 'state' => 'draft',
                'sections' => json_encode(self::normalise($page, $live ? json_decode((string) $live->sections, true) : null)),
                'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);

            return DB::table('page_layouts')->where('id', $id)->first();
        });
    }

    public function save(int $id, mixed $sections, User $actor): void
    {
        abort_unless($actor->hasPermission('content'), 403);
        $row = $this->open($id);
        DB::table('page_layouts')->where('id', $id)->update(['sections' => json_encode(self::normalise($row->page, $sections)), 'updated_at' => now()]);
    }

    public function publish(int $id, User $actor): void
    {
        abort_unless($actor->canPerform('content_publish'), 403);
        DB::transaction(function () use ($id, $actor) {
            $row = $this->open($id, lock: true);
            $before = DB::table('page_layouts')->where('page', $row->page)->where('state', 'published')->orderByDesc('version')->first();
            DB::table('page_layouts')->where('page', $row->page)->where('state', 'published')->update(['state' => 'archived', 'updated_at' => now()]);
            DB::table('page_layouts')->where('id', $id)->update(['state' => 'published', 'published_by' => $actor->id, 'published_at' => now(), 'updated_at' => now()]);
            Audit::record('page_layout.publish', 'PageLayout', $id, $before ? ['version' => $before->version, 'sections' => json_decode((string) $before->sections, true)] : null,
                ['page' => $row->page, 'version' => $row->version, 'sections' => json_decode((string) $row->sections, true)]);
        });
    }

    /** An earlier version comes back as the open draft; the public page changes only when it is published. */
    public function restoreAsDraft(int $id, User $actor): object
    {
        abort_unless($actor->canPerform('content_publish'), 403);
        $old = DB::table('page_layouts')->where('id', $id)->first();
        abort_unless($old && $old->state !== 'draft', 404);
        $draft = $this->draft((string) $old->page, $actor);
        DB::table('page_layouts')->where('id', $draft->id)->update(['sections' => $old->sections, 'updated_at' => now()]);

        return DB::table('page_layouts')->where('id', $draft->id)->first();
    }

    public function previewUrl(object $row): string
    {
        $signed = URL::temporarySignedRoute('page-layout.preview', now()->addMinutes(15), ['layout' => $row->id], absolute: false);

        return url(($row->page === 'home' ? '/xem-truoc/trang-chu/' : '/xem-truoc/gioi-thieu/').$row->id).'?'.parse_url($signed, PHP_URL_QUERY);
    }

    private function open(int $id, bool $lock = false): object
    {
        $row = DB::table('page_layouts')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if (! $row || $row->state !== 'draft') {
            throw ValidationException::withMessages(['layout' => 'Bản nháp này đã được xuất bản hoặc không còn.']);
        }

        return $row;
    }

    private function nextVersion(string $page): int
    {
        return (int) DB::table('page_layouts')->where('page', $page)->max('version') + 1;
    }

    private static function assertPage(string $page): void
    {
        abort_unless(isset(self::PAGES[$page]), 404);
    }
}
