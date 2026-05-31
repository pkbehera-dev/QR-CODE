<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

function send_mail($to, $subject, $htmlBody) {
    if (!SMTP_HOST || !SMTP_USER || !SMTP_PASS) {
        return ['success' => false, 'message' => 'SMTP is not configured in config.php'];
    }

    // Wrap plain bodies with the premium layout template
    $finalHtmlBody = render_qams_email($subject, '', $htmlBody);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, 'QR Asset System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $finalHtmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $finalHtmlBody));

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}
