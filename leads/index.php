<?php
/**
 * Lightandpulse Leads — find, manage and connect with potential clients in Bali.
 * Single-file front controller. Pages are selected with ?p=<page>.
 */
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/audit.php';
require __DIR__ . '/src/places.php';

$page = get('p', 'dashboard');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    verify_csrf();
}

// ---------------------------------------------------------------------------
// Public routes: first-run setup and login
// ---------------------------------------------------------------------------
if (!is_setup()) {
    if ($isPost && $page === 'setup') {
        $pw = post('password');
        if (strlen($pw) < 10) {
            flash('Use a password with at least 10 characters.', 'err');
            redirect('setup');
        }
        if ($pw !== post('password_confirm')) {
            flash('Passwords do not match.', 'err');
            redirect('setup');
        }
        set_setting('password_hash', password_hash($pw, PASSWORD_DEFAULT));
        foreach (['my_name', 'my_agency', 'my_website', 'my_phone', 'my_email'] as $k) {
            set_setting($k, post($k));
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = 1;
        flash('Welcome! Your lead finder is ready.');
        redirect('dashboard');
    }
    $title = 'Setup';
    require __DIR__ . '/views/setup.php';
    exit;
}

if ($page === 'logout' && $isPost) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!is_logged_in()) {
    $error = null;
    if ($isPost && $page === 'login') {
        $fails = (int) setting('login_fails', '0');
        $lockedUntil = (int) setting('login_locked_until', '0');
        if ($lockedUntil > time()) {
            $error = 'Too many attempts. Try again in ' . ceil(($lockedUntil - time()) / 60) . ' minute(s).';
        } elseif (password_verify(post('password'), (string) setting('password_hash'))) {
            set_setting('login_fails', '0');
            session_regenerate_id(true);
            $_SESSION['uid'] = 1;
            redirect('dashboard');
        } else {
            $fails++;
            set_setting('login_fails', (string) $fails);
            if ($fails >= 5) {
                set_setting('login_locked_until', (string) (time() + 15 * 60));
                set_setting('login_fails', '0');
            }
            usleep(500000);
            $error = 'Wrong password.';
        }
    }
    $title = 'Login';
    require __DIR__ . '/views/login.php';
    exit;
}

// ---------------------------------------------------------------------------
// Authenticated routes
// ---------------------------------------------------------------------------
function lead_from_post(): array
{
    $services = array_values(array_intersect(array_keys(SERVICES), (array) ($_POST['services'] ?? [])));
    $stage = post('stage', 'new');
    $prio = post('priority', 'warm');
    $follow = post('next_followup');
    return [
        'name'           => mb_substr(post('name'), 0, 200),
        'contact_person' => post('contact_person'),
        'category'       => post('category'),
        'area'           => post('area'),
        'address'        => post('address'),
        'phone'          => post('phone'),
        'whatsapp'       => wa_number(post('whatsapp') ?: post('phone')),
        'email'          => filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: '',
        'website'        => normalize_url(post('website')),
        'instagram'      => ltrim(post('instagram'), '@'),
        'facebook'       => normalize_url(post('facebook')),
        'maps_url'       => normalize_url(post('maps_url')),
        'stage'          => isset(STAGES[$stage]) ? $stage : 'new',
        'priority'       => isset(PRIORITIES[$prio]) ? $prio : 'warm',
        'services'       => implode(',', $services),
        'est_value'      => (int) preg_replace('/\D/', '', post('est_value')),
        'source'         => post('source', 'manual'),
        'tags'           => post('tags'),
        'next_followup'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow) ? $follow : null,
        'summary'        => post('summary'),
    ];
}

