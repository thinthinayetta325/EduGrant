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
    'page_title' => 'လျှောက်လွှာများကြည့်ရှုရန်',
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
    'page_title' => 'View Applications',
];

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['ids'])) {
    $action = $_POST['action'];
    $ids = array_map('intval', (array)$_POST['ids']);
    $admin_id = $_SESSION['admin_id'];

    foreach ($ids as $id) {
        if ($action === 'approve') {
            $conn->query("UPDATE applications SET status='Approved', approved_by=$admin_id, approved_at=NOW() WHERE id=$id AND status NOT IN ('Approved','Rejected')");

            // Insert into scholarship_recipients if not already there
            $app_data = $conn->query("SELECT student_id, scheme_id, application_no FROM applications WHERE id=$id")->fetch_assoc();
            if ($app_data) {
                $existing = $conn->query("SELECT id FROM scholarship_recipients WHERE application_id=$id")->num_rows;
                if ($existing === 0) {
                    $conn->query("INSERT INTO scholarship_recipients (application_id, start_year) VALUES ($id, YEAR(CURDATE()))");
                }

                // Create notification for the student
                $student_id = $app_data['student_id'];
                $title = "Application Approved";
                $message = "Your application #{$app_data['application_no']} has been approved. Congratulations!";
                $stmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, type) VALUES (?, ?, ?, 'approval')");
                $stmt->bind_param("iss", $student_id, $title, $message);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($action === 'reject') {
            $conn->query("UPDATE applications SET status='Rejected', approved_by=$admin_id, approved_at=NOW() WHERE id=$id AND status NOT IN ('Approved','Rejected')");

            $app_data = $conn->query("SELECT student_id, application_no FROM applications WHERE id=$id")->fetch_assoc();
            if ($app_data) {
                $student_id = $app_data['student_id'];
                $title = "Application Rejected";
                $message = "Your application #{$app_data['application_no']} has been rejected.";
                $stmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, type) VALUES (?, ?, ?, 'rejection')");
                $stmt->bind_param("iss", $student_id, $title, $message);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: applications.php?status=" . ($action === 'approve' ? 'Approved' : 'Rejected'));
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM payment_records WHERE recipient_id IN (SELECT id FROM scholarship_recipients WHERE application_id = $del_id)");
    $conn->query("DELETE FROM application_reviews WHERE application_id = $del_id");
    $conn->query("DELETE FROM scholarship_recipients WHERE application_id = $del_id");
    $conn->query("DELETE FROM receipts WHERE application_id = $del_id");
    $conn->query("DELETE FROM applications WHERE id = $del_id");
    header("Location: applications.php?lang=$lang_param");
    exit();
}

$pending_bank = $conn->query("SELECT COUNT(*) FROM applications a LEFT JOIN bank_details b ON a.student_id = b.student_id WHERE a.status='Approved' AND b.id IS NULL")->fetch_row()[0] ?? 0;
$current_page = 'applications';

$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "WHERE 1=1";
if ($status_filter) $where .= " AND a.status = '$status_filter'";
if ($search) $where .= " AND (s.name LIKE '%$search%' OR a.application_no LIKE '%$search%')";

$apps = $conn->query("SELECT a.*, s.name AS student_name, s.roll_no, sc.scheme_name, r.name AS reviewer_name, ar.recommendation, ar.remarks
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN application_reviews ar ON a.id = ar.application_id
    LEFT JOIN reviewers r ON ar.reviewer_id = r.id
    $where
    ORDER BY a.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
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
        .badge-submitted { background-color: #dbeafe; color: #1e40af; }
        .badge-review { background-color: #fef3c7; color: #92400e; }
        .badge-recommended { background-color: #dcfce7; color: #15803d; }
        .badge-approved { background-color: #10b981; color: #fff; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
        .form-input, .form-select { padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .action-link { color: #2563eb; text-decoration: none; font-weight: bold; font-size: 12px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
        .status-link { padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-decoration: none; color: #475569; background: #f1f5f9; }
        .status-link.active { background: #053b29; color: #fff; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        .badge-count { margin-left: auto; background: rgba(255,255,255,0.1); padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex;align-items:center;">
                <div>
                    <h2 class="card-title">📁 <?php echo $sidebar_lang['page_title']; ?></h2>
                    <p class="card-subtitle"><?php echo $is_mm ? 'လျှောက်လွှာအားလုံးကြည့်ရန်' : 'Browse all submitted applications'; ?></p>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                    <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                    <span style="color:#cbd5e1;">|</span>
                    <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                </div>
            </div>

            <div class="filter-bar">
                <a href="applications.php?lang=<?php echo $lang_param; ?>" class="status-link <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                <a href="applications.php?status=Submitted&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Submitted' ? 'active' : ''; ?>">Submitted</a>
                <a href="applications.php?status=Under Review&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Under Review' ? 'active' : ''; ?>">Under Review</a>
                <a href="applications.php?status=Recommended&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Recommended' ? 'active' : ''; ?>">Recommended</a>
                <a href="applications.php?status=Approved&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">Approved</a>
                <a href="applications.php?status=Rejected&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
                <div style="flex-grow:1;"></div>
                <form method="GET" style="display:flex; gap:8px;">
                    <input type="hidden" name="lang" value="<?php echo $lang_param; ?>">
                    <input type="text" name="search" class="form-input" placeholder="Search name or ID..." value="<?php echo htmlspecialchars($search); ?>" style="width:200px;">
                    <button type="submit" class="btn-blue-sm">🔍 Search</button>
                </form>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>App No</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Scheme</th>
                        <th>Status</th>
                        <th>Reviewer</th>
                        <th>Recommendation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($apps && $apps->num_rows > 0): ?>
                        <?php while ($row = $apps->fetch_assoc()): ?>
                            <?php
                            $stat = $row['status'];
                            $cls = 'badge-submitted';
                            if ($stat === 'Under Review') $cls = 'badge-review';
                            elseif ($stat === 'Recommended') $cls = 'badge-recommended';
                            elseif ($stat === 'Approved') $cls = 'badge-approved';
                            elseif ($stat === 'Rejected') $cls = 'badge-rejected';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['application_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['scheme_name']); ?></td>
                                <td><span class="badge <?php echo $cls; ?>"><?php echo $stat; ?></span></td>
                                <td><?php echo htmlspecialchars($row['reviewer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['recommendation'] ?? '-'); ?></td>
                                <td style="display:flex;gap:6px;align-items:center;">
                                    <a href="view_app.php?id=<?php echo $row['id']; ?>&amp;lang=<?php echo $lang_param; ?>" class="action-link">View</a>
                                    <a href="?delete=<?php echo $row['id']; ?>&amp;lang=<?php echo $lang_param; ?>" class="action-link" style="color:#dc2626;" onclick="return confirm('<?php echo $is_mm ? 'ဤလျှောက်လွှာကိုဖျက်ရန်သေချာပါသလား။' : 'Are you sure you want to delete this application?'; ?>')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:20px; color:#94a3b8;">No applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
