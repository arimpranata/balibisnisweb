<?php
/**
 * Lightandpulse Leads — constants, database, settings and shared helpers.
 */
defined('ABSPATH') || exit;

const LPL_DB_VERSION = '1';
const LPL_CAP = 'manage_lp_leads';

const LPL_STAGES = [
    'new'         => 'New',
    'researched'  => 'Researched',
    'contacted'   => 'Contacted',
    'replied'     => 'Replied',
    'proposal'    => 'Proposal sent',
    'negotiation' => 'Negotiation',
    'won'         => 'Won',
    'lost'        => 'Lost',
];

const LPL_PRIORITIES = ['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'];

const LPL_SERVICES = [
    'website'     => 'New WordPress website',
    'redesign'    => 'Redesign (Elementor)',
    'woocommerce' => 'WooCommerce store',
    'booking'     => 'Booking / reservation',
    'maintenance' => 'Care plan & maintenance',
    'seo'         => 'Local SEO',
    'speed'       => 'Speed & security fix',
    'migration'   => 'Migrate from Wix/other',
];

const LPL_SOURCES = [
    'manual'        => 'Manual',
    'openstreetmap' => 'OpenStreetMap',
    'google_maps'   => 'Google Maps',
    'website_form'  => 'Website form',
    'instagram'     => 'Instagram',
    'referral'      => 'Referral',
    'walk_in'       => 'Walk-in / networking',
    'import'        => 'Import',
];

const LPL_ACTIVITY_TYPES = [
    'note' => 'Note', 'call' => 'Call', 'whatsapp' => 'WhatsApp', 'email' => 'Email',
    'meeting' => 'Meeting', 'instagram' => 'Instagram DM', 'stage' => 'Stage change', 'audit' => 'Website audit',
];

const LPL_BALI_AREAS = [
    'Canggu', 'Seminyak', 'Kuta', 'Legian', 'Kerobokan', 'Jimbaran', 'Uluwatu', 'Nusa Dua',
    'Sanur', 'Denpasar', 'Ubud', 'Tabanan', 'Pererenan', 'Amed', 'Lovina', 'Singaraja',
    'Nusa Penida', 'Nusa Lembongan', 'Sidemen', 'Munduk', 'Gianyar', 'Klungkung', 'Karangasem', 'Jembrana',
];

const LPL_BALI_CATEGORIES = [
    'Villa', 'Boutique hotel', 'Guesthouse', 'Restaurant', 'Cafe', 'Beach club', 'Spa', 'Yoga studio',
    'Surf school', 'Dive center', 'Tour operator', 'Car / scooter rental', 'Wedding organizer', 'Photographer',
    'Real estate agency', 'Property developer', 'Coworking space', 'Fitness / gym', 'Clinic', 'Dental clinic',
    'Fashion boutique', 'Jewelry / silver', 'Furniture / handicraft', 'Coffee roaster', 'Bakery',
    'Cooking class', 'Retreat center', 'Language school', 'Event organizer', 'Interior designer',
];

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
function lpl_table(string $name): string
{
    global $wpdb;
    return $wpdb->prefix . 'lp_' . $name;
}

