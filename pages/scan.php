<?php
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['s'] ?? 0);
if (!$id) { http_response_code(404); die('<h2>Invalid QR code link.</h2>'); }

$stmt = $pdo->prepare(
    "SELECT s.*, p.name as product_name, p.short_name, p.icon as product_icon, u.name as owner_name, u.logo_url, u.company_heading, u.company_subheading, u.show_reference
     FROM serials s
     JOIN products p ON s.product_id = p.id
     JOIN users      u ON s.user_id     = u.id
     WHERE s.id = ?"
);
$stmt->execute([$id]);
$serial = $stmt->fetch();

if (!$serial) { http_response_code(404); die('<h2>Serial not found.</h2>'); }

// Fetch parent asset if parent_id is set
$parent = null;
$is_child = false;
if (!empty($serial['parent_id'])) {
    $is_child = true;
    $p_stmt = $pdo->prepare(
        "SELECT s.id, s.serial_number, p.name as product_name, p.icon as product_icon, s.custom_fields
         FROM serials s
         JOIN products p ON s.product_id = p.id
         WHERE s.id = ?"
    );
    $p_stmt->execute([$serial['parent_id']]);
    $parent = $p_stmt->fetch() ?: null;
}

// Fetch children assets
$children = [];
$c_stmt = $pdo->prepare(
    "SELECT s.id, s.serial_number, s.status, p.name as product_name, p.icon as product_icon, s.custom_fields
     FROM serials s
     JOIN products p ON s.product_id = p.id
     WHERE s.parent_id = ?"
);
$c_stmt->execute([$id]);
$children = $c_stmt->fetchAll();

