<?php
/**
 * Leads — form handlers (admin-post.php). Every handler checks the capability and nonce.
 */
defined('ABSPATH') || exit;

const LPL_ACTIONS = [
    'lead_save', 'lead_delete', 'lead_stage', 'activity_add', 'activity_delete', 'audit', 'audit_bulk',
    'connect', 'finder_add', 'template_save', 'template_delete', 'export', 'import', 'settings_save',
];

foreach (LPL_ACTIONS as $lpl_action) {
    add_action('admin_post_lpl_' . $lpl_action, function () use ($lpl_action) {
        if (!current_user_can(LPL_CAP)) {
            wp_die(esc_html__('You do not have access to Leads.', 'lightandpulse'), 403);
        }
        check_admin_referer('lpl_' . $lpl_action, '_lplnonce');
        call_user_func('lpl_handle_' . $lpl_action);
    });
}

function lpl_lead_from_post(): array
{
    $services = array_values(array_intersect(array_keys(LPL_SERVICES), array_map('sanitize_key', (array) ($_POST['services'] ?? []))));
    $stage = lpl_post('stage', 'new');
    $prio = lpl_post('priority', 'warm');
    $source = lpl_post('source', 'manual');
    $follow = lpl_post('next_followup');
    return [
        'name'           => mb_substr(sanitize_text_field(lpl_post('name')), 0, 200),
        'contact_person' => sanitize_text_field(lpl_post('contact_person')),
        'category'       => sanitize_text_field(lpl_post('category')),
        'area'           => sanitize_text_field(lpl_post('area')),
        'address'        => sanitize_text_field(lpl_post('address')),
        'phone'          => sanitize_text_field(lpl_post('phone')),
        'whatsapp'       => lpl_wa_number(lpl_post('whatsapp') ?: lpl_post('phone')),
        'email'          => sanitize_email(lpl_post('email')),
        'website'        => lpl_normalize_url(lpl_post('website')),
        'instagram'      => ltrim(sanitize_text_field(lpl_post('instagram')), '@'),
        'facebook'       => lpl_normalize_url(lpl_post('facebook')),
        'maps_url'       => lpl_normalize_url(lpl_post('maps_url')),
        'stage'          => isset(LPL_STAGES[$stage]) ? $stage : 'new',
        'priority'       => isset(LPL_PRIORITIES[$prio]) ? $prio : 'warm',
        'services'       => implode(',', $services),
        'est_value'      => (int) preg_replace('/\D/', '', lpl_post('est_value')),
        'source'         => isset(LPL_SOURCES[$source]) ? $source : 'manual',
        'tags'           => sanitize_text_field(lpl_post('tags')),
        'next_followup'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow) ? $follow : null,
        'summary'        => sanitize_textarea_field(lpl_post('summary')),
    ];
}

/** Insert a lead and return its id (0 when it is a duplicate place). */
function lpl_insert_lead(array $data, string $note = 'Lead created.'): int
{
    global $wpdb;
    $data += ['summary' => '', 'created_at' => lpl_now(), 'updated_at' => lpl_now()];
    if (!empty($data['place_id'])) {
        $exists = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . lpl_table('leads') . ' WHERE place_id = %s', $data['place_id']));
        if ($exists) {
            return 0;
        }
    }
    if (!$wpdb->insert(lpl_table('leads'), $data)) {
        return 0;
    }
    $id = (int) $wpdb->insert_id;
    lpl_log_activity($id, 'note', $note);
    return $id;
}

function lpl_handle_lead_save(): void
{
    global $wpdb;
    $data = lpl_lead_from_post();
    $id = (int) lpl_post('id');
    if ($data['name'] === '') {
        lpl_flash(__('Business name is required.', 'lightandpulse'), 'error');
        lpl_redirect('leads', ['edit' => $id ?: 'new']);
    }
    if ($id > 0) {
        $old = lpl_find_lead($id);
        if (!$old) {
            lpl_redirect('leads');
        }
        $wpdb->update(lpl_table('leads'), $data + ['updated_at' => lpl_now()], ['id' => $id]);
        if ($old['stage'] !== $data['stage']) {
            lpl_log_activity($id, 'stage', 'Stage: ' . LPL_STAGES[$old['stage']] . ' → ' . LPL_STAGES[$data['stage']]);
        }
        lpl_flash(__('Lead updated.', 'lightandpulse'));
    } else {
        $id = lpl_insert_lead($data);
        lpl_flash(__('Lead added.', 'lightandpulse'));
    }
    lpl_redirect('leads', ['lead' => $id]);
}

