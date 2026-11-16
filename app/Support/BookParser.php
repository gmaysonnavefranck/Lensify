<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use RuntimeException;
use ZipArchive;

/**
 * Turns an uploaded book file into plain-text chapters.
 *
 * Returns ['title' => ?string, 'author' => ?string, 'language' => ?string, 'chapters' => [['title' => string, 'content' => string], ...]]
 */
class BookParser
{
    private const BLOCK_TAGS = ['p', 'div', 'section', 'article', 'blockquote', 'li', 'tr', 'pre', 'h4', 'h5', 'h6', 'table', 'ul', 'ol', 'dl', 'dd', 'dt', 'figure', 'hr'];

    private const HEADING_TAGS = ['h1', 'h2', 'h3'];

    public static function parse(string $path, string $extension): array
    {
        return strtolower($extension) === 'epub' ? (new self)->epub($path) : (new self)->text(file_get_contents($path));
    }

    public function epub(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException("This file isn't a valid EPUB.");
        }

        $container = $this->xml($zip->getFromName('META-INF/container.xml'));
        $container->registerXPathNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
        $opfPath = (string) ($container->xpath('//c:rootfile')[0]['full-path'] ?? '');

        $opf = $this->xml($zip->getFromName($opfPath));
        $opf->registerXPathNamespace('o', 'http://www.idpf.org/2007/opf');
        $opf->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $meta = fn (string $tag) => trim((string) ($opf->xpath("//dc:$tag")[0] ?? '')) ?: null;

        $manifest = [];
        foreach ($opf->xpath('//o:manifest/o:item') as $item) {
            $manifest[(string) $item['id']] = (string) $item['href'];
        }

        $baseDir = dirname($opfPath) === '.' ? '' : dirname($opfPath) . '/';
        $chapters = [];
        foreach ($opf->xpath('//o:spine/o:itemref') as $ref) {
            $href = $manifest[(string) $ref['idref']] ?? null;
            if (! $href || (string) $ref['linear'] === 'no') {
                continue;
            }
            $html = $zip->getFromName($baseDir . urldecode(explode('#', $href)[0]));
            if ($html !== false) {
                $this->collectChapters($html, $chapters);
            }
        }
        $zip->close();

        return ['title' => $meta('title'), 'author' => $meta('creator'), 'language' => $meta('language'), 'chapters' => $this->finish($chapters)];
    }

    public function text(string $raw): array
    {
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }
        $raw = str_replace(["\r\n", "\r"], "\n", preg_replace('/^\xEF\xBB\xBF/', '', $raw));

        $header = mb_substr($raw, 0, 3000);
        preg_match('/^Title:\s*(.+)$/mi', $header, $title);
        preg_match('/^Author:\s*(.+)$/mi', $header, $author);

        // Plain-text ebooks often break lines at ~70 chars; join those back into paragraphs
        // ponytail: line-length heuristic, poetry with long lines could get joined; add an upload option if that happens
        $lines = array_filter(explode("\n", $raw), fn ($line) => trim($line) !== '');
        $wrapped = $lines && count(array_filter($lines, fn ($line) => mb_strlen($line) >= 55 && mb_strlen($line) <= 80)) / count($lines) > 0.5;
        $unwrap = fn (string $content) => $wrapped ? preg_replace('/(?<=\S)\n(?=\S)/u', ' ', $content) : $content;

        // A line like "CHAPTER IV.", "Capítulo 3" or "BOOK II" starts a new chapter
        $parts = preg_split('/^[ \t]*((?:chapter|cap[íi]tulo|book|livro|part|parte)[ \t]+(?-i:[IVXLCDM]+|\d+)\b[^\n]{0,100})$/miu', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);

        $chapters = [['title' => 'Beginning', 'content' => $unwrap($parts[0])]];
        for ($i = 1; $i < count($parts); $i += 2) {
            $chapters[] = ['title' => trim($parts[$i]), 'content' => $unwrap($parts[$i + 1] ?? '')];
        }

        return ['title' => isset($title[1]) ? trim($title[1]) : null, 'author' => isset($author[1]) ? trim($author[1]) : null, 'language' => null, 'chapters' => $this->finish($chapters)];
    }

    // Adds the chapters found in one EPUB document: each h1-h3 starts a new one
    private function collectChapters(string $html, array &$chapters): void
    {
        $dom = new DOMDocument;
        // Encode non-ASCII as entities so the HTML parser can't misread the charset
        @$dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return;
        }

        // A document that doesn't open with a heading is a new untitled section
        $chapters[] = ['title' => '', 'content' => ''];
        $this->walk($body, $chapters);
    }

    private function walk(DOMNode $node, array &$chapters): void
    {
        foreach ($node->childNodes as $child) {
            $last = array_key_last($chapters);

            if ($child->nodeType === XML_TEXT_NODE) {
                $chapters[$last]['content'] .= preg_replace('/\s+/u', ' ', $child->textContent);

                continue;
            }
            if (! $child instanceof DOMElement || in_array($child->tagName, ['script', 'style'])) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::HEADING_TAGS)) {
                $heading = trim(preg_replace('/\s+/u', ' ', $child->textContent));
                if ($heading === '') {
                    continue;
                }
                // Consecutive headings ("CHAPTER I" + its subtitle) make one title
                if (trim($chapters[$last]['content']) === '') {
                    $chapters[$last]['title'] = trim($chapters[$last]['title'] . ' ' . $heading);
                } else {
                    $chapters[] = ['title' => $heading, 'content' => ''];
                }
            } elseif ($tag === 'br') {
                $chapters[$last]['content'] .= "\n";
            } elseif (in_array($tag, self::BLOCK_TAGS)) {
                $chapters[$last]['content'] .= "\n\n";
                $this->walk($child, $chapters);
                $chapters[array_key_last($chapters)]['content'] .= "\n\n";
            } else {
                $this->walk($child, $chapters);
            }
        }
    }

    // Tidy whitespace, drop empty chapters, name untitled ones
    private function finish(array $chapters): array
    {
        $result = [];
        foreach ($chapters as $chapter) {
            $content = preg_replace('/[ \t]+/u', ' ', $chapter['content']);
            $content = preg_replace('/ *\n */u', "\n", $content);
            $content = trim(preg_replace('/\n{3,}/', "\n\n", $content));
            if ($content === '') {
                continue;
            }
            $title = mb_substr(trim($chapter['title']), 0, 255) ?: 'Section ' . (count($result) + 1);
            $result[] = ['title' => $title, 'content' => $content];
        }

        return $result;
    }

    private function xml(string|false $content): \SimpleXMLElement
    {
        $xml = $content ? @simplexml_load_string($content) : false;
        if (! $xml) {
            throw new RuntimeException("This file isn't a valid EPUB.");
        }

        return $xml;
    }
}
