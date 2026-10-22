<?php
/**
 * Website audit: fetches a prospect's site and detects sales opportunities
 * (no HTTPS, not mobile friendly, outdated WordPress, Wix/other builder, slow, etc.).
 * Produces an "opportunity score" 0-100: higher means more room to help.
 */
defined('ABSPATH') || exit;

/**
 * Fetch a homepage with WordPress's safe HTTP API, which refuses private,
 * reserved and loopback addresses on every redirect hop.
 */
function lpl_fetch(string $url): array
{
    $start = microtime(true);
    $res = wp_safe_remote_get($url, [
        'timeout'             => 15,
        'redirection'         => 5,
        'limit_response_size' => 8 * 1024 * 1024,
        'user-agent'          => 'Mozilla/5.0 (compatible; LightandpulseAudit/1.0; +' . home_url('/') . ')',
        'headers'             => ['Accept' => 'text/html,application/xhtml+xml', 'Accept-Language' => 'en,id;q=0.8'],
    ]);
    if (is_wp_error($res)) {
        $msg = $res->get_error_message();
        return ['error' => $msg, 'ssl_error' => (bool) preg_match('/SSL|certificate/i', $msg)];
    }
    $final = $url;
    $http = $res['http_response'] ?? null;
    if ($http && method_exists($http, 'get_response_object')) {
        $final = $http->get_response_object()->url ?: $url;
    }
    return [
        'status' => (int) wp_remote_retrieve_response_code($res),
        'url'    => $final,
        'body'   => (string) wp_remote_retrieve_body($res),
        'time'   => round(microtime(true) - $start, 2),
    ];
}

function lpl_audit_website(string $website): array
{
    $result = [
        'url'      => $website,
        'checked'  => wp_date('Y-m-d H:i'),
        'signals'  => [],
        'findings' => [],
        'score'    => 0,
    ];
    $add = function (int $points, string $text, string $service = '') use (&$result) {
        $result['score'] += $points;
        $result['findings'][] = ['points' => $points, 'text' => $text, 'service' => $service];
    };

    if ($website === '') {
        $result['signals']['has_website'] = false;
        $add(95, 'No website — relies only on Google Maps / Instagram / OTAs', 'website');
        return $result;
    }

    $res = lpl_fetch($website);
    if (isset($res['error'])) {
        $result['signals']['reachable'] = false;
        $result['signals']['error'] = $res['error'];
        if (!empty($res['ssl_error'])) {
            $add(70, 'SSL certificate is broken — browsers show a "Not secure" warning', 'speed');
        } else {
            $add(85, 'Website is down or unreachable (' . $res['error'] . ')', 'website');
        }
        $result['score'] = min(100, $result['score']);
        return $result;
    }

    return lpl_analyze_html($result, $res['body'], $res['url'], $res['status'], (float) $res['time']);
}

