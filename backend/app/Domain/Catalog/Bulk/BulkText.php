<?php

namespace App\Domain\Catalog\Bulk;

/** Conversions between what staff type in a spreadsheet cell and what is stored. */
final class BulkText
{
    public static function htmlToText(?string $html): string
    {
        $value = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = (string) preg_replace('~<br\s*/?>~iu', "\n", $value);
        $value = (string) preg_replace('~</(?:p|div|h[1-6]|tr|li)>~iu', "\n", $value);
        $value = (string) preg_replace('~<li\b[^>]*>~iu', '- ', $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::cleanLines($value);
    }

    public static function textToHtml(string $text): string
    {
        if (self::hasTags($text)) {
            return $text;
        }
        $paragraphs = array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn ($line) => $line !== '');

        return implode("\n", array_map(fn ($line) => '<p>'.e($line).'</p>', $paragraphs));
    }

    /** A value like "<105 kg" is not markup; only real element tags count. */
    public static function hasTags(string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][a-z0-9]*(?:\s[^>]*)?>/iu', $value);
    }

    public static function plain(string $value): string
    {
        return trim(self::hasTags($value) ? strip_tags($value) : $value);
    }

    public static function cleanLines(string $value): string
    {
        $lines = [];
        foreach (preg_split('/\R/u', $value) ?: [] as $line) {
            $line = trim((string) preg_replace('/[\t ]+/u', ' ', $line));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    /** "1.235.000", "1,235,000 đ" and 1235000 are the same price; null when it is not a price. */
    public static function money(string $value): ?int
    {
        $value = trim((string) preg_replace('/\s*(đ|₫|vnđ|vnd)\s*$/iu', '', trim($value)));
        $value = str_replace(' ', '', $value);
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $value)) {
            return (int) str_replace(['.', ','], '', $value);
        }

        return is_numeric($value) && (float) $value >= 0 ? (int) round((float) $value) : null;
    }

    public static function decimal(string $value): ?string
    {
        $value = str_replace(',', '.', trim($value));
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 99999.9) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    /** @return bool|null null when it is not a yes/no */
    public static function flag(string $value): ?bool
    {
        return match (mb_strtolower(trim($value), 'UTF-8')) {
            '1', 'có', 'co', 'x', 'true', 'bật', 'yes' => true,
            '0', 'không', 'khong', 'false', 'tắt', 'no' => false,
            default => null,
        };
    }

    public static function action(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return [
            'add' => 'create', 'them' => 'create', 'thêm' => 'create', 'tạo' => 'create', 'tạo mới' => 'create',
            'edit' => 'update', 'sua' => 'update', 'sửa' => 'update', 'cập nhật' => 'update',
            'xoa' => 'archive', 'xóa' => 'archive', 'delete' => 'archive', 'ẩn' => 'archive', 'ẩn sản phẩm' => 'archive',
            'boqua' => 'skip', 'bỏ qua' => 'skip', 'không nhập' => 'skip',
        ][$value] ?? $value;
    }

    /** @return array{items: list<mixed>, errors: list<string>} */
    public static function parseLines(string $text, bool $paired, int $limit): array
    {
        $items = [];
        $errors = [];
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $number => $line) {
            $line = trim(strip_tags($line));
            if ($line === '') {
                continue;
            }
            if (count($items) >= $limit) {
                $errors[] = 'chỉ nhận tối đa '.$limit.' dòng';
                break;
            }
            if (! $paired) {
                $items[] = $line;

                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                $errors[] = 'dòng '.($number + 1).' phải theo mẫu “Tiêu đề | Giải thích”';

                continue;
            }
            $items[] = ['title' => $parts[0], 'text' => $parts[1]];
        }

        return ['items' => $items, 'errors' => $errors];
    }

    public static function linesFrom(?string $json, bool $paired): string
    {
        $items = json_decode((string) $json, true);
        $lines = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if ($paired && is_array($item) && ! empty($item['title']) && isset($item['text'])) {
                $lines[] = $item['title'].' | '.$item['text'];
            } elseif (! $paired && is_scalar($item)) {
                $lines[] = (string) $item;
            }
        }

        return implode("\n", $lines);
    }

    public static function escapeSpec(string $value): string
    {
        return str_replace(['\\', '|', "\r", "\n"], ['\\\\', '\\|', '\\r', '\\n'], $value);
    }

    public static function specsFrom(?string $json): string
    {
        $specs = json_decode((string) $json, true);
        $lines = [];
        foreach (is_array($specs) ? $specs : [] as $spec) {
            $label = trim((string) ($spec['label'] ?? ''));
            if ($label === '' || ! isset($spec['value'])) {
                continue;
            }
            $key = trim((string) ($spec['key'] ?? ''));
            $lines[] = ($key !== '' && preg_match('/^[a-z0-9_]+$/', $key) ? $key.' | ' : '').self::escapeSpec($label).' | '.self::escapeSpec(trim((string) $spec['value']));
        }

        return implode("\n", $lines);
    }

    /** @return list<string> a line cut on unescaped "|" */
    public static function specParts(string $line): array
    {
        $parts = [''];
        $last = 0;
        $escaped = false;
        foreach (str_split($line) as $char) {
            if ($escaped) {
                $parts[$last] .= match ($char) {
                    'n' => "\n", 'r' => "\r", '|', '\\' => $char, default => '\\'.$char,
                };
                $escaped = false;
            } elseif ($char === '\\') {
                $escaped = true;
            } elseif ($char === '|') {
                $parts[++$last] = '';
            } else {
                $parts[$last] .= $char;
            }
        }
        if ($escaped) {
            $parts[$last] .= '\\';
        }

        return $parts;
    }

    /** @return array{specs: list<array{key: string, label: string, value: string}>, errors: list<string>} */
    public static function parseSpecs(string $text, bool $allowDuplicates): array
    {
        $specs = [];
        $errors = [];
        $seen = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $number => $line) {
            if (trim($line) === '') {
                continue;
            }
            $at = 'Dòng '.($number + 1);
            $parts = self::specParts($line);
            if (count($parts) < 2) {
                $errors[] = $at.' phải theo mẫu: Mã | Tên thông số | Giá trị';

                continue;
            }
            $first = strtolower(trim($parts[0]));
            $hasKey = count($parts) >= 3 && preg_match('/^[a-z0-9_]+$/', $first);
            $key = $hasKey ? $first : '';
            $label = self::plain($hasKey ? $parts[1] : $parts[0]);
            // A "|" left unescaped inside a value by an older export belongs to the value.
            $value = self::plain(implode('|', array_slice($parts, $hasKey ? 2 : 1)));
            if ($label === '' || $value === '') {
                $errors[] = $at.' thiếu tên thông số hoặc giá trị';

                continue;
            }
            if (preg_match('/(?:hãng\s+xác\s+nhận|hãng\s+hiện\s+tại|hãng\s+công\s+bố|nguồn\s+ngoài|đối\s+chiếu|căn\s+cứ|tham\s+khảo|theo\s+(?:datasheet|series|hãng)|được\s+.+?\s+xác\s+nhận)/iu', $label)) {
                $errors[] = $at.' không nhận nhãn nguồn/xác minh “'.$label.'”; hãy đưa vào đúng cột thông số hoặc bỏ dòng này';

                continue;
            }
            $identity = $key !== '' ? 'key:'.$key : 'label:'.mb_strtolower((string) preg_replace('/\s+/u', ' ', $label), 'UTF-8');
            if (! $allowDuplicates && isset($seen[$identity])) {
                $errors[] = 'Tên thông số bị trùng: '.$label;

                continue;
            }
            $seen[$identity] = true;
            $specs[] = ['key' => $key, 'label' => $label, 'value' => $value];
        }

        return ['specs' => $specs, 'errors' => $errors];
    }

    /** @param list<array<string, mixed>> $blocks flat blocks as stored */
    public static function blocksToMarkdown(array $blocks): string
    {
        $out = [];
        foreach ($blocks as $block) {
            $photo = trim((string) ($block['photo'] ?? ''));
            $out[] = match ($block['type'] ?? '') {
                'heading' => '## '.trim((string) ($block['text'] ?? '')),
                'text' => trim((string) ($block['text'] ?? '')),
                'list' => self::listLines($block),
                'image' => '!['.trim((string) ($block['caption'] ?? '')).']('.$photo.')',
                'media' => self::mediaChunk($block, $photo),
                'faq' => filled($block['question'] ?? '') && filled($block['answer'] ?? '') ? '[faq] '.trim((string) $block['question']).' | '.trim((string) $block['answer']) : '',
                default => '',
            };
        }

        return implode("\n\n", array_filter($out, fn ($chunk) => $chunk !== ''));
    }

    /** @return list<array<string, mixed>> flat blocks */
    public static function markdownToBlocks(string $text): array
    {
        $text = trim(str_replace("\r", '', $text));
        if ($text === '') {
            return [];
        }
        $blocks = [];
        foreach (preg_split('/\n{2,}/', $text) ?: [] as $group) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $group)), fn ($line) => $line !== ''));
            if ($lines === []) {
                continue;
            }
            $one = count($lines) === 1;
            if (preg_match('#^\[media:(left|right|center)\]\(([^)]*)\)$#i', $lines[0], $m)) {
                $rest = array_slice($lines, 1);
                $title = '';
                if ($rest !== [] && preg_match('/^\*\*(.+)\*\*$/', $rest[0], $t)) {
                    $title = trim($t[1]);
                    $rest = array_slice($rest, 1);
                }
                $blocks[] = ['type' => 'media', 'side' => strtolower($m[1]), 'photo' => trim($m[2]), 'title' => $title, 'text' => implode("\n", $rest)];
            } elseif ($one && preg_match('#^!\[([^\]]*)\]\(([^)]*)\)$#', $lines[0], $m)) {
                $blocks[] = ['type' => 'image', 'photo' => trim($m[2]), 'caption' => trim($m[1])];
            } elseif ($one && preg_match('/^\[faq\]\s*(.+?)\s*\|\s*(.+)$/u', $lines[0], $m)) {
                $blocks[] = ['type' => 'faq', 'question' => trim($m[1]), 'answer' => trim($m[2])];
            } elseif (! $one && array_filter($lines, fn ($line) => ! preg_match('/^([-•*]|\d+[.)])\s+/u', $line)) === []) {
                $blocks[] = ['type' => 'list', 'ordered' => (bool) preg_match('/^\d/', $lines[0]),
                    'items' => array_map(fn ($line) => (string) preg_replace('/^([-•*]|\d+[.)])\s+/u', '', $line), $lines)];
            } elseif ($one && preg_match('/^#{1,4}\s+(.+)$/', $lines[0], $m)) {
                $blocks[] = ['type' => 'heading', 'text' => trim($m[1])];
            } else {
                $blocks[] = ['type' => 'text', 'text' => implode("\n", $lines)];
            }
        }

        return $blocks;
    }

    private static function listLines(array $block): string
    {
        $lines = [];
        foreach ((array) ($block['items'] ?? []) as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $lines[] = (! empty($block['ordered']) ? (count($lines) + 1).'. ' : '- ').$item;
            }
        }

        return implode("\n", $lines);
    }

    private static function mediaChunk(array $block, string $photo): string
    {
        $side = in_array($block['side'] ?? '', ['left', 'right', 'center'], true) ? $block['side'] : 'left';
        $chunk = '[media:'.$side.']('.$photo.')';
        if (filled($block['title'] ?? '')) {
            $chunk .= "\n**".trim((string) $block['title']).'**';
        }

        return filled($block['text'] ?? '') ? $chunk."\n".trim((string) $block['text']) : $chunk;
    }
}
