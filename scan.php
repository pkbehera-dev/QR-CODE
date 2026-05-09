<?php
require_once __DIR__ . '/includes/db.php';

$id = (int)($_GET['s'] ?? 0);
if (!$id) { http_response_code(404); die('<h2>Invalid QR code link.</h2>'); }

$stmt = $pdo->prepare(
    "SELECT s.*, p.name as product_name, p.short_name, u.name as owner_name, u.logo_url, u.company_heading, u.company_subheading
     FROM serials s
     JOIN products p ON s.product_id = p.id
     JOIN users      u ON s.user_id     = u.id
     WHERE s.id = ?"
);
$stmt->execute([$id]);
$serial = $stmt->fetch();

if (!$serial) { http_response_code(404); die('<h2>Serial not found.</h2>'); }

$custom = $serial['custom_fields'] ? json_decode($serial['custom_fields'], true) : [];
$logo = !empty($serial['logo_url']) ? $serial['logo_url'] : 'https://pasupatigroup.com/wp-content/uploads/2020/09/pasupati-favicon.png';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($serial['serial_number']) ?> - Asset Details</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <meta name="robots" content="noindex">
    <style>
        :root { --p: #006837; --a: #ffc107; --bg: #f3f4f6; --txt: #1f2937; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--txt); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding: 20px 15px; }
        .card { background: #fff; width: 100%; max-width: 500px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .head { background: var(--p); color: #fff; padding: 20px; text-align: center; border-bottom: 4px solid var(--a); }
        .head h1 { font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .head p { font-size: 0.8rem; opacity: 0.9; }
        .body { padding: 24px; }
        .device-header { display: flex; align-items: center; gap: 16px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 16px; margin-bottom: 20px; }
        .brand-logo { width: 64px; height: 64px; object-fit: contain; flex-shrink: 0; border-radius: 8px; }
        .device-header h2 { font-size: 1.15rem; color: var(--p); font-weight: 700; text-transform: uppercase; }
        .device-header span { font-size: 0.85rem; color: #6b7280; text-transform: uppercase; font-weight: 600; display: block; margin-top: 2px; }
        .tag-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .title { font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; }
        .id-badge { background: #111827; color: #fff; font-family: monospace; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .list { display: flex; flex-direction: column; gap: 10px; }
        .item { background: #f9fafb; border: 1px solid #f3f4f6; padding: 15px; border-radius: 10px; display: flex; gap: 15px; align-items: flex-start; transition: 0.2s; }
        .item:hover { border-color: var(--p); background: #fff; }
        .icon { width: 40px; height: 40px; background: #fff; border-radius: 8px; display: grid; place-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex-shrink: 0; }
        .det { flex: 1; }
        .det h3 { font-size: 0.9rem; font-weight: 700; color: var(--txt); margin-bottom: 4px; }
        .det p { font-size: 0.8rem; color: #4b5563; line-height: 1.5; white-space: pre-wrap; }
        .sn-box { background: #ecfdf5; color: var(--p); font-family: monospace; font-weight: 700; font-size: 0.85rem; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-top: 8px; border: 1px solid rgba(0,104,55,0.1); }
        .dev-credit { font-size: 0.75rem; color: #cbd5e1; text-decoration: none; transition: color 0.3s; opacity: 0.6; }
        .dev-credit:hover { color: #94a3b8; opacity: 1; }
        @media (max-width: 480px) { .device-header { flex-direction: column; text-align: center; } }
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
                    <span><?= $sub_val ?></span>
                </div>
            </div>
            
            <div class="tag-row">
                <div class="title">Equipment Label</div>
                <div class="id-badge"><?= htmlspecialchars($serial['serial_number']) ?></div>
            </div>
            
            <div class="list">
                <?php 
                $items = $custom['items'] ?? [];
                if (empty($items)): 
                ?>
                    <div class="item">
                        <div class="det"><p style="text-align:center;color:#888;margin:0">No hardware components recorded.</p></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <div class="item">
                        <div class="icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h.01M15 9h.01M9 15h.01M15 15h.01"/>
                            </svg>
                        </div>
                        <div class="det">
                            <h3><?= htmlspecialchars($item['name'] ?? '') ?></h3>
                            <?php if(!empty($item['details'])): ?>
                                <p><?= htmlspecialchars($item['details']) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($item['sn'])): ?>
                                <div class="sn-box">S/N: <?= htmlspecialchars($item['sn']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 20px; font-size: 0.7rem; color: #9ca3af; text-align: center;">
                Generated on <?= htmlspecialchars(date('d M Y, H:i', strtotime($serial['created_at']))) ?>
            </div>
        </div>
    </div>
    <a href="https://pkbehera.in" class="dev-credit" target="_blank" rel="noopener">pkbehera.in</a>
</body>
</html>
