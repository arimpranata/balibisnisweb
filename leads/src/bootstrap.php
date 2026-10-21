<?php
/**
 * Lightandpulse Lead Finder — bootstrap: database, session, helpers.
 */
declare(strict_types=1);

const APP_NAME = 'Lightandpulse Leads';
const APP_VERSION = '1.0.0';

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');

date_default_timezone_set('Asia/Makassar'); // WITA — Bali

const STAGES = [
    'new'         => 'New',
    'researched'  => 'Researched',
    'contacted'   => 'Contacted',
    'replied'     => 'Replied',
    'proposal'    => 'Proposal sent',
    'negotiation' => 'Negotiation',
    'won'         => 'Won',
    'lost'        => 'Lost',
];

const PRIORITIES = ['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'];

const SERVICES = [
    'website'     => 'New WordPress website',
    'redesign'    => 'Redesign (Elementor)',
    'woocommerce' => 'WooCommerce store',
    'booking'     => 'Booking / reservation',
    'maintenance' => 'Care plan & maintenance',
    'seo'         => 'Local SEO',
    'speed'       => 'Speed & security fix',
    'migration'   => 'Migrate from Wix/other',
];

const ACTIVITY_TYPES = [
    'note' => 'Note', 'call' => 'Call', 'whatsapp' => 'WhatsApp', 'email' => 'Email',
    'meeting' => 'Meeting', 'instagram' => 'Instagram DM', 'stage' => 'Stage change', 'audit' => 'Website audit',
];

const BALI_AREAS = [
    'Canggu', 'Seminyak', 'Kuta', 'Legian', 'Kerobokan', 'Jimbaran', 'Uluwatu', 'Nusa Dua',
    'Sanur', 'Denpasar', 'Ubud', 'Tabanan', 'Pererenan', 'Amed', 'Lovina', 'Singaraja',
    'Nusa Penida', 'Nusa Lembongan', 'Sidemen', 'Munduk', 'Gianyar', 'Klungkung', 'Karangasem', 'Jembrana',
];

const BALI_CATEGORIES = [
    'Villa', 'Boutique hotel', 'Guesthouse', 'Restaurant', 'Cafe', 'Beach club', 'Spa', 'Yoga studio',
    'Surf school', 'Dive center', 'Tour operator', 'Car / scooter rental', 'Wedding organizer', 'Photographer',
    'Real estate agency', 'Property developer', 'Coworking space', 'Fitness / gym', 'Clinic', 'Dental clinic',
    'Fashion boutique', 'Jewelry / silver', 'Furniture / handicraft', 'Coffee roaster', 'Bakery',
    'Cooking class', 'Retreat center', 'Language school', 'Event organizer', 'Interior designer',
];

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0775, true);
}

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('lpleads');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $pdo = new PDO('sqlite:' . DATA_DIR . '/leads.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT
    );
    CREATE TABLE IF NOT EXISTS leads (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        name            TEXT NOT NULL,
        contact_person  TEXT DEFAULT '',
        category        TEXT DEFAULT '',
        area            TEXT DEFAULT '',
        address         TEXT DEFAULT '',
        phone           TEXT DEFAULT '',
        whatsapp        TEXT DEFAULT '',
        email           TEXT DEFAULT '',
        website         TEXT DEFAULT '',
        instagram       TEXT DEFAULT '',
        facebook        TEXT DEFAULT '',
        maps_url        TEXT DEFAULT '',
        place_id        TEXT DEFAULT NULL UNIQUE,
        rating          REAL DEFAULT NULL,
        reviews         INTEGER DEFAULT NULL,
        stage           TEXT NOT NULL DEFAULT 'new',
        priority        TEXT NOT NULL DEFAULT 'warm',
        services        TEXT DEFAULT '',
        est_value       INTEGER DEFAULT 0,
        source          TEXT DEFAULT 'manual',
        tags            TEXT DEFAULT '',
        next_followup   TEXT DEFAULT NULL,
        last_contacted  TEXT DEFAULT NULL,
        audit_json      TEXT DEFAULT NULL,
        audit_score     INTEGER DEFAULT NULL,
        audited_at      TEXT DEFAULT NULL,
        summary         TEXT DEFAULT '',
        created_at      TEXT NOT NULL DEFAULT (datetime('now','localtime')),
        updated_at      TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE INDEX IF NOT EXISTS idx_leads_stage ON leads(stage);
    CREATE INDEX IF NOT EXISTS idx_leads_followup ON leads(next_followup);
    CREATE TABLE IF NOT EXISTS activities (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id    INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
        type       TEXT NOT NULL DEFAULT 'note',
        body       TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE INDEX IF NOT EXISTS idx_act_lead ON activities(lead_id);
    CREATE TABLE IF NOT EXISTS templates (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        name     TEXT NOT NULL,
        channel  TEXT NOT NULL DEFAULT 'whatsapp',
        language TEXT NOT NULL DEFAULT 'id',
        subject  TEXT DEFAULT '',
        body     TEXT NOT NULL
    );
    SQL);

    if ((int) $pdo->query('SELECT COUNT(*) FROM templates')->fetchColumn() === 0) {
        seed_templates($pdo);
    }
}