function lpl_install(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $leads = lpl_table('leads');
    $acts = lpl_table('activities');
    $tpls = lpl_table('templates');

    dbDelta("CREATE TABLE $leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(200) NOT NULL,
  contact_person varchar(150) NOT NULL DEFAULT '',
  category varchar(100) NOT NULL DEFAULT '',
  area varchar(100) NOT NULL DEFAULT '',
  address varchar(255) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  whatsapp varchar(30) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  website varchar(255) NOT NULL DEFAULT '',
  instagram varchar(100) NOT NULL DEFAULT '',
  facebook varchar(255) NOT NULL DEFAULT '',
  maps_url varchar(255) NOT NULL DEFAULT '',
  place_id varchar(190) DEFAULT NULL,
  rating decimal(2,1) DEFAULT NULL,
  reviews int(11) DEFAULT NULL,
  stage varchar(20) NOT NULL DEFAULT 'new',
  priority varchar(10) NOT NULL DEFAULT 'warm',
  services varchar(255) NOT NULL DEFAULT '',
  est_value bigint(20) NOT NULL DEFAULT 0,
  source varchar(30) NOT NULL DEFAULT 'manual',
  tags varchar(255) NOT NULL DEFAULT '',
  next_followup date DEFAULT NULL,
  last_contacted datetime DEFAULT NULL,
  audit_json longtext DEFAULT NULL,
  audit_score tinyint(3) unsigned DEFAULT NULL,
  audited_at datetime DEFAULT NULL,
  summary text NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY place_id (place_id),
  KEY stage (stage),
  KEY next_followup (next_followup)
) $charset;");

    dbDelta("CREATE TABLE $acts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  type varchar(20) NOT NULL DEFAULT 'note',
  body text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id)
) $charset;");

    dbDelta("CREATE TABLE $tpls (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(150) NOT NULL,
  channel varchar(20) NOT NULL DEFAULT 'whatsapp',
  language varchar(5) NOT NULL DEFAULT 'id',
  subject varchar(255) NOT NULL DEFAULT '',
  body text NOT NULL,
  PRIMARY KEY  (id)
) $charset;");

    if ((int) $wpdb->get_var("SELECT COUNT(*) FROM $tpls") === 0) {
        lpl_seed_templates();
    }
    update_option('lpl_db_version', LPL_DB_VERSION, false);
}

function lpl_seed_templates(): void
{
    global $wpdb;
    $rows = [
        ['Intro — no website (ID)', 'whatsapp', 'id', '',
"Halo {contact}, salam kenal 🙏\n\nSaya {my_name} dari {my_agency}, web developer di Bali. Saya lihat {name} punya review bagus di Google Maps, tapi belum ada website resmi.\n\nWebsite sendiri bisa bantu tamu booking langsung tanpa komisi OTA, dan tampil lebih profesional untuk tamu mancanegara.\n\nKalau berkenan, saya bisa kirimkan contoh desain gratis untuk {name}. Boleh?\n\nTerima kasih,\n{my_name}\n{my_website}"],
        ['Intro — no website (EN)', 'whatsapp', 'en', '',
"Hi {contact}, hope you're well!\n\nI'm {my_name} from {my_agency}, a Bali-based WordPress developer. I noticed {name} has great reviews on Google Maps but no website of its own yet.\n\nA direct-booking website helps you save on OTA commission and look more professional to international guests.\n\nWould you like me to put together a free homepage mock-up for {name}?\n\nCheers,\n{my_name}\n{my_website}"],
        ['Website audit findings (EN)', 'whatsapp', 'en', '',
"Hi {contact},\n\nI'm {my_name}, a web developer in Bali ({my_website}). I took a quick look at {website} and noticed a few things that could be costing you bookings:\n\n{audit_findings}\n\nThese are usually quick fixes. Happy to send a short free report with suggestions — interested?\n\nBest,\n{my_name}"],
        ['Temuan audit website (ID)', 'whatsapp', 'id', '',
"Halo {contact},\n\nSaya {my_name}, web developer di Bali ({my_website}). Saya sempat cek website {website} dan menemukan beberapa hal yang bisa mengurangi jumlah booking/penjualan:\n\n{audit_findings}\n\nBiasanya ini bisa diperbaiki dengan cepat. Mau saya kirimkan laporan singkat gratis beserta saran perbaikannya?\n\nSalam,\n{my_name}"],
        ['Follow-up (ID)', 'whatsapp', 'id', '',
"Halo {contact}, saya {my_name} dari {my_agency} 🙏\n\nMau follow-up pesan saya sebelumnya soal website untuk {name}. Apakah ada waktu minggu ini untuk ngobrol 15 menit, atau saya kirim penawaran via WhatsApp saja?\n\nTerima kasih!"],
        ['Reply to website enquiry (ID)', 'whatsapp', 'id', '',
"Halo {contact}, terima kasih sudah menghubungi {my_agency} lewat website 🙏\n\nSaya {my_name}. Boleh saya tahu sedikit tentang {name} dan website seperti apa yang dibutuhkan? Kalau lebih nyaman, kita bisa ngobrol lewat telepon atau ketemu langsung.\n\nSalam,\n{my_name}"],
        ['Online shop pitch — WooCommerce (EN)', 'email', 'en', 'An online shop for {name}?',
"Hi {contact},\n\nI'm {my_name}, founder of {my_agency} — a Bali-based studio building WordPress and WooCommerce websites.\n\nI came across {name} in {area} and love what you're doing. Many {category} businesses here now sell directly to customers abroad through their own online shop, with local payment (QRIS, bank transfer) and international cards, and shipping rates calculated automatically.\n\nI'd be glad to show you a few examples and a rough quote — no obligation. Would a short call this week work?\n\nBest regards,\n{my_name}\n{my_agency}\n{my_website} · WhatsApp {my_phone}"],
        ['Proposal follow-up (EN)', 'email', 'en', 'Following up: website proposal for {name}',
"Hi {contact},\n\nJust following up on the proposal I sent for {name}. Happy to answer any questions or adjust the scope to fit your budget and timeline.\n\nWould you like to schedule a quick call?\n\nBest,\n{my_name}\n{my_agency} · {my_website}"],
    ];
    foreach ($rows as $r) {
        $wpdb->insert(lpl_table('templates'), ['name' => $r[0], 'channel' => $r[1], 'language' => $r[2], 'subject' => $r[3], 'body' => $r[4]]);
    }
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function lpl_settings(): array
{
    $user = wp_get_current_user();
    $defaults = [
        'my_name'        => $user && $user->exists() ? $user->display_name : 'Ida Bagus Arim Pranata',
        'my_agency'      => get_bloginfo('name') ?: 'Lightandpulse Digital Agency',
        'my_website'     => home_url('/'),
        'my_phone'       => '',
        'my_email'       => get_option('admin_email'),
        'followup_days'  => 3,
        'places_api_key' => '',
        'osm_endpoint'   => LPL_OSM_DEFAULT_ENDPOINT,
        'form_notify'    => 1,
    ];
    return array_merge($defaults, (array) get_option('lpl_settings', []));
}

function lpl_setting(string $key): mixed
{
    return lpl_settings()[$key] ?? null;
}

function lpl_profile(): array
{
    $s = lpl_settings();
    return array_intersect_key($s, array_flip(['my_name', 'my_agency', 'my_website', 'my_phone', 'my_email']));
}

// ---------------------------------------------------------------------------
// Request & URL helpers
// ---------------------------------------------------------------------------
function lpl_url(string $page = 'dashboard', array $params = []): string
{
    return add_query_arg(array_merge(['page' => 'lpl-' . $page], $params), admin_url('admin.php'));
}

function lpl_post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim(wp_unslash($v)) : $default;
}

