<?php
/**
 * Send subscription receipt emails to user and admin.
 */
require_once __DIR__ . '/config.php';

// Require PHPMailer
$mailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
if (file_exists($mailerPath . 'PHPMailer.php')) {
    require_once $mailerPath . 'PHPMailer.php';
    require_once $mailerPath . 'SMTP.php';
    require_once $mailerPath . 'Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('render_qams_email')) {
    function render_qams_email(string $title, string $subtitle, string $content_body): string {
        $logo_url = BASE_URL . '/assets/img/logo%20png.png';
        $home_url = BASE_URL . '/';
        $year = date('Y');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <title>" . htmlspecialchars($title) . "</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Outfit, Inter, Arial, sans-serif; color: #0f172a;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f1f5f9; padding: 40px 15px;'>
                <tr>
                    <td align='center'>
                        <table width='100%' max-width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);'>
                            
                            <!-- Header / Navbar Style -->
                            <tr>
                                <td style='background-color: #0f172a; padding: 22px 32px; border-bottom: 3px solid #00cfe8;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='left'>
                                                <a href='{$home_url}' target='_blank' style='text-decoration: none; display: inline-block;'>
                                                    <img src='{$logo_url}' alt='Logo' style='height: 32px; vertical-align: middle; border: 0;' onerror=\"this.style.display='none'\">
                                                </a>
                                            </td>
                                            <td align='right' style='font-size: 0.8rem; font-weight: 700; color: #00cfe8; text-transform: uppercase; letter-spacing: 1px;'>
                                                Asset Platform
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Banner Section -->
                            <tr>
                                <td style='background-color: #1e293b; padding: 35px 32px; text-align: center;'>
                                    <h1 style='color: #ffffff; font-size: 1.65rem; font-weight: 800; margin: 0; letter-spacing: -0.5px;'>{$title}</h1>
                                    " . ($subtitle ? "<p style='color: #94a3b8; font-size: 0.95rem; margin: 8px 0 0 0;'>{$subtitle}</p>" : "") . "
                                </td>
                            </tr>

                            <!-- Content Body -->
                            <tr>
                                <td style='padding: 40px 32px; font-size: 0.95rem; line-height: 1.6; color: #334155;'>
                                    {$content_body}
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 25px 32px; text-align: center; font-size: 0.8rem; color: #64748b;'>
                                    <p style='margin: 0 0 8px 0; font-weight: 700; color: #0f172a;'>QR Asset Management System</p>
                                    <p style='margin: 0;'>Automated status notification. Please do not reply directly to this email.</p>
                                    <p style='margin: 12px 0 0 0;'>&copy; {$year} QAMS. All rights reserved.</p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}

