<?php
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../libs/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../libs/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Returns TRUE on success, or an error string on failure.
function edugrant_send_email($to, $subject, $html_body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = SMTP_TIMEOUT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>', '</h3>', '</li>'], "\n", $html_body));
        return $mail->send() ? true : 'Email could not be sent.';
    } catch (Exception $e) {
        return 'PHPMailer Error: ' . $mail->ErrorInfo;
    }
}

// Branded HTML email layout wrapper.
function edugrant_email_layout($content_html) {
    return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f0f7f5;padding:24px 12px;">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e0eae8;">'
        . '<div style="background:#006D69;color:#fff;padding:20px 28px;">'
        . '<div style="font-size:18px;font-weight:bold;">EduGrant Myanmar</div>'
        . '<div style="font-size:12px;color:#FFD700;">Computer University (Meiktila) — Scholarship Portal</div>'
        . '</div>'
        . '<div style="padding:28px;">' . $content_html . '</div>'
        . '<div style="background:#f8fafc;color:#94a3b8;font-size:11px;padding:14px 28px;border-top:1px solid #e0eae8;">'
        . 'This is an automated message from the EduGrant Myanmar Scholarship Management System. Please do not reply to this email.'
        . '</div>'
        . '</div>'
        . '</div>';
}

// Approval email body builder. $data must include:
// student_name, scheme_name, application_no, approved_at
function edugrant_approval_email_body($data) {
    $name    = htmlspecialchars($data['student_name']);
    $scheme  = htmlspecialchars($data['scheme_name']);
    $app_no  = htmlspecialchars($data['application_no']);
    $date    = date('d M Y', strtotime($data['approved_at']));
    $content = '<h2 style="color:#0f172a;font-size:18px;margin:0 0 16px;">Your Application Has Been Approved! 🎉</h2>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Dear <strong>' . $name . '</strong>,</p>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Congratulations! Your application for <strong>' . $scheme . '</strong> has been approved.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:13px;">'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Application Number</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $app_no . '</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Approval Status</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#15803d;border:1px solid #e0eae8;">Approved ✅</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Approval Date</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $date . '</td></tr>'
        . '</table>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Please log in to your EduGrant Myanmar account to view your application details.</p>'
        . '<p style="color:#64748b;font-size:13px;">— EduGrant Myanmar Admin</p>';
    return edugrant_email_layout($content);
}

// Next-semester payment email body builder. $data must include:
// student_name, scheme_name, application_no, amount, semester, academic_year
function edugrant_disbursement_email_body($data) {
    $name     = htmlspecialchars($data['student_name']);
    $scheme   = htmlspecialchars($data['scheme_name']);
    $app_no   = htmlspecialchars($data['application_no']);
    $amount   = number_format((float)$data['amount']);
    $semester = htmlspecialchars($data['semester']);
    $year     = htmlspecialchars($data['academic_year']);
    $content = '<h2 style="color:#0f172a;font-size:18px;margin:0 0 16px;">Next Semester Payment Released 💰</h2>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Dear <strong>' . $name . '</strong>,</p>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">We are pleased to inform you that your next semester scholarship payment has been released for <strong>' . $scheme . '</strong>.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:13px;">'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Application Number</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $app_no . '</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Payment Status</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#15803d;border:1px solid #e0eae8;">Released ✅</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Amount</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $amount . ' MMK</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Semester</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $semester . ' (' . $year . ')</td></tr>'
        . '</table>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Please log in to your EduGrant Myanmar account to view your payment details.</p>'
        . '<p style="color:#64748b;font-size:13px;">— EduGrant Myanmar Admin</p>';
    return edugrant_email_layout($content);
}

// Initial fund-release payment email body builder. $data must include:
// student_name, scheme_name, application_no, amount, academic_year, semester
function edugrant_payment_email_body($data) {
    $name     = htmlspecialchars($data['student_name']);
    $scheme   = htmlspecialchars($data['scheme_name']);
    $app_no   = htmlspecialchars($data['application_no']);
    $amount   = number_format((float)$data['amount']);
    $year     = htmlspecialchars($data['academic_year']);
    $semester = htmlspecialchars($data['semester']);
    $content = '<h2 style="color:#0f172a;font-size:18px;margin:0 0 16px;">Scholarship Funds Released 💰</h2>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Dear <strong>' . $name . '</strong>,</p>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">We are pleased to inform you that your scholarship payment has been released for <strong>' . $scheme . '</strong>.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:13px;">'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Application Number</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $app_no . '</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Payment Status</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#15803d;border:1px solid #e0eae8;">Released ✅</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Amount</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $amount . ' MMK</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Semester</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $semester . ' (' . $year . ')</td></tr>'
        . '</table>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Please log in to your EduGrant Myanmar account to view your payment details and download your receipt.</p>'
        . '<p style="color:#64748b;font-size:13px;">— EduGrant Myanmar Admin</p>';
    return edugrant_email_layout($content);
}

// Rejection email body builder. $data must include:
// student_name, scheme_name, application_no
function edugrant_rejection_email_body($data) {
    $name   = htmlspecialchars($data['student_name']);
    $scheme = htmlspecialchars($data['scheme_name']);
    $app_no = htmlspecialchars($data['application_no']);
    $content = '<h2 style="color:#0f172a;font-size:18px;margin:0 0 16px;">Application Rejected</h2>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Dear <strong>' . $name . '</strong>,</p>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">We regret to inform you that your application for <strong>' . $scheme . '</strong> could not be accepted at this time.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:13px;">'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Application Number</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#0f172a;border:1px solid #e0eae8;">' . $app_no . '</td></tr>'
        . '<tr><td style="padding:8px 12px;background:#f8fafc;color:#64748b;border:1px solid #e0eae8;">Approval Status</td>'
        . '<td style="padding:8px 12px;font-weight:bold;color:#b91c1c;border:1px solid #e0eae8;">Rejected ❌</td></tr>'
        . '</table>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">If you believe this decision is in error, please contact the EduGrant Myanmar admin office.</p>'
        . '<p style="color:#64748b;font-size:13px;">— EduGrant Myanmar Admin</p>';
    return edugrant_email_layout($content);
}
