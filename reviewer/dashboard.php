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
$reviewer_img = $conn->query("SELECT profile_image FROM reviewers WHERE id = " . (int)$_SESSION['reviewer_id'])->fetch_assoc()['profile_image'] ?? null;

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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 50: '#1e293b', 100: '#0f172a', 200: '#162032', 300: '#0d1520' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; transition: background-color 0.3s, color 0.3s; }
        .theme-toggle { position: relative; width: 52px; height: 28px; border-radius: 14px; cursor: pointer; transition: background 0.3s; }
        .theme-toggle .toggle-thumb { position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #fff; transition: transform 0.3s, background 0.3s; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .dark .theme-toggle { background: #1e293b; }
        .theme-toggle { background: #006D69; }
        .dark .theme-toggle .toggle-thumb { transform: translateX(24px); background: #0f172a; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#0f172a] text-slate-800 dark:text-slate-200">

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
            <div class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                <div class="toggle-thumb">
                    <svg class="w-3.5 h-3.5 text-amber-400 dark:hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                    <svg class="w-3.5 h-3.5 text-blue-400 hidden dark:block" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                </div>
            </div>
            <a href="profile.php" class="flex items-center gap-2 text-[11px] sm:text-sm font-medium bg-white/10 dark:bg-white/5 hover:bg-white/20 px-2 sm:px-3 py-1 sm:py-1.5 rounded-md border border-white/10 transition truncate max-w-[120px] sm:max-w-none">
                <?php if (!empty($reviewer_img) && file_exists('../uploads/profile_pics/' . $reviewer_img)): ?>
                    <img src="../uploads/profile_pics/<?php echo htmlspecialchars($reviewer_img); ?>" alt="" class="w-6 h-6 rounded-full object-cover border border-white/20">
                <?php else: ?>
                    <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-bold text-white"><?php echo strtoupper(substr($reviewer_name, 0, 1)); ?></span>
                <?php endif; ?>
                <span class="truncate hidden sm:inline"><?php echo htmlspecialchars($reviewer_name); ?></span>
            </a>
            <a href="../auth/logout.php" class="text-[11px] sm:text-sm bg-red-600/20 hover:bg-red-600/40 text-red-200 px-2 sm:px-3 py-1 sm:py-1.5 rounded-md border border-red-500/30 transition whitespace-nowrap">
                Logout
            </a>
        </div>
    </header>

    <!-- KPI Metric Cards -->
    <section class="max-w-7xl mx-auto px-4 pt-10 pb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Assigned</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $total_assigned; ?></p>
                    <p class="text-xs text-slate-400">Applications waiting for review</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Reviews</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $pending_reviews; ?></p>
                    <p class="text-xs text-slate-400">Not yet touched</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approved / Recommended</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-0.5"><?php echo $approved; ?></p>
                    <p class="text-xs text-slate-400">Reviewed and forwarded</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
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

    <main class="max-w-7xl mx-auto px-3 sm:px-4 pb-10">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">All Applications</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Reviewing all incoming scholarship applications.</p>
        </div>

        <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[600px]">
                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase">
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
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm">
    <?php
    if ($result && $result->num_rows > 0):
        $result->data_seek(0);

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
                <td class="px-6 py-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                    <?php echo htmlspecialchars($row['application_no'] ?? 'N/A'); ?>
                </td>
                <td class="px-6 py-4">
                    <div class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></div>
                    <div class="text-xs text-slate-400">Roll: <?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></div>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($row['scheme_name'] ?? 'N/A'); ?></div>
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

    <script>
        // Theme toggle
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }

        // Load saved theme
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>