function lpl_handle_lead_delete(): void
{
    global $wpdb;
    $id = (int) lpl_post('id');
    $wpdb->delete(lpl_table('activities'), ['lead_id' => $id]);
    $wpdb->delete(lpl_table('leads'), ['id' => $id]);
    lpl_flash(__('Lead deleted.', 'lightandpulse'));
    lpl_redirect('leads');
}

function lpl_handle_lead_stage(): void
{
    $lead = lpl_find_lead((int) lpl_post('id'));
    if ($lead) {
        lpl_set_stage($lead, lpl_post('stage'));
    }
    if (lpl_post('back') === 'board') {
        lpl_redirect('leads', ['view' => 'board']);
    }
    lpl_redirect('leads', $lead ? ['lead' => $lead['id']] : []);
}

function lpl_handle_activity_add(): void
{
    global $wpdb;
    $lead = lpl_find_lead((int) lpl_post('id'));
    if (!$lead) {
        lpl_redirect('leads');
    }
    $type = isset(LPL_ACTIVITY_TYPES[lpl_post('type')]) ? lpl_post('type') : 'note';
    $body = sanitize_textarea_field(lpl_post('body'));
    if ($body !== '') {
        lpl_log_activity((int) $lead['id'], $type, $body);
    }
    $follow = lpl_post('next_followup');
    $wpdb->update(lpl_table('leads'), ['next_followup' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow) ? $follow : null], ['id' => $lead['id']]);
    // Reaching out moves new/researched leads to Contacted.
    if (in_array($type, ['whatsapp', 'email', 'call', 'instagram', 'meeting'], true) && in_array($lead['stage'], ['new', 'researched'], true)) {
        lpl_set_stage($lead, 'contacted');
    }
    lpl_flash(__('Activity logged.', 'lightandpulse'));
    lpl_redirect('leads', ['lead' => $lead['id']]);
}

function lpl_handle_activity_delete(): void
{
    global $wpdb;
    $wpdb->delete(lpl_table('activities'), ['id' => (int) lpl_post('aid'), 'lead_id' => (int) lpl_post('id')]);
    lpl_redirect('leads', ['lead' => (int) lpl_post('id')]);
}

function lpl_run_audit(array $lead): array
{
    global $wpdb;
    $audit = lpl_audit_website((string) $lead['website']);
    $services = array_unique(array_filter(array_merge(explode(',', (string) $lead['services']), lpl_audit_services($audit))));
    $wpdb->update(lpl_table('leads'), [
        'audit_json'  => wp_json_encode($audit),
        'audit_score' => $audit['score'],
        'audited_at'  => lpl_now(),
        'services'    => implode(',', $services),
        'updated_at'  => lpl_now(),
    ], ['id' => $lead['id']]);
    $count = count(array_filter($audit['findings'], fn($f) => $f['points'] > 0));
    lpl_log_activity((int) $lead['id'], 'audit', "Website audit: score {$audit['score']}/100, $count issue(s) found.");
    return $audit;
}

function lpl_handle_audit(): void
{
    $lead = lpl_find_lead((int) lpl_post('id'));
    if (!$lead) {
        lpl_redirect('leads');
    }
    @set_time_limit(60);
    $audit = lpl_run_audit($lead);
    if ($lead['stage'] === 'new') {
        lpl_set_stage($lead, 'researched');
    }
    lpl_flash(sprintf(__('Audit complete — opportunity score %d/100.', 'lightandpulse'), $audit['score']));
    lpl_redirect('leads', ['lead' => $lead['id']]);
}

function lpl_handle_audit_bulk(): void
{
    @set_time_limit(300);
    $all = array_map('intval', (array) ($_POST['ids'] ?? []));
    $n = 0;
    foreach (array_slice($all, 0, 15) as $id) {
        if ($lead = lpl_find_lead($id)) {
            lpl_run_audit($lead);
            $n++;
        }
    }
    lpl_flash(sprintf(__('Audited %d lead(s).', 'lightandpulse'), $n) . (count($all) > 15 ? ' ' . __('(Max 15 per batch.)', 'lightandpulse') : ''));
    lpl_redirect('leads', ['orderby' => 'score']);
}

