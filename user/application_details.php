<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($app_id <= 0) {
    header("Location: my_applications.php");
    exit();
}

// Fetch application with JOINs, ensuring it belongs to this student
$stmt = $conn->prepare("
    SELECT a.application_no, a.family_income, a.apply_date, a.status,
           a.payment_status, a.approved_at,
           a.father_occupation, a.mother_occupation, a.grade_10_marks,
           a.num_siblings, a.house_photo, a.household_registration, a.reason,
           s.name AS student_name, s.roll_no, s.email AS student_email,
           sc.scheme_name, sc.amount
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    WHERE a.id = ? AND a.student_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $app_id, $student_id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$app) {
    header("Location: my_applications.php");
    exit();
}

// Fetch rejection remarks if rejected
$reject_reason = null;
if ($app['status'] === 'Rejected') {
    $stmt2 = $conn->prepare("SELECT remarks FROM application_reviews WHERE application_id = ? AND remarks IS NOT NULL AND remarks != '' ORDER BY reviewed_at DESC LIMIT 1");
    $stmt2->bind_param("i", $app_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $reject_reason = $res2['remarks'] ?? null;
    $stmt2->close();
}

// Fetch receipt if payment is Paid
$receipt = null;
if ($app['payment_status'] === 'Paid') {
    $stmt3 = $conn->prepare("SELECT filename, created_at, downloaded FROM receipts WHERE application_id = ? ORDER BY id DESC LIMIT 1");
    $stmt3->bind_param("i", $app_id);
    $stmt3->execute();
    $receipt = $stmt3->get_result()->fetch_assoc();
    $stmt3->close();
}


$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

$status = $app['status'];
$status_colors = [
    'Submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
    'Under Review' => 'bg-amber-50 text-amber-700 border-amber-200',
    'Recommended' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
    'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'Rejected' => 'bg-red-50 text-red-700 border-red-200',
];
$status_class = $status_colors[$status] ?? 'bg-slate-100 text-slate-800 border-slate-200';
$p_status = $app['payment_status'] ?? 'Pending';
?>
<?php include_once('../includes/header.php'); ?>

<div class="min-h-screen flex flex-col">
    <main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 py-4 sm:py-5">

        <!-- Back link -->
        <a href="my_applications.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-[#004D4A] transition mb-6 group">
            <svg class="w-4 h-4 transition group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to My Applications
        </a>

        <!-- ===================== SINGLE APPLICATION DETAILS CARD ===================== -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-5">

            <!-- Card Header -->
            <div class="relative overflow-hidden bg-gradient-to-r from-[#006D69] to-[#003D3B] px-4 sm:px-6 py-3 sm:py-4">
                <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/5 rounded-full"></div>
                <div class="absolute -bottom-14 -left-8 w-56 h-56 bg-white/5 rounded-full"></div>
                <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase tracking-wider text-teal-200 font-semibold mb-0.5">Scholarship Scheme</p>
                        <h1 class="text-lg sm:text-xl font-bold text-white truncate"><?= htmlspecialchars($app['scheme_name']) ?></h1>
                        <p class="text-xs text-teal-100 mt-1 font-semibold">Funding: <span class="text-[#FFD700]"><?= number_format($app['amount']) ?> MMK</span></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full border <?= $status_class ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full border bg-white/10 text-white border-white/20">
                            <svg class="w-3.5 h-3.5 mr-1 <?= $p_status === 'Paid' ? 'text-emerald-300' : 'text-teal-300' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <?= htmlspecialchars($p_status) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rejection Reason -->
            <?php if ($status === 'Rejected' && $reject_reason): ?>
            <div class="px-5 sm:px-8 pt-4">
                <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 sm:p-4">
                    <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-red-800">Rejection Reason</h3>
                        <p class="text-[10px] text-red-500 mt-0.5 mb-1">Please review the reason below</p>
                        <p class="text-xs text-red-700 leading-relaxed"><?= nl2br(htmlspecialchars($reject_reason)) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card Body -->
            <div class="p-4 sm:p-6">

                <!-- Student Section -->
                <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Student Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 sm:gap-x-8 gap-y-2.5 sm:gap-y-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Student Name</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= htmlspecialchars($app['student_name']) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Roll Number</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= htmlspecialchars($app['roll_no']) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Email</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900 break-all"><?= htmlspecialchars($app['student_email']) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Application No</label>
                        <p class="text-xs sm:text-sm font-bold text-slate-900 font-mono"><?= htmlspecialchars($app['application_no']) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Apply Date</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= date("d M Y", strtotime($app['apply_date'])) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Payment Status</label>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold <?= $p_status === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= htmlspecialchars($p_status) ?>
                        </span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-slate-100 my-4"></div>

                <!-- Scholarship Section -->
                <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Scholarship Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 sm:gap-x-8 gap-y-2.5 sm:gap-y-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Scholarship Scheme</label>
                        <p class="text-xs sm:text-sm font-bold text-[#004D4A]"><?= htmlspecialchars($app['scheme_name']) ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Funding Amount</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= number_format($app['amount']) ?> MMK</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 my-4"></div>

                <!-- Family & Academics Section -->
                <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Family & Academic Background
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 sm:gap-x-8 gap-y-2.5 sm:gap-y-3 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Family Monthly Income</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= number_format($app['family_income']) ?> MMK</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total 10th Grade Marks</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= htmlspecialchars($app['grade_10_marks'] ?? '-') ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Father's Occupation</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= htmlspecialchars($app['father_occupation'] ?? '-') ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Mother's Occupation</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= htmlspecialchars($app['mother_occupation'] ?? '-') ?></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Number of Siblings</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= (int)($app['num_siblings'] ?? 0) ?></p>
                    </div>
                    <?php if ($app['approved_at']): ?>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Approved At</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900"><?= date("d M Y H:i", strtotime($app['approved_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="border-t border-slate-100 my-4"></div>

                <!-- Documents Section -->
                <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Submitted Documents
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <?php if (!empty($app['house_photo'])): ?>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">House Photo</label>
                        <a href="../uploads/house_photos/<?= htmlspecialchars($app['house_photo']) ?>" target="_blank" class="block group relative overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                            <img src="../uploads/house_photos/<?= htmlspecialchars($app['house_photo']) ?>" alt="House Photo" class="w-full max-h-36 object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="bg-white/90 text-slate-800 text-[10px] font-bold px-3 py-1.5 rounded-full">Click to view full size</span>
                            </span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($app['household_registration'])): ?>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Household Registration List</label>
                        <a href="../uploads/household_registration/<?= htmlspecialchars($app['household_registration']) ?>" target="_blank" class="block group relative overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                            <img src="../uploads/household_registration/<?= htmlspecialchars($app['household_registration']) ?>" alt="Household Registration" class="w-full max-h-36 object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="bg-white/90 text-slate-800 text-[10px] font-bold px-3 py-1.5 rounded-full">Click to view full size</span>
                            </span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="border-t border-slate-100 my-4"></div>

                <!-- Reason Section -->
                <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Reason for Applying
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-700 leading-relaxed whitespace-pre-wrap bg-slate-50 border border-slate-100 rounded-xl p-3 mb-4"><?= nl2br(htmlspecialchars($app['reason'] ?? '-')) ?></p>

                <!-- Receipt Section -->
                <?php if ($p_status === 'Paid' && $receipt): ?>
                <div class="border-t border-slate-100 my-4 pt-4">
                    <h2 class="flex items-center gap-1 text-[11px] font-bold text-[#004D4A] uppercase tracking-wider mb-2.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Payment Receipt
                    </h2>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                        <img src="../uploads/receipts/<?= htmlspecialchars($receipt['filename']) ?>" alt="Payment Receipt" class="rounded-lg border border-emerald-200 max-h-44 w-auto object-contain shadow-sm bg-white">
                        <?php if (!$receipt['downloaded'] || $receipt['downloaded'] == 0): ?>
                        <a href="download_receipt.php?app_id=<?= $app_id ?>" class="mt-2.5 inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold px-3.5 py-1.5 rounded-lg transition shadow-md hover:shadow-lg">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download Receipt
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Back to applications -->
        <a href="my_applications.php" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-[#004D4A] transition mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            View All Applications
        </a>

    </main>
</div>

<?php include_once('../includes/footer.php'); ?>
<?php $conn->close(); ?>
