<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin Clerk";
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';
$sidebar_lang = $is_mm ? [
    'dashboard' => 'ဒက်ရှ်ဘုတ်',
    'schemes' => 'ပညာသင်ဆုအစီအစဉ်များ',
    'reviewers' => 'စိစစ်ရေးမှူးများ',
    'applications' => 'လျှောက်လွှာများ',
    'bank_verify' => 'ဘဏ်စစ်ဆေးခြင်း',
    'recipients' => 'ဆုရရှိသူများ',
    'disbursements' => 'ငွေပေးချေမှုများ',
    'reports' => 'အစီရင်ခံစာများ',
    'logout' => 'ထွက်မည်',
    'page_title' => 'ဘဏ်စစ်ဆေးခြင်း',
] : [
    'dashboard' => 'Dashboard Overview',
    'schemes' => 'Manage Schemes',
    'reviewers' => 'Manage Reviewers',
    'applications' => 'View Applications',
    'bank_verify' => 'Bank Verification',
    'recipients' => 'Recipients Matrix',
    'disbursements' => 'Disbursements Log',
    'reports' => 'Analytics Reports',
    'logout' => 'Logout',
    'page_title' => 'Bank Verification',
];

// Auto-migration: ensure is_verified column exists on bank_details
$col_chk = $conn->query("SHOW COLUMNS FROM bank_details LIKE 'is_verified'");
if ($col_chk && $col_chk->num_rows === 0) {
    $conn->query("ALTER TABLE bank_details ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER account_holder");
}

// Auto-migration: ensure payment_records table exists
$conn->query("CREATE TABLE IF NOT EXISTS payment_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    bank_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES scholarship_recipients(id),
    FOREIGN KEY (bank_id) REFERENCES bank_details(id)
)");

// Auto-migration: ensure receipts table exists
$conn->query("CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    application_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(id),
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (uploaded_by) REFERENCES admin(id)
)");

// Handle form submission: verify bank + upload receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $app_id = (int)$_POST['application_id'];
    $admin_id = $_SESSION['admin_id'];

    if ($_POST['action'] === 'verify_receipt') {
        $academic_year = $conn->real_escape_string($_POST['academic_year'] ?? date('Y') . '-' . (date('Y') + 1));
        $semester = $conn->real_escape_string($_POST['semester'] ?? 'First Semester');

        // Mark bank as verified
        $conn->query("UPDATE bank_details SET is_verified = TRUE WHERE student_id = (SELECT student_id FROM applications WHERE id = $app_id)");

        // Handle receipt upload
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/receipts/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $app_data = $conn->query("SELECT student_id FROM applications WHERE id = $app_id")->fetch_assoc();
                $stu_id = $app_data['student_id'];
                $filename = 'receipt_' . $app_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (!$move_ok = move_uploaded_file($_FILES['receipt_file']['tmp_name'], $dest)) {
                    die("Error: File upload failed (move_uploaded_file).");
                }
                $stmt = $conn->prepare("INSERT INTO receipts (student_id, application_id, filename, uploaded_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $stu_id, $app_id, $filename, $admin_id);
                if (!$stmt->execute()) {
                    die("Error inserting receipt: " . $stmt->error);
                }
                $stmt->close();

                // Auto-create disbursement (payment record)
                $app_full = $conn->query("SELECT a.student_id, a.scheme_id, sc.amount FROM applications a JOIN schemes sc ON a.scheme_id = sc.id WHERE a.id = $app_id")->fetch_assoc();
                $recipient_row = $conn->query("SELECT id FROM scholarship_recipients WHERE application_id = $app_id LIMIT 1")->fetch_assoc();
                $bank_row = $conn->query("SELECT id FROM bank_details WHERE student_id = $stu_id AND is_verified = TRUE LIMIT 1")->fetch_assoc();
                $errs = [];
                if (!$app_full) $errs[] = "app_full is empty (app_id=$app_id)";
                if (!$recipient_row) $errs[] = "No scholarship_recipients row for app_id=$app_id";
                if (!$bank_row) $errs[] = "No bank_details with is_verified=TRUE for student_id=$stu_id";
                if (!empty($errs)) {
                    die("Disbursement skipped: " . implode("; ", $errs));
                }
                $amount = floatval(str_replace(',', '', $app_full['amount']));
                $stmt3 = $conn->prepare("INSERT INTO payment_records (recipient_id, bank_id, amount, academic_year, semester, payment_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                $stmt3->bind_param("iidss", $recipient_row['id'], $bank_row['id'], $amount, $academic_year, $semester);
                if (!$stmt3->execute()) {
                    die("Error inserting payment_record: " . $stmt3->error);
                }
                $stmt3->close();

                // Send notification to student
                $app_info = $conn->query("SELECT application_no FROM applications WHERE id = $app_id")->fetch_assoc();
                $title = "Funds Released";
                $message = "Your scholarship funds for application #{$app_info['application_no']} have been released. Download your receipt now.";
                $stmt2 = $conn->prepare("INSERT INTO notifications (student_id, title, message, type) VALUES (?, ?, ?, 'disbursement')");
                $stmt2->bind_param("iss", $stu_id, $title, $message);
                if (!$stmt2->execute()) {
                    die("Error inserting notification: " . $stmt2->error);
                }
                $stmt2->close();

                header("Location: bank_verify.php?verified=1");
                exit();
            }
        }
        header("Location: bank_verify.php?verified=1");
        exit();
    } elseif ($_POST['action'] === 'reject_bank') {
        $conn->query("UPDATE applications SET status='Rejected' WHERE id=$app_id");
        header("Location: bank_verify.php?rejected=1");
        exit();
    }
}