function lpl_get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim(sanitize_text_field(wp_unslash($v))) : $default;
}

/** Opening tag + hidden fields for a form handled by admin-post.php. */
function lpl_form_open(string $action, array $attrs = []): string
{
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
    }
    return '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"' . $extra . '>'
        . '<input type="hidden" name="action" value="lpl_' . esc_attr($action) . '">'
        . wp_nonce_field('lpl_' . $action, '_lplnonce', true, false);
}

function lpl_flash(?string $msg = null, string $type = 'success'): ?array
{
    $key = 'lpl_flash_' . get_current_user_id();
    if ($msg !== null) {
        set_transient($key, ['msg' => $msg, 'type' => $type], 120);
        return null;
    }
    $f = get_transient($key);
    if ($f) {
        delete_transient($key);
    }
    return $f ?: null;
}

function lpl_redirect(string $page, array $params = []): void
{
    wp_safe_redirect(lpl_url($page, $params));
    exit;
}

// ---------------------------------------------------------------------------
// Formatting
// ---------------------------------------------------------------------------
function lpl_rupiah(?int $n): string
{
    return 'Rp ' . number_format((int) $n, 0, ',', '.');
}

/** Normalise Indonesian phone numbers into wa.me format (62xxxxxxxxx). */
function lpl_wa_number(?string $raw): string
{
    $d = preg_replace('/\D+/', '', (string) $raw);
    if ($d === '') {
        return '';
    }
    if (str_starts_with($d, '0')) {
        $d = '62' . substr($d, 1);
    } elseif (str_starts_with($d, '8')) {
        $d = '62' . $d;
    }
    return $d;
}

