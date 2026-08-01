<?php

namespace App\Support;

class ProductNotesFormatter
{
    /**
     * Normalize TipTap/HTML notes for DomPDF.
     * Nested <li><p>…</p></li> makes DomPDF put bullets on their own line.
     */
    public static function forPdf(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Prefer extracting list items into plain dashed lines
        if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $html, $matches)) {
            $lines = [];

            // Keep any intro paragraph(s) before the list
            $beforeList = preg_split('/<(?:ol|ul)\b/i', $html, 2)[0] ?? '';
            $beforeList = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $beforeList)));
            if ($beforeList !== '') {
                foreach (preg_split("/\n+/", $beforeList) as $intro) {
                    $intro = trim($intro);
                    if ($intro !== '') {
                        $lines[] = e($intro);
                    }
                }
            }

            foreach ($matches[1] as $itemHtml) {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags($itemHtml)));
                if ($text === '') {
                    continue;
                }
                $text = ltrim($text, " \t.-•●·");
                $lines[] = '- '.e($text);
            }

            return implode('<br>', $lines);
        }

        // No list: keep simple paragraphs as lines
        $html = preg_replace('/<\/p>\s*<p[^>]*>/i', "<br>", $html) ?? $html;
        $html = strip_tags($html, '<br><b><strong><em>');
        $html = str_replace(['•', '●', '·'], '-', $html);

        return trim($html);
    }

    /**
     * Same cleanup used by HTML preview (browser handles nested lists better).
     */
    public static function forPreview(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $detailsNotes = strip_tags($html, '<p><b><strong><em><ul><ol><li><br><span><div>');
        $detailsNotes = str_replace(['•', '&bull;', '&#8226;', '●', '·'], '-', $detailsNotes);
        $detailsNotes = preg_replace('/(^|<br\s*\/?>)\s*\d+\.\s*/i', '$1- ', $detailsNotes) ?? $detailsNotes;
        $detailsNotes = preg_replace('/<p([^>]*)>\s*\d+\.\s*/i', '<p$1>- ', $detailsNotes) ?? $detailsNotes;

        // Flatten <li><p>text</p></li> so browser/PDF-like print stays compact
        $detailsNotes = preg_replace('/<li([^>]*)>\s*<p[^>]*>(.*?)<\/p>\s*<\/li>/is', '<li$1>$2</li>', $detailsNotes) ?? $detailsNotes;

        return $detailsNotes;
    }
}