$pending_bank = $conn->query("SELECT COUNT(*) FROM applications a LEFT JOIN bank_details b ON a.student_id = b.student_id WHERE a.status='Approved' AND b.id IS NULL")->fetch_row()[0] ?? 0;
$current_page = 'bank_verify';

$pending = $conn->query("SELECT a.*, s.name AS student_name, s.roll_no, sc.scheme_name, sc.amount AS scheme_amount, bd.id AS bank_detail_id, bd.bank_name, bd.account_number, bd.account_holder, bd.is_verified,
    (SELECT filename FROM receipts WHERE application_id = a.id ORDER BY id DESC LIMIT 1) AS receipt_file,
    (SELECT id FROM scholarship_recipients WHERE application_id = a.id LIMIT 1) AS recipient_id
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    JOIN bank_details bd ON bd.student_id = a.student_id
    WHERE a.status IN ('Recommended', 'Approved')
    ORDER BY bd.is_verified ASC, a.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Verification - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; color: #1e293b; }
        .sidebar { width: 240px; background-color: #006D69; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-brand { padding: 22px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .brand-icon { width: 38px; height: 38px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: #004D4A; flex-shrink: 0; }
        .brand-text h2 { font-size: 15px; font-weight: 700; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; }
        .admin-profile { padding: 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #004D4A; }
        .admin-meta h4 { margin: 0; font-size: 13px; font-weight: bold; }
        .admin-meta p { margin: 2px 0 0 0; font-size: 11px; color: #FFD700; font-weight: 500; }
        .sidebar-menu { list-style: none; padding: 15px 0; margin: 0; flex-grow: 1; display: flex; flex-direction: column; }
        .menu-item a { display: flex; align-items: center; gap: 8px; padding: 10px 20px; color: rgba(255,255,255,0.75); text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.2s ease; margin: 2px 8px; border-radius: 8px; }
        .menu-item.active a, .menu-item a:hover { background-color: #005a56; color: #fff; }
        .menu-item.logout { margin-top: auto; }
        .menu-item.logout a { color: #fca5a5; }
        .menu-item.logout a:hover { background: rgba(252,165,165,0.1); }
        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
        .dashboard-body { flex-grow: 1; padding: 15px; overflow-y: auto; box-sizing: border-box; }
        .admin-card { background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 15px; }
        .card-title { margin: 0 0 5px 0; font-size: 16px; font-weight: bold; color: #0f172a; }
        .card-subtitle { margin: 0 0 15px 0; font-size: 11px; color: #94a3b8; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; }
        .admin-table th { background: #f8fafc; padding: 10px 8px; font-weight: bold; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .admin-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-verified { background-color: #dcfce7; color: #15803d; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .btn-red-sm { background-color: #dc2626; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .inline-form { display: inline; }
        .badge-count { margin-left: auto; background: rgba(255,255,255,0.1); padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .bank-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .bank-info h4 { margin: 0 0 4px 0; font-size: 14px; }
        .bank-info p { margin: 0; font-size: 12px; color: #475569; }
        .bank-info .detail { color: #64748b; font-size: 11px; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                <h2 class="card-title" style="margin-bottom:0;">🏦 <?php echo $sidebar_lang['page_title']; ?></h2>
                <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                    <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                    <span style="color:#cbd5e1;">|</span>
                    <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                </div>
            </div>
            <p class="card-subtitle"><?php echo $is_mm ? 'ဘဏ်အကောင့်အချက်အလက်စစ်ဆေးရန်' : 'Review and verify bank account details before disbursement'; ?></p>

            <?php if ($pending && $pending->num_rows > 0): ?>
                <?php while ($row = $pending->fetch_assoc()): ?>
                    <div class="bank-card" style="flex-direction:column; align-items:stretch;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="bank-info">
                                <h4><?php echo htmlspecialchars($row['student_name']); ?> (<?php echo htmlspecialchars($row['roll_no']); ?>)</h4>
                                <p><strong>Scheme:</strong> <?php echo htmlspecialchars($row['scheme_name']); ?></p>
                                <p class="detail">
                                    <strong>Bank:</strong> <?php echo htmlspecialchars($row['bank_name'] ?? 'N/A'); ?> |
                                    <strong>Account:</strong> <?php echo htmlspecialchars($row['account_number'] ?? 'N/A'); ?> |
                                    <strong>Holder:</strong> <?php echo htmlspecialchars($row['account_holder'] ?? 'N/A'); ?>
                                </p>
                                <p class="detail"><strong>App No:</strong> <?php echo htmlspecialchars($row['application_no']); ?></p>
                            </div>
                            <div>
                                <?php if ($row['is_verified']): ?>
                                    <span class="badge badge-verified">✓ Bank Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Pending Verification</span>
                                <?php endif; ?>
                                <?php if ($row['receipt_file']): ?>
                                    <span class="badge badge-verified" style="margin-left:4px;">📄 Receipt Uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$row['is_verified'] || !$row['receipt_file']): ?>
                        <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                            <form method="POST" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                                <input type="hidden" name="action" value="verify_receipt">
                                <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                                <div style="flex:2; min-width:180px;">
                                    <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:4px;">Upload Receipt (JPG/PNG)</label>
                                    <input type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.gif,.webp" required style="font-size:12px; width:100%;">
                                </div>
                                <div style="flex:1; min-width:120px;">
                                    <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:4px;">Academic Year</label>
                                    <select name="academic_year" class="form-select" style="margin-bottom:0;">
                                        <option value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>"><?php echo date('Y') . '-' . (date('Y') + 1); ?></option>
                                        <option value="<?php echo (date('Y') - 1) . '-' . date('Y'); ?>"><?php echo (date('Y') - 1) . '-' . date('Y'); ?></option>
                                    </select>
                                </div>
                                <div style="flex:1; min-width:120px;">
                                    <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:4px;">Semester</label>
                                    <select name="semester" class="form-select" style="margin-bottom:0;">
                                        <option value="First Semester">First Semester</option>
                                        <option value="Second Semester">Second Semester</option>
                                    </select>
                                </div>
                                <div style="flex:1; display:flex; gap:8px; justify-content:flex-end; align-items:flex-end;">
                                    <button type="submit" class="btn-green-sm" style="white-space:nowrap;">✓ Verify & Disburse</button>
                                    <button type="submit" form="rejectForm" class="btn-red-sm" style="white-space:nowrap;">✕ Reject</button>
                                </div>
                            </form>
                            <form method="POST" id="rejectForm" onsubmit="return confirm('Reject this bank details?')">
                                <input type="hidden" name="action" value="reject_bank">
                                <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:30px; color:#94a3b8;">
                    <p style="font-size:40px; margin-bottom:10px;">🏦</p>
                    <p>No pending bank verifications.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal Workspace</strong></div>
        <div style="font-weight: 500;">စီမံခန့်ခွဲရေး ကွန်ပျူတာတက္ကသိုလ် (မိတ္ထီလာ)</div>
        <div class="bottom-links">
            <span>📞 +95 9 123 456 789</span>
            <a href="mailto:info@ucsmt.edu.mm">📧 info@ucsmt.edu.mm</a>
            <span style="margin-left:15px;">© 2026 Computer University</span>
        </div>
    </footer> -->
</div>

</body>
</html>
<?php $conn->close(); ?>
