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


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: 'MyanmarTaungyi';
            src: url('../MyanmarTaungyi/MyanmarTaungyi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'MyanmarTaungyi', 'Padauk', 'Pyidaungsu', sans-serif !important;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <main class="max-w-4xl mx-auto px-4 py-10">

        <!-- Back link -->
        <a href="my_applications.php" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-[#004D4A] transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to My Applications
        </a>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Application Details</h1>
            <p class="text-slate-500 mt-1">Review your submitted scholarship application.</p>
        </div>

        <!-- Status Badge -->
        <div class="mb-6">
            <?php
            $status = $app['status'];
            $status_colors = [
                'Submitted' => 'bg-blue-100 text-blue-800 border-blue-200',
                'Under Review' => 'bg-amber-100 text-amber-800 border-amber-200',
                'Recommended' => 'bg-green-100 text-green-800 border-green-200',
                'Approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'Rejected' => 'bg-red-100 text-red-800 border-red-200',
            ];
            $status_class = $status_colors[$status] ?? 'bg-slate-100 text-slate-800 border-slate-200';
            ?>
            <span class="inline-block px-4 py-1.5 text-sm font-bold rounded-full border <?= $status_class ?>">
                <?= htmlspecialchars($status) ?>
            </span>
        </div>

        <!-- Rejection Reason -->
        <?php if ($status === 'Rejected' && $reject_reason): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Rejection Reason</h3>
                    <p class="text-xs text-red-600">Please review the reason below</p>
                </div>
            </div>
            <p class="text-sm text-red-700 leading-relaxed"><?= nl2br(htmlspecialchars($reject_reason)) ?></p>
        </div>
        <?php endif; ?>

        <!-- Combined Application & Student Info Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-8">

            <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-lg font-bold text-slate-800">Application & Student Information</h2>
            </div>

            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Student Name</label>
                    <p class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($app['student_name']) ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Roll Number</label>
                    <p class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($app['roll_no']) ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                    <p class="text-lg font-semibold text-slate-900 break-all"><?= htmlspecialchars($app['student_email']) ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Application No</label>
                    <p class="text-lg font-bold text-slate-900 font-mono"><?= htmlspecialchars($app['application_no']) ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Apply Date</label>
                    <p class="text-lg font-semibold text-slate-900"><?= date("d M Y", strtotime($app['apply_date'])) ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Payment Status</label>
                    <?php $p_status = $app['payment_status'] ?? 'Pending'; ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold <?= $p_status === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= htmlspecialchars($p_status) ?>
                    </span>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Scholarship Scheme</label>
                    <p class="text-lg font-bold text-[#004D4A]"><?= htmlspecialchars($app['scheme_name']) ?></p>
                    <p class="text-sm text-slate-500 mt-0.5">Funding: <?= number_format($app['amount']) ?> MMK</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Family Monthly Income</label>
                    <p class="text-lg font-semibold text-slate-900"><?= number_format($app['family_income']) ?> MMK</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Total 10th Grade Marks</label>
                    <p class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($app['grade_10_marks'] ?? '-') ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Father's Occupation</label>
                    <p class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($app['father_occupation'] ?? '-') ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Mother's Occupation</label>
                    <p class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($app['mother_occupation'] ?? '-') ?></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Number of Siblings</label>
                    <p class="text-lg font-semibold text-slate-900"><?= (int)($app['num_siblings'] ?? 0) ?></p>
                </div>

                <?php if (!empty($app['house_photo'])): ?>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">House Photo</label>
                    <img src="../uploads/house_photos/<?= htmlspecialchars($app['house_photo']) ?>" alt="House Photo" class="mt-2 rounded-xl border border-slate-200 max-h-64 object-cover">
                </div>
                <?php endif; ?>

                <?php if (!empty($app['household_registration'])): ?>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Household Registration List</label>
                    <img src="../uploads/household_registration/<?= htmlspecialchars($app['household_registration']) ?>" alt="Household Registration" class="mt-2 rounded-xl border border-slate-200 max-h-64 object-cover">
                </div>
                <?php endif; ?>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Reason for Applying</label>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"><?= nl2br(htmlspecialchars($app['reason'] ?? '-')) ?></p>
                </div>

                <?php if ($app['approved_at']): ?>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Approved At</label>
                    <p class="text-lg font-semibold text-slate-900"><?= date("d M Y H:i", strtotime($app['approved_at'])) ?></p>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </main>

</body>
</html>
<?php $conn->close(); ?>
