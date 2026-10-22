<?php
/**
 * OpenStreetMap search via the Overpass API — free, no API key, no billing.
 * OSM has fewer reviews/ratings than Google, but often includes phone, website,
 * email and Instagram tags that business owners or locals added themselves.
 */
declare(strict_types=1);

const OSM_DEFAULT_ENDPOINT = 'https://overpass-api.de/api/interpreter';

/** Approximate bounding boxes (south, west, north, east) for Bali areas. */
const OSM_AREA_BBOX = [
    'Bali'           => [-8.86, 114.42, -8.05, 115.72],
    'Canggu'         => [-8.665, 115.115, -8.62, 115.16],
    'Pererenan'      => [-8.655, 115.10, -8.625, 115.13],
    'Seminyak'       => [-8.70, 115.15, -8.675, 115.175],
    'Kerobokan'      => [-8.68, 115.15, -8.645, 115.185],
    'Legian'         => [-8.715, 115.16, -8.695, 115.18],
    'Kuta'           => [-8.74, 115.16, -8.71, 115.185],
    'Jimbaran'       => [-8.80, 115.15, -8.76, 115.19],
    'Uluwatu'        => [-8.85, 115.06, -8.78, 115.15],
    'Nusa Dua'       => [-8.83, 115.20, -8.78, 115.24],
    'Sanur'          => [-8.72, 115.25, -8.66, 115.27],
    'Denpasar'       => [-8.70, 115.17, -8.60, 115.25],
    'Ubud'           => [-8.54, 115.24, -8.48, 115.29],
    'Tabanan'        => [-8.60, 115.05, -8.50, 115.15],
    'Amed'           => [-8.36, 115.62, -8.32, 115.69],
    'Lovina'         => [-8.17, 114.98, -8.14, 115.06],
    'Singaraja'      => [-8.13, 115.07, -8.09, 115.12],
    'Nusa Penida'    => [-8.82, 115.43, -8.67, 115.62],
    'Nusa Lembongan' => [-8.70, 115.42, -8.66, 115.47],
    'Sidemen'        => [-8.50, 115.42, -8.45, 115.46],
    'Munduk'         => [-8.29, 115.04, -8.25, 115.09],
    'Gianyar'        => [-8.60, 115.25, -8.48, 115.40],
    'Klungkung'      => [-8.58, 115.37, -8.50, 115.45],
    'Karangasem'     => [-8.48, 115.55, -8.40, 115.65],
    'Jembrana'       => [-8.42, 114.50, -8.25, 114.75],
];

/**
 * Overpass tag filters per category. Each entry is a list of filters that are
 * OR-ed together; a filter is the tag part of an Overpass statement.
 */
const OSM_CATEGORY_TAGS = [
    'Villa'                  => ['["tourism"~"^(guest_house|apartment|chalet|hotel)$"]["name"~"villa",i]'],
    'Boutique hotel'         => ['["tourism"="hotel"]'],
    'Guesthouse'             => ['["tourism"~"^(guest_house|hostel)$"]'],
    'Restaurant'             => ['["amenity"="restaurant"]'],
    'Cafe'                   => ['["amenity"="cafe"]'],
    'Beach club'             => ['["amenity"~"^(bar|nightclub|restaurant)$"]["name"~"beach club",i]'],
    'Spa'                    => ['["leisure"="spa"]', '["shop"="massage"]', '["amenity"="spa"]', '["shop"="beauty"]["name"~"spa",i]'],
    'Yoga studio'            => ['["sport"="yoga"]', '["name"~"yoga",i]["leisure"]', '["name"~"yoga",i]["amenity"]'],
    'Surf school'            => ['["sport"="surfing"]', '["name"~"surf (school|camp|lesson)",i]'],
    'Dive center'            => ['["shop"="scuba_diving"]', '["sport"="scuba_diving"]', '["name"~"div(e|ing)",i]["shop"]'],
    'Tour operator'          => ['["shop"="travel_agency"]', '["office"="travel_agent"]', '["office"="tourism"]'],
    'Car / scooter rental'   => ['["amenity"~"^(car_rental|motorcycle_rental|scooter_rental)$"]', '["shop"~"^(motorcycle_rental|rental)$"]'],
    'Wedding organizer'      => ['["shop"="wedding"]', '["name"~"wedding",i]["office"]', '["name"~"wedding",i]["shop"]'],
    'Photographer'           => ['["craft"="photographer"]', '["shop"="photo"]', '["office"="photographer"]'],
    'Real estate agency'     => ['["office"="estate_agent"]', '["shop"="estate_agent"]'],
    'Property developer'     => ['["office"="property_developer"]', '["name"~"(property|properties|realty)",i]["office"]'],
    'Coworking space'        => ['["amenity"="coworking_space"]', '["office"="coworking"]'],
    'Fitness / gym'          => ['["leisure"="fitness_centre"]'],
    'Clinic'                 => ['["amenity"~"^(clinic|doctors)$"]'],
    'Dental clinic'          => ['["amenity"="dentist"]'],
    'Fashion boutique'       => ['["shop"~"^(clothes|boutique|fashion)$"]'],
    'Jewelry / silver'       => ['["shop"="jewelry"]', '["craft"~"^(jeweller|silversmith)$"]'],
    'Furniture / handicraft' => ['["shop"~"^(furniture|craft|art|interior_decoration)$"]', '["craft"~"^(carpenter|woodcarver|handicraft)$"]'],
    'Coffee roaster'         => ['["craft"="coffee_roaster"]', '["shop"="coffee"]'],
    'Bakery'                 => ['["shop"~"^(bakery|pastry)$"]'],
    'Cooking class'          => ['["name"~"cooking class",i]'],
    'Retreat center'         => ['["name"~"retreat",i]["tourism"]', '["name"~"retreat",i]["leisure"]'],
    'Language school'        => ['["amenity"="language_school"]'],
    'Event organizer'        => ['["office"="event_management"]', '["name"~"event",i]["office"]'],
    'Interior designer'      => ['["office"="interior_design"]', '["craft"="interior_designer"]'],
];

