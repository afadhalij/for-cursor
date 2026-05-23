<?php
/**
 * Members API
 *
 *   GET  ?op=list&status=active|retired      List members (with filters)
 *   GET  ?op=get&id=N                         One member (full profile)
 *   POST ?op=create                           Create member
 *   POST ?op=update&id=N                      Update member
 *   POST ?op=retire&id=N                      Move to Inganzo Nkuru
 *   POST ?op=reactivate&id=N                  Bring back from Nkuru
 *   POST ?op=delete&id=N                      Hard delete
 *   POST ?op=insurance&id=N                   Upsert insurance record
 *   POST ?op=upload_photo&id=N                multipart photo upload
 */

require_once __DIR__ . '/_router.php';

$op = $_GET['op'] ?? 'list';

switch ($op) {

    case 'list': {
        $where  = [];
        $params = [];
        if (!empty($_GET['status']))         { $where[] = 'status = ?';          $params[] = $_GET['status']; }
        if (!empty($_GET['section_id']))     { $where[] = 'section_id = ?';      $params[] = (int) $_GET['section_id']; }
        if (!empty($_GET['performer_type'])) { $where[] = 'performer_type_id = ?'; $params[] = (int) $_GET['performer_type']; }
        if (!empty($_GET['role_id']))        { $where[] = 'role_id = ?';         $params[] = (int) $_GET['role_id']; }
        if (!empty($_GET['category_id']))    { $where[] = 'category_id = ?';     $params[] = (int) $_GET['category_id']; }
        if (!empty($_GET['gender']))         { $where[] = 'gender = ?';          $params[] = $_GET['gender']; }
        $sql = "SELECT * FROM v_member_full" . ($where ? (' WHERE ' . implode(' AND ', $where)) : '') . ' ORDER BY full_name';
        reply(['data' => db_all($sql, $params)]);
    }

    case 'get': {
        $id = (int) ($_GET['id'] ?? 0);
        $row = db_one("SELECT * FROM v_member_full WHERE id = ?", [$id]);
        if (!$row) fail('not_found', 404);
        reply(['data' => $row]);
    }

    case 'create': case 'update': {
        $b   = read_body();
        $id  = $op === 'update' ? (int) ($_GET['id'] ?? 0) : 0;

        // Validate required fields
        $full_name   = required($b, 'full_name');
        $national_id = preg_replace('/\D/', '', required($b, 'national_id'));
        $gender      = strtoupper(required($b, 'gender'));
        $phone       = required($b, 'phone');
        $akarere     = required($b, 'akarere');
        $umurenge    = required($b, 'umurenge');
        $akagari     = required($b, 'akagari');
        $section_id  = nullable_int($b, 'section_id') ?: 0;
        $category_id = nullable_int($b, 'category_id') ?: 0;

        if (!in_array($gender, ['M', 'F'], true)) fail('invalid_gender');
        if (strlen($national_id) < 6) fail('invalid_national_id');
        if ($section_id  <= 0) fail('missing_section_id');
        if ($category_id <= 0) fail('missing_category_id');

        // Performer type only when section = Performers
        $section_code  = db_scalar("SELECT code FROM sections WHERE id = ?", [$section_id]);
        $performer_type_id = ($section_code === 'performers') ? nullable_int($b, 'performer_type_id') : null;
        if ($section_code === 'performers' && !$performer_type_id) fail('missing_performer_type');

        $role_id = nullable_int($b, 'role_id');
        // Make sure role belongs to the chosen section if provided
        if ($role_id) {
            $role_section = db_scalar("SELECT section_id FROM roles WHERE id = ?", [$role_id]);
            if ((int) $role_section !== $section_id) fail('role_section_mismatch');
        }

        $params = [
            ':full_name'        => $full_name,
            ':national_id'      => $national_id,
            ':gender'           => $gender,
            ':date_of_birth'    => nullable($b, 'date_of_birth'),
            ':phone'            => $phone,
            ':email'            => nullable($b, 'email'),
            ':akarere'          => $akarere,
            ':umurenge'         => $umurenge,
            ':akagari'          => $akagari,
            ':umudugudu'        => nullable($b, 'umudugudu'),
            ':emergency_name'   => nullable($b, 'emergency_name'),
            ':emergency_phone'  => nullable($b, 'emergency_phone'),
            ':section_id'       => $section_id,
            ':performer_type_id'=> $performer_type_id,
            ':role_id'          => $role_id,
            ':category_id'      => $category_id,
        ];

        try {
            if ($op === 'create') {
                $sql = "INSERT INTO members
                          (full_name, national_id, gender, date_of_birth, phone, email,
                           akarere, umurenge, akagari, umudugudu,
                           emergency_name, emergency_phone,
                           section_id, performer_type_id, role_id, category_id, status, joined_date)
                        VALUES
                          (:full_name, :national_id, :gender, :date_of_birth, :phone, :email,
                           :akarere, :umurenge, :akagari, :umudugudu,
                           :emergency_name, :emergency_phone,
                           :section_id, :performer_type_id, :role_id, :category_id,
                           'active', CURDATE())";
                $id = db_insert($sql, $params);
                audit_log('member', $id, 'create');
            } else {
                $sql = "UPDATE members SET
                          full_name=:full_name, national_id=:national_id, gender=:gender,
                          date_of_birth=:date_of_birth, phone=:phone, email=:email,
                          akarere=:akarere, umurenge=:umurenge, akagari=:akagari, umudugudu=:umudugudu,
                          emergency_name=:emergency_name, emergency_phone=:emergency_phone,
                          section_id=:section_id, performer_type_id=:performer_type_id,
                          role_id=:role_id, category_id=:category_id
                        WHERE id = :id";
                $params[':id'] = $id;
                db_exec($sql, $params);
                audit_log('member', $id, 'update');
            }
        } catch (PDOException $e) {
            // duplicate national_id
            if (str_contains($e->getMessage(), 'national_id')) fail('national_id_taken', 409);
            throw $e;
        }

        // Insurance upsert (if posted)
        if (array_key_exists('has_insurance', $b)) {
            $has = !empty($b['has_insurance']) && $b['has_insurance'] !== '0' && $b['has_insurance'] !== 'No';
            db_exec(
                "INSERT INTO member_insurance (member_id, has_insurance, insurance_name, start_date, expiry_date)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    has_insurance=VALUES(has_insurance),
                    insurance_name=VALUES(insurance_name),
                    start_date=VALUES(start_date),
                    expiry_date=VALUES(expiry_date)",
                [
                    $id,
                    $has ? 1 : 0,
                    $has ? (nullable($b, 'insurance_name') ?: 'Mutuelle de Santé') : null,
                    $has ? nullable($b, 'insurance_start') : null,
                    $has ? nullable($b, 'insurance_expiry') : null,
                ]
            );
        }

        reply(['ok' => true, 'id' => $id]);
    }

    case 'retire': {
        $id = (int) ($_GET['id'] ?? 0);
        $b  = read_body();
        $reason = nullable($b, 'reason') ?: 'retired';
        $allowed = ['retired','health','moved_abroad','family','career_change','other'];
        if (!in_array($reason, $allowed, true)) fail('invalid_reason');
        $note = nullable($b, 'note');
        $date = nullable($b, 'date') ?: date('Y-m-d');
        db_exec(
            "UPDATE members
                SET status='retired', retirement_date=?, retirement_reason=?, retirement_note=?
              WHERE id = ?",
            [$date, $reason, $note, $id]
        );
        audit_log('member', $id, 'retire', ['reason' => $reason]);
        reply(['ok' => true]);
    }

    case 'reactivate': {
        $id = (int) ($_GET['id'] ?? 0);
        db_exec(
            "UPDATE members
                SET status='active', retirement_date=NULL, retirement_reason=NULL, retirement_note=NULL
              WHERE id = ?",
            [$id]
        );
        audit_log('member', $id, 'reactivate');
        reply(['ok' => true]);
    }

    case 'delete': {
        $id = (int) ($_GET['id'] ?? 0);
        db_exec("DELETE FROM members WHERE id = ?", [$id]);
        audit_log('member', $id, 'delete');
        reply(['ok' => true]);
    }

    case 'upload_photo': {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0)                          fail('missing_id');
        if (empty($_FILES['photo']))            fail('missing_photo');
        if ($_FILES['photo']['error'] !== 0)    fail('upload_error');
        if ($_FILES['photo']['size'] > MAX_UPLOAD_BYTES) fail('too_large');
        $mime = mime_content_type($_FILES['photo']['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) fail('unsupported_type');
        if (!is_dir(UPLOAD_DIR . '/members')) @mkdir(UPLOAD_DIR . '/members', 0775, true);
        $fname = sprintf('m-%d-%s.%s', $id, substr(bin2hex(random_bytes(4)), 0, 8), $allowed[$mime]);
        $dest  = UPLOAD_DIR . '/members/' . $fname;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) fail('write_failed');
        $url = UPLOAD_URL . '/members/' . $fname;
        db_exec("UPDATE members SET photo_path = ? WHERE id = ?", [$url, $id]);
        audit_log('member', $id, 'upload_photo');
        reply(['ok' => true, 'photo' => $url]);
    }

    /* -------------------------------------------------------------------
     * Bulk import (called by the Import-from-Excel UI on the front end)
     *
     * Body shape:
     *   {
     *     defaults: {
     *       section_id: 2, performer_type_id: 1, role_id: null, category_id: 6,
     *       akarere: 'Gasabo', umurenge: 'Remera', akagari: '—'
     *     },
     *     rows: [
     *       { full_name, national_id, gender?, date_of_birth?, phone?, email?,
     *         akarere?, umurenge?, akagari?, umudugudu?, emergency_name?, emergency_phone?,
     *         section_id?, performer_type_id?, role_id?, category_id? },
     *       …
     *     ]
     *   }
     *
     * Response shape:
     *   { imported: N, skipped: N, total: N, errors: [{ row_index, full_name, message }] }
     * ----------------------------------------------------------------- */
    case 'import': {
        $b        = read_body();
        $rows     = $b['rows']     ?? [];
        $defaults = $b['defaults'] ?? [];

        if (!is_array($rows) || count($rows) === 0) fail('no_rows');

        $imported = 0;
        $errors   = [];

        // Pre-compute valid IDs so we can validate without 50 round-trips
        $validSections   = array_column(db_all("SELECT id FROM sections"), 'id');
        $validPerfTypes  = array_column(db_all("SELECT id FROM performer_types"), 'id');
        $validRoles      = array_column(db_all("SELECT id FROM roles"), 'id');
        $validCategories = array_column(db_all("SELECT id FROM categories"), 'id');
        $performersSection = (int) db_scalar("SELECT id FROM sections WHERE code='performers'");

        db()->beginTransaction();

        try {
            $stmt = db()->prepare("
                INSERT INTO members
                    (full_name, national_id, gender, date_of_birth, phone, email,
                     akarere, umurenge, akagari, umudugudu,
                     emergency_name, emergency_phone,
                     section_id, performer_type_id, role_id, category_id, status, joined_date)
                VALUES
                    (:full_name, :national_id, :gender, :date_of_birth, :phone, :email,
                     :akarere, :umurenge, :akagari, :umudugudu,
                     :emergency_name, :emergency_phone,
                     :section_id, :performer_type_id, :role_id, :category_id,
                     'active', CURDATE())
            ");

            $insStmt = db()->prepare(
                "INSERT INTO member_insurance (member_id, has_insurance) VALUES (?, 0)"
            );

            foreach ($rows as $i => $r) {
                $row = function ($k, $default = null) use ($r, $defaults) {
                    $v = $r[$k] ?? null;
                    if ($v === null || $v === '') $v = $defaults[$k] ?? $default;
                    return (is_string($v)) ? trim($v) : $v;
                };

                $full_name = (string) $row('full_name');
                if ($full_name === '') {
                    $errors[] = ['row_index' => $i + 1, 'full_name' => '(missing)', 'message' => 'missing_full_name'];
                    continue;
                }

                // National ID: digits-only; if missing or malformed, synthesise a placeholder so the row
                // still imports (admin can fix later)
                $national_id = preg_replace('/\D/', '', (string) $row('national_id', ''));
                if (strlen($national_id) < 4) {
                    // Placeholder NID — admins can edit after import
                    $national_id = 'IMP' . str_pad(date('YmdHis') . $i, 13, '0', STR_PAD_LEFT);
                }

                // Gender: from explicit field or parsed from NID (6th digit)
                $gender = strtoupper((string) $row('gender', ''));
                if ($gender !== 'M' && $gender !== 'F') {
                    $d6 = substr($national_id, 5, 1);
                    if ($d6 === '7') $gender = 'F';
                    elseif ($d6 === '8') $gender = 'M';
                    else $gender = 'M'; // fallback — admin can correct
                }

                // DOB: explicit field or parsed from NID (digits 2-5)
                $dob = $row('date_of_birth');
                if (!$dob && strlen($national_id) >= 5 && ctype_digit($national_id)) {
                    $year = (int) substr($national_id, 1, 4);
                    if ($year >= 1900 && $year <= 2100) $dob = sprintf('%04d-01-01', $year);
                }

                $section_id  = (int) ($row('section_id')  ?? 0);
                $category_id = (int) ($row('category_id') ?? 0);
                if (!in_array($section_id,  $validSections,   true)) {
                    $errors[] = ['row_index' => $i + 1, 'full_name' => $full_name, 'message' => 'invalid_section'];
                    continue;
                }
                if (!in_array($category_id, $validCategories, true)) {
                    $errors[] = ['row_index' => $i + 1, 'full_name' => $full_name, 'message' => 'invalid_category'];
                    continue;
                }

                $perf_type = $row('performer_type_id');
                $perf_type = $perf_type ? (int) $perf_type : null;
                if ($section_id === $performersSection && !$perf_type) {
                    $errors[] = ['row_index' => $i + 1, 'full_name' => $full_name, 'message' => 'missing_performer_type'];
                    continue;
                }
                if ($perf_type !== null && !in_array($perf_type, $validPerfTypes, true)) $perf_type = null;

                $role_id = $row('role_id');
                $role_id = $role_id ? (int) $role_id : null;
                if ($role_id !== null && !in_array($role_id, $validRoles, true)) $role_id = null;

                try {
                    $stmt->execute([
                        ':full_name'        => $full_name,
                        ':national_id'      => $national_id,
                        ':gender'           => $gender,
                        ':date_of_birth'    => $dob ?: null,
                        ':phone'            => (string) ($row('phone',           '—') ?: '—'),
                        ':email'            => $row('email'),
                        ':akarere'          => (string) ($row('akarere',  '—') ?: '—'),
                        ':umurenge'         => (string) ($row('umurenge', '—') ?: '—'),
                        ':akagari'          => (string) ($row('akagari',  '—') ?: '—'),
                        ':umudugudu'        => $row('umudugudu'),
                        ':emergency_name'   => $row('emergency_name'),
                        ':emergency_phone'  => $row('emergency_phone'),
                        ':section_id'       => $section_id,
                        ':performer_type_id'=> $perf_type,
                        ':role_id'          => $role_id,
                        ':category_id'      => $category_id,
                    ]);
                    $newId = (int) db()->lastInsertId();
                    $insStmt->execute([$newId]);
                    $imported++;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'national_id')) {
                        $errors[] = ['row_index' => $i + 1, 'full_name' => $full_name, 'message' => 'duplicate_national_id'];
                    } else {
                        $errors[] = ['row_index' => $i + 1, 'full_name' => $full_name, 'message' => 'db_error'];
                    }
                }
            }

            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            fail('import_failed: ' . $e->getMessage(), 500);
        }

        audit_log('member', null, 'bulk_import', ['imported' => $imported, 'errors' => count($errors)]);
        reply([
            'imported' => $imported,
            'skipped'  => count($errors),
            'total'    => count($rows),
            'errors'   => array_slice($errors, 0, 100) // cap reported errors at 100
        ]);
    }
}

fail('unknown_op');

