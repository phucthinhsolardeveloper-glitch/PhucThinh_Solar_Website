<?php

namespace App\Console\Commands;

use App\Domain\Content\ContentTidy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the PHP editor's leftovers from stored content and reports pictures that no longer exist.
 *
 * Dry run by default: it says what it would change and shows examples, and writes nothing until
 * --apply. Run it after an import from the old site, or whenever Admin shows markup nobody typed.
 */
class TidyContentCommand extends Command
{
    protected $signature = 'pts:tidy-content {--apply : Write the changes (otherwise a dry run)} {--samples=5 : How many examples to print}';

    protected $description = 'Dọn mã thừa của bản PHP trong nội dung và báo ảnh đã mất file';

    /** Columns holding rich text, by table. */
    private const COLUMNS = [
        'products' => ['body', 'summary', 'specs_html', 'highlights', 'applications', 'installation', 'purchase_policy'],
        'posts' => ['body', 'summary', 'extra_body'],
        'page_items' => ['body', 'extra', 'summary'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $samples = max(0, (int) $this->option('samples'));
        $host = parse_url((string) config('site.canonical_origin'), PHP_URL_HOST) ?: 'phucthinhsolar.com';
        $hosts = array_unique([$host, 'phucthinhsolar.com']);
        $uploadRoot = rtrim((string) config('site.upload_path'), '/');

        $totals = array_fill_keys(ContentTidy::COUNTERS, 0);
        $rowsChanged = 0;
        $examples = [];
        $missing = [];

        foreach (self::COLUMNS as $table => $columns) {
            $available = array_values(array_intersect($columns, Schema::getColumnListing($table)));
            if ($available === []) {
                continue;
            }
            DB::table($table)->orderBy('id')->select(array_merge(['id'], $available))->chunk(200, function ($rows) use (
                $table, $available, $hosts, $uploadRoot, $apply, $samples, &$totals, &$rowsChanged, &$examples, &$missing
            ) {
                foreach ($rows as $row) {
                    $update = [];
                    foreach ($available as $column) {
                        $before = (string) ($row->$column ?? '');
                        if ($before === '') {
                            continue;
                        }
                        foreach (ContentTidy::uploadPaths($before) as $path) {
                            if ($uploadRoot !== '' && ! is_file($uploadRoot.substr($path, strlen('/upload')))) {
                                $missing[] = $table.' #'.$row->id.' → '.$path;
                            }
                        }
                        ['html' => $after, 'changes' => $changes] = ContentTidy::clean($before, $hosts);
                        if ($after === $before) {
                            continue;
                        }
                        foreach ($changes as $key => $count) {
                            $totals[$key] += $count;
                        }
                        $update[$column] = $after;
                        if (count($examples) < $samples) {
                            $examples[] = $table.'.'.$column.' #'.$row->id.': '.implode(', ',
                                array_map(fn ($k) => $k.'×'.$changes[$k], array_keys(array_filter($changes))));
                        }
                    }
                    if ($update !== []) {
                        $rowsChanged++;
                        if ($apply) {
                            DB::table($table)->where('id', $row->id)->update($update + ['updated_at' => now()]);
                        }
                    }
                }
            });
        }

        $labels = [
            'absolute_link' => 'Link tuyệt đối về chính site → đường dẫn tương đối',
            'image_size' => 'Kích thước ảnh từ style → thuộc tính width/height',
            'style_attr' => 'Thuộc tính style= (website vốn đã bỏ qua)',
            'class_attr' => 'Thuộc tính class= (website vốn đã bỏ qua)',
            'empty_paragraph' => 'Đoạn văn rỗng',
        ];
        $this->table(['Việc', 'Số lần'], array_map(fn ($k) => [$labels[$k], $totals[$k]], ContentTidy::COUNTERS));

        foreach (array_slice($examples, 0, $samples) as $example) {
            $this->line('  '.$example);
        }

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Ảnh trong nội dung không còn file ('.count($missing).'). Lệnh này không tự xóa, chủ website xem rồi quyết:');
            foreach (array_slice(array_unique($missing), 0, max(10, $samples)) as $line) {
                $this->line('  '.$line);
            }
        }

        $this->newLine();
        $this->info($apply
            ? 'Đã dọn '.$rowsChanged.' bản ghi.'
            : $rowsChanged.' bản ghi sẽ được dọn. Chạy lại với --apply để ghi.');

        return self::SUCCESS;
    }
}