function osm_bbox(string $area): array
{
    foreach (OSM_AREA_BBOX as $name => $box) {
        if (strcasecmp($name, trim($area)) === 0) {
            return $box;
        }
    }
    return OSM_AREA_BBOX['Bali'];
}

/** Reduce user text to letters, digits and spaces for an Overpass name match. */
function osm_regex(string $s): string
{
    // Keep it to plain words so nothing needs escaping inside the Overpass string.
    return trim(preg_replace('/\s+/u', ' ', preg_replace("/[^\\p{L}\\p{N} &'-]/u", ' ', $s)));
}

function osm_build_query(string $category, string $area, string $keyword): string
{
    $filters = [];
    if ($keyword !== '') {
        $filters[] = '["name"~"' . osm_regex($keyword) . '",i]';
    } elseif (isset(OSM_CATEGORY_TAGS[$category])) {
        $filters = OSM_CATEGORY_TAGS[$category];
    } elseif ($category !== '') {
        $filters[] = '["name"~"' . osm_regex($category) . '",i]';
    }
    [$s, $w, $n, $e] = osm_bbox($area);
    $bbox = "($s,$w,$n,$e)";
    $parts = '';
    foreach ($filters as $f) {
        // Only named places can be prospects.
        $parts .= "  nwr{$f}[\"name\"]{$bbox};\n";
    }
    return "[out:json][timeout:60];\n(\n{$parts});\nout center tags 300;";
}

function osm_search(string $category, string $area, string $keyword, string $endpoint = OSM_DEFAULT_ENDPOINT): array
{
    if ($category === '' && $keyword === '') {
        return ['error' => 'Choose a category or type a keyword.'];
    }
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 75,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_POSTFIELDS     => http_build_query(['data' => osm_build_query($category, $area, $keyword)]),
        // Overpass asks clients to identify themselves.
        CURLOPT_USERAGENT      => 'LightandpulseLeads/' . APP_VERSION . ' (+https://lightandpulse.com)',
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['error' => 'Could not reach OpenStreetMap: ' . $err];
    }
    if ($status === 429 || $status === 504) {
        return ['error' => 'OpenStreetMap is busy right now. Wait a minute and search again.'];
    }
    $data = json_decode($raw, true);
    if ($status !== 200 || !is_array($data)) {
        return ['error' => "OpenStreetMap returned HTTP $status"];
    }

    $results = [];
    foreach ($data['elements'] ?? [] as $el) {
        $t = $el['tags'] ?? [];
        if (empty($t['name'])) {
            continue;
        }
        $key = $el['type'] . '/' . $el['id'];
        $lat = $el['lat'] ?? ($el['center']['lat'] ?? null);
        $lon = $el['lon'] ?? ($el['center']['lon'] ?? null);
        $addr = trim(implode(', ', array_filter([
            trim(($t['addr:street'] ?? '') . ' ' . ($t['addr:housenumber'] ?? '')),
            $t['addr:village'] ?? ($t['addr:suburb'] ?? ''),
            $t['addr:city'] ?? ($t['addr:district'] ?? ''),
        ])));
        $results[$key] = [
            'place_id'  => 'osm:' . $key,
            'name'      => $t['name'],
            'address'   => $addr !== '' ? $addr : ($area !== '' ? "$area, Bali" : 'Bali'),
            'phone'     => $t['phone'] ?? ($t['contact:phone'] ?? ($t['contact:mobile'] ?? ($t['contact:whatsapp'] ?? ''))),
            'whatsapp'  => $t['contact:whatsapp'] ?? '',
            'website'   => normalize_url($t['website'] ?? ($t['contact:website'] ?? ($t['url'] ?? ''))),
            'email'     => filter_var($t['email'] ?? ($t['contact:email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'instagram' => osm_instagram($t['contact:instagram'] ?? ($t['instagram'] ?? '')),
            'facebook'  => normalize_url($t['contact:facebook'] ?? ($t['facebook'] ?? '')),
            'rating'    => null,
            'reviews'   => null,
            'maps_url'  => "https://www.openstreetmap.org/{$key}",
            'gmaps_url' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($t['name'] . ($lat !== null ? " $lat,$lon" : ' Bali')),
            'type'      => osm_type_label($t),
            'status'    => isset($t['disused:shop']) || isset($t['disused:amenity']) ? 'CLOSED_PERMANENTLY' : '',
        ];
    }
    $results = array_values($results);
    // Best prospects first: reachable (phone/email) but no website.
    usort($results, function ($a, $b) {
        $rank = fn($r) => ($r['website'] === '' ? 2 : 0) + (($r['phone'] !== '' || $r['email'] !== '') ? 1 : 0);
        return $rank($b) <=> $rank($a) ?: strcasecmp($a['name'], $b['name']);
    });
    return ['results' => $results, 'next' => null];
}

function osm_instagram(string $v): string
{
    $v = trim($v);
    if ($v === '') {
        return '';
    }
    if (preg_match('~instagram\.com/([A-Za-z0-9_.]+)~i', $v, $m)) {
        return $m[1];
    }
    return ltrim($v, '@');
}

function osm_type_label(array $t): string
{
    foreach (['tourism', 'amenity', 'shop', 'leisure', 'office', 'craft', 'sport'] as $k) {
        if (!empty($t[$k])) {
            return ucfirst(str_replace('_', ' ', (string) $t[$k]));
        }
    }
    return '';
}