/** Log the outreach, then send the browser to WhatsApp or the mail app. */
function lpl_handle_connect(): void
{
    global $wpdb;
    $lead = lpl_find_lead((int) lpl_post('id'));
    if (!$lead) {
        lpl_redirect('leads');
    }
    $channel = lpl_post('channel') === 'email' ? 'email' : 'whatsapp';
    $msg = sanitize_textarea_field(lpl_post('message'));
    $subject = sanitize_text_field(lpl_post('subject'));
    if ($channel === 'whatsapp' && $lead['whatsapp'] !== '') {
        $target = 'https://wa.me/' . $lead['whatsapp'] . '?text=' . rawurlencode($msg);
    } elseif ($channel === 'email' && $lead['email'] !== '') {
        $target = 'mailto:' . $lead['email'] . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($msg);
    } else {
        lpl_flash($channel === 'email' ? __('This lead has no email address yet.', 'lightandpulse') : __('This lead has no WhatsApp number yet.', 'lightandpulse'), 'error');
        lpl_redirect('leads', ['lead' => $lead['id']]);
    }
    lpl_log_activity((int) $lead['id'], $channel, ($subject !== '' && $channel === 'email' ? "Subject: $subject\n\n" : '') . $msg);
    if (in_array($lead['stage'], ['new', 'researched'], true)) {
        lpl_set_stage($lead, 'contacted');
    }
    $days = max(1, (int) lpl_setting('followup_days'));
    $wpdb->update(lpl_table('leads'), ['next_followup' => wp_date('Y-m-d', strtotime("+$days days"))], ['id' => $lead['id']]);
    // wp_redirect (not wp_safe_redirect): the target is WhatsApp or the mail app.
    wp_redirect($target);
    exit;
}

function lpl_finder_cache_key(): string
{
    return 'lpl_finder_' . get_current_user_id();
}

function lpl_handle_finder_add(): void
{
    $cache = get_transient(lpl_finder_cache_key()) ?: [];
    $results = $cache['results'] ?? [];
    $source = $cache['source'] ?? 'openstreetmap';
    $added = 0;
    foreach ((array) ($_POST['place_ids'] ?? []) as $pid) {
        $pid = is_string($pid) ? wp_unslash($pid) : '';
        if (!isset($results[$pid])) {
            continue;
        }
        $r = $results[$pid];
        [$prio] = lpl_result_hint($r);
        $id = lpl_insert_lead([
            'name'      => sanitize_text_field($r['name']),
            'category'  => sanitize_text_field($cache['category'] ?? $r['type']),
            'area'      => lpl_guess_area($r['address']) ?: sanitize_text_field($cache['area'] ?? ''),
            'address'   => sanitize_text_field($r['address']),
            'phone'     => sanitize_text_field($r['phone']),
            'whatsapp'  => lpl_wa_number(($r['whatsapp'] ?? '') ?: $r['phone']),
            'email'     => sanitize_email($r['email'] ?? ''),
            'website'   => esc_url_raw($r['website']),
            'instagram' => sanitize_text_field($r['instagram'] ?? ''),
            'facebook'  => esc_url_raw($r['facebook'] ?? ''),
            'maps_url'  => esc_url_raw($r['maps_url']),
            'place_id'  => $r['place_id'],
            'rating'    => $r['rating'],
            'reviews'   => $r['reviews'],
            'priority'  => $prio,
            'source'    => $source,
        ], $source === 'openstreetmap' ? 'Found via OpenStreetMap search.' : 'Found via Google Maps search.');
        if ($id) {
            $added++;
        }
    }
    lpl_flash(sprintf(__('Added %d new lead(s). Duplicates were skipped.', 'lightandpulse'), $added));
    // Back to the same results; they come from the cache, so no new search runs.
    $back = esc_url_raw(lpl_post('back'));
    if ($back !== '' && str_starts_with($back, admin_url('admin.php?page=lpl-finder'))) {
        wp_safe_redirect($back);
        exit;
    }
    lpl_redirect('leads', ['source' => $source]);
}

function lpl_handle_template_save(): void
{
    global $wpdb;
    $data = [
        'name'     => sanitize_text_field(lpl_post('name')),
        'channel'  => lpl_post('channel') === 'email' ? 'email' : 'whatsapp',
        'language' => lpl_post('language') === 'en' ? 'en' : 'id',
        'subject'  => sanitize_text_field(lpl_post('subject')),
        'body'     => sanitize_textarea_field(lpl_post('body')),
    ];
    if ($data['name'] !== '' && $data['body'] !== '') {
        if ((int) lpl_post('id') > 0) {
            $wpdb->update(lpl_table('templates'), $data, ['id' => (int) lpl_post('id')]);
        } else {
            $wpdb->insert(lpl_table('templates'), $data);
        }
        lpl_flash(__('Template saved.', 'lightandpulse'));
    }
    lpl_redirect('templates');
}

function lpl_handle_template_delete(): void
{
    global $wpdb;
    $wpdb->delete(lpl_table('templates'), ['id' => (int) lpl_post('id')]);
    lpl_flash(__('Template deleted.', 'lightandpulse'));
    lpl_redirect('templates');
}

