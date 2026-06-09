<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid    = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function propagate_custodian_to_children($pdo, $parent_id, $uid, $parent_fields) {
    $stmt = $pdo->prepare("SELECT id, custom_fields FROM serials WHERE parent_id = ? AND user_id = ?");
    $stmt->execute([$parent_id, $uid]);
    $children = $stmt->fetchAll();
    
    $p_user = $parent_fields['user'] ?? '';
    $p_desig = $parent_fields['designation'] ?? '';
    $p_dept = $parent_fields['department'] ?? '';
    
    foreach ($children as $child) {
        $cf = json_decode($child['custom_fields'], true) ?: [];
        $cf['user'] = $p_user;
        $cf['designation'] = $p_desig;
        $cf['department'] = $p_dept;
        
        $up = $pdo->prepare("UPDATE serials SET custom_fields = ? WHERE id = ?");
        $up->execute([json_encode($cf), $child['id']]);
        
        propagate_custodian_to_children($pdo, $child['id'], $uid, $cf);
    }
}

/* ── GET: list serials or preview next ──────────────── */
if ($method === 'GET') {
    if (isset($_GET['preview_next']) && isset($_GET['product_id'])) {
        $pid = (int)$_GET['product_id'];
        $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int)$_GET['parent_id'] : null;
        $next = generate_serial_number($pdo, $uid, $pid, $parent_id);
        json_out(true, ['next_serial' => $next]);
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, p.name as product_name, p.short_name, p.icon as product_icon,
                (SELECT s2.serial_number FROM serials s2 WHERE s2.id = s.parent_id) as parent_serial_number
         FROM serials s
         JOIN products p ON s.product_id = p.id
         WHERE s.user_id = ? ORDER BY s.created_at DESC"
    );
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['custom_fields'] = $r['custom_fields'] ? json_decode($r['custom_fields'], true) : [];
        $r['parent_id'] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
    }
    json_out(true, ['serials' => $rows]);
}