/** Score a fetched homepage. Kept separate from fetching so it can be tested offline. */
function lpl_analyze_html(array $result, string $html, string $final, int $status, float $time): array
{
    $add = function (int $points, string $text, string $service = '') use (&$result) {
        $result['score'] += $points;
        $result['findings'][] = ['points' => $points, 'text' => $text, 'service' => $service];
    };
    $lower = strtolower($html);
    $s = &$result['signals'];
    $s['reachable'] = $status < 400;
    $s['status'] = $status;
    $s['final_url'] = $final;
    $s['https'] = str_starts_with(strtolower($final), 'https://');
    $s['load_time'] = $time;
    $s['html_kb'] = round(strlen($html) / 1024);

    if ($status >= 400) {
        $add(80, "Homepage returns an error (HTTP {$status})", 'website');
    }

    // Platform detection
    $platform = 'Unknown / custom';
    if (str_contains($lower, 'wp-content') || str_contains($lower, 'wp-includes')) {
        $platform = 'WordPress';
    } elseif (str_contains($lower, 'wix.com') || str_contains($lower, 'wixstatic')) {
        $platform = 'Wix';
    } elseif (str_contains($lower, 'squarespace')) {
        $platform = 'Squarespace';
    } elseif (str_contains($lower, 'cdn.shopify') || str_contains($lower, 'shopify.theme')) {
        $platform = 'Shopify';
    } elseif (str_contains($lower, 'webflow')) {
        $platform = 'Webflow';
    } elseif (str_contains($lower, 'blogger.com') || str_contains($lower, 'blogspot')) {
        $platform = 'Blogger';
    } elseif (str_contains($lower, 'sites.google.com') || str_contains($lower, 'business.site')) {
        $platform = 'Google Sites / Business Profile';
    }
    $s['platform'] = $platform;
    $s['elementor'] = str_contains($lower, 'elementor');
    $s['woocommerce'] = str_contains($lower, 'woocommerce');
    if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress\s*([\d.]+)/i', $html, $m)) {
        $s['wp_version'] = $m[1];
    }

    // SEO & UX basics
    $s['title'] = preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : '';
    $s['meta_description'] = (bool) preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'][^"\']{20,}/i', $html);
    $s['viewport'] = (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html);
    $s['h1_count'] = preg_match_all('~<h1[\s>]~i', $html);
    $s['og_tags'] = str_contains($lower, 'property="og:') || str_contains($lower, "property='og:");
    $s['schema'] = str_contains($lower, 'application/ld+json');
    $s['whatsapp_link'] = (bool) preg_match('~wa\.me/|api\.whatsapp\.com|whatsapp://~i', $html);
    $s['analytics'] = (bool) preg_match('~gtag\(|googletagmanager\.com|google-analytics\.com|plausible\.io|clarity\.ms~i', $html);
    $s['booking'] = (bool) preg_match('~booking\.com|cloudbeds|sirvoy|little ?hotelier|simplebooking|siteminder|beds24|checkfront|fareharbor|rezdy|bookingkit|calendly|mphb|booking engine|book now~i', $html);
    $s['shop'] = $s['woocommerce'] || $platform === 'Shopify' || (bool) preg_match('~add to cart|add-to-cart|tambah ke keranjang~i', $html);
    $s['languages'] = (bool) preg_match('~hreflang=|wpml|polylang|gtranslate|weglot~i', $html);
    if (preg_match_all('~(?:©|&copy;|copyright)\s*(?:\d{4}\s*[-–]\s*)?(\d{4})~i', $html, $m)) {
        $s['copyright_year'] = (int) max($m[1]);
    }
    if (preg_match('~jquery(?:\.min)?\.js\?ver=([\d.]+)|jquery[-.]([\d]+\.[\d.]+)(?:\.min)?\.js~i', $html, $m)) {
        $s['jquery'] = $m[1] ?: $m[2];
    }

    // Opportunity scoring
    if (!$s['https']) {
        $add(15, 'No HTTPS — Chrome marks the site as "Not secure"', 'speed');
    }
    if (!$s['viewport']) {
        $add(20, 'Not mobile-friendly (no responsive viewport) — most guests browse on phones', 'redesign');
    }
    if ($platform === 'Wix' || $platform === 'Blogger' || $platform === 'Google Sites / Business Profile' || $platform === 'Squarespace') {
        $add(12, "Built on $platform — limited SEO and ownership; good candidate to migrate to WordPress", 'migration');
    } elseif ($platform === 'Unknown / custom') {
        $add(5, 'Custom/unknown platform — likely hard for the owner to update content', 'redesign');
    }
    if (!empty($s['wp_version']) && version_compare($s['wp_version'], '6.4', '<')) {
        $add(12, "Outdated WordPress {$s['wp_version']} — security risk; needs a maintenance plan", 'maintenance');
    }
    if ($platform === 'WordPress' && !$s['elementor']) {
        $add(3, 'WordPress without Elementor — owner may struggle to edit pages', 'redesign');
    }
    if (!empty($s['jquery']) && version_compare($s['jquery'], '3.0', '<')) {
        $add(6, "Very old jQuery {$s['jquery']} — the theme has not been updated in years", 'maintenance');
    }
    if ($s['title'] === '') {
        $add(6, 'Missing page title — hurts Google ranking', 'seo');
    }
    if (!$s['meta_description']) {
        $add(8, 'No meta description — weak Google search snippet', 'seo');
    }
    if ($s['h1_count'] === 0) {
        $add(4, 'No H1 heading on the homepage', 'seo');
    }
    if (!$s['og_tags']) {
        $add(4, 'No social sharing tags — links look bare on WhatsApp / Facebook', 'seo');
    }
    if (!$s['schema']) {
        $add(3, 'No structured data (schema) for Google rich results', 'seo');
    }
    if (!$s['whatsapp_link']) {
        $add(10, 'No WhatsApp button — the #1 way guests contact businesses in Bali', 'redesign');
    }
    if (!$s['analytics']) {
        $add(4, 'No analytics installed — the owner cannot measure visitors', 'seo');
    }
    if ($s['load_time'] > 4) {
        $add(12, "Slow server response ({$s['load_time']}s)", 'speed');
    } elseif ($s['load_time'] > 2.5) {
        $add(6, "Server response could be faster ({$s['load_time']}s)", 'speed');
    }
    if ($s['html_kb'] > 600) {
        $add(5, "Heavy homepage HTML ({$s['html_kb']} KB)", 'speed');
    }
    if (!empty($s['copyright_year']) && $s['copyright_year'] < (int) wp_date('Y') - 1) {
        $add(8, "Footer says © {$s['copyright_year']} — site looks abandoned", 'maintenance');
    }
    if (!$s['languages']) {
        $add(2, 'Single language only — consider EN / ID (or RU, ZH for tourism)', 'redesign');
    }

    $result['score'] = max(0, min(100, $result['score']));
    usort($result['findings'], fn($a, $b) => $b['points'] <=> $a['points']);
    return $result;
}

/** Suggest services for a lead based on audit findings. */
function lpl_audit_services(array $audit): array
{
    $svc = [];
    foreach ($audit['findings'] as $f) {
        if ($f['service'] !== '' && $f['points'] >= 4) {
            $svc[$f['service']] = true;
        }
    }
    return array_keys($svc);
}