function send_subscription_receipt(PDO $pdo, array $sub, string $expires_at): void {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) return;
    if (!defined('SMTP_HOST') || !SMTP_HOST) return;

    $user_email = $sub['user_email'];
    $user_name  = $sub['user_name'];
    $amount     = number_format($sub['amount'], 2);
    $cycle      = ucfirst($sub['billing_cycle']);
    $limit      = $sub['asset_limit'];
    $branding   = !empty($sub['remove_branding']) ? 'Yes' : 'No';
    $expires    = date('d M Y', strtotime($expires_at));

    $user_body = "
    <p style='font-size: 1.1rem; color: #0f172a; margin-top: 0;'>Hello <b>{$user_name}</b>,</p>
    <p style='color: #475569;'>Your payment verification is successfully approved. Your QAMS subscription account is now fully upgraded and active. Here are your verified plan parameters:</p>
    
    <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
        <tr style='border-bottom: 1px solid #e2e8f0;'>
            <td style='padding: 12px 0; color: #64748b;'>Plan Cycle</td>
            <td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$cycle}</td>
        </tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'>
            <td style='padding: 12px 0; color: #64748b;'>Asset Generation Limit</td>
            <td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$limit} Serials</td>
        </tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'>
            <td style='padding: 12px 0; color: #64748b;'>Branding Removal</td>
            <td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$branding}</td>
        </tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'>
            <td style='padding: 12px 0; color: #64748b;'>Active Until</td>
            <td style='padding: 12px 0; text-align: right; font-weight: 800; color: #00cfe8;'>{$expires}</td>
        </tr>
        <tr>
            <td style='padding: 16px 0; color: #0f172a; font-size: 1.1rem; font-weight: 700;'>Amount Paid</td>
            <td style='padding: 16px 0; text-align: right; font-weight: 900; color: #10b981; font-size: 1.3rem;'>₹{$amount}</td>
        </tr>
    </table>
    
    <p style='color: #64748b; font-size: 0.9rem; margin-bottom: 0;'>Thank you for choosing QAMS for your asset tracking infrastructure. Log in anytime to configure serialized hardware tags.</p>
    ";

    $receipt_html = render_qams_email('Payment Confirmed', 'Your subscription is successfully activated', $user_body);

    // Send to user
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_FROM, 'QAMS');
        $mail->addAddress($user_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = "QAMS — Payment Confirmed & Plan Activated";
        $mail->Body    = $receipt_html;
        $mail->send();
    } catch (\Exception $e) { /* silent fail */ }

    // Send admin notification
    $admin = $pdo->query("SELECT email, name FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    if ($admin) {
        $admin_body = "
        <h2 style='color: #0f172a; margin-top: 0; font-size: 1.2rem;'>Plan Approval Notice</h2>
        <p>A submitted subscription payment request was fully approved and activated for the following user account:</p>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>User</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$user_name}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Email</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$user_email}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Plan Tier</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$cycle} ({$limit} Assets)</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Branding Removed</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$branding}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Amount Verified</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #10b981;'>₹{$amount}</td></tr>
            <tr><td style='padding: 10px 0; color: #64748b;'>Account Expiry</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #00cfe8;'>{$expires}</td></tr>
        </table>";

        $admin_html = render_qams_email('Subscription Approved', "User account upgraded successfully", $admin_body);

        try {
            $mail2 = new PHPMailer(true);
            $mail2->isSMTP();
            $mail2->Host       = SMTP_HOST;
            $mail2->SMTPAuth   = true;
            $mail2->Username   = SMTP_USER;
            $mail2->Password   = SMTP_PASS;
            $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail2->Port       = SMTP_PORT;
            $mail2->setFrom(SMTP_FROM, 'QAMS');
            $mail2->addAddress($admin['email'], $admin['name']);
            $mail2->isHTML(true);
            $mail2->Subject = "QAMS — Subscription Approved: {$user_name}";
            $mail2->Body    = $admin_html;
            $mail2->send();
        } catch (\Exception $e) { /* silent fail */ }
    }
}

