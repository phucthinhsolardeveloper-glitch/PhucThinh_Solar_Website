<?php

namespace App\Console\Commands;

use App\Support\RetiredLegacyContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PruneRetiredContentCommand extends Command
{
    protected $signature = 'pts:prune-retired-content
        {--apply : Back up and remove retired Admin records}
        {--isolated-candidate : Permit the named local candidate database even when its APP_ENV is production}';

    protected $description = 'Report or prune banner and page-item types without a current public consumer.';

    public function handle(): int
    {
        $banners = DB::table('banners')->whereIn('placement', RetiredLegacyContent::BANNER_PLACEMENTS);
        $items = DB::table('page_items')->where('section', 'like', RetiredLegacyContent::PAGE_SECTION_PREFIX.'%');
        $this->line('Ảnh vị trí không còn dùng: '.$banners->count());
        $this->line('Nội dung archive: '.$items->count());

        if (! $this->option('apply')) {
            $this->info('Chưa xóa dữ liệu. Dùng --apply để lưu bản sao và dọn bản ghi.');

            return self::SUCCESS;
        }
        $candidate = $this->option('isolated-candidate') && DB::getDatabaseName() === 'pts_laravel13_candidate';
        if (($this->option('isolated-candidate') && ! $candidate) || (app()->environment('production') && ! $candidate)) {
            $this->error('Lệnh dọn dữ liệu chỉ chạy trên clone/staging hoặc DB candidate riêng đã xác minh.');

            return self::FAILURE;
        }

        $backup = DB::transaction(function (): ?string {
            $banners = DB::table('banners')->whereIn('placement', RetiredLegacyContent::BANNER_PLACEMENTS)->lockForUpdate()->get();
            $items = DB::table('page_items')->where('section', 'like', RetiredLegacyContent::PAGE_SECTION_PREFIX.'%')->lockForUpdate()->get();
            if ($banners->isEmpty() && $items->isEmpty()) {
                return null;
            }
            $dir = storage_path('app/private/cleanup');
            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Không tạo được thư mục backup cleanup.');
            }
            $path = $dir.'/retired-content-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.json';
            $payload = json_encode(['banners' => $banners, 'page_items' => $items], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (file_put_contents($path, $payload, LOCK_EX) === false) {
                throw new \RuntimeException('Không ghi được backup cleanup.');
            }
            if (! chmod($path, 0600)) {
                throw new \RuntimeException('Không bảo vệ được quyền đọc backup cleanup.');
            }
            DB::table('banners')->whereIn('id', $banners->pluck('id'))->delete();
            DB::table('page_items')->whereIn('id', $items->pluck('id'))->delete();

            return $path;
        });

        $this->info($backup ? 'Đã dọn bản ghi. Backup: '.$backup : 'Không có bản ghi cần dọn.');

        return self::SUCCESS;
    }
}