function lpl_normalize_url(?string $u): string
{
    $u = trim((string) $u);
    if ($u === '') {
        return '';
    }
    if (!preg_match('~^https?://~i', $u)) {
        $u = 'https://' . $u;
    }
    return filter_var($u, FILTER_VALIDATE_URL) ? esc_url_raw($u) : '';
}

function lpl_instagram_url(?string $handle): string
{
    $h = trim((string) $handle);
    if ($h === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $h)) {
        return $h;
    }
    return 'https://instagram.com/' . rawurlencode(ltrim($h, '@'));
}

function lpl_stage_badge(string $stage): string
{
    return '<span class="lpl-badge stage-' . esc_attr($stage) . '">' . esc_html(LPL_STAGES[$stage] ?? $stage) . '</span>';
}

function lpl_priority_badge(string $p): string
{
    return '<span class="lpl-badge prio-' . esc_attr($p) . '">' . esc_html(LPL_PRIORITIES[$p] ?? $p) . '</span>';
}

function lpl_score_badge($score): string
{
    if ($score === null || $score === '') {
        return '<span class="lpl-score score-none" title="Not audited yet">—</span>';
    }
    $score = (int) $score;
    $cls = $score >= 60 ? 'high' : ($score >= 35 ? 'mid' : 'low');
    return '<span class="lpl-score score-' . $cls . '" title="Opportunity score: higher = more room to help">' . $score . '</span>';
}

// ---------------------------------------------------------------------------
// Leads & activity
// ---------------------------------------------------------------------------
function lpl_find_lead(int $id): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . lpl_table('leads') . ' WHERE id = %d', $id), ARRAY_A);
    return $row ?: null;
}

function lpl_now(): string
{
    return current_time('mysql');
}

function lpl_today(): string
{
    return wp_date('Y-m-d');
}

function lpl_log_activity(int $leadId, string $type, string $body): void
{
    global $wpdb;
    $wpdb->insert(lpl_table('activities'), ['lead_id' => $leadId, 'type' => $type, 'body' => $body, 'created_at' => lpl_now()]);
    $update = ['updated_at' => lpl_now()];
    if (in_array($type, ['call', 'whatsapp', 'email', 'meeting', 'instagram'], true)) {
        $update['last_contacted'] = lpl_now();
    }
    $wpdb->update(lpl_table('leads'), $update, ['id' => $leadId]);
}

function lpl_set_stage(array $lead, string $stage): void
{
    global $wpdb;
    if (!isset(LPL_STAGES[$stage]) || $lead['stage'] === $stage) {
        return;
    }
    $wpdb->update(lpl_table('leads'), ['stage' => $stage, 'updated_at' => lpl_now()], ['id' => $lead['id']]);
    lpl_log_activity((int) $lead['id'], 'stage', 'Stage: ' . LPL_STAGES[$lead['stage']] . ' → ' . LPL_STAGES[$stage]);
}

/** Fill a message template with lead + profile placeholders. */
function lpl_render_template(string $text, array $lead): string
{
    $audit = !empty($lead['audit_json']) ? json_decode($lead['audit_json'], true) : null;
    $findings = '';
    if ($audit && !empty($audit['findings'])) {
        $top = array_slice($audit['findings'], 0, 4);
        $findings = '• ' . implode("\n• ", array_map(fn($f) => $f['text'], $top));
    }
    $vars = lpl_profile() + [
        'name'           => $lead['name'],
        'contact'        => $lead['contact_person'] ?: ($lead['name'] . ' team'),
        'area'           => $lead['area'] ?: 'Bali',
        'category'       => $lead['category'] ? strtolower($lead['category']) : 'local',
        'website'        => $lead['website'] ?: 'your website',
        'audit_findings' => $findings,
    ];
    return preg_replace_callback('/\{(\w+)\}/', fn($m) => array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0], $text);
}
