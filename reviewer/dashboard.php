<?php
session_start();

// Security Guard Check
if (!isset($_SESSION['reviewer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php'; 

$reviewer_name = $_SESSION['reviewer_name'] ?? 'Reviewer';

// Fetch profile image
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$reviewer_img = $conn->query("SELECT profile_image FROM reviewers WHERE id = " . (int)$_SESSION['reviewer_id'])->fetch_assoc()['profile_image'] ?? null;

// UPDATED QUERY: Check if application already recommended by another reviewer
$reviewer_id = $_SESSION['reviewer_id'];

// Pagination
$per_page = 10;
$current_page = max(1, intval($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $per_page;

// Get total rows for pagination
$total_rows = $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'] ?? 0;
$total_pages = max(1, ceil($total_rows / $per_page));

$query = "SELECT a.id as app_id, a.application_no, a.family_income, a.apply_date, a.status,
                 a.father_occupation, a.mother_occupation, a.grade_10_marks,
                 a.num_siblings, a.house_photo, a.reason,
                 s.name as student_name, s.roll_no, 
                 sc.scheme_name, sc.amount,
                 ar.reviewer_id AS reviewed_by
          FROM applications a
          LEFT JOIN student s ON a.student_id = s.id
          LEFT JOIN schemes sc ON a.scheme_id = sc.id
          LEFT JOIN application_reviews ar ON a.id = ar.application_id AND ar.recommendation = 'Recommended'
          ORDER BY a.apply_date DESC
          LIMIT $per_page OFFSET $offset";

$result = $conn->query($query);

// Count metrics for the 4 stat cards
$total_assigned = $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'] ?? 0;
$pending_reviews = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status = 'Submitted'")->fetch_assoc()['c'] ?? 0;
$approved = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status = 'Recommended'")->fetch_assoc()['c'] ?? 0;
$flagged = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status IN ('Rejected','Under Review')")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<script>if(sessionStorage.getItem('scrollPos')){window.addEventListener('load',function(){setTimeout(function(){window.scrollTo(0,parseInt(sessionStorage.getItem('scrollPos')));sessionStorage.removeItem('scrollPos')},50)})}</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Workspace | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

    <?php $page_title = 'Reviewer Workspace'; include 'header.php'; ?>

    <!-- KPI Metric Cards -->
    <section class="max-w-[1400px] mx-auto px-4 pt-10 pb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Assigned</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $total_assigned; ?></p>
                    <p class="text-xs text-slate-400">Applications waiting for review</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Reviews</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $pending_reviews; ?></p>
                    <p class="text-xs text-slate-400">Not yet touched</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approved / Recommended</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $approved; ?></p>
                    <p class="text-xs text-slate-400">Reviewed and forwarded</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Flagged / Returned</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $flagged; ?></p>
                    <p class="text-xs text-slate-400">Sent back or rejected</p>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-[1400px] mx-auto px-3 sm:px-4 pb-10">
        <div class="mb-5">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">All Applications</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Reviewing all incoming scholarship applications.</p>
        </div>

        <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">No.</th>
                        <th class="px-4 py-3">App No</th>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Scheme</th>
                        <th class="px-4 py-3">Income</th>
                        <th class="px-4 py-3">Father Occ</th>
                        <th class="px-4 py-3">Mother Occ</th>
                        <th class="px-4 py-3">10th Marks</th>
                        <th class="px-4 py-3">Siblings</th>
                        <th class="px-4 py-3">House Photo</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
    <?php
    if ($result && $result->num_rows > 0):
        $result->data_seek(0);
        $no = $offset + 1;

        while ($row = $result->fetch_assoc()):
            $status = $row['status'] ?? 'Unknown';
            $status_class = "bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-600";

            if ($status == 'Submitted') {
                $status_class = "bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700";
            } elseif ($status == 'Under Review') {
                $status_class = "bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-700";
            } elseif ($status == 'Recommended') {
                $status_class = "bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700";
            } elseif ($status == 'Rejected') {
                $status_class = "bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700";
            }
    ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-center">
                    <?php echo $no++; ?>
                </td>
                <td class="px-4 py-3 font-mono font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                    <?php echo htmlspecialchars($row['application_no'] ?? 'N/A'); ?>
                </td>
                <td class="px-4 py-3">
                    <div class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></div>
                    <div class="text-[10px] text-slate-400">Roll: <?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></div>
                </td>
                <td class="px-4 py-3 text-slate-800 dark:text-slate-200 whitespace-nowrap">
                    <?php echo htmlspecialchars($row['scheme_name'] ?? 'N/A'); ?>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                    <?php echo number_format($row['family_income'] ?? 0); ?> MMK
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                    <?php echo htmlspecialchars($row['father_occupation'] ?? '-'); ?>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                    <?php echo htmlspecialchars($row['mother_occupation'] ?? '-'); ?>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-center">
                    <?php echo htmlspecialchars($row['grade_10_marks'] ?? '-'); ?>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-center">
                    <?php echo (int)($row['num_siblings'] ?? 0); ?>
                </td>
                <td class="px-4 py-3">
                    <?php if (!empty($row['house_photo'])): ?>
                        <img src="../uploads/house_photos/<?php echo htmlspecialchars($row['house_photo']); ?>" alt="House" class="h-10 w-10 rounded-lg border border-slate-200 dark:border-slate-600 object-cover">
                    <?php else: ?>
                        <span class="text-slate-400">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 max-w-[180px] truncate" title="<?php echo htmlspecialchars($row['reason'] ?? ''); ?>">
                    <?php echo htmlspecialchars(mb_strimwidth($row['reason'] ?? '-', 0, 40, '...')); ?>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-[10px] font-semibold rounded-full border <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <?php
                    $reviewed_by = $row['reviewed_by'] ?? null;
                    $already_by_other = ($status === 'Recommended') && $reviewed_by && ($reviewed_by != $reviewer_id);
                    $is_approved = ($status === 'Approved');
                    ?>
                    <?php if ($already_by_other): ?>
                        <span class="bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium px-3 py-1.5 rounded-lg text-[11px] cursor-not-allowed" title="Already recommended by another reviewer">
                            Evaluate
                        </span>
                    <?php elseif ($is_approved): ?>
                        <span class="bg-[#004D4A] text-white font-medium px-3 py-1.5 rounded-lg text-[11px] opacity-60 cursor-not-allowed pointer-events-none" title="Application already approved">
                            Evaluate
                        </span>
                    <?php else: ?>
                        <a href="evaluate.php?id=<?php echo $row['app_id']; ?>" class="bg-[#004D4A] hover:bg-[#003D3B] text-white font-medium px-3 py-1.5 rounded-lg text-[11px] transition">
                            Evaluate
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
    <?php
        endwhile;
    else:
    ?>
        <tr>
            <td colspan="13" class="text-center py-12 text-slate-400">
                No applications found in the system.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-700">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_rows); ?> of <?php echo $total_rows; ?>
                </p>
                <div class="flex gap-1">
                    <?php
                    $lang_param = $is_mm ? '&lang=mm' : '';
                    ?>
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $lang_param; ?>" class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Prev</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $current_page - 2);
                    $end = min($total_pages, $current_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $lang_param; ?>" class="px-3 py-1.5 text-xs rounded-lg border transition <?php echo $i == $current_page ? 'bg-[#004D4A] text-white border-[#004D4A]' : 'border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $lang_param; ?>" class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Load saved theme
        if (localStorage.getItem('reviewer_theme') === 'dark' || (!localStorage.getItem('reviewer_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>