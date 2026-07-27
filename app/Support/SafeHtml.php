<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class SafeHtml
{
    /**
     * Sanitize rich text HTML while preserving common formatting tags.
     */
    public static function fromRichText(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $allowedTags = [
            'p', 'br', 'b', 'strong', 'em', 'i', 'ul', 'ol', 'li',
            'span', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'table', 'thead', 'tbody', 'tr', 'td', 'th',
            'blockquote', 'code', 'pre', 'img', 'div', 'hr',
        ];

        $allowedAttributes = [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            'div' => ['class'],
            'span' => ['class'],
            'p' => ['class'],
            'ul' => ['class'],
            'ol' => ['class'],
            'li' => ['class'],
            'table' => ['class'],
            'td' => ['colspan', 'rowspan'],
            'th' => ['colspan', 'rowspan'],
        ];

        return self::sanitize($html, $allowedTags, $allowedAttributes);
    }

    /**
     * @param  array<int, string>  $allowedTags
     * @param  array<string, array<int, string>>  $allowedAttributes
     */
    public static function sanitize(string $html, array $allowedTags, array $allowedAttributes = []): string
    {
        if (! class_exists(DOMDocument::class)) {
            $allowed = '<'.implode('><', $allowedTags).'>';

            return strip_tags($html, $allowed);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrappedHtml = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        $dom->loadHTML(mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8'));

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMNode) {
            return '';
        }

        /** @var array<int, DOMNode> $nodes */
        $nodes = iterator_to_array($body->getElementsByTagName('*'));

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($node->tagName);
            if (! in_array($tagName, $allowedTags, true)) {
                self::unwrapNode($node);
                continue;
            }

            $allowedForTag = $allowedAttributes[$tagName] ?? [];
            $attributes = iterator_to_array($node->attributes);

            foreach ($attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = trim($attribute->nodeValue ?? '');

                if (str_starts_with($name, 'on') || $name === 'style') {
                    $node->removeAttribute($attribute->nodeName);
                    continue;
                }

                if (! in_array($name, $allowedForTag, true)) {
                    $node->removeAttribute($attribute->nodeName);
                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($value, $name === 'src')) {
                    $node->removeAttribute($attribute->nodeName);
                    continue;
                }

                if ($name === 'class' && ! preg_match('/^[a-zA-Z0-9_\-\s:\/]+$/', $value)) {
                    $node->removeAttribute($attribute->nodeName);
                    continue;
                }

                if ($tagName === 'a' && $name === 'target' && $value === '_blank') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        $output = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function isSafeUrl(string $url, bool $isImage = false): bool
    {
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');

        if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return true;
        }

        if ($isImage && $scheme === 'data') {
            return str_starts_with(strtolower($url), 'data:image/');
        }

        return false;
    }

    private static function unwrapNode(DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (! $parent instanceof DOMNode) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
