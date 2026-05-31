<?php
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['s'] ?? 0);
if (!$id) { http_response_code(404); die('<h2>Invalid QR code link.</h2>'); }

$stmt = $pdo->prepare(
    "SELECT s.*, p.name as product_name, p.short_name, u.name as owner_name, u.logo_url, u.company_heading, u.company_subheading, u.show_reference
     FROM serials s
     JOIN products p ON s.product_id = p.id
     JOIN users      u ON s.user_id     = u.id
     WHERE s.id = ?"
);
$stmt->execute([$id]);
$serial = $stmt->fetch();

if (!$serial) { http_response_code(404); die('<h2>Serial not found.</h2>'); }

$custom = $serial['custom_fields'] ? json_decode($serial['custom_fields'], true) : [];
$logo = !empty($serial['logo_url']) ? ((strpos($serial['logo_url'], 'http') === 0) ? $serial['logo_url'] : BASE_URL . '/' . $serial['logo_url']) : 'https://pasupatigroup.com/wp-content/uploads/2020/09/pasupati-favicon.png';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($serial['serial_number']) ?> - Asset Details</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta name="robots" content="noindex">
    <style>
        :root { 
            --p: #0f172a; 
            --accent: #00cfe8; 
            --bg: #f5f7fb; 
            --txt: #111827; 
            --txt-sec: #6b7280;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Outfit', 'Inter', system-ui, sans-serif; 
            background: var(--bg); 
            color: var(--txt); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh; 
            padding: 30px 15px; 
            line-height: 1.5;
        }
        .card { 
            background: #ffffff; 
            width: 100%; 
            max-width: 520px; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); 
            border: 1px solid var(--border);
            margin-bottom: 20px; 
        }
        .head { 
            background: var(--p); 
            color: #ffffff; 
            padding: 24px 20px; 
            text-align: center; 
            border-bottom: 4px solid var(--accent); 
        }
        .head h1 { 
            font-size: 1.15rem; 
            font-weight: 800;
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 4px; 
        }
        .head p { 
            font-size: 0.85rem; 
            color: #94a3b8; 
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }
        .body { padding: 28px 24px; }
        .device-header { 
            display: flex; 
            align-items: center; 
            gap: 18px; 
            border-bottom: 1px dashed var(--border); 
            padding-bottom: 20px; 
            margin-bottom: 22px; 
        }
        .brand-logo { 
            width: 70px; 
            height: 70px; 
            object-fit: contain; 
            flex-shrink: 0; 
            border-radius: 12px; 
            background: #ffffff;
            padding: 4px;
            border: 1px solid var(--border);
        }
        .device-header h2 { 
            font-size: 1.25rem; 
            color: var(--txt); 
            font-weight: 800; 
            text-transform: uppercase; 
            line-height: 1.2;
        }
        .device-header span { 
            font-size: 0.85rem; 
            color: var(--accent); 
            text-transform: uppercase; 
            font-weight: 700; 
            display: block; 
            margin-top: 4px; 
            font-family: 'Inter', sans-serif;
        }
        .tag-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 16px; 
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
            flex-wrap: nowrap;
            gap: 10px;
        }
        .title { 
            font-size: 0.75rem; 
            font-weight: 800; 
            color: var(--txt-sec); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            white-space: nowrap;
            flex-shrink: 0;
        }
        .id-badge { 
            background: var(--p); 
            color: var(--accent); 
            font-family: monospace; 
            padding: 5px 12px; 
            border-radius: 8px; 
            font-size: 0.85rem; 
            font-weight: 800; 
            word-break: break-all;
            text-align: right;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }
        .list { display: flex; flex-direction: column; gap: 12px; }
        .item { 
            background: #ffffff; 
            border: 1px solid var(--border); 
            padding: 16px; 
            border-radius: 14px; 
            display: flex; 
            gap: 16px; 
            align-items: flex-start; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .item:hover { 
            border-color: var(--accent); 
            box-shadow: 0 4px 12px rgba(0, 207, 232, 0.1); 
            transform: translateY(-2px);
        }
        .icon { 
            width: 42px; 
            height: 42px; 
            background: rgba(0, 207, 232, 0.1); 
            color: var(--accent);
            border-radius: 10px; 
            display: grid; 
            place-items: center; 
            flex-shrink: 0; 
            font-size: 1.2rem;
        }
        .det { flex: 1; min-width: 0; }
        .det h3 { 
            font-size: 0.95rem; 
            font-weight: 700; 
            color: var(--txt); 
            margin-bottom: 2px; 
        }
        .det p { 
            font-size: 0.85rem; 
            color: var(--txt-sec); 
            font-family: 'Inter', sans-serif;
            line-height: 1.5; 
            white-space: pre-wrap; 
            word-break: break-word;
        }
        .sn-box { 
            background: #f8fafc; 
            color: var(--p); 
            font-family: monospace; 
            font-weight: 700; 
            font-size: 0.8rem; 
            padding: 4px 10px; 
            border-radius: 6px; 
            display: inline-block; 
            margin-top: 8px; 
            border: 1px solid var(--border); 
        }
        @media (max-width: 480px) { 
            .device-header { flex-direction: column; text-align: center; gap: 12px; } 
            .brand-logo { width: 60px; height: 60px; }
            .tag-row { flex-direction: column; align-items: stretch; text-align: center; gap: 6px; }
            .id-badge { max-width: 100%; text-align: center; font-size: 0.9rem; }
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
                    <div class="item" style="justify-content:center;">
                        <div class="det" style="flex:none;"><p style="text-align:center;color:var(--txt-sec);margin:0">No hardware components recorded.</p></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <div class="item">
                        <div class="icon">
                            <i class="bi bi-box-seam"></i>
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
            
            <div style="margin-top: 24px; font-size: 0.75rem; color: #94a3b8; text-align: center; font-family:'Inter',sans-serif; font-weight:500;">
                Generated on <?= htmlspecialchars(date('d M Y, H:i', strtotime($serial['created_at']))) ?>
            </div>

            <?php if ($serial['show_reference']): ?>
            <div style="margin-top: 30px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center;">
                <p style="font-size: 0.8rem; color: var(--txt-sec); line-height: 1.6; font-family:'Inter',sans-serif;">
                    Created on <strong style="color: var(--p); font-weight:700;">QAMS</strong> 
                    <br> 
                    Developed by <a href="https://pkbehera.in" target="_blank" style="color: var(--accent); text-decoration: none; font-weight: 800; font-family:'Outfit',sans-serif; text-transform: uppercase; letter-spacing: 0.5px;">PRADYUMNA</a>
                </p>
            </div>

            <div style="margin-top: 25px; text-align: center; border-top: 1px dashed var(--border); padding-top: 1.5rem;">
                <a href="<?= BASE_URL ?>/" style="color: var(--txt-sec); font-size: 0.85rem; text-decoration: none; font-weight: 600; transition: all 0.2s; display:block;">
                    Interested in a professional QR Asset System? <br><span style="color: var(--accent); font-weight: 800;">Get Started for Free</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
