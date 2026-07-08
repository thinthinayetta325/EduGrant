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
    <title>Evaluate Application File</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 40px 20px; transition: background-color 0.3s, color 0.3s; }
        body.light-mode { background-color: #f8fafc; color: #1e293b; }
        body:not(.light-mode) { background-color: #0f172a; color: #e2e8f0; }
        .container { width: 50%; max-width: 500px; margin: 0 auto; padding: 30px; border-radius: 12px; transition: all 0.3s; }
        body.light-mode .container { background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        body:not(.light-mode) .container { background: #1e293b; border: 1px solid rgba(255,255,255,0.08); }
        .title { margin-top: 0; font-size: 16px; font-weight: bold; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        body.light-mode .title { color: #003D3B; border-bottom: 2px solid #f1f5f9; }
        body:not(.light-mode) .title { color: #fff; border-bottom: 2px solid rgba(255,255,255,0.08); }
        .info-card { padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; }
        body.light-mode .info-card { background: #f8fafc; border: 1px solid #e2e8f0; }
        body:not(.light-mode) .info-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; }
        body.light-mode .info-row { border-bottom: 1px dashed #e2e8f0; }
        body:not(.light-mode) .info-row { border-bottom: 1px dashed rgba(255,255,255,0.08); }
        .info-row:last-child { border-bottom: none; }
        .label-text { font-weight: bold; }
        body.light-mode .label-text { color: #64748b; }
        body:not(.light-mode) .label-text { color: rgba(255,255,255,0.5); }
        .value-text { font-weight: 500; }
        body.light-mode .value-text { color: #0f172a; }
        body:not(.light-mode) .value-text { color: #fff; }
        .error-alert { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; }
        body.light-mode .error-alert { background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; }
        body:not(.light-mode) .error-alert { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #f87171; }
        .form-label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; }
        body.light-mode .form-label { color: #64748b; }
        body:not(.light-mode) .form-label { color: rgba(255,255,255,0.5); }
        .textarea-input { width: 100%; height: 120px; padding: 12px; border-radius: 6px; box-sizing: border-box; font-family: inherit; font-size: 14px; resize: vertical; margin-bottom: 20px; outline: none; transition: all 0.25s; }
        body.light-mode .textarea-input { border: 1px solid #cbd5e1; background: #fff; color: #1e293b; }
        body:not(.light-mode) .textarea-input { border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: #fff; }
        .textarea-input:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.15); }
        .actions { display: flex; gap: 10px; }
        .btn-submit { flex: 2; padding: 12px; background: #006D69; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.25s; }
        .btn-submit:hover { background: #005a56; transform: translateY(-1px); }
        .btn-cancel { flex: 1; padding: 12px; text-align: center; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; box-sizing: border-box; transition: all 0.25s; }
        body.light-mode .btn-cancel { background: #e2e8f0; color: #475569; }
        body:not(.light-mode) .btn-cancel { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.6); }
        body.light-mode .btn-cancel:hover { background: #cbd5e1; }
        body:not(.light-mode) .btn-cancel:hover { background: rgba(255,255,255,0.1); }

        .theme-toggle { position: relative; width: 44px; height: 24px; border-radius: 12px; cursor: pointer; transition: background 0.3s; }
        .theme-toggle .toggle-thumb { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; transition: transform 0.3s, background 0.3s; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        body.light-mode .theme-toggle { background: #006D69; }
        body:not(.light-mode) .theme-toggle { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); }
        body.light-mode .theme-toggle .toggle-thumb { transform: translateX(20px); background: #fff; }
        body:not(.light-mode) .theme-toggle .toggle-thumb { background: #0f172a; }
    </style>
</head>
<body>

<div class="container">
    <div class="title">
        <span>📋 Review & Recommend Application</span>
        <div class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
            <div class="toggle-thumb">
                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                <svg class="w-3 h-3 text-blue-400 hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
            </div>
        </div>
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
            <a href="dashboards.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

    <script>
        // Theme toggle
        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
        }

        // Load saved theme (default dark)
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>