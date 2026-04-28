<?php
/**
 * Label-driven Gravity Forms -> allgreendb.tblprocurementleads
 * Works with multiple forms that share the same Admin Labels.
 *
 * Required Admin Labels (case-insensitive matches):
 *   Name, Email, Zip, Phone, Service, Company, Message, Address (optional), Purpose (ignored)
 */

if (!session_id()) session_start();

/** === DB CONFIG (edit if needed) === */
define('AGR_DSN',      'mysql:host=allgreen.cmsfg3ols5pn.us-west-2.rds.amazonaws.com;dbname=allgreendb;charset=utf8mb4');
define('AGR_DB_USER',  'root');
define('AGR_DB_PASS',  'ggA7LRiSrwis');

/** Build a PDO connection */
function agr_pdo() {
    return new PDO(
        AGR_DSN, AGR_DB_USER, AGR_DB_PASS,
        [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        ]
    );
}

/** Get field value from $entry by matching the field's adminLabel (case-insensitive). */
function agr_value_by_admin_label($form, $entry, $wantedLabel) {
    $wanted = strtolower(trim($wantedLabel));
    foreach ($form['fields'] as $field) {
        // Some GF versions use objects, some arrays – normalize via array cast
        $f = (array)$field;
        $adminLabel = isset($f['adminLabel']) ? strtolower(trim($f['adminLabel'])) : '';

        if ($adminLabel === $wanted) {
            // Multi-input fields (rare here, but handle just in case)
            if (!empty($f['inputs']) && is_array($f['inputs'])) {
                $parts = [];
                foreach ($f['inputs'] as $in) {
                    $id = (string)$in['id'];
                    if (!empty($entry[$id])) $parts[] = trim($entry[$id]);
                }
                return trim(implode(', ', $parts));
            }
            // Single input
            $id = (string)$f['id'];
            return isset($entry[$id]) ? trim($entry[$id]) : '';
        }
    }
    return '';
}

/** Split a full name into first/last. If single token, last is 'None'. */
function agr_split_name($full) {
    $full = trim(preg_replace('/\s+/', ' ', (string)$full));
    if ($full === '') return ['first' => '', 'last' => ''];
    $parts = explode(' ', $full, 2);
    $first = $parts[0];
    $last  = isset($parts[1]) ? $parts[1] : 'None';
    return ['first' => $first, 'last' => $last];
}

/** Map Services text to ServiceSelection integer codes used by your DB. */
function corresponding_service($label) {
    $k = strtolower(trim((string)$label));

    // Exact options provided by the client (second site)
    $map = [
        'hard drive data wiping'          => 3, // Data Destruction
        'on-site hard drive shredding'    => 4, // On-Site Shredding
        'on site hard drive shredding'    => 4, // tolerate spelling
        'hard drive shredding'            => 3, // off-site/default shredding -> Data Destruction
        'paper shredding'                 => 3, // document DD
        'secure document destruction'     => 3, // document DD
        'certified equipment destruction' => 6, // Product/Equipment Destruction
        'other'                           => 0,
    ];
    if (isset($map[$k])) return $map[$k];

    // Fallbacks for small variations
    if (strpos($k,'on-site') !== false && strpos($k,'shred') !== false) return 4;
    if (strpos($k,'shred')   !== false) return 3;
    if (strpos($k,'wipe')    !== false) return 3;
    if (strpos($k,'paper')   !== false) return 3;
    if (strpos($k,'document')!== false) return 3;
    if (strpos($k,'equipment')!== false || strpos($k,'product') !== false) return 6;

    return 0;
}

/** Build a dedupe session key similar to the legacy code */
function agr_session_key($form, $email) {
    $formId = isset($form['id']) ? $form['id'] : (isset($form['fields'][0]->formId) ? $form['fields'][0]->formId : 0);
    $sess   = substr(session_id(), 0, 5);
    $mail15 = strtolower(substr((string)$email, 0, 15));
    return 'gformtitanium' . $formId . date('dmy') . $sess . $mail15;
}

/** Current page referrer (fallback to current URL if no HTTP_REFERER) */
function agr_referrer_url() {
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if ($ref) return $ref;
    // Fallback to the current page
    if (function_exists('site_url')) {
        return site_url() . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
    }
    return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
}

/**
 * IPv4-only getter.
 * Order:
 *   0) Gravity Forms $entry['ip'] (extract embedded v4 if needed)
 *   1) HTTP_CF_CONNECTING_IP (Cloudflare)
 *   2) first item of HTTP_X_FORWARDED_FOR
 *   3) HTTP_X_REAL_IP, HTTP_CLIENT_IP
 *   4) REMOTE_ADDR
 * Returns '' if no IPv4 is found (no IPv6 fallback).
 */