function send_payment_notification(PDO $pdo, array $sub): void {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) return;
    if (!defined('SMTP_HOST') || !SMTP_HOST) return;

    $user_email = $sub['user_email'];
    $user_name  = $sub['user_name'];
    $amount     = number_format($sub['amount'], 2);
    $cycle      = ucfirst($sub['billing_cycle']);
    $limit      = $sub['asset_limit'];
    $branding   = !empty($sub['remove_branding']) ? 'Yes' : 'No';
    $txn_id     = !empty($sub['transaction_id']) ? $sub['transaction_id'] : 'Not provided';
    $proof_url  = !empty($sub['payment_proof']) ? BASE_URL . '/' . $sub['payment_proof'] : null;

    // 1. Send beautifully designed notification to the User
    $user_review_body = "
    <p style='font-size: 1.1rem; color: #0f172a; margin-top: 0;'>Hello <b>{$user_name}</b>,</p>
    <p style='color: #475569;'>We have successfully received your payment verification request for upgrading your QAMS workspace plan. Here is a summary of your request details:</p>
    
    <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
        <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 12px 0; color: #64748b;'>Requested Plan</td><td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$cycle}</td></tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 12px 0; color: #64748b;'>Asset Limit</td><td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$limit} Serials</td></tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 12px 0; color: #64748b;'>Branding Removal</td><td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$branding}</td></tr>
        <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 12px 0; color: #64748b;'>Transaction ID Reference</td><td style='padding: 12px 0; text-align: right; font-weight: 700; color: #0f172a;'><code style='background: #f1f5f9; padding: 3px 8px; border-radius: 6px;'>{$txn_id}</code></td></tr>
        <tr><td style='padding: 16px 0; color: #0f172a; font-weight: 700;'>Amount Submitted</td><td style='padding: 16px 0; text-align: right; font-weight: 900; color: #10b981; font-size: 1.2rem;'>₹{$amount}</td></tr>
    </table>

    <div style='background-color: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 16px 20px; border-radius: 8px; margin: 25px 0;'>
        <p style='margin: 0; color: #b45309; font-weight: 700; font-size: 0.95rem;'>Request Under Verification</p>
        <p style='margin: 6px 0 0 0; color: #92400e; font-size: 0.85rem;'>Your request is currently in review. Our administrative support team will verify your submitted parameters and activate your updated account status within the next 24 hours.</p>
    </div>

    <p style='color: #64748b; font-size: 0.85rem; margin-bottom: 0;'>You will receive a separate automated activation receipt email the moment verification succeeds.</p>
    ";

    $user_review_html = render_qams_email('Request Received', 'Your payment verification is under review', $user_review_body);

    try {
        $uMail = new PHPMailer(true);
        $uMail->isSMTP();
        $uMail->Host       = SMTP_HOST;
        $uMail->SMTPAuth   = true;
        $uMail->Username   = SMTP_USER;
        $uMail->Password   = SMTP_PASS;
        $uMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $uMail->Port       = SMTP_PORT;
        $uMail->setFrom(SMTP_FROM, 'QAMS');
        $uMail->addAddress($user_email, $user_name);
        $uMail->isHTML(true);
        $uMail->Subject = "QAMS — Payment Request Received (Under Review)";
        $uMail->Body    = $user_review_html;
        $uMail->send();
    } catch (\Exception $e) { /* silent fail */ }

    // 2. Send beautifully designed notification to the Admin
    $admin = $pdo->query("SELECT email, name FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    if ($admin) {
        $admin_body = "
        <h2 style='color: #0f172a; margin-top: 0; font-size: 1.2rem;'>Action Required: Verify Payment</h2>
        <p>A workspace subscription upgrade request requires verification. Please audit the transaction parameter details below:</p>
        
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>User Name</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$user_name}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>User Email</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$user_email}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Plan Tier Requested</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$cycle} ({$limit} Assets)</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Branding Removal</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>{$branding}</td></tr>
            <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 10px 0; color: #64748b;'>Transaction ID</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'><code style='background: #f1f5f9; padding: 2px 6px; border-radius: 4px;'>{$txn_id}</code></td></tr>
            <tr><td style='padding: 10px 0; color: #64748b;'>Amount to Verify</td><td style='padding: 10px 0; text-align: right; font-weight: 700; color: #10b981;'>₹{$amount}</td></tr>
        </table>
        
        " . ($proof_url ? "<p style='margin: 15px 0; text-align: center;'><a href='{$proof_url}' target='_blank' style='display: inline-block; background-color: #f1f5f9; color: #2563eb; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem;'>📁 View Attached Proof Screenshot</a></p>" : "") . "
        
        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
        <div style='text-align: center;'>
            <a href='" . BASE_URL . "/admin' style='display: inline-block; background-color: #0f172a; color: #ffffff; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; letter-spacing: 0.5px;'>Open Admin Dashboard</a>
        </div>
        ";

        $admin_html = render_qams_email('Payment Verification', 'Pending upgrade request needs audit', $admin_body);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM, 'QAMS');
            $mail->addAddress($admin['email'], $admin['name']);
            $mail->isHTML(true);
            $mail->Subject = "QAMS — New Payment Request: {$user_name} (₹{$amount})";
            $mail->Body    = $admin_html;
            $mail->send();
        } catch (\Exception $e) { /* silent fail */ }
    }
}