function run_audit(array $lead): array
{
    $audit = audit_website((string) $lead['website']);
    $services = array_unique(array_filter(array_merge(explode(',', (string) $lead['services']), audit_services($audit))));
    db()->prepare("UPDATE leads SET audit_json = ?, audit_score = ?, audited_at = datetime('now','localtime'), services = ?, updated_at = datetime('now','localtime') WHERE id = ?")
        ->execute([json_encode($audit), $audit['score'], implode(',', $services), $lead['id']]);
    $count = count(array_filter($audit['findings'], fn($f) => $f['points'] > 0));
    log_activity((int) $lead['id'], 'audit', "Website audit: score {$audit['score']}/100, $count issue(s) found.");
    return $audit;
}

switch ($page) {
    // ----------------------------------------------------------------- Dashboard
    case 'dashboard':
        $pdo = db();
        $today = date('Y-m-d');
        $stats = [
            'total'    => (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
            'active'   => (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE stage NOT IN ('won','lost')")->fetchColumn(),
            'won'      => (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE stage = 'won'")->fetchColumn(),
            'pipeline' => (int) $pdo->query("SELECT COALESCE(SUM(est_value),0) FROM leads WHERE stage IN ('replied','proposal','negotiation')")->fetchColumn(),
            'won_value'=> (int) $pdo->query("SELECT COALESCE(SUM(est_value),0) FROM leads WHERE stage = 'won'")->fetchColumn(),
        ];
        $byStage = $pdo->query('SELECT stage, COUNT(*) c FROM leads GROUP BY stage')->fetchAll(PDO::FETCH_KEY_PAIR);
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE next_followup IS NOT NULL AND next_followup <= ? AND stage NOT IN ('won','lost') ORDER BY next_followup LIMIT 15");
        $stmt->execute([$today]);
        $due = $stmt->fetchAll();
        $hot = $pdo->query("SELECT * FROM leads WHERE stage IN ('new','researched') ORDER BY (priority='hot') DESC, COALESCE(audit_score, 50) DESC, reviews DESC LIMIT 8")->fetchAll();
        $recent = $pdo->query('SELECT a.*, l.name FROM activities a JOIN leads l ON l.id = a.lead_id ORDER BY a.id DESC LIMIT 10')->fetchAll();
        $title = 'Dashboard';
        require __DIR__ . '/views/dashboard.php';
        break;

    // ----------------------------------------------------------------- Lead list
    case 'leads':
        $where = [];
        $args = [];
        $q = get('q');
        if ($q !== '') {
            $where[] = '(name LIKE :q OR contact_person LIKE :q OR category LIKE :q OR area LIKE :q OR tags LIKE :q OR email LIKE :q OR phone LIKE :q OR website LIKE :q OR summary LIKE :q)';
            $args[':q'] = '%' . $q . '%';
        }
        foreach (['stage', 'priority', 'area', 'category', 'source'] as $f) {
            if (get($f) !== '') {
                $where[] = "$f = :$f";
                $args[":$f"] = get($f);
            }
        }
        if (get('service') !== '' && isset(SERVICES[get('service')])) {
            $where[] = "(',' || services || ',') LIKE :svc";
            $args[':svc'] = '%,' . get('service') . ',%';
        }
        if (get('nosite') === '1') {
            $where[] = "(website IS NULL OR website = '')";
        }
        $sorts = [
            'updated'  => 'updated_at DESC',
            'name'     => 'name COLLATE NOCASE ASC',
            'score'    => 'audit_score IS NULL, audit_score DESC',
            'followup' => 'next_followup IS NULL, next_followup ASC',
            'value'    => 'est_value DESC',
            'reviews'  => 'reviews IS NULL, reviews DESC',
        ];
        $sort = isset($sorts[get('sort')]) ? get('sort') : 'updated';
        $sql = 'SELECT * FROM leads' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . $sorts[$sort] . ' LIMIT 500';
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        $leads = $stmt->fetchAll();
        $areas = db()->query("SELECT DISTINCT area FROM leads WHERE area != '' ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);
        $categories = db()->query("SELECT DISTINCT category FROM leads WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        $view = get('view') === 'board' ? 'board' : 'table';
        $title = 'Leads';
        require __DIR__ . '/views/leads.php';
        break;

    // ----------------------------------------------------------------- Lead detail
    case 'lead':
        $lead = find_lead((int) get('id'));
        if (!$lead) {
            flash('Lead not found.', 'err');
            redirect('leads');
        }
        $stmt = db()->prepare('SELECT * FROM activities WHERE lead_id = ? ORDER BY id DESC');
        $stmt->execute([$lead['id']]);
        $activities = $stmt->fetchAll();
        $templates = db()->query('SELECT * FROM templates ORDER BY channel, name')->fetchAll();
        $audit = $lead['audit_json'] ? json_decode($lead['audit_json'], true) : null;
        $title = $lead['name'];
        require __DIR__ . '/views/lead.php';
        break;

    case 'lead_edit':
        $lead = get('id') !== '' ? find_lead((int) get('id')) : null;
        if (get('id') !== '' && !$lead) {
            redirect('leads');
        }
        $title = $lead ? 'Edit ' . $lead['name'] : 'Add lead';
        require __DIR__ . '/views/lead_form.php';
        break;

    case 'lead_save':
        if (!$isPost) {
            redirect('leads');
        }
        $data = lead_from_post();
        if ($data['name'] === '') {
            flash('Business name is required.', 'err');
            redirect('lead_edit', (int) post('id') > 0 ? ['id' => (int) post('id')] : []);
        }
        $id = (int) post('id');
        if ($id > 0) {
            $old = find_lead($id);
            if (!$old) {
                redirect('leads');
            }
            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            $stmt = db()->prepare("UPDATE leads SET $sets, updated_at = datetime('now','localtime') WHERE id = :id");
            $stmt->execute(array_combine(array_map(fn($k) => ":$k", array_keys($data)), $data) + [':id' => $id]);
            if ($old['stage'] !== $data['stage']) {
                log_activity($id, 'stage', 'Stage: ' . STAGES[$old['stage']] . ' → ' . STAGES[$data['stage']]);
            }
            flash('Lead updated.');
        } else {
            $cols = implode(', ', array_keys($data));
            $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
            db()->prepare("INSERT INTO leads ($cols) VALUES ($ph)")->execute(array_combine(array_map(fn($k) => ":$k", array_keys($data)), $data));
            $id = (int) db()->lastInsertId();
            log_activity($id, 'note', 'Lead created.');
            flash('Lead added.');
        }
        redirect('lead', ['id' => $id]);

    case 'lead_delete':
        if ($isPost) {
            db()->prepare('DELETE FROM leads WHERE id = ?')->execute([(int) post('id')]);
            flash('Lead deleted.');
        }
        redirect('leads');

    case 'lead_stage':
        if ($isPost && ($lead = find_lead((int) post('id'))) && isset(STAGES[post('stage')])) {
            if ($lead['stage'] !== post('stage')) {
                db()->prepare("UPDATE leads SET stage = ?, updated_at = datetime('now','localtime') WHERE id = ?")->execute([post('stage'), $lead['id']]);
                log_activity((int) $lead['id'], 'stage', 'Stage: ' . STAGES[$lead['stage']] . ' → ' . STAGES[post('stage')]);
            }
            if (post('back') === 'board') {
                redirect('leads', ['view' => 'board']);
            }
            redirect('lead', ['id' => $lead['id']]);
        }
        redirect('leads');

    case 'activity_add':
        if ($isPost && ($lead = find_lead((int) post('id')))) {
            $type = isset(ACTIVITY_TYPES[post('type')]) ? post('type') : 'note';
            if (post('body') !== '') {
                log_activity((int) $lead['id'], $type, post('body'));
            }
            $follow = post('next_followup');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow)) {
                db()->prepare('UPDATE leads SET next_followup = ? WHERE id = ?')->execute([$follow, $lead['id']]);
            } elseif (post('clear_followup') === '1') {
                db()->prepare('UPDATE leads SET next_followup = NULL WHERE id = ?')->execute([$lead['id']]);
            }
            // Auto-advance new/researched leads once you reach out
            if (in_array($type, ['whatsapp', 'email', 'call', 'instagram', 'meeting'], true) && in_array($lead['stage'], ['new', 'researched'], true)) {
                db()->prepare("UPDATE leads SET stage = 'contacted' WHERE id = ?")->execute([$lead['id']]);
                log_activity((int) $lead['id'], 'stage', 'Stage: ' . STAGES[$lead['stage']] . ' → Contacted');
            }
            flash('Activity logged.');
            redirect('lead', ['id' => $lead['id']]);
        }
        redirect('leads');

    case 'activity_delete':
        if ($isPost) {
            db()->prepare('DELETE FROM activities WHERE id = ? AND lead_id = ?')->execute([(int) post('aid'), (int) post('id')]);
        }
        redirect('lead', ['id' => (int) post('id')]);

    case 'audit':
        if ($isPost && ($lead = find_lead((int) post('id')))) {
            @set_time_limit(60);
            $audit = run_audit($lead);
            if ($lead['stage'] === 'new') {
                db()->prepare("UPDATE leads SET stage = 'researched' WHERE id = ?")->execute([$lead['id']]);
            }
            flash("Audit complete — opportunity score {$audit['score']}/100.");
            redirect('lead', ['id' => $lead['id']]);
        }
        redirect('leads');

    case 'audit_bulk':
        if ($isPost) {
            @set_time_limit(300);
            $ids = array_slice(array_map('intval', (array) ($_POST['ids'] ?? [])), 0, 15);
            $n = 0;
            foreach ($ids as $id) {
                if ($lead = find_lead($id)) {
                    run_audit($lead);
                    $n++;
                }
            }
            flash("Audited $n lead(s)." . (count((array) ($_POST['ids'] ?? [])) > 15 ? ' (Max 15 per batch.)' : ''));
        }
        redirect('leads', ['sort' => 'score']);

    // ----------------------------------------------------------------- Connect / outreach
    case 'connect':
        // Logs the outreach, then sends the browser to WhatsApp / email.
        if ($isPost && ($lead = find_lead((int) post('id')))) {
            $channel = post('channel');
            $msg = post('message');
            $subject = post('subject');
            if ($channel === 'whatsapp' && $lead['whatsapp'] !== '') {
                $target = 'https://wa.me/' . $lead['whatsapp'] . '?text=' . rawurlencode($msg);
            } elseif ($channel === 'email' && $lead['email'] !== '') {
                $target = 'mailto:' . rawurlencode($lead['email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($msg);
            } else {
                flash('This lead has no ' . ($channel === 'email' ? 'email address' : 'WhatsApp number') . ' yet.', 'err');
                redirect('lead', ['id' => $lead['id']]);
            }
            log_activity((int) $lead['id'], $channel, ($subject !== '' ? "Subject: $subject\n\n" : '') . $msg);
            if (in_array($lead['stage'], ['new', 'researched'], true)) {
                db()->prepare("UPDATE leads SET stage = 'contacted' WHERE id = ?")->execute([$lead['id']]);
                log_activity((int) $lead['id'], 'stage', 'Stage: ' . STAGES[$lead['stage']] . ' → Contacted');
            }
            $days = (int) setting('followup_days', '3');
            db()->prepare('UPDATE leads SET next_followup = ? WHERE id = ?')->execute([date('Y-m-d', strtotime("+$days days")), $lead['id']]);
            header('Location: ' . $target);
            exit;
        }
        redirect('leads');

    // ----------------------------------------------------------------- Finder
    case 'finder':
        $apiKey = (string) setting('places_api_key', '');
        $category = get('category');
        $area = get('area');
        $keyword = get('keyword');
        $query = trim(($keyword !== '' ? $keyword : $category) . ($area !== '' ? " in $area" : '') . ' Bali');
        $results = null;
        $searchError = null;
        $next = null;
        $existing = [];
        if (get('go') === '1' && $apiKey !== '' && ($keyword !== '' || $category !== '')) {
            $res = places_search($apiKey, $query, get('page') ?: null);
            if (isset($res['error'])) {
                $searchError = $res['error'];
            } else {
                $results = $res['results'];
                $next = $res['next'];
                if (get('nosite') === '1') {
                    $results = array_values(array_filter($results, fn($r) => result_hint($r)[0] !== 'cold'));
                }
                $ids = array_column($results, 'place_id');
                if ($ids) {
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = db()->prepare("SELECT place_id, id FROM leads WHERE place_id IN ($in)");
                    $stmt->execute($ids);
                    $existing = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                }
                $_SESSION['finder_results'] = array_column($results, null, 'place_id');
                $_SESSION['finder_category'] = $category ?: $keyword;
            }
        }
        $title = 'Find clients';
        require __DIR__ . '/views/finder.php';
        break;

    case 'finder_add':
        if ($isPost) {
            $cache = $_SESSION['finder_results'] ?? [];
            $added = 0;
            foreach ((array) ($_POST['place_ids'] ?? []) as $pid) {
                if (!is_string($pid) || !isset($cache[$pid])) {
                    continue;
                }
                $r = $cache[$pid];
                [$prio] = result_hint($r);
                $stmt = db()->prepare("INSERT OR IGNORE INTO leads (name, category, area, address, phone, whatsapp, website, maps_url, place_id, rating, reviews, priority, source)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'google_maps')");
                $stmt->execute([
                    $r['name'], $_SESSION['finder_category'] ?? $r['type'], guess_area($r['address']), $r['address'],
                    $r['phone'], wa_number($r['phone']), $r['website'], $r['maps_url'], $r['place_id'],
                    $r['rating'], $r['reviews'], $prio,
                ]);
                if ($stmt->rowCount()) {
                    $id = (int) db()->lastInsertId();
                    log_activity($id, 'note', 'Found via Google Maps search.');
                    $added++;
                }
            }
            flash("Added $added new lead(s). Duplicates were skipped.");
            $back = (string) post('back');
            if ($back !== '' && str_starts_with($back, 'index.php?p=finder')) {
                header('Location: ' . $back);
                exit;
            }
        }
        redirect('leads', ['source' => 'google_maps']);

    // ----------------------------------------------------------------- Templates
    case 'templates':
        $templates = db()->query('SELECT * FROM templates ORDER BY channel, name')->fetchAll();
        $edit = null;
        if (get('id') !== '') {
            $stmt = db()->prepare('SELECT * FROM templates WHERE id = ?');
            $stmt->execute([(int) get('id')]);
            $edit = $stmt->fetch() ?: null;
        }
        $title = 'Message templates';
        require __DIR__ . '/views/templates.php';
        break;

    case 'template_save':
        if ($isPost && post('name') !== '' && post('body') !== '') {
            $vals = [post('name'), post('channel') === 'email' ? 'email' : 'whatsapp', post('language') === 'en' ? 'en' : 'id', post('subject'), post('body')];
            if ((int) post('id') > 0) {
                db()->prepare('UPDATE templates SET name=?, channel=?, language=?, subject=?, body=? WHERE id=?')->execute([...$vals, (int) post('id')]);
            } else {
                db()->prepare('INSERT INTO templates (name, channel, language, subject, body) VALUES (?,?,?,?,?)')->execute($vals);
            }
            flash('Template saved.');
        }
        redirect('templates');

    case 'template_delete':
        if ($isPost) {
            db()->prepare('DELETE FROM templates WHERE id = ?')->execute([(int) post('id')]);
            flash('Template deleted.');
        }
        redirect('templates');

    // ----------------------------------------------------------------- Import / export
    case 'export':
        $rows = db()->query('SELECT id, name, contact_person, category, area, address, phone, whatsapp, email, website, instagram, facebook, maps_url, rating, reviews, stage, priority, services, est_value, source, tags, next_followup, last_contacted, audit_score, summary, created_at, updated_at FROM leads ORDER BY id')->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lightandpulse-leads-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $rows ? array_keys($rows[0]) : ['name']);
        foreach ($rows as $r) {
            // Neutralise spreadsheet formula injection
            fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r));
        }
        exit;

    case 'import':
        $title = 'Import leads';
        if ($isPost && isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
            $fh = fopen($_FILES['csv']['tmp_name'], 'r');
            $header = fgetcsv($fh);
            if (!$header) {
                flash('Empty CSV file.', 'err');
                redirect('import');
            }
            $header = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
            $alias = ['business' => 'name', 'business name' => 'name', 'company' => 'name', 'nama' => 'name', 'contact' => 'contact_person',
                      'telepon' => 'phone', 'phone number' => 'phone', 'wa' => 'whatsapp', 'url' => 'website', 'kategori' => 'category',
                      'lokasi' => 'area', 'location' => 'area', 'ig' => 'instagram', 'notes' => 'summary', 'catatan' => 'summary'];
            $header = array_map(fn($h) => $alias[$h] ?? $h, $header);
            $allowed = ['name', 'contact_person', 'category', 'area', 'address', 'phone', 'whatsapp', 'email', 'website', 'instagram', 'facebook', 'maps_url', 'tags', 'summary', 'est_value'];
            $added = 0;
            $skipped = 0;
            $dupe = db()->prepare("SELECT 1 FROM leads WHERE lower(name) = lower(?) AND (area = ? OR ? = '')");
            while (($row = fgetcsv($fh)) !== false) {
                $rec = [];
                foreach ($header as $i => $h) {
                    if (in_array($h, $allowed, true) && isset($row[$i])) {
                        $rec[$h] = trim($row[$i]);
                    }
                }
                if (($rec['name'] ?? '') === '') {
                    $skipped++;
                    continue;
                }
                $dupe->execute([$rec['name'], $rec['area'] ?? '', $rec['area'] ?? '']);
                if ($dupe->fetchColumn()) {
                    $skipped++;
                    continue;
                }
                $rec['whatsapp'] = wa_number(($rec['whatsapp'] ?? '') ?: ($rec['phone'] ?? ''));
                $rec['website'] = normalize_url($rec['website'] ?? '');
                $rec['est_value'] = (int) preg_replace('/\D/', '', $rec['est_value'] ?? '0');
                $rec['source'] = post('source') ?: 'import';
                $cols = array_keys($rec);
                db()->prepare('INSERT INTO leads (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')->execute(array_values($rec));
                $added++;
            }
            fclose($fh);
            flash("Imported $added lead(s), skipped $skipped (empty or duplicate).");
            redirect('leads');
        }
        require __DIR__ . '/views/import.php';
        break;

    // ----------------------------------------------------------------- Settings
    case 'settings':
        if ($isPost) {
            foreach (['my_name', 'my_agency', 'my_website', 'my_phone', 'my_email'] as $k) {
                set_setting($k, post($k));
            }
            set_setting('followup_days', (string) max(1, min(30, (int) post('followup_days', '3'))));
            if (post('places_api_key') !== '') {
                set_setting('places_api_key', post('places_api_key'));
            }
            if (post('remove_api_key') === '1') {
                set_setting('places_api_key', '');
            }
            if (post('new_password') !== '') {
                if (!password_verify(post('current_password'), (string) setting('password_hash'))) {
                    flash('Current password is incorrect — password not changed.', 'err');
                    redirect('settings');
                }
                if (strlen(post('new_password')) < 10) {
                    flash('New password must be at least 10 characters.', 'err');
                    redirect('settings');
                }
                set_setting('password_hash', password_hash(post('new_password'), PASSWORD_DEFAULT));
            }
            flash('Settings saved.');
            redirect('settings');
        }
        $title = 'Settings';
        require __DIR__ . '/views/settings.php';
        break;

    default:
        http_response_code(404);
        $title = 'Not found';
        require __DIR__ . '/views/header.php';
        echo '<div class="card"><h2>Page not found</h2><p><a href="' . e(url('dashboard')) . '">Back to dashboard</a></p></div>';
        require __DIR__ . '/views/footer.php';
}