function agr_ipv4($entryIp = null) {
    $isIPv4 = function($ip, $publicOnly = true) {
        $flags = FILTER_FLAG_IPV4;
        if ($publicOnly) $flags |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $flags) ? $ip : false;
    };
    $extractV4 = function($ip) {
        if (preg_match('/(?:\:\:ffff\:)?(\d{1,3}(?:\.\d{1,3}){3})$/i', (string)$ip, $m)) return $m[1];
        return '';
    };

    // 1) Cloudflare canonical header
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $v4 = $extractV4($_SERVER['HTTP_CF_CONNECTING_IP']);
        if ($v4 && ($ok = $isIPv4($v4, true)))  return $ok;
        if ($v4 && ($ok = $isIPv4($v4, false))) return $ok;
    }

    // 2) X-Forwarded-For (first hop)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        $v4 = $extractV4($first);
        if ($v4 && ($ok = $isIPv4($v4, true)))  return $ok;
        if ($v4 && ($ok = $isIPv4($v4, false))) return $ok;
    }

    // 3) Other proxy headers
    foreach (['HTTP_X_REAL_IP','HTTP_CLIENT_IP'] as $h) {
        if (!empty($_SERVER[$h])) {
            $v4 = $extractV4($_SERVER[$h]);
            if ($v4 && ($ok = $isIPv4($v4, true)))  return $ok;
            if ($v4 && ($ok = $isIPv4($v4, false))) return $ok;
        }
    }

    // 4) REMOTE_ADDR
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $v4 = $extractV4($_SERVER['REMOTE_ADDR']);
        if ($v4 && ($ok = $isIPv4($v4, true)))  return $ok;
        if ($v4 && ($ok = $isIPv4($v4, false))) return $ok;
    }

    // 5) Last resort: GF’s stored IP (may be Cloudflare IP on shared hosting)
    if (!empty($entryIp)) {
        $v4 = $extractV4(trim($entryIp));
        if ($v4 && ($ok = $isIPv4($v4, true)))  return $ok;
        if ($v4 && ($ok = $isIPv4($v4, false))) return $ok;
    }

    return '';
}

/** Insert or Update tblprocurementleads with the fields we care about */
function agr_upsert_lead($data) {
    $pdo = agr_pdo();

    // Does this session already exist?
    $exists = $pdo->prepare('SELECT 1 FROM tblprocurementleads WHERE session_Id = :sid LIMIT 1');
    $exists->execute([':sid' => $data['session_id']]);
    $found = (bool)$exists->fetchColumn();

    if (!$found) {
        // Minimal, schema-safe insert
        $sql = 'INSERT INTO tblprocurementleads
                (stage, stage_time, first_name, last_name, email, phone, company, zip, ServiceSelection, IPAddress, session_Id, AGRpage, address, ALL_AdditionalNotes)
                VALUES
                (:stage, :stage_time, :first_name, :last_name, :email, :phone, :company, :zip, :service, :ip, :session_id, :referrer, :address, :notes)';
        $stmt = $pdo->prepare($sql);
    } else {
        // Update only the columns we manage
        $sql = 'UPDATE tblprocurementleads SET
                    stage = :stage,
                    stage_time = :stage_time,
                    first_name = :first_name,
                    last_name  = :last_name,
                    email      = :email,
                    phone      = :phone,
                    company    = :company,
                    zip        = :zip,
                    ServiceSelection = :service,
                    IPAddress  = :ip,
                    AGRpage    = :referrer,
                    address    = :address,
                    ALL_AdditionalNotes = :notes
                WHERE session_Id = :session_id';
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute([
        ':stage'      => 1,
        ':stage_time' => function_exists('current_time') ? current_time('mysql', false) : date('Y-m-d H:i:s'),
        ':first_name' => $data['first_name'],
        ':last_name'  => $data['last_name'],
        ':email'      => $data['email'],
        ':phone'      => $data['phone'],
        ':company'    => $data['company'],
        ':zip'        => $data['zip'],
        ':service'    => $data['service_code'],
        ':ip'         => $data['ip'],
        ':session_id' => $data['session_id'],
        ':referrer'   => $data['referrer'],
        ':address'    => $data['address'],
        ':notes'      => $data['notes'],
    ]);
}

/** Handle a completed GF submission */
function agr_on_submit($entry, $form) {
    // Pull values by Admin Label (robust across different field IDs)
    $fullName = agr_value_by_admin_label($form, $entry, 'Name');
    $email    = agr_value_by_admin_label($form, $entry, 'Email');
    $zip      = agr_value_by_admin_label($form, $entry, 'Zip');
    $phone    = agr_value_by_admin_label($form, $entry, 'Phone');
    $service  = agr_value_by_admin_label($form, $entry, 'Service');
    $company  = agr_value_by_admin_label($form, $entry, 'Company');
    $message  = agr_value_by_admin_label($form, $entry, 'Message'); // now stored in ALL_AdditionalNotes
    $address  = agr_value_by_admin_label($form, $entry, 'Address'); // optional
    /* Purpose is intentionally ignored as requested */

    // Name -> first/last
    $nameParts  = agr_split_name($fullName);
    $firstName  = $nameParts['first'];
    $lastName   = $nameParts['last'];

    // Map service label -> integer code
    $serviceCode = corresponding_service($service);

    // IPv4 only (no IPv6 fallback)
    $ip = agr_ipv4(isset($entry['ip']) ? $entry['ip'] : null);

    // Build row data
    $data = [
        'first_name'   => $firstName,
        'last_name'    => $lastName,
        'email'        => $email,
        'phone'        => $phone,
        'company'      => $company,
        'zip'          => $zip,
        'service_code' => $serviceCode,
        'ip'           => $ip,
        'session_id'   => agr_session_key($form, $email),
        'referrer'     => agr_referrer_url(),
        'address'      => $address,
        'notes'        => $message, // <— store Message here
    ];

    // Basic guard: require at least Email or Phone to create a lead
    if ($data['email'] === '' && $data['phone'] === '') {
        return; // nothing to save
    }

    agr_upsert_lead($data);
}

/* === Hook into Gravity Forms ===
   Use after_submission so we only save final (complete) entries. */
add_action('gform_after_submission', 'agr_on_submit', 10, 2);