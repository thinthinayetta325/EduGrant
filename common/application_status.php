<?php
session_start();
require_once '../config/db.php';

$app_no = $_GET['app_no'] ?? '';

// DEBUG: This will show you exactly what the script is "seeing"
echo "<!-- Debug: app_no variable is: " . $app_no . " -->";

$application = null;

if (!empty($app_no)) {
    // Trim the input just in case there are hidden spaces
    $app_no = trim($app_no); 
    
    $stmt = $conn->prepare("SELECT * FROM applications WHERE application_no = ?");
    $stmt->bind_param("s", $app_no);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-10">
    <div class="max-w-xl mx-auto border p-6 rounded">
        <h2 class="text-xl font-bold mb-4">Application Details</h2>
        
        <?php if ($application): ?>
            <p><strong>Found!</strong></p>
            <p>App No: <?php echo $application['application_no']; ?></p>
            <p>Status: <?php echo $application['status']; ?></p>
        <?php elseif (!empty($app_no)): ?>
            <p class="text-red-500">No application found for: <strong><?php echo htmlspecialchars($app_no); ?></strong></p>
        <?php else: ?>
            <p>No Application Number was provided.</p>
        <?php endif; ?>
        
        <a href="javascript:history.back()" class="text-blue-600 block mt-4">Go Back</a>
    </div>
</body>
</html>