function lpl_handle_export(): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT id, name, contact_person, category, area, address, phone, whatsapp, email, website, instagram, facebook, maps_url, rating, reviews, stage, priority, services, est_value, source, tags, next_followup, last_contacted, audit_score, summary, created_at, updated_at FROM ' . lpl_table('leads') . ' ORDER BY id', ARRAY_A);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lightandpulse-leads-' . wp_date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $rows ? array_keys($rows[0]) : ['name']);
    foreach ($rows as $r) {
        // Neutralise spreadsheet formula injection.
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r));
    }
    exit;
}

function lpl_handle_import(): void
{
    global $wpdb;
    if (empty($_FILES['csv']['tmp_name']) || ($_FILES['csv']['error'] ?? 1) !== UPLOAD_ERR_OK) {
        lpl_flash(__('Choose a CSV file to import.', 'lightandpulse'), 'error');
        lpl_redirect('import');
    }
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    $header = $fh ? fgetcsv($fh) : false;
    if (!$header) {
        lpl_flash(__('Empty CSV file.', 'lightandpulse'), 'error');
        lpl_redirect('import');
    }
    $header = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
    $alias = ['business' => 'name', 'business name' => 'name', 'company' => 'name', 'nama' => 'name', 'contact' => 'contact_person',
              'telepon' => 'phone', 'phone number' => 'phone', 'wa' => 'whatsapp', 'url' => 'website', 'kategori' => 'category',
              'lokasi' => 'area', 'location' => 'area', 'ig' => 'instagram', 'notes' => 'summary', 'catatan' => 'summary'];
    $header = array_map(fn($h) => $alias[$h] ?? $h, $header);
    $allowed = ['name', 'contact_person', 'category', 'area', 'address', 'phone', 'whatsapp', 'email', 'website', 'instagram', 'facebook', 'maps_url', 'tags', 'summary', 'est_value'];
    $source = isset(LPL_SOURCES[lpl_post('source')]) ? lpl_post('source') : 'import';
    $added = 0;
    $skipped = 0;
    while (($row = fgetcsv($fh)) !== false) {
        $rec = [];
        foreach ($header as $i => $h) {
            if (in_array($h, $allowed, true) && isset($row[$i])) {
                $rec[$h] = $h === 'summary' ? sanitize_textarea_field($row[$i]) : sanitize_text_field($row[$i]);
            }
        }
        if (($rec['name'] ?? '') === '') {
            $skipped++;
            continue;
        }
        $dupe = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . lpl_table('leads') . " WHERE LOWER(name) = LOWER(%s) AND (area = %s OR %s = '')", $rec['name'], $rec['area'] ?? '', $rec['area'] ?? ''));
        if ($dupe) {
            $skipped++;
            continue;
        }
        $rec['whatsapp'] = lpl_wa_number(($rec['whatsapp'] ?? '') ?: ($rec['phone'] ?? ''));
        $rec['website'] = lpl_normalize_url($rec['website'] ?? '');
        $rec['email'] = sanitize_email($rec['email'] ?? '');
        $rec['est_value'] = (int) preg_replace('/\D/', '', $rec['est_value'] ?? '0');
        $rec['source'] = $source;
        lpl_insert_lead($rec, 'Imported from CSV.') ? $added++ : $skipped++;
    }
    fclose($fh);
    lpl_flash(sprintf(__('Imported %1$d lead(s), skipped %2$d (empty or duplicate).', 'lightandpulse'), $added, $skipped));
    lpl_redirect('leads');
}

function lpl_handle_settings_save(): void
{
    $s = lpl_settings();
    foreach (['my_name', 'my_agency', 'my_phone'] as $k) {
        $s[$k] = sanitize_text_field(lpl_post($k));
    }
    $s['my_website'] = esc_url_raw(lpl_post('my_website'));
    $s['my_email'] = sanitize_email(lpl_post('my_email'));
    $s['followup_days'] = max(1, min(30, (int) lpl_post('followup_days', '3')));
    $s['form_notify'] = lpl_post('form_notify') === '1' ? 1 : 0;
    $osm = esc_url_raw(lpl_post('osm_endpoint'));
    $s['osm_endpoint'] = str_starts_with($osm, 'https://') ? $osm : LPL_OSM_DEFAULT_ENDPOINT;
    if (lpl_post('places_api_key') !== '') {
        $s['places_api_key'] = sanitize_text_field(lpl_post('places_api_key'));
    }
    if (lpl_post('remove_api_key') === '1') {
        $s['places_api_key'] = '';
    }
    update_option('lpl_settings', $s, false);
    lpl_flash(__('Settings saved.', 'lightandpulse'));
    lpl_redirect('settings');
}