/* ── POST: add serial or update ─────────────────────── */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $sid   = (int)($input['id'] ?? 0);

    if ($sid > 0) {
        // UPDATE (PUT fallback)
        $fields = $input['custom_fields'] ?? [];
        $parent_id = isset($input['parent_id']) ? ($input['parent_id'] !== '' ? (int)$input['parent_id'] : null) : -1;

        $chk = $pdo->prepare("SELECT id FROM serials WHERE id = ? AND user_id = ?");
        $chk->execute([$sid, $uid]);
        if (!$chk->fetch()) json_out(false, [], 'Not found.');

        // Verify parent ownership & circular references
        if ($parent_id !== -1 && $parent_id !== null) {
            if ($parent_id === $sid) {
                json_out(false, [], 'An asset cannot be its own parent.');
            }
            $chk_p = $pdo->prepare("SELECT id, custom_fields FROM serials WHERE id = ? AND user_id = ?");
            $chk_p->execute([$parent_id, $uid]);
            $parent_row = $chk_p->fetch();
            if (!$parent_row) {
                json_out(false, [], 'Invalid parent asset selected.');
            }
            $p_fields = json_decode($parent_row['custom_fields'], true) ?: [];
            $fields['user'] = $p_fields['user'] ?? '';
            $fields['designation'] = $p_fields['designation'] ?? '';
            $fields['department'] = $p_fields['department'] ?? '';
        }

        $new_status = $input['status'] ?? null;
        $note = trim($input['maintenance_note'] ?? '');
        
        $curr_stmt = $pdo->prepare("SELECT status FROM serials WHERE id = ?");
        $curr_stmt->execute([$sid]);
        $old_status = $curr_stmt->fetchColumn() ?: 'In Stock';
        
        if ($new_status === null) {
            $new_status = $old_status;
        }

        if ($parent_id !== -1) {
            $stmt = $pdo->prepare("UPDATE serials SET custom_fields = ?, parent_id = ?, status = ? WHERE id = ?");
            $stmt->execute([json_encode($fields), $parent_id, $new_status, $sid]);
        } else {
            $stmt = $pdo->prepare("UPDATE serials SET custom_fields = ?, status = ? WHERE id = ?");
            $stmt->execute([json_encode($fields), $new_status, $sid]);
        }

        if ($new_status !== $old_status || !empty($note)) {
            $log_note = !empty($note) ? $note : "Status updated from '{$old_status}' to '{$new_status}'";
            $log_stmt = $pdo->prepare("INSERT INTO maintenance_logs (serial_id, status, note) VALUES (?, ?, ?)");
            $log_stmt->execute([$sid, $new_status, $log_note]);
        }

        propagate_custodian_to_children($pdo, $sid, $uid, $fields);
        json_out(true, ['custom_fields' => $fields, 'status' => $new_status], 'Serial updated.');
    }

    // Check serial limit
    $u_stmt = $pdo->prepare("SELECT serial_limit FROM users WHERE id = ?");
    $u_stmt->execute([$uid]);
    $limitVal = $u_stmt->fetchColumn();
    $limit = (int)$limitVal;
    if ($limit <= 0) $limit = 50; // default free tier limit

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM serials WHERE user_id = ?");
    $count_stmt->execute([$uid]);
    $current_count = (int)$count_stmt->fetchColumn();

    if ($current_count >= $limit) {
        json_out(false, ['limit_reached' => true], "Limit reached ($limit). Please upgrade your plan to create more serials.");
    }

    $pid   = (int)($input['product_id'] ?? 0);
    $fields = $input['custom_fields'] ?? [];
    $parent_id = isset($input['parent_id']) && $input['parent_id'] !== '' ? (int)$input['parent_id'] : null;

    if (!$pid) json_out(false, [], 'Product required.');

    // Verify product ownership
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$pid, $uid]);
    $product = $stmt->fetch();
    if (!$product) json_out(false, [], 'Product not found.');

    // Verify parent ownership
    if ($parent_id) {
        $chk_p = $pdo->prepare("SELECT id, custom_fields FROM serials WHERE id = ? AND user_id = ?");
        $chk_p->execute([$parent_id, $uid]);
        $parent_row = $chk_p->fetch();
        if (!$parent_row) {
            json_out(false, [], 'Invalid parent asset selected.');
        }
        $p_fields = json_decode($parent_row['custom_fields'], true) ?: [];
        $fields['user'] = $p_fields['user'] ?? '';
        $fields['designation'] = $p_fields['designation'] ?? '';
        $fields['department'] = $p_fields['department'] ?? '';
    }

    $status = $input['status'] ?? 'In Stock';
    $serial_number = generate_serial_number($pdo, $uid, $pid, $parent_id);

    try {
        // Attempt insertion including created_at explicitly
        $stmt = $pdo->prepare(
            "INSERT INTO serials (user_id, product_id, parent_id, serial_number, custom_fields, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $pid, $parent_id, $serial_number, json_encode($fields), $status, date('Y-m-d H:i:s')]);
        $new_id = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Fallback insertion without created_at if schema is strictly autogenerated
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO serials (user_id, product_id, parent_id, serial_number, custom_fields, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$uid, $pid, $parent_id, $serial_number, json_encode($fields), $status]);
            $new_id = $pdo->lastInsertId();
        } catch (PDOException $e2) {
            json_out(false, [], "Database error: " . $e2->getMessage());
        }
    }

    $log_stmt = $pdo->prepare("INSERT INTO maintenance_logs (serial_id, status, note) VALUES (?, ?, ?)");
    $log_stmt->execute([$new_id, $status, "Asset generated with status '{$status}'."]);

    $parent_serial_number = null;
    if ($parent_id) {
        $p_sn_stmt = $pdo->prepare("SELECT serial_number FROM serials WHERE id = ?");
        $p_sn_stmt->execute([$parent_id]);
        $parent_serial_number = $p_sn_stmt->fetchColumn() ?: null;
    }

    json_out(true, [
        'serial' => [
            'id'            => $new_id,
            'product_id'    => $pid,
            'parent_id'     => $parent_id,
            'parent_serial_number' => $parent_serial_number,
            'serial_number' => $serial_number,
            'custom_fields' => $fields,
            'product_name'  => $product['name'],
            'short_name'    => $product['short_name'],
            'created_at'    => date('Y-m-d H:i:s'),
        ]
    ]);
}

