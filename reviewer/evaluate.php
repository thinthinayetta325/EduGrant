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

// ==========================================
// 3. PROCESS GET REQUEST (DISPLAY APP DETAILS)
// ==========================================
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
    <title>Evaluate Application File</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .container { max-width: 650px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .title { color: #003D3B; margin-top: 0; font-size: 22px; font-weight: bold; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .label-text { font-weight: bold; color: #64748b; }
        .value-text { color: #0f172a; font-weight: 500; }
        .error-alert { background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; }
        .form-label { display: block; font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        .radio-group { display: flex; gap: 20px; margin-bottom: 20px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .radio-label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .textarea-input { width: 100%; height: 120px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; font-size: 14px; resize: vertical; margin-bottom: 20px; }
        .actions { display: flex; gap: 10px; }
        .btn-submit { flex: 2; padding: 12px; background: #0f6d6aff; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-cancel { flex: 1; padding: 12px; background: #e2e8f0; color: #475569; text-align: center; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; box-sizing: border-box; }
        .btn-submit:hover { background: #0c5653; }
        .btn-cancel:hover { background: #cbd5e1; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="title">📋 Review & Recommend Application</h2>
    
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

        <label class="form-label">Official Assessment Decision</label>
        <div class="radio-group" style="background:#f0fdf4;border-color:#bbf7d0;">
            <label class="radio-label" style="color: #16a34a;font-size:15px;">
                👍 Recommend File (Reviewers can only recommend)
            </label>
            <input type="hidden" name="recommendation" value="Recommended">
        </div>

        <label class="form-label">📋 Assessor Evaluation Remarks (စိစစ်ချက် မှတ်ချက်)</label>
        <textarea name="remarks" required class="textarea-input" placeholder="Type verified eligibility checks, certificate matching status, or regional evaluation comments here..."></textarea>

        <div class="actions">
            <button type="submit" class="btn-submit">Submit Official Assessment Review</button>
            <a href="dashboards.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
<?php $conn->close(); ?>