$has_children = !empty($children);
$custom = $serial['custom_fields'] ? json_decode($serial['custom_fields'], true) : [];
$logo = !empty($serial['logo_url']) ? ((strpos($serial['logo_url'], 'http') === 0) ? $serial['logo_url'] : BASE_URL . '/' . $serial['logo_url']) : 'https://pasupatigroup.com/wp-content/uploads/2020/09/pasupati-favicon.png';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($serial['serial_number']) ?> - Asset Details</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta name="robots" content="noindex">
    <style>
        :root {
            --p: #0f172a;
            --p-light: #1e293b;
            --accent: #00cfe8;
            --accent-glow: rgba(0, 207, 232, 0.15);
            --bg: #f0f2f7;
            --card-bg: #ffffff;
            --txt: #111827;
            --txt-sec: #6b7280;
            --border: #e2e5ec;
            --border-light: #f1f3f8;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 8px 30px rgba(15, 23, 42, 0.07);
            --shadow-lg: 0 20px 50px rgba(15, 23, 42, 0.1);
            --radius: 18px;
            --radius-sm: 12px;
            --radius-xs: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            background-image: 
                radial-gradient(ellipse at 20% 0%, rgba(0, 207, 232, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 100%, rgba(15, 23, 42, 0.04) 0%, transparent 50%);
            color: var(--txt);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
            line-height: 1.6;
        }

        /* ── Main Card ── */
        .card {
            background: var(--card-bg);
            width: 100%;
            max-width: 500px;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            animation: cardEntry 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes cardEntry {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Header ── */
        .head {
            background: var(--p);
            background-image: linear-gradient(135deg, var(--p) 0%, var(--p-light) 100%);
            color: #ffffff;
            padding: 22px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .head::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }
        .head h1 {
            font-size: 1.05rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 3px;
        }
        .head p {
            font-size: 0.78rem;
            color: #94a3b8;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* ── Body ── */
        .body { padding: 24px; }

        /* ── Device Header ── */
        .device-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border-light);
        }
        .brand-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            flex-shrink: 0;
            border-radius: var(--radius-sm);
            background: #ffffff;
            padding: 4px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .device-header h2 {
            font-size: 1.15rem;
            color: var(--txt);
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .device-header .subtitle {
            font-size: 0.8rem;
            color: var(--accent);
            text-transform: uppercase;
            font-weight: 700;
            display: block;
            margin-top: 3px;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.3px;
        }

        /* ── Tag Rows (Parent Asset, Equipment Label) ── */
        .tag-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            background: var(--border-light);
            padding: 10px 14px;
            border-radius: var(--radius-xs);
            border: 1px solid var(--border);
            flex-wrap: nowrap;
            gap: 10px;
            transition: border-color 0.2s ease;
        }
        .tag-row:hover { border-color: rgba(0, 207, 232, 0.3); }
        .tag-row.parent-tag {
            background: rgba(0, 207, 232, 0.04);
            border-color: rgba(0, 207, 232, 0.18);
        }

        .title {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--txt-sec);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .id-badge {
            background: var(--p);
            color: var(--accent);
            font-family: 'SF Mono', 'Fira Code', monospace;
            padding: 5px 12px;
            border-radius: var(--radius-xs);
            font-size: 0.82rem;
            font-weight: 800;
            word-break: break-all;
            text-align: right;
            letter-spacing: 0.5px;
        }

        /* ── Asset Details Panel ── */
        .asset-details {
            margin-bottom: 18px;
            padding: 14px 16px;
            background: var(--border-light);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .asset-details .title { margin-bottom: 6px; font-size: 0.65rem; }
        .asset-details .asset-name {
            font-weight: 800;
            font-size: 1rem;
            color: var(--txt);
        }
        .asset-details .asset-desc {
            font-size: 0.82rem;
            color: var(--txt-sec);
            margin-top: 4px;
            white-space: pre-wrap;
            font-family: 'Inter', sans-serif;
            line-height: 1.55;
        }
        .asset-details .serial-tag {
            margin-top: 8px;
            display: inline-block;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.75rem;
            background: rgba(0, 0, 0, 0.04);
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 700;
            color: var(--txt);
            border: 1px solid var(--border);
        }

        /* ── Component List ── */
        .section-label {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--txt-sec);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .list { display: flex; flex-direction: column; gap: 10px; }

        .item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            display: flex;
            gap: 14px;
            align-items: flex-start;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 3px solid transparent;
        }
        .item:hover {
            border-color: var(--accent);
            border-left-color: var(--accent);
            box-shadow: 0 4px 16px var(--accent-glow);
            transform: translateY(-1px);
        }
        .item.component-item { border-left-color: var(--accent); }

        .icon {
            width: 38px;
            height: 38px;
            background: var(--accent-glow);
            color: var(--accent);
            border-radius: var(--radius-xs);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .det { flex: 1; min-width: 0; }
        .det h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--txt);
            margin-bottom: 2px;
        }
        .det p {
            font-size: 0.8rem;
            color: var(--txt-sec);
            font-family: 'Inter', sans-serif;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .component-name {
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--txt);
            margin-top: 2px;
        }
        .component-desc {
            font-size: 0.78rem;
            color: var(--txt-sec);
            margin-top: 2px;
            white-space: pre-wrap;
            font-family: 'Inter', sans-serif;
        }

        .status-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .code-badge {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-weight: 800;
            font-size: 0.7rem;
            color: var(--accent);
            background: rgba(0, 207, 232, 0.06);
            border: 1px solid rgba(0, 207, 232, 0.12);
            padding: 2px 7px;
            border-radius: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 20px 16px;
            color: var(--txt-sec);
            font-size: 0.82rem;
            font-family: 'Inter', sans-serif;
        }
        .empty-state i {
            font-size: 1.6rem;
            display: block;
            margin-bottom: 8px;
            opacity: 0.35;
        }

        /* ── Timestamp ── */
        .timestamp {
            margin-top: 22px;
            font-size: 0.7rem;
            color: #94a3b8;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border-light);
            text-align: center;
        }
        .footer p {
            font-size: 0.78rem;
            color: var(--txt-sec);
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }
        .footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.2s ease;
        }
        .footer a:hover { color: #00b8cf; }

        .cta-link {
            display: block;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px dashed var(--border);
            text-align: center;
        }
        .cta-link a {
            color: var(--txt-sec);
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .cta-link a span {
            display: block;
            margin-top: 4px;
            color: var(--accent);
            font-weight: 800;
            font-size: 0.85rem;
        }
        .cta-link a:hover { color: var(--txt); }

        /* ── Responsive ── */
        @media (min-width: 640px) {
            .card { max-width: 520px; }
            .body { padding: 28px; }
            .head { padding: 26px 28px; }
        }
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            .device-header { flex-direction: column; text-align: center; gap: 10px; }
            .brand-logo { width: 56px; height: 56px; }
            .device-header h2 { font-size: 1.05rem; }
            .tag-row {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 6px;
            }
            .id-badge { max-width: 100%; text-align: center; font-size: 0.85rem; }
            .item { gap: 12px; padding: 12px 14px; }
            .icon { width: 34px; height: 34px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1><?= htmlspecialchars($serial['company_heading'] ?: 'PASUPATI AGROVET PRIVATE LIMITED') ?></h1>
            <p><?= htmlspecialchars($serial['company_subheading'] ?: 'RAIPUR UNIT (Plant-4)') ?></p>
        </div>
        <div class="body">
            <div class="device-header">
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="brand-logo">
                <div>
                    <?php
                        $user_val = $custom['user'] ?? $serial['short_name'];
                        $desig_val = trim($custom['designation'] ?? '');
                        $dept_val = trim($custom['department'] ?? '');

                        $parts = [];
                        if ($desig_val !== '') $parts[] = htmlspecialchars($desig_val);
                        if ($dept_val !== '') $parts[] = htmlspecialchars($dept_val);
                        $sub_val = implode(' &bull; ', $parts);
                    ?>
                    <h2><?= htmlspecialchars($user_val) ?></h2>
                    <?php if ($sub_val): ?>
                        <span class="subtitle"><?= $sub_val ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tag-row">
                <div class="title">Equipment Label</div>
                <div class="id-badge"><?= htmlspecialchars($serial['serial_number']) ?></div>
            </div>

            <?php if ($parent): ?>
            <div class="section-label">Parent Asset</div>
            <div class="list" style="margin-bottom: 20px;">
                <?php
                $p_custom = $parent['custom_fields'] ? json_decode($parent['custom_fields'], true) : [];
                ?>
                <a href="<?= BASE_URL ?>/scan?s=<?= $parent['id'] ?>" class="item component-item" style="text-decoration: none;">
                    <div class="icon">
                        <i class="bi <?= htmlspecialchars($parent['product_icon'] ?: 'bi-box-seam') ?>"></i>
                    </div>
                    <div class="det">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom: 2px;">
                            <h3 style="margin:0; color: var(--txt);"><?= htmlspecialchars($parent['product_name']) ?></h3>
                            <span class="code-badge"><?= htmlspecialchars($parent['serial_number']) ?></span>
                        </div>
                        <?php if (!empty($p_custom['name'])): ?>
                            <div class="component-name"><?= htmlspecialchars($p_custom['name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($p_custom['description'])): ?>
                            <p class="component-desc"><?= htmlspecialchars($p_custom['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php
            $show_asset_details = !empty($custom['name']) || !empty($custom['description']) || (!$is_child && !empty($custom['serial_no']));
            ?>
            <?php if ($show_asset_details): ?>
            <div class="section-label">Asset Details</div>
            <div class="list" style="margin-bottom: 20px;">
                <div class="item component-item">
                    <div class="icon">
                        <i class="bi <?= htmlspecialchars($serial['product_icon'] ?: 'bi-box-seam') ?>"></i>
                    </div>
                    <div class="det">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom: 2px;">
                            <h3 style="margin:0;"><?= htmlspecialchars($serial['product_name']) ?></h3>
                            <?php if (!$is_child && !empty($custom['serial_no'])): ?>
                                <span class="code-badge">S/N: <?= htmlspecialchars($custom['serial_no']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($custom['name'])): ?>
                            <div class="component-name"><?= htmlspecialchars($custom['name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($custom['description'])): ?>
                            <p class="component-desc"><?= htmlspecialchars($custom['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($has_children): ?>
            <div class="section-label">Components (<?= count($children) ?>)</div>
            <?php endif; ?>

            <div class="list">
                <?php
                if (!$has_children):
                ?>
                    <div class="item">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            No hardware components recorded.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($children as $child):
                        $child_custom = $child['custom_fields'] ? json_decode($child['custom_fields'], true) : [];
                        $c_status = $child['status'] ?: 'In Stock';

                        // REQ #2: Hide active status for child assets (assets assigned to a parent)
                        // Status is hidden here since these children are displayed on a parent's scan page
                        $status_badge = "";
                    ?>
                    <div class="item component-item">
                        <div class="icon">
                            <i class="bi <?= htmlspecialchars($child['product_icon'] ?: 'bi-box-seam') ?>"></i>
                        </div>
                        <div class="det">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom: 2px;">
                                <h3 style="margin:0;"><?= htmlspecialchars($child['product_name']) ?></h3>
                                <?php
                                // REQ #2: Hide serial number for child assets on scan page
                                // (no code-badge or status_badge output)
                                ?>
                            </div>

                            <?php if (!empty($child_custom['name'])): ?>
                                <div class="component-name"><?= htmlspecialchars($child_custom['name']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($child_custom['description'])): ?>
                                <p class="component-desc"><?= htmlspecialchars($child_custom['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="timestamp">
                Generated on <?= htmlspecialchars(date('d M Y, H:i', strtotime($serial['created_at']))) ?>
            </div>

            <?php if ($serial['show_reference']): ?>
            <!-- REQ #3: Simplified footer — just "Developed by Pradyumna" -->
            <div class="footer">
                <p>Developed by <a href="https://pkbehera.in" target="_blank">Pradyumna</a></p>
            </div>

            <div class="cta-link">
                <a href="<?= BASE_URL ?>/">
                    Interested in a professional QR Asset System?
                    <span>Get Started for Free</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
