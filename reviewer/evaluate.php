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
                               a.num_siblings, a.house_photo, a.reason,
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
            $stmt->bind_result($app_no, $fam_income, $apply_date, $father_occ, $mother_occ, $grade10, $siblings, $house_photo, $reason, $student_name, $roll_no, $student_email, $scheme_name, $amount);
            
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
    <title>Evaluate Application | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

<?php $page_title = 'Evaluate Application'; include 'header.php'; ?>

<main class="max-w-4xl mx-auto px-4 py-8">

    <!-- Back -->
    <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-[#004D4A] transition mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Dashboard
    </a>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Review & Recommend</h1>
        <p class="text-slate-500 mt-1">Verify the applicant information below, then submit your assessment.</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-2xl mb-6 text-sm font-medium">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Combined Application & Student Info Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-slate-800">Application & Student Information</h2>
        </div>
        <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-x-10 gap-y-5">
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Name</span>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($student_name ?? '') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Roll Number</span>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($roll_no ?? '') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email</span>
                <p class="text-sm font-semibold text-slate-900 break-all"><?= htmlspecialchars($student_email ?? '') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Application No</span>
                <p class="text-sm font-bold text-slate-900 font-mono"><?= htmlspecialchars($app_no ?? '') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Submission Date</span>
                <p class="text-sm font-semibold text-slate-900"><?= date("d M Y", strtotime($apply_date ?? '')) ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Family Monthly Income</span>
                <p class="text-sm font-semibold text-slate-900"><?= number_format($fam_income ?? 0) ?> MMK</p>
            </div>
            <div class="md:col-span-3">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Scholarship Scheme</span>
                <p class="text-sm font-bold text-[#004D4A]"><?= htmlspecialchars($scheme_name ?? '') ?></p>
                <p class="text-xs text-slate-500 mt-0.5">Funding: <?= number_format($amount ?? 0) ?> MMK</p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Father's Occupation</span>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($father_occ ?? '-') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mother's Occupation</span>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($mother_occ ?? '-') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total 10th Grade Marks</span>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($grade10 ?? '-') ?></p>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Number of Siblings</span>
                <p class="text-sm font-semibold text-slate-900"><?= (int)($siblings ?? 0) ?></p>
            </div>
            <?php if (!empty($house_photo)): ?>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">House Photo</span>
                <img src="../uploads/house_photos/<?= htmlspecialchars($house_photo) ?>" alt="House Photo" class="mt-1 rounded-xl border border-slate-200 max-h-48 object-cover">
            </div>
            <?php endif; ?>
            <?php if (!empty($reason)): ?>
            <div class="md:col-span-3">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reason for Applying</span>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($reason) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assessment Form Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-slate-800">Assessment Remarks</h2>
        </div>
        <div class="p-8">
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="application_id" value="<?= htmlspecialchars($application_id) ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                        Evaluation Remarks
                    </label>
                    <textarea
                        name="remarks"
                        rows="5"
                        required
                        placeholder="Type verified eligibility checks, certificate matching status, or regional evaluation comments here..."
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 placeholder-slate-400 outline-none focus:border-[#004D4A] focus:ring-2 focus:ring-[#004D4A]/10 transition resize-none"></textarea>
                </div>

                <div class="flex gap-4">
                    <button
                        type="submit"
                        class="flex-1 bg-[#004D4A] hover:bg-[#003D3B] text-white py-3 rounded-xl font-bold text-sm transition">
                        Submit Assessment
                    </button>
                    <a
                        href="dashboard.php"
                        class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-sm transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</main>

<?php $conn->close(); ?>