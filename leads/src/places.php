<?php
/**
 * Google Places API (New) — Text Search. Finds Bali businesses by keyword + area.
 * Requires an API key with "Places API (New)" enabled (Settings page).
 */
declare(strict_types=1);

function places_search(string $apiKey, string $query, ?string $pageToken = null): array
{
    $fields = [
        'places.id', 'places.displayName', 'places.formattedAddress', 'places.nationalPhoneNumber',
        'places.internationalPhoneNumber', 'places.websiteUri', 'places.rating', 'places.userRatingCount',
        'places.googleMapsUri', 'places.primaryTypeDisplayName', 'places.businessStatus', 'nextPageToken',
    ];
    $payload = [
        'textQuery'    => $query,
        'pageSize'     => 20,
        'languageCode' => 'en',
        'regionCode'   => 'ID',
        // Bias results to Bali island
        'locationBias' => ['rectangle' => [
            'low'  => ['latitude' => -8.86, 'longitude' => 114.42],
            'high' => ['latitude' => -8.05, 'longitude' => 115.72],
        ]],
    ];
    if ($pageToken) {
        $payload['pageToken'] = $pageToken;
    }

    $ch = curl_init('https://places.googleapis.com/v1/places:searchText');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . implode(',', $fields),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['error' => 'Network error: ' . $err];
    }
    $data = json_decode($raw, true);
    if ($status !== 200) {
        return ['error' => $data['error']['message'] ?? "Google API returned HTTP $status"];
    }

    $results = [];
    foreach ($data['places'] ?? [] as $p) {
        $results[] = [
            'place_id' => $p['id'],
            'name'     => $p['displayName']['text'] ?? '(no name)',
            'address'  => $p['formattedAddress'] ?? '',
            'phone'    => $p['nationalPhoneNumber'] ?? ($p['internationalPhoneNumber'] ?? ''),
            'website'  => $p['websiteUri'] ?? '',
            'rating'   => $p['rating'] ?? null,
            'reviews'  => $p['userRatingCount'] ?? null,
            'maps_url' => $p['googleMapsUri'] ?? '',
            'type'     => $p['primaryTypeDisplayName']['text'] ?? '',
            'status'   => $p['businessStatus'] ?? '',
        ];
    }
    return ['results' => $results, 'next' => $data['nextPageToken'] ?? null];
}

/** Guess the Bali area from a formatted address. */
function guess_area(string $address): string
{
    foreach (BALI_AREAS as $a) {
        if (stripos($address, $a) !== false) {
            return $a;
        }
    }
    $regency = ['Badung' => 'Kuta', 'Gianyar' => 'Gianyar', 'Denpasar' => 'Denpasar', 'Tabanan' => 'Tabanan', 'Buleleng' => 'Singaraja', 'Karangasem' => 'Karangasem', 'Klungkung' => 'Klungkung', 'Bangli' => 'Bangli', 'Jembrana' => 'Jembrana'];
    foreach ($regency as $k => $v) {
        if (stripos($address, $k) !== false) {
            return $v;
        }
    }
    return '';
}

/**
 * Quick lead-quality hint for a search result before it is saved.
 * Businesses with many reviews but no website are the best prospects.
 */
function result_hint(array $r): array
{
    $reviews = (int) ($r['reviews'] ?? 0);
    if ($r['website'] === '') {
        if ($r['reviews'] === null) {
            // OpenStreetMap has no review counts; reachable businesses are the best bet.
            $reachable = ($r['phone'] ?? '') !== '' || ($r['email'] ?? '') !== '' || ($r['instagram'] ?? '') !== '';
            return $reachable ? ['hot', 'No website · has contact details'] : ['warm', 'No website · find contact on Maps'];
        }
        return $reviews >= 30 ? ['hot', 'No website · busy business'] : ['warm', 'No website'];
    }
    $host = strtolower((string) parse_url($r['website'], PHP_URL_HOST));
    foreach (['facebook.com', 'instagram.com', 'linktr.ee', 'wa.me', 'business.site', 'wixsite.com', 'blogspot.', 'booking.com', 'airbnb.', 'tripadvisor.'] as $weak) {
        if (str_contains($host, $weak)) {
            return ['hot', 'Uses ' . $weak . ' instead of own site'];
        }
    }
    return ['cold', 'Has a website — audit it'];
}
