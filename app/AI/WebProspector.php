<?php

declare(strict_types=1);

final class WebProspector
{
    private int $maxBytes = 1500000;

    public function crawl(string $seedUrl, string $category = '', string $location = '', int $maxPages = 10): array
    {
        $seedUrl = $this->validateUrl($seedUrl);
        $maxPages = max(1, min(25, $maxPages));
        $queue = [$seedUrl];
        $seen = [];
        $prospects = [];
        $host = parse_url($seedUrl, PHP_URL_HOST);

        while ($queue && count($seen) < $maxPages) {
            $url = array_shift($queue);
            $normalized = $this->normalizeUrl($url);
            if (!$normalized || isset($seen[$normalized])) continue;
            $seen[$normalized] = true;

            $html = $this->fetch($normalized);
            if ($html === '') continue;

            foreach ($this->extractBusinesses($html, $normalized, $category, $location) as $item) {
                $fingerprint = hash('sha256', strtolower(($item['website'] ?: $item['business_name']) . '|' . ($item['phone'] ?: $item['email'] ?: $item['address'])));
                $item['fingerprint'] = $fingerprint;
                $prospects[$fingerprint] = $item;
            }

            foreach ($this->links($html, $normalized, $host) as $link) {
                if (!isset($seen[$link]) && count($queue) + count($seen) < $maxPages + 15) $queue[] = $link;
            }
        }

        return ['pages_crawled' => count($seen), 'prospects' => array_values($prospects)];
    }

    private function validateUrl(string $url): string
    {
        $url = trim($url);
        if (!preg_match('~^https?://~i', $url)) throw new InvalidArgumentException('Only http:// and https:// URLs are allowed.');
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) throw new InvalidArgumentException('Invalid seed URL.');
        $host = strtolower($parts['host']);
        if ($this->isPrivateHost($host)) throw new InvalidArgumentException('Private/local network URLs are not allowed.');
        return $url;
    }

    private function isPrivateHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.local')) return true;
        $ip = gethostbyname($host);
        if ($ip === $host) return false;
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function fetch(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'AI-CRM Web Prospecting Bot/1.0 (+respect robots.txt and site policies)',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
            CURLOPT_ENCODING => '',
            CURLOPT_HEADER => false,
        ]);
        $body = curl_exec($ch);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if (!is_string($body) || stripos($type, 'text/html') === false) return '';
        return substr($body, 0, $this->maxBytes);
    }

    private function extractBusinesses(string $html, string $sourceUrl, string $category, string $location): array
    {
        $result = [];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $json = json_decode($node->textContent, true);
            $items = $this->flattenJsonLd($json);
            foreach ($items as $data) {
                $types = isset($data['@type']) ? (array)$data['@type'] : [];
                $isBusiness = false;
                foreach ($types as $type) if (preg_match('/business|store|shop|restaurant|organization/i', (string)$type)) $isBusiness = true;
                if (!$isBusiness || empty($data['name'])) continue;
                $result[] = [
                    'business_name' => trim((string)$data['name']),
                    'category' => $category ?: ($data['category'] ?? ($types[0] ?? null)),
                    'website' => $this->absoluteUrl((string)($data['url'] ?? ''), $sourceUrl),
                    'domain' => parse_url($sourceUrl, PHP_URL_HOST),
                    'email' => $this->cleanEmail((string)($data['email'] ?? '')),
                    'phone' => $this->cleanPhone((string)($data['telephone'] ?? '')),
                    'address' => $this->addressText($data['address'] ?? ''),
                    'city' => $location ?: $this->jsonCity($data['address'] ?? ''),
                    'source_url' => $sourceUrl,
                    'source_type' => 'web_jsonld',
                    'raw_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }

        // Fallback: pages that expose contact details in visible HTML.
        $text = preg_replace('/\s+/u', ' ', $dom->textContent ?? '');
        $email = $this->cleanEmail($this->firstMatch('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text));
        $phone = $this->cleanPhone($this->firstMatch('/(?:\+?91[\s-]?)?[6-9]\d{9}\b/', $text));
        if ($email || $phone) {
            $title = trim((string)$xpath->evaluate('string(//title)'));
            if ($title) $result[] = [
                'business_name' => $this->cleanTitle($title), 'category' => $category ?: null,
                'website' => $sourceUrl, 'domain' => parse_url($sourceUrl, PHP_URL_HOST),
                'email' => $email, 'phone' => $phone, 'address' => null, 'city' => $location ?: null,
                'source_url' => $sourceUrl, 'source_type' => 'web_contact', 'raw_data' => null,
            ];
        }
        return $result;
    }

    private function flattenJsonLd($data): array
    {
        if (!is_array($data)) return [];
        if (isset($data['@graph']) && is_array($data['@graph'])) return $data['@graph'];
        if (array_is_list($data)) return $data;
        return [$data];
    }

    private function links(string $html, string $baseUrl, ?string $host): array
    {
        libxml_use_internal_errors(true); $dom = new DOMDocument(); @$dom->loadHTML($html); $out = [];
        foreach ($dom->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href')); if (!$href || str_starts_with($href, '#') || preg_match('~^(mailto:|tel:|javascript:)~i', $href)) continue;
            $url = $this->absoluteUrl($href, $baseUrl); if (!$url) continue;
            $uHost = parse_url($url, PHP_URL_HOST); if ($uHost && $host && strcasecmp($uHost, $host) === 0) $out[$this->normalizeUrl($url)] = true;
        }
        return array_keys($out);
    }

    private function absoluteUrl(string $href, string $base): ?string
    {
        if ($href === '') return null; if (preg_match('~^https?://~i', $href)) return $href;
        $p = parse_url($base); if (!$p || empty($p['scheme']) || empty($p['host'])) return null;
        if (str_starts_with($href, '//')) return $p['scheme'] . ':' . $href;
        if (str_starts_with($href, '/')) return $p['scheme'] . '://' . $p['host'] . $href;
        return rtrim(dirname($p['path'] ?? '/'), '/') . '/' . ltrim($href, '/');
    }

    private function normalizeUrl(?string $url): ?string
    { return $url ? preg_replace('/#.*$/', '', $url) : null; }

    private function firstMatch(string $pattern, string $text): string
    { return preg_match($pattern, $text, $m) ? trim($m[0]) : ''; }

    private function cleanEmail(string $v): ?string
    { $v = trim($v); return filter_var($v, FILTER_VALIDATE_EMAIL) ? strtolower($v) : null; }

    private function cleanPhone(string $v): ?string
    { $v = trim($v); $digits = preg_replace('/\D+/', '', $v); return strlen($digits) >= 10 ? '+' . ltrim($digits, '+') : null; }

    private function cleanTitle(string $v): string
    { return trim(preg_replace('/\s*[|\-–—].*$/u', '', $v)); }

    private function addressText($address): ?string
    {
        if (is_string($address)) return trim($address) ?: null;
        if (is_array($address)) return trim(implode(', ', array_filter([(string)($address['streetAddress'] ?? ''), (string)($address['addressLocality'] ?? ''), (string)($address['addressRegion'] ?? ''), (string)($address['postalCode'] ?? '')])) ?: null;
        return null;
    }

    private function jsonCity($address): ?string
    { return is_array($address) ? trim((string)($address['addressLocality'] ?? '')) ?: null : null; }
}