function seed_templates(PDO $pdo): void
{
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
        ['Online shop pitch — WooCommerce (EN)', 'email', 'en', 'An online shop for {name}?',
"Hi {contact},\n\nI'm {my_name}, founder of {my_agency} — a Bali-based studio building WordPress and WooCommerce websites.\n\nI came across {name} in {area} and love what you're doing. Many {category} businesses here now sell directly to customers abroad through their own online shop, with local payment (QRIS, bank transfer) and international cards, and shipping rates calculated automatically.\n\nI'd be glad to show you a few examples and a rough quote — no obligation. Would a short call this week work?\n\nBest regards,\n{my_name}\n{my_agency}\n{my_website} · WhatsApp {my_phone}"],
        ['Proposal follow-up (EN)', 'email', 'en', 'Following up: website proposal for {name}',
"Hi {contact},\n\nJust following up on the proposal I sent for {name}. Happy to answer any questions or adjust the scope to fit your budget and timeline.\n\nWould you like to schedule a quick call?\n\nBest,\n{my_name}\n{my_agency} · {my_website}"],
    ];
    $stmt = $pdo->prepare('INSERT INTO templates (name, channel, language, subject, body) VALUES (?,?,?,?,?)');
    foreach ($rows as $r) {
        $stmt->execute($r);
    }
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, ?string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([$key, $value]);
}

function profile(): array
{
    return [
        'my_name'    => setting('my_name', 'Ida Bagus Arim Pranata'),
        'my_agency'  => setting('my_agency', 'Lightandpulse Digital Agency'),
        'my_website' => setting('my_website', 'https://lightandpulse.com'),
        'my_phone'   => setting('my_phone', ''),
        'my_email'   => setting('my_email', ''),
    ];
}

// ---------------------------------------------------------------------------
// Auth & CSRF
// ---------------------------------------------------------------------------
function is_setup(): bool
{
    return setting('password_hash') !== null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['uid']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['p' => $page] + $params);
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function rupiah(?int $n): string
{
    return 'Rp ' . number_format((int) $n, 0, ',', '.');
}

/** Normalise Indonesian phone numbers into wa.me format (62xxxxxxxxx). */
function wa_number(?string $raw): string
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

function normalize_url(?string $u): string
{
    $u = trim((string) $u);
    if ($u === '') {
        return '';
    }
    if (!preg_match('~^https?://~i', $u)) {
        $u = 'https://' . $u;
    }
    return filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
}

function safe_href(?string $u): string
{
    $u = (string) $u;
    return preg_match('~^https?://~i', $u) ? $u : '#';
}

function instagram_url(?string $handle): string
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

function stage_badge(string $stage): string
{
    return '<span class="badge stage-' . e($stage) . '">' . e(STAGES[$stage] ?? $stage) . '</span>';
}

function priority_badge(string $p): string
{
    return '<span class="badge prio-' . e($p) . '">' . e(PRIORITIES[$p] ?? $p) . '</span>';
}

function score_badge(?int $score): string
{
    if ($score === null) {
        return '<span class="score score-none" title="Not audited yet">—</span>';
    }
    $cls = $score >= 60 ? 'high' : ($score >= 35 ? 'mid' : 'low');
    return '<span class="score score-' . $cls . '" title="Opportunity score: higher = more room to help">' . (int) $score . '</span>';
}

function find_lead(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function log_activity(int $leadId, string $type, string $body): void
{
    db()->prepare('INSERT INTO activities (lead_id, type, body) VALUES (?,?,?)')->execute([$leadId, $type, $body]);
    if (in_array($type, ['call', 'whatsapp', 'email', 'meeting', 'instagram'], true)) {
        db()->prepare("UPDATE leads SET last_contacted = datetime('now','localtime'), updated_at = datetime('now','localtime') WHERE id = ?")->execute([$leadId]);
    }
}

/** Fill a message template with lead + profile placeholders. */
function render_template(string $text, array $lead): string
{
    $audit = $lead['audit_json'] ? json_decode($lead['audit_json'], true) : null;
    $findings = '';
    if ($audit && !empty($audit['findings'])) {
        $top = array_slice($audit['findings'], 0, 4);
        $findings = '• ' . implode("\n• ", array_map(fn($f) => $f['text'], $top));
    }
    $vars = profile() + [
        'name'           => $lead['name'],
        'contact'        => $lead['contact_person'] ?: ($lead['name'] . ' team'),
        'area'           => $lead['area'] ?: 'Bali',
        'category'       => $lead['category'] ? strtolower($lead['category']) : 'local',
        'website'        => $lead['website'] ?: 'your website',
        'audit_findings' => $findings,
    ];
    return preg_replace_callback('/\{(\w+)\}/', fn($m) => array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0], $text);
}
