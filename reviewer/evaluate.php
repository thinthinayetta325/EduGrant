<?php
// Start session to manage success/error alerts
session_start();

// 1. Pull in your central database connection configuration
require_once '../config/db.php'; 

// Check if the user is logged in as a reviewer
if (!isset($_SESSION['reviewer_id'])) {
    header("Location: login.php");
    exit();
}

$reviewer_id = $_SESSION['reviewer_id'];
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$application_id = $recommendation = $remarks = "";
$error_message = "";

// ==========================================
// 2. PROCESS POST REQUEST (SUBMIT ASSESSMENT)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $application_id = intval($_POST['application_id']);
    $recommendation = 'Recommended';
    $remarks = trim($_POST['remarks']);
    
    if (!empty($application_id) && !empty($remarks)) {
        
        // Begin transaction to make sure both operations succeed together
        $conn->begin_transaction();

        try {
            // ✅ FIX: Removed 'review_date' and 'NOW()' from the query to bypass the unknown column error
            $query_review = "INSERT INTO application_reviews (application_id, reviewer_id, recommendation, remarks) 
                             VALUES (?, ?, ?, ?)";
            $stmt_review = $conn->prepare($query_review);
            $stmt_review->bind_param("iiss", $application_id, $reviewer_id, $recommendation, $remarks);
            $stmt_review->execute();
            $stmt_review->close();
            
            // B. Update the master status inside the main applications table
            $query_app = "UPDATE applications SET status = ? WHERE id = ?";
            $stmt_app = $conn->prepare($query_app);
            $stmt_app->bind_param("si", $recommendation, $application_id);
            $stmt_app->execute();
            $stmt_app->close();
            
            // Mark reviewer notifications as read for this application
            $mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE reviewer_id = ? AND type = 'new_application' AND is_read = 0");
            if ($mark_read) {
                $mark_read->bind_param("i", $reviewer_id);
                $mark_read->execute();
                $mark_read->close();
            }

            // Commit database adjustments securely
            $conn->commit();

            // Notify admin when reviewer recommends
            if ($recommendation === 'Recommended') {
                $admin_notify = $conn->prepare("INSERT INTO notifications (student_id, admin_id, title, message, type) VALUES (0, 1, ?, ?, 'reviewer_recommend')");
                if ($admin_notify) {
                    $app_no_q = $conn->prepare("SELECT application_no, student_id FROM applications WHERE id = ?");
                    $app_no_q->bind_param("i", $application_id);
                    $app_no_q->execute();
                    $app_info = $app_no_q->get_result()->fetch_assoc();
                    $app_no_q->close();
                    $reviewer_name_q = $conn->prepare("SELECT name FROM reviewers WHERE id = ?");
                    $reviewer_name_q->bind_param("i", $reviewer_id);
                    $reviewer_name_q->execute();
                    $rname = $reviewer_name_q->get_result()->fetch_assoc()['name'] ?? 'A reviewer';
                    $reviewer_name_q->close();
                    $notify_title = "Application Recommended";
                    $notify_msg = "$rname recommended application " . ($app_info['application_no'] ?? '#') . ". Pending admin approval.";
                    $admin_notify->bind_param("ss", $notify_title, $notify_msg);
                    $admin_notify->execute();
                    $admin_notify->close();
                }
            }

            // Redirect cleanly back to dashboard with a success message flag
            header("Location: dashboard.php?success=1");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Database processing pipeline failed: " . $e->getMessage();
        }
    } else {
        $error_message = "Please write evaluation remarks to submit your recommendation.";
    }
} 


