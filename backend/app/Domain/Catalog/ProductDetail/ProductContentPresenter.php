<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Legacy\Support\LegacyText;

/**
 * Turns resolved content blocks into the PDP content DTO (ptsPdpContentDto + productSplitFaqBlocks):
 * FAQ lines pulled out, only the block types the page renders, images checked on disk.
 */
final class ProductContentPresenter
{
    private const SOURCES = ['', 'product', 'brand', 'cat', 'list', 'both', 'stack', 'hide', 'own'];

    public function __construct(private readonly ProductMediaUrls $urls) {}

    /**
     * @param  array{blocks: list<array<string, mixed>>, source: string}  $sections
     * @return array{rendered: true, source: string, blocks: list<array<string, mixed>>, faqs: list<array{question: string, answer: string}>}
     */
    public function present(array $sections, bool $excludeApplications = false): array
    {
        $source = in_array($sections['source'], self::SOURCES, true) ? $sections['source'] : '';
        [$faqBlocks, $rest] = self::splitFaq($sections['blocks']);

        $blocks = [];
        $skipping = false;
        foreach ($rest as $block) {
            if (empty($block['type'])) {
                continue;
            }
            $type = (string) $block['type'];
            if ($excludeApplications && $type === 'heading') {
                $skipping = self::isApplicationHeading($block['text'] ?? '');
                if ($skipping) {
                    continue;
                }
            }
            if ($skipping) {
                continue;
            }
            if ($type === 'heading' || $type === 'text') {
                $text = LegacyText::content($block['text'] ?? '');
                if ($text !== '') {
                    $blocks[] = ['type' => $type, 'text' => $text];
                }
            } elseif ($type === 'list') {
                $items = [];
                foreach ((array) ($block['items'] ?? []) as $item) {
                    $item = LegacyText::content($item);
                    if ($item !== '') {
                        $items[] = $item;
                    }
                    if (count($items) >= 50) {
                        break;
                    }
                }
                if ($items) {
                    $blocks[] = ['type' => 'list', 'ordered' => ! empty($block['ordered']), 'items' => $items];
                }
            } elseif ($type === 'image') {
                $image = $this->urls->content($block['photo'] ?? '');
                if ($image !== '') {
                    $blocks[] = ['type' => 'image', 'image' => $image, 'caption' => LegacyText::content($block['caption'] ?? '')];
                }
            } elseif ($type === 'media') {
                $image = $this->urls->content($block['photo'] ?? '');
                $title = LegacyText::content($block['title'] ?? '');
                $text = LegacyText::content($block['text'] ?? '');
                if ($image === '' && $title === '' && $text === '') {
                    continue;
                }
                $side = in_array($block['side'] ?? '', ['left', 'right', 'center'], true) ? $block['side'] : 'left';
                $blocks[] = ['type' => 'media', 'image' => $image, 'title' => $title, 'text' => $text, 'side' => $side];
            }
        }

        $faqs = [];
        foreach ($faqBlocks as $faq) {
            $question = LegacyText::content($faq['question'] ?? '');
            $answer = LegacyText::content($faq['answer'] ?? '');
            if ($question !== '' && $answer !== '') {
                $faqs[] = ['question' => $question, 'answer' => $answer];
            }
            if (count($faqs) >= 30) {
                break;
            }
        }

        return ['rendered' => true, 'source' => $source, 'blocks' => $blocks, 'faqs' => $faqs];
    }

    public static function isApplicationHeading(mixed $value): bool
    {
        $heading = LegacyText::content($value);

        return mb_stripos($heading, 'ứng dụng') !== false || mb_stripos($heading, 'phù hợp') !== false;
    }

    /**
     * Separate FAQ blocks, including "[faq] question | answer" lines typed inside text blocks.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    public static function splitFaq(array $blocks): array
    {
        $faq = [];
        $rest = [];
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'faq') {
                $faq[] = $block;

                continue;
            }
            if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $lines = preg_split('/\R/u', trim((string) $block['text']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $remaining = [];
                $found = false;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^\[faq\]\s*(.+?)\s*\|\s*(.+)$/u', $line, $match)) {
                        $faq[] = ['type' => 'faq', 'question' => trim($match[1]), 'answer' => trim($match[2])];
                        $found = true;
                    } else {
                        $remaining[] = $line;
                    }
                }
                if ($found && $remaining) {
                    $block['text'] = implode("\n", $remaining);
                    $rest[] = $block;
                } elseif (! $found) {
                    $rest[] = $block;
                }

                continue;
            }
            $rest[] = $block;
        }

        return [$faq, $rest];
    }
}