/* ── PUT: update serial custom fields ── */
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $sid   = (int)($input['id'] ?? 0);
    $fields = $input['custom_fields'] ?? [];
    $parent_id = isset($input['parent_id']) ? ($input['parent_id'] !== '' ? (int)$input['parent_id'] : null) : -1;

    if (!$sid) json_out(false, [], 'Serial ID required.');

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM serials WHERE id = ? AND user_id = ?");
    $chk->execute([$sid, $uid]);
    if (!$chk->fetch()) json_out(false, [], 'Not found.');

    // Verify parent ownership & circular references
    if ($parent_id !== -1 && $parent_id !== null) {
        if ($parent_id === $sid) {
            json_out(false, [], 'An asset cannot be its own parent.');
        }
        $chk_p = $pdo->prepare("SELECT id, custom_fields FROM serials WHERE id = ? AND user_id = ?");
        $chk_p->execute([$parent_id, $uid]);
        $parent_row = $chk_p->fetch();
        if (!$parent_row) {
            json_out(false, [], 'Invalid parent asset selected.');
        }
        $p_fields = json_decode($parent_row['custom_fields'], true) ?: [];
        $fields['user'] = $p_fields['user'] ?? '';
        $fields['designation'] = $p_fields['designation'] ?? '';
        $fields['department'] = $p_fields['department'] ?? '';
    }

    $new_status = $input['status'] ?? null;
    $note = trim($input['maintenance_note'] ?? '');
    
    $curr_stmt = $pdo->prepare("SELECT status FROM serials WHERE id = ?");
    $curr_stmt->execute([$sid]);
    $old_status = $curr_stmt->fetchColumn() ?: 'In Stock';
    
    if ($new_status === null) {
        $new_status = $old_status;
    }

    if ($parent_id !== -1) {
        $stmt = $pdo->prepare("UPDATE serials SET custom_fields = ?, parent_id = ?, status = ? WHERE id = ?");
        $stmt->execute([json_encode($fields), $parent_id, $new_status, $sid]);
    } else {
        $stmt = $pdo->prepare("UPDATE serials SET custom_fields = ?, status = ? WHERE id = ?");
        $stmt->execute([json_encode($fields), $new_status, $sid]);
    }

    if ($new_status !== $old_status || !empty($note)) {
        $log_note = !empty($note) ? $note : "Status updated from '{$old_status}' to '{$new_status}'";
        $log_stmt = $pdo->prepare("INSERT INTO maintenance_logs (serial_id, status, note) VALUES (?, ?, ?)");
        $log_stmt->execute([$sid, $new_status, $log_note]);
    }

    propagate_custodian_to_children($pdo, $sid, $uid, $fields);
    json_out(true, ['custom_fields' => $fields, 'status' => $new_status], 'Serial updated.');
}

/* ── DELETE: remove serial ──────────────────────────── */
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $params);
    $sid = (int)($params['id'] ?? 0);
    if (!$sid) json_out(false, [], 'Serial ID required.');
    $chk = $pdo->prepare("SELECT id FROM serials WHERE id = ? AND user_id = ?");
    $chk->execute([$sid, $uid]);
    if (!$chk->fetch()) json_out(false, [], 'Not found.');
    $pdo->prepare("DELETE FROM serials WHERE id = ?")->execute([$sid]);
    json_out(true, [], 'Deleted.');
}

json_out(false, [], 'Method not allowed.');