// 3. PROCESS GET REQUEST (DISPLAY APP DETAILS)
else {
    if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
        $application_id = intval($_GET["id"]);
        
        // Fetch detailed applicant information for review layout
        $query_fetch = "SELECT a.application_no, a.family_income, a.apply_date,
                               a.father_occupation, a.mother_occupation, a.grade_10_marks,
                               a.num_siblings, a.house_photo, a.household_registration, a.reason,
                               s.name AS student_name, s.roll_no, s.email AS student_email,
                               sc.scheme_name, sc.amount
                        FROM applications a 
                        JOIN student s ON a.student_id = s.id 
                        JOIN schemes sc ON a.scheme_id = sc.id 
                        WHERE a.id = ? LIMIT 1";
                        
        if ($stmt = $conn->prepare($query_fetch)) {
            $stmt->bind_param("i", $application_id);
            $stmt->execute();
            
            // Safe manual data binding sequence (Avoids get_result driver crashes)
            $stmt->bind_result($app_no, $fam_income, $apply_date, $father_occ, $mother_occ, $grade10, $siblings, $house_photo, $household_registration, $reason, $student_name, $roll_no, $student_email, $scheme_name, $amount);
            
            if (!$stmt->fetch()) {
                // If ID is completely missing or invalid, route back to safety
                header("Location: dashboard.php");
                exit();
            }
            $stmt->close();
        }

        // Check if any reviewer already recommended the application
        $existing_recommendation = null;
        $any_recommended = false;
        $last_remarks = '';
        $review_q = $conn->prepare("SELECT recommendation FROM application_reviews WHERE application_id = ? AND reviewer_id = ? ORDER BY id DESC LIMIT 1");
        if ($review_q) {
            $review_q->bind_param("ii", $application_id, $reviewer_id);
            $review_q->execute();
            $review_q->bind_result($existing_recommendation);
            $review_q->fetch();
            $review_q->close();
        }
        $any_review_q = $conn->prepare("SELECT recommendation FROM application_reviews WHERE application_id = ? AND recommendation = 'Recommended' LIMIT 1");
        if ($any_review_q) {
            $any_review_q->bind_param("i", $application_id);
            $any_review_q->execute();
            $any_res = $any_review_q->get_result()->fetch_assoc();
            if ($any_res) { $any_recommended = true; }
            $any_review_q->close();
        }
        $last_remarks_q = $conn->prepare("SELECT remarks FROM application_reviews WHERE application_id = ? ORDER BY id DESC LIMIT 1");
        if ($last_remarks_q) {
            $last_remarks_q->bind_param("i", $application_id);
            $last_remarks_q->execute();
            $last_remarks_q->bind_result($last_remarks);
            $last_remarks_q->fetch();
            $last_remarks_q->close();
        }
    } else {
        header("Location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Application | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{colors:{teal:{50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a',950:'#042f2e'}}}}}</script>
    <style>
        * { box-sizing: border-box; }
        .eval-gradient { background: linear-gradient(135deg, #006D69 0%, #004D4A 50%, #003D3B 100%); }
        .eval-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); transition: box-shadow 0.3s ease, transform 0.3s ease; }
        .eval-card:hover { box-shadow: var(--shadow-lg); }
        .field-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .field-label svg { width: 14px; height: 14px; opacity: 0.6; }
        .field-value { font-size: 14px; font-weight: 600; color: var(--text-primary); line-height: 1.5; }
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 32px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; gap: 16px; } }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; }
        .badge-scheme { background: rgba(0,109,105,0.08); color: #006D69; border: 1px solid rgba(0,109,105,0.15); }
        .badge-amount { background: rgba(0,109,105,0.06); color: #0d9488; border: 1px solid rgba(0,109,105,0.1); }
        html.dark .badge-scheme { background: rgba(0,109,105,0.2); border-color: rgba(0,109,105,0.3); color: #5eead4; }
        html.dark .badge-amount { background: rgba(0,109,105,0.15); border-color: rgba(0,109,105,0.25); color: #2dd4bf; }
        .doc-thumb { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .doc-thumb:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .doc-thumb img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .doc-thumb .doc-overlay { position: absolute; bottom: 0; left: 0; right: 0; padding: 8px 12px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: #fff; font-size: 11px; font-weight: 600; }
        .eval-textarea { width: 100%; border: 2px solid var(--border); border-radius: 12px; padding: 14px 16px; font-size: 14px; color: var(--text-primary); background: var(--card-bg); outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease; resize: none; font-family: inherit; line-height: 1.6; }
        .eval-textarea::placeholder { color: var(--text-muted); }
        .eval-textarea:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.1); }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; background: linear-gradient(135deg, #006D69, #004D4A); color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; letter-spacing: 0.01em; }
        .btn-primary:hover { background: linear-gradient(135deg, #004D4A, #003D3B); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,109,105,0.3); }
        .btn-primary:active { transform: translateY(0); }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; background: var(--card-bg); color: var(--text-secondary); border: 2px solid var(--border); border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
        .btn-secondary:hover { background: var(--body-bg); border-color: var(--text-muted); color: var(--text-primary); }
        .section-divider { height: 1px; background: var(--border); margin: 0; }
        .progress-bar { height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #006D69, #0d9488); border-radius: 2px; transition: width 0.6s ease; }
        .reason-box { background: var(--body-bg); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 10px; }
        .alert-error svg { flex-shrink: 0; color: #dc2626; }
        html.dark .alert-error { background: rgba(220,38,38,0.1); border-color: rgba(220,38,38,0.3); }
        .back-link { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; color: var(--text-secondary); text-decoration: none; transition: all 0.2s ease; background: var(--card-bg); border: 1px solid var(--border); }
        .back-link:hover { color: #006D69; border-color: rgba(0,109,105,0.3); background: rgba(0,109,105,0.04); }
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; line-height: 1.2; }
        .page-header p { font-size: 14px; color: var(--text-secondary); margin-top: 6px; line-height: 1.5; }
        .card-header { padding: 20px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; }
        .card-header-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .card-header-icon.teal { background: rgba(0,109,105,0.08); color: #006D69; }
        html.dark .card-header-icon.teal { background: rgba(0,109,105,0.2); color: #5eead4; }
        .card-header-text h2 { font-size: 16px; font-weight: 700; color: var(--text-primary); }
        .card-header-text p { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
        .card-body { padding: 28px; }
        html.dark .reason-box { background: rgba(255,255,255,0.03); border-color: var(--border); }
        html.dark .eval-textarea { background: rgba(255,255,255,0.03); border-color: var(--border); color: var(--text-primary); }
        html.dark .btn-secondary { background: rgba(255,255,255,0.03); border-color: var(--border); color: var(--text-secondary); }
        html.dark .btn-secondary:hover { background: rgba(255,255,255,0.06); }
        html.dark .back-link { background: var(--card-bg); border-color: var(--border); }
        html.dark .doc-thumb { border-color: var(--border); }
        html.dark .doc-thumb .doc-overlay { background: linear-gradient(transparent, rgba(0,0,0,0.85)); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeInUp 0.4s ease forwards; }
        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
    </style>
</head>
<body>

<?php $page_title = 'Evaluate Application'; include 'header.php'; ?>

<main style="max-width:820px;margin:0 auto;padding:28px 20px 60px;">

    <!-- Back Link -->
    <div class="animate-in" style="margin-bottom:20px;">
        <a href="dashboard.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header animate-in animate-delay-1">
        <h1>Review & Recommend</h1>
        <p>Verify the applicant information below, then submit your assessment.</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert-error animate-in" style="margin-bottom:24px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span style="font-size:13px;font-weight:600;color:#dc2626;"><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Application Info Card -->
    <div class="eval-card animate-in animate-delay-2" style="margin-bottom:24px;">
        <div class="card-header">
            <div class="card-header-icon teal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="card-header-text">
                <h2>Application & Student Information</h2>
                <p>Applicant details and supporting documents</p>
            </div>
        </div>

        <div class="card-body">
            <!-- Quick Stats Row -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px;">
                <div style="background:var(--body-bg);border-radius:10px;padding:14px 16px;text-align:center;border:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:4px;">Marks</div>
                    <div style="font-size:20px;font-weight:800;color:var(--text-primary);"><?= htmlspecialchars($grade10 ?? '-') ?></div>
                </div>
                <div style="background:var(--body-bg);border-radius:10px;padding:14px 16px;text-align:center;border:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:4px;">Income</div>
                    <div style="font-size:16px;font-weight:800;color:var(--text-primary);"><?= number_format($fam_income ?? 0) ?></div>
                    <div style="font-size:10px;color:var(--text-muted);">MMK/mo</div>
                </div>
                <div style="background:var(--body-bg);border-radius:10px;padding:14px 16px;text-align:center;border:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:4px;">Siblings</div>
                    <div style="font-size:20px;font-weight:800;color:var(--text-primary);"><?= (int)($siblings ?? 0) ?></div>
                </div>
                <div style="background:rgba(0,109,105,0.06);border-radius:10px;padding:14px 16px;text-align:center;border:1px solid rgba(0,109,105,0.12);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#006D69;margin-bottom:4px;">Funding</div>
                    <div style="font-size:16px;font-weight:800;color:#006D69;"><?= number_format($amount ?? 0) ?></div>
                    <div style="font-size:10px;color:#0d9488;">MMK</div>
                </div>
            </div>

            <!-- Scholar Chip -->
            <div style="margin-bottom:24px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="badge badge-scheme">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <?= htmlspecialchars($scheme_name ?? '') ?>
                </span>
                <span class="badge badge-amount">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <?= number_format($amount ?? 0) ?> MMK
                </span>
            </div>

            <!-- Info Fields -->
            <div class="info-grid">
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Name
                    </div>
                    <div class="field-value"><?= htmlspecialchars($student_name ?? '') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Roll Number
                    </div>
                    <div class="field-value"><?= htmlspecialchars($roll_no ?? '') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        Email
                    </div>
                    <div class="field-value" style="word-break:break-all;"><?= htmlspecialchars($student_email ?? '') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                        Application No
                    </div>
                    <div class="field-value" style="font-family:monospace;font-weight:800;"><?= htmlspecialchars($app_no ?? '') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Submission Date
                    </div>
                    <div class="field-value"><?= date("d M Y", strtotime($apply_date ?? '')) ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Family Monthly Income
                    </div>
                    <div class="field-value"><?= number_format($fam_income ?? 0) ?> MMK</div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        Father's Occupation
                    </div>
                    <div class="field-value"><?= htmlspecialchars($father_occ ?? '-') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        Mother's Occupation
                    </div>
                    <div class="field-value"><?= htmlspecialchars($mother_occ ?? '-') ?></div>
                </div>
                <div>
                    <div class="field-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        Total 10th Grade Marks
                    </div>
                    <div class="field-value"><?= htmlspecialchars($grade10 ?? '-') ?></div>
                </div>
            </div>

            <!-- Documents -->
            <?php if (!empty($house_photo) || !empty($household_registration)): ?>
            <div style="margin-top:28px;">
                <div class="section-divider" style="margin-bottom:24px;"></div>
                <div class="field-label" style="margin-bottom:14px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Supporting Documents
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
                    <?php if (!empty($house_photo)): ?>
                    <div class="doc-thumb">
                        <img src="../uploads/house_photos/<?= htmlspecialchars($house_photo) ?>" alt="House Photo">
                        <div class="doc-overlay">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-2px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                            House Photo
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($household_registration)): ?>
                    <div class="doc-thumb">
                        <img src="../uploads/household_registration/<?= htmlspecialchars($household_registration) ?>" alt="Household Registration">
                        <div class="doc-overlay">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                            Household Registration
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reason -->
            <?php if (!empty($reason)): ?>
            <div style="margin-top:28px;">
                <div class="section-divider" style="margin-bottom:24px;"></div>
                <div class="field-label" style="margin-bottom:10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Reason for Applying
                </div>
                <div class="reason-box">
                    <p style="font-size:14px;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($reason) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assessment Form Card -->
    <div class="eval-card animate-in animate-delay-3">
        <div class="card-header">
            <div class="card-header-icon teal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div class="card-header-text">
                <h2>Assessment Remarks</h2>
                <p>Provide your evaluation and recommendation</p>
            </div>
        </div>
        <div class="card-body">
            <form action="" method="POST" style="display:flex;flex-direction:column;gap:24px;">
                <input type="hidden" name="application_id" value="<?= htmlspecialchars($application_id) ?>">

                <div>
                    <label class="field-label" for="remarks" style="margin-bottom:10px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        Evaluation Remarks
                    </label>
                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="5"
                        required
                        placeholder="Type verified eligibility checks, certificate matching status, or regional evaluation comments here..."
                        class="eval-textarea"><?php echo htmlspecialchars($last_remarks ?: ''); ?></textarea>
                    <div style="margin-top:8px;font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        Required for submission. Be specific about your findings.
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <button type="submit" class="btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Submit Assessment
                    </button>
                    <a href="dashboard.php" class="btn-secondary" style="text-align:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</main>

<?php $conn->close(); ?>