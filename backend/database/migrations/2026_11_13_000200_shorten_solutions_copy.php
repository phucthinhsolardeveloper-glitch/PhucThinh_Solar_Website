<?php

use App\Domain\Audit\Audit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shorter copy for the solutions hub and the five solution pages, approved by the owner on 2026-11-13 ("ok duyệt" on the
 * side-by-side table: no fact added, repeats and filler dropped). Each row is replaced only while it still holds the text
 * it had on production on 2026-11-13 (compared after stripping tags and collapsing spaces, by hash), so anything the owner
 * has edited in Admin since is left alone.
 */
return new class extends Migration
{
    /** @return list<array{table: string, where: array<string, string>, column: string, old: string, new: string}> */
    private function changes(): array
    {
        return [
            ['table' => 'page_items', 'where' => ['section' => 'solutions.cta'], 'column' => 'title', 'old' => 'b481c964192aac2005bde443583c26aa56d7d425', 'new' => 'Chưa biết chọn giải pháp nào?'],
            ['table' => 'page_items', 'where' => ['section' => 'solutions.cta'], 'column' => 'summary', 'old' => '8a08755e0bcaf47a5e8ec38f16b024ab72505a41', 'new' => 'Không cần tự chọn thiết bị. Chỉ cần cho biết nhu cầu dùng điện, đội kỹ thuật sẽ khảo sát, thiết kế và đề xuất phương án phù hợp trước khi triển khai.'],
            ['table' => 'page_items', 'where' => ['section' => 'solutions.intro'], 'column' => 'summary', 'old' => 'dbcac646b0f6202d5785574d9f25a6a2b4a4b188', 'new' => 'Mỗi công trình có nhu cầu khác nhau. Phúc Thịnh Solar thiết kế điện mặt trời, lưu trữ, chiếu sáng và bơm nước dựa trên mức dùng điện theo từng thời điểm, diện tích lắp đặt, điều kiện vận hành, mục tiêu đầu tư và khả năng mở rộng.'],
            ['table' => 'posts', 'where' => ['kind' => 'solution', 'slug' => 'dien-mat-troi-ho-gia-dinh'], 'column' => 'summary', 'old' => '6650a4dea6489f3390bc1b1de59fd0532515a5ca', 'new' => 'Tạo điện ngay trên mái nhà để giảm lượng điện mua từ lưới vào ban ngày. Có thể kết hợp pin lưu trữ để dùng điện linh hoạt hơn và duy trì thiết bị quan trọng khi mất điện.'],
            ['table' => 'posts', 'where' => ['kind' => 'solution', 'slug' => 'dien-mat-troi-doanh-nghiep'], 'column' => 'summary', 'old' => '0cc7d0ec8acd242ae9dfcede0020be8c88ba5bd0', 'new' => 'Mái rộng và dùng nhiều điện ban ngày là điều kiện tốt để tạo điện tại chỗ. Phúc Thịnh Solar khảo sát nhu cầu điện theo từng thời điểm và thiết kế hệ thống ưu tiên dùng ngay tại cơ sở.'],
            ['table' => 'posts', 'where' => ['kind' => 'solution', 'slug' => 'he-thong-luu-tru-bess'], 'column' => 'summary', 'old' => 'cef08ade3dab497dc2be8f0778b2d34bb7464f65', 'new' => 'Nắng chỉ có vào ban ngày, còn nhu cầu điện thay đổi theo từng giờ. Pin lưu trữ giữ lại điện để dùng khi cần, giúp gia đình và doanh nghiệp chủ động quản lý năng lượng.'],
            ['table' => 'posts', 'where' => ['kind' => 'solution', 'slug' => 'chieu-sang-nang-luong-mat-troi'], 'column' => 'summary', 'old' => '37105b632413317c8c7b23884b2acafe47e14578', 'new' => 'Mỗi đèn tự lấy năng lượng tại chỗ, không cần kéo cáp. Phù hợp đường nội bộ, khu dân cư, sân vườn, công viên, khu công nghiệp và nơi xa nguồn điện.'],
            ['table' => 'posts', 'where' => ['kind' => 'solution', 'slug' => 'bom-nuoc-nang-luong-mat-troi'], 'column' => 'summary', 'old' => 'd8099c86c04c7cfde440889c223a0c6e2e65348e', 'new' => 'Nhu cầu bơm nước nông nghiệp thường vào ban ngày, đúng lúc có nắng. Bơm năng lượng mặt trời dùng nguồn năng lượng tại chỗ cho tưới tiêu, cấp nước, kể cả nơi khó tiếp cận điện lưới.'],
        ];
    }

    public function up(): void
    {
        $done = [];
        foreach ($this->changes() as $change) {
            $row = DB::table($change['table'])->where($change['where'])->first();
            if ($row === null) {
                continue;
            }
            $current = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $row->{$change['column']}, ENT_QUOTES, 'UTF-8'))));
            if (sha1($current) !== $change['old']) {
                continue; // already changed, by this migration or by the owner
            }
            DB::table($change['table'])->where('id', $row->id)->update([$change['column'] => $change['new'], 'updated_at' => now()]);
            $done[] = $change['table'].'#'.$row->id.'.'.$change['column'];
        }
        if ($done !== []) {
            Audit::record('content.solutions_copy_shortened', 'Post', null, null, ['changed' => $done], 'system:migration');
        }
    }

    public function down(): void
    {
        // Content edit approved by the owner; restore from the pre-deploy backup if it must be undone.
    }
};
