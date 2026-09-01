<?php

namespace App\Services\Sources;

use App\Data\ImportedTitleData;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SourceImporter
{
    private const SOURCES = [
        'asurascans.com' => ['name' => 'Asura Scans', 'type' => 'manhwa', 'available' => true],
        'www.asurascans.com' => ['name' => 'Asura Scans', 'type' => 'manhwa', 'available' => true],
        'genztoons.org' => ['name' => 'Genz Toons', 'type' => 'manhwa', 'available' => true],
        'www.genztoons.org' => ['name' => 'Genz Toons', 'type' => 'manhwa', 'available' => true],
        'comix.to' => ['name' => 'Comix', 'type' => 'manga', 'available' => false],
        'www.comix.to' => ['name' => 'Comix', 'type' => 'manga', 'available' => false],
    ];

    public function import(string $url): ImportedTitleData
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $source = self::SOURCES[$host] ?? null;

        if ($source === null) {
            throw ValidationException::withMessages(['source_url' => 'This website is not supported yet. Add the title manually instead.']);
        }
        if (! $source['available']) {
            throw ValidationException::withMessages(['source_url' => 'Comix pages do not currently expose reliable import data. Add this title manually for now.']);
        }

        try {
            $response = Http::accept('text/html')
                ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
                ->withUserAgent('Nora/1.0 personal reading tracker')
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->connectTimeout(10)
                ->timeout(20)
                ->get($url)
                ->throw();
        } catch (ConnectionException|RequestException) {
            throw ValidationException::withMessages(['source_url' => "Nora could not retrieve this {$source['name']} page. You can still add it manually."]);
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $title = $this->cleanTitle($this->meta($xpath, 'og:title') ?: trim($xpath->query('//h1')->item(0)?->textContent ?? ''), $source['name']);

        if ($title === '') {
            throw ValidationException::withMessages(['source_url' => 'Nora retrieved the page but could not identify its title. Add it manually instead.']);
        }

        $chapterUrls = $this->chapterUrls($xpath, $url, $source['name']);

        return new ImportedTitleData($title, $source['type'], $this->absoluteUrl($this->meta($xpath, 'og:image'), $url),
            $this->nullable($this->meta($xpath, 'og:description')), $url, $source['name'], array_key_first($chapterUrls), $chapterUrls);
    }

    private function meta(DOMXPath $xpath, string $property): string
    {
        $node = $xpath->query("//meta[@property='{$property}' or @name='{$property}']")->item(0);

        return $node instanceof DOMElement ? trim($node->getAttribute('content')) : '';
    }

    private function cleanTitle(string $title, string $source): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s*(?:[-|]\s*)?'.preg_quote($source, '/').'\s*$/i', '', $title));
    }

    /** @return array<string, string> */
    private function chapterUrls(DOMXPath $xpath, string $pageUrl, string $sourceName): array
    {
        $chapters = [];
        $seriesPath = rtrim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
        foreach ($xpath->query('//a') as $anchor) {
            if ($anchor instanceof DOMElement) {
                $path = (string) parse_url($anchor->getAttribute('href'), PHP_URL_PATH);
                if ($sourceName === 'Asura Scans' && str_starts_with($path, $seriesPath.'/chapter/')
                    && preg_match('~/chapter/(\d+(?:\.\d+)?)(?:/|$)~i', $path, $match)) {
                    $label = 'Chapter '.$match[1];
                    $chapters[$label] = ['url' => $this->absoluteUrl($anchor->getAttribute('href'), $pageUrl), 'number' => (float) $match[1]];

                    continue;
                }

                if ($sourceName !== 'Genz Toons' || ! str_starts_with($path, '/chapter/')) {
                    continue;
                }

                $label = $this->chapterLabel($anchor);
                if ($label !== null && preg_match('/\bchapter\s+(\d+(?:\.\d+)?)(?!\d)/i', $label, $match)) {
                    $label = 'Chapter '.$match[1];
                    $chapters[$label] = ['url' => $this->absoluteUrl($anchor->getAttribute('href'), $pageUrl), 'number' => (float) $match[1]];
                }
            }
        }
        uasort($chapters, fn ($left, $right) => $right['number'] <=> $left['number']);

        return array_filter(array_map(fn ($chapter) => $chapter['url'], $chapters));
    }

    private function chapterLabel(DOMElement $anchor): ?string
    {
        $candidates = [$anchor->getAttribute('title')];
        foreach ($anchor->childNodes as $child) {
            $candidates[] = $child->textContent;
        }
        $candidates[] = $anchor->textContent;

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if (preg_match('/\bchapter\s+\d+(?:\.\d+)?\b/i', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function absoluteUrl(string $value, string $pageUrl): ?string
    {
        if ($value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (! str_starts_with($value, '/')) {
            return null;
        }

        return parse_url($pageUrl, PHP_URL_SCHEME).'://'.parse_url($pageUrl, PHP_URL_HOST).$value;
    }

    private function nullable(string $value): ?string
    {
        $value = trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value === '' ? null : mb_substr($value, 0, 5000);
    }
}
