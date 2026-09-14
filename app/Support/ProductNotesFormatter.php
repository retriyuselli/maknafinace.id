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

        $rows = [];

        // Prefer extracting list items into dashed rows with hanging indent
        if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $html, $matches)) {
            $beforeList = preg_split('/<(?:ol|ul)\b/i', $html, 2)[0] ?? '';
            $beforeList = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $beforeList)));
            if ($beforeList !== '') {
                foreach (preg_split("/\n+/", $beforeList) as $intro) {
                    $intro = trim($intro);
                    if ($intro !== '') {
                        $rows[] = '<div>'.e($intro).'</div>';
                    }
                }
            }

            foreach ($matches[1] as $itemHtml) {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags($itemHtml)));
                if ($text === '') {
                    continue;
                }
                $rows[] = self::pdfDashRow($text);
            }

            return implode('', $rows);
        }

        // No list: keep simple paragraphs as lines
        $html = preg_replace('/<p[^>]*>\s*(?:&nbsp;|\s|<br\s*\/?>)*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/p>\s*<p[^>]*>/i', '<br>', $html) ?? $html;
        $html = strip_tags($html, '<br>');
        $html = str_replace(['•', '●', '·'], '-', $html);
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $html) ?? $html;

        foreach (preg_split('/<br\s*\/?>/i', $html) as $line) {
            $line = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^[-*]\s*(.+)$/u', $line, $match)) {
                $rows[] = self::pdfDashRow($match[1]);
                continue;
            }

            $rows[] = '<div>'.e($line).'</div>';
        }

        return implode('', $rows);
    }

    /**
     * Dash stays on the first line; wrapped text aligns with the sentence, not the dash.
     */
    private static function pdfDashRow(string $text): string
    {
        $text = ltrim(trim($text), " \t.-•●·");
        if ($text === '') {
            return '';
        }

        return '<table class="notes-line" width="100%" style="width:100%;border-collapse:collapse;margin:0;padding:0;border:none;">'
            .'<tr>'
            .'<td width="12" style="width:12px;border:none;padding:0 6px 1px 0;margin:0;vertical-align:top;line-height:1.3;white-space:nowrap;">-</td>'
            .'<td style="border:none;padding:0 0 1px 0;margin:0;vertical-align:top;line-height:1.3;">'.e($text).'</td>'
            .'</tr></table>';
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
