<?php
session_start();
require_once '../config/db.php';

// 1. Security: Ensure someone is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['reviewer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$app_no = $_GET['app_no'] ?? '';
$application = null;

if (!empty($app_no)) {
    // 2. Fetch Data
    // We join the tables to get the Student and Scheme info
    $stmt = $conn->prepare("SELECT a.*, s.name as student_name, s.roll_no, sc.scheme_name 
                            FROM applications a 
                            LEFT JOIN student s ON a.student_id = s.id 
                            LEFT JOIN schemes sc ON a.scheme_id = sc.id 
                            WHERE a.application_no = ?");
    $stmt->bind_param("s", $app_no);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();

    // 3. Security Check: If Student, ensure they own the application
    if (isset($_SESSION['student_id']) && $application && $application['student_id'] != $_SESSION['student_id']) {
        die("Access Denied: You do not have permission to view this application.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Application Status</title>
</head>
<body class="bg-slate-50 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Application Details</h2>

        <?php if ($application): ?>
            <div class="grid grid-cols-2 gap-6 text-sm mb-8">
                <div><p class="text-slate-500">Application No</p><p class="font-bold text-lg"><?php echo htmlspecialchars($application['application_no']); ?></p></div>
                <div><p class="text-slate-500">Status</p><span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-semibold"><?php echo htmlspecialchars($application['status']); ?></span></div>
                <div><p class="text-slate-500">Student Name</p><p class="font-medium"><?php echo htmlspecialchars($application['student_name']); ?></p></div>
                <div><p class="text-slate-500">Scheme</p><p class="font-medium"><?php echo htmlspecialchars($application['scheme_name']); ?></p></div>
            </div>

            <!-- ROLE-BASED ACCESS LOGIC -->
            <?php if (isset($_SESSION['reviewer_id'])): ?>
                <!-- SHOW TO REVIEWER ONLY -->
                <div class="border-t pt-6 mt-6 border-slate-100">
                    <h3 class="text-md font-bold mb-4">Reviewer Controls</h3>
                    <div class="flex gap-4">
                        <a href="evaluate.php?id=<?php echo $application['id']; ?>" class="bg-[#004D4A] text-white px-6 py-2 rounded-lg hover:bg-[#003D3B]">Evaluate Application</a>
                        <a href="reviewer_dashboard.php" class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg hover:bg-slate-200">Back to Queue</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- SHOW TO STUDENT ONLY -->
                <div class="border-t pt-6 mt-6 border-slate-100">
                    <p class="text-slate-500 text-sm italic">You are viewing your own application status. No further action is required at this time.</p>
                    <a href="../user/dashboard.php" class="inline-block mt-4 text-[#004D4A] font-semibold hover:underline">← Back to Dashboard</a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-red-500 p-4 bg-red-50 rounded-lg">No application found with that number.</p>
        <?php endif; ?>
    </div>
</body>
</html>