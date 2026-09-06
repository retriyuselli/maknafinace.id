<?php

namespace App\Support;

class ExcelRichText
{
    /**
     * Convert Excel cell text into HTML that Filament RichEditor can render.
     *
     * Excel does not support native bullets. Users type:
     * - paragraphs separated by blank lines (Alt+Enter)
     * - "- item" or "* item" for bullets
     * - "1. item" or "1) item" for numbered lists
     */
    public static function toHtml(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return null;
        }

        if (preg_match('/<(p|ul|ol|li|br|div)\b/i', $text)) {
            $html = SafeHtml::fromRichText($text);

            return $html !== '' ? $html : null;
        }

        $blocks = [];
        $buffer = [];
        $type = null;

        $flush = static function () use (&$blocks, &$buffer, &$type): void {
            if ($buffer === []) {
                $type = null;

                return;
            }

            if ($type === 'ul') {
                $blocks[] = '<ul>'.implode('', array_map(
                    fn (string $item): string => '<li>'.e($item).'</li>',
                    $buffer
                )).'</ul>';
            } elseif ($type === 'ol') {
                $blocks[] = '<ol>'.implode('', array_map(
                    fn (string $item): string => '<li>'.e($item).'</li>',
                    $buffer
                )).'</ol>';
            } else {
                $blocks[] = '<p>'.implode('<br>', array_map(
                    fn (string $item): string => e($item),
                    $buffer
                )).'</p>';
            }

            $buffer = [];
            $type = null;
        };

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if ($line === '') {
                $flush();

                continue;
            }

            if (preg_match('/^[-*•●·]\s+(.+)$/u', $line, $matches)) {
                if ($type !== 'ul') {
                    $flush();
                    $type = 'ul';
                }
                $buffer[] = $matches[1];

                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $matches)) {
                if ($type !== 'ol') {
                    $flush();
                    $type = 'ol';
                }
                $buffer[] = $matches[1];

                continue;
            }

            if ($type !== 'p') {
                $flush();
                $type = 'p';
            }

            $buffer[] = $line;
        }

        $flush();

        $html = implode('', $blocks);

        return $html !== '' ? $html : null;
    }
}
