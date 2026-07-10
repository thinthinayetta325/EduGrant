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
            $stmt->bind_result($app_no, $fam_income, $apply_date, $student_name, $roll_no, $student_email, $scheme_name, $amount);
            
            if (!$stmt->fetch()) {
                // If ID is completely missing or invalid, route back to safety
                header("Location: dashboard.php");
                exit();
            }
            $stmt->close();
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
    <title>Evaluate Application File</title>
    <style>
        .eval-box { width: 40%; max-width: 420px; margin: 0 auto; padding: 30px; border-radius: 12px; background: #fff; box-shadow: var(--shadow-lg); }
        .title { margin-top: 0; font-size: 16px; font-weight: bold; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; color: var(--text-primary); border-bottom: 2px solid var(--border); }
        .info-card { padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; background: #f8fafc; border: 1px solid var(--border); }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border); }
        .info-row:last-child { border-bottom: none; }
        .label-text { font-weight: bold; color: var(--text-secondary); }
        .value-text { font-weight: 500; color: var(--text-primary); }
        .error-alert { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; }
        .form-label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; color: var(--text-secondary); }
        .textarea-input { width: 100%; height: 120px; padding: 12px; border-radius: 6px; box-sizing: border-box; font-family: inherit; font-size: 14px; resize: vertical; margin-bottom: 20px; outline: none; transition: all 0.25s; border: 1px solid #cbd5e1; background: #fff; color: var(--text-primary); }
        .textarea-input:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.15); }
        .actions { display: flex; gap: 10px; }
        .btn-submit { flex: 2; padding: 12px; background: #006D69; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.25s; }
        .btn-submit:hover { background: #005a56; transform: translateY(-1px); }
        .btn-cancel { flex: 1; padding: 12px; text-align: center; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; box-sizing: border-box; transition: all 0.25s; background: #e2e8f0; color: #475569; }
        .btn-cancel:hover { background: #cbd5e1; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; margin: 16px 24px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--text-secondary); text-decoration: none; background: #fff; border: 1px solid var(--border); transition: var(--transition); }
        .back-link:hover { background: var(--body-bg); color: var(--text-primary); }
        .eval-content { padding: 0 24px; }
        @media (max-width: 768px) {
            .eval-box { width: 100%; margin: 0 16px; padding: 20px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php $page_title = 'Evaluate Application'; include 'header.php'; ?>

<div class="eval-content">
<a href="dashboard.php" class="back-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Back to Dashboard
</a>

<div class="eval-box">
    <div class="title">
        <span>📋 Review & Recommend Application</span>
    </div>
    
    <?php if (!empty($error_message)): ?>
        <div class="error-alert">❌ <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Display Structural Info Summary -->
    <div class="info-card">
        <div class="info-row"><span class="label-text">Application No:</span> <span class="value-text"><?php echo htmlspecialchars($app_no ?? ''); ?></span></div>
        <div class="info-row"><span class="label-text">Student Name:</span> <span class="value-text"><?php echo htmlspecialchars($student_name ?? ''); ?></span></div>
        <div class="info-row"><span class="label-text">Roll Number:</span> <span class="value-text"><?php echo htmlspecialchars($roll_no ?? ''); ?></span></div>
        <div class="info-row"><span class="label-text">Email Address:</span> <span class="value-text"><?php echo htmlspecialchars($student_email ?? ''); ?></span></div>
        <div class="info-row"><span class="label-text">Requested Scheme:</span> <span class="value-text"><?php echo htmlspecialchars($scheme_name ?? ''); ?></span></div>
        <div class="info-row"><span class="label-text">Family Income Status:</span> <span class="value-text"><?php echo number_format($fam_income ?? 0); ?> MMK</span></div>
        <div class="info-row"><span class="label-text">Submission Date:</span> <span class="value-text"><?php echo htmlspecialchars($apply_date ?? ''); ?></span></div>
    </div>

    <!-- Reviewer Processing Input Panel -->
    <form action="" method="POST">
        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application_id); ?>">

        <label class="form-label">📋 Assessor Evaluation Remarks (စိစစ်ချက် မှတ်ချက်)</label>
        <textarea name="remarks" required class="textarea-input" placeholder="Type verified eligibility checks, certificate matching status, or regional evaluation comments here..."></textarea>

        <div class="actions">
            <button type="submit" class="btn-submit">Submit Official Assessment Review</button>
            <a href="dashboard.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</div>

<?php $conn->close(); ?>