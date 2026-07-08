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

    <header class="bg-[#006D69] text-white px-6 py-4 shadow-md flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-wide">EduGrant Portal</h1>
            <p class="text-xs text-teal-200">Reviewer Workspace - View All Applications</p>
        </div>
        <!-- 
         -->
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium bg-white/10 px-3 py-1.5 rounded-md border border-white/10">
                👤 <?php echo htmlspecialchars($reviewer_name); ?>
            </span>
            <a href="../auth/logout.php" class="text-sm bg-red-600/20 hover:bg-red-600/40 text-red-200 px-3 py-1.5 rounded-md border border-red-500/30 transition">
                Logout
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">All Applications</h2>
            <p class="text-sm text-slate-500 mt-1">Reviewing all incoming scholarship applications.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
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
    </main>
</body>
</html>
<?php $conn->close(); ?>