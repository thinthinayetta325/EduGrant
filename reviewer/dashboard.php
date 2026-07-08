<?php
session_start();

// Security Guard Check
if (!isset($_SESSION['reviewer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php'; 

$reviewer_name = $_SESSION['reviewer_name'] ?? 'Reviewer';

// UPDATED QUERY: Removed the JOIN to reviewer_scheme so ALL applications appear.
// I have also included the LEFT JOIN just in case you want to show names/amounts later.
$query = "SELECT a.id as app_id, a.application_no, a.family_income, a.apply_date, a.status,
                 s.name as student_name, s.roll_no, 
                 sc.scheme_name, sc.amount
          FROM applications a
          LEFT JOIN student s ON a.student_id = s.id
          LEFT JOIN schemes sc ON a.scheme_id = sc.id
          ORDER BY a.apply_date DESC";

$result = $conn->query($query);

// Count metrics for the 4 stat cards
$total_assigned = $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'] ?? 0;
$pending_reviews = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status = 'Submitted'")->fetch_assoc()['c'] ?? 0;
$approved = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status = 'Recommended'")->fetch_assoc()['c'] ?? 0;
$flagged = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status IN ('Rejected','Under Review')")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Workspace | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

    <header class="bg-[#006D69] text-white px-4 sm:px-6 py-3 sm:py-4 shadow-md sticky top-0 z-50 flex flex-wrap items-center justify-between gap-2">
        <a href="../index.php" class="min-w-0 flex-shrink block hover:opacity-90 transition">
            <div class="flex items-center gap-2.5">
                <div class="bg-white/10 p-1.5 rounded-lg text-teal-300 shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-white text-lg sm:text-xl font-bold leading-tight truncate">EduGrant</h1>
                    <p class="text-teal-200 text-[11px] sm:text-xs mt-0.5 opacity-90 tracking-wide">Reviewer Workspace</p>
                </div>
            </div>
        </a>
        <div class="flex items-center gap-2 sm:gap-4 shrink-0">
            <span class="text-[11px] sm:text-sm font-medium bg-white/10 px-2 sm:px-3 py-1 sm:py-1.5 rounded-md border border-white/10 truncate max-w-[120px] sm:max-w-none">
                👤 <?php echo htmlspecialchars($reviewer_name); ?>
            </span>
            <a href="../auth/logout.php" class="text-[11px] sm:text-sm bg-red-600/20 hover:bg-red-600/40 text-red-200 px-2 sm:px-3 py-1 sm:py-1.5 rounded-md border border-red-500/30 transition whitespace-nowrap">
                Logout
            </a>
        </div>
    </header>

    <!-- KPI Metric Cards -->
    <section class="max-w-7xl mx-auto px-4 pt-10 pb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Assigned</p>
                    <p class="text-2xl font-bold text-slate-900 mt-0.5"><?php echo $total_assigned; ?></p>
                    <p class="text-xs text-slate-400">Applications waiting for review</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Reviews</p>
                    <p class="text-2xl font-bold text-slate-900 mt-0.5"><?php echo $pending_reviews; ?></p>
                    <p class="text-xs text-slate-400">Not yet touched</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approved / Recommended</p>
                    <p class="text-2xl font-bold text-slate-900 mt-0.5"><?php echo $approved; ?></p>
                    <p class="text-xs text-slate-400">Reviewed and forwarded</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Flagged / Returned</p>
                    <p class="text-2xl font-bold text-slate-900 mt-0.5"><?php echo $flagged; ?></p>
                    <p class="text-xs text-slate-400">Sent back or rejected</p>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-3 sm:px-4 pb-10">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">All Applications</h2>
            <p class="text-sm text-slate-500 mt-1">Reviewing all incoming scholarship applications.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[600px]">
                <thead class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase">
                    <tr>
                        <th class="px-6 py-4">App No</th>
                        <th class="px-6 py-4">Student</th>
                        <th class="px-6 py-4">Scheme</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <!-- application table -->
                <!-- <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono font-bold"><?php echo htmlspecialchars($row['application_no']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['scheme_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php echo ($row['status'] == 'Submitted') ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="evaluate.php?id=<?php echo $row['app_id']; ?>" class="bg-[#004D4A] text-white px-4 py-2 rounded-lg text-xs hover:bg-[#003D3B]">Evaluate</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-12 text-slate-400">No applications found in the system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody> -->
                <tbody class="divide-y divide-slate-100 text-sm">
    <?php 
    // Reset the pointer just in case
    if ($result && $result->num_rows > 0): 
        // Use data_seek to ensure we start from the beginning
        $result->data_seek(0); 
        
        while ($row = $result->fetch_assoc()): 
            // 1. Define color based on status
            $status = $row['status'] ?? 'Unknown';
            $status_class = "bg-slate-100 text-slate-800 border-slate-200"; // Default
            
            if ($status == 'Submitted') {
                $status_class = "bg-blue-100 text-blue-800 border-blue-200";
            } elseif ($status == 'Under Review') {
                $status_class = "bg-amber-100 text-amber-800 border-amber-200";
            } elseif ($status == 'Recommended') {
                $status_class = "bg-green-100 text-green-800 border-green-200";
            } elseif ($status == 'Rejected') {
                $status_class = "bg-red-100 text-red-800 border-red-200";
            }
    ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-mono font-bold text-slate-700">
                    <?php echo htmlspecialchars($row['application_no'] ?? 'N/A'); ?>
                </td>
                <td class="px-6 py-4">
                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></div>
                    <div class="text-xs text-slate-400">Roll: <?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></div>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium text-slate-800"><?php echo htmlspecialchars($row['scheme_name'] ?? 'N/A'); ?></div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <a href="evaluate.php?id=<?php echo $row['app_id']; ?>" class="bg-[#004D4A] hover:bg-[#003D3B] text-white font-medium px-4 py-2 rounded-lg text-xs transition">
                        Evaluate
                    </a>
                </td>
            </tr>
    <?php 
        endwhile; 
    else: 
    ?>
        <tr>
            <td colspan="5" class="text-center py-12 text-slate-400 text-sm">
                No applications found in the system.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
            </div>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>