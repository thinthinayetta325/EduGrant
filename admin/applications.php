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
    'bank_verify' => 'ဘဏ်စစ်ဆေးခြင်းများ',
    'recipients' => 'ဆုရရှိသူများ',
    'disbursements' => 'ငွေပေးချေမှုများ',
    'reports' => 'အစီရင်ခံစာများ',
    'messages' => 'စာတိုပေးစာများ',
    'logout' => 'ထွက်မည်',
    'page_title' => 'လျှောက်လွှာများကြည့်ရှုရန်',
] : [
    'dashboard' => 'Dashboard ',
    'schemes' => ' Schemes',
    'reviewers' => ' Reviewers',
    'applications' => ' Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients ',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => ' Applications',
];
// include "header.php";
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
            $reject_reason = trim($_POST['reject_reason'] ?? '');
            $conn->query("UPDATE applications SET status='Rejected', approved_by=$admin_id, approved_at=NOW() WHERE id=$id AND status NOT IN ('Approved','Rejected')");

            // Save rejection reason in application_reviews
            if ($reject_reason) {
                $stmt = $conn->prepare("INSERT INTO application_reviews (application_id, reviewer_id, recommendation, remarks, reviewed_at) VALUES (?, 0, 'Not Recommended', ?, NOW()) ON DUPLICATE KEY UPDATE remarks=?, reviewed_at=NOW()");
                $stmt->bind_param("iss", $id, $reject_reason, $reject_reason);
                $stmt->execute();
                $stmt->close();
            }

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

    // Redirect to user's application_details if requested
    if (isset($_POST['redirect_user']) && $action === 'reject' && !empty($ids)) {
        header("Location: ../user/application_details.php?id=" . $ids[0]);
        exit();
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

$apps = $conn->query("SELECT a.*, s.name AS student_name, s.roll_no, sc.scheme_name,
    GROUP_CONCAT(DISTINCT r.name SEPARATOR ', ') AS reviewer_name,
    GROUP_CONCAT(DISTINCT ar.recommendation SEPARATOR ', ') AS recommendation,
    GROUP_CONCAT(DISTINCT ar.remarks SEPARATOR ' | ') AS remarks
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN application_reviews ar ON a.id = ar.application_id
    LEFT JOIN reviewers r ON ar.reviewer_id = r.id
    $where
    GROUP BY a.id
    ORDER BY a.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
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
        .sidebar-footer {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: center;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 24px;
            background: rgba(252,165,165,0.1);
            border: 1px solid rgba(252,165,165,0.2);
            border-radius: 10px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }
        .logout-btn:hover {
            background: rgba(252,165,165,0.2);
            border-color: rgba(252,165,165,0.4);
            transform: translateY(-1px);
        }
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
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .btn-red-sm { background-color: #dc2626; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; }
        .inline-form { display: inline; }
        .action-link { color: #2563eb; text-decoration: none; font-weight: bold; font-size: 12px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
        .status-link { padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-decoration: none; color: #475569; background: #f1f5f9; }
        .status-link.active { background: #053b29; color: #fff; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }
        .badge-count { margin-left: auto; background: rgba(255,255,255,0.1); padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }

        /* Dark Mode */
        html.dark-mode body { background: #0f172a; color: #e2e8f0; }
        html.dark-mode .sidebar { background: #1e293b; }
        html.dark-mode .sidebar-brand { border-bottom-color: rgba(255,255,255,0.06); }
        html.dark-mode .menu-item a { color: rgba(255,255,255,0.55); }
        html.dark-mode .menu-item a:hover, html.dark-mode .menu-item.active a { background: #334155; color: #fff; }
        html.dark-mode .menu-item.active a { background: rgba(255,215,0,0.08); color: #FFD700; }
        html.dark-mode .sidebar-footer { border-top-color: rgba(255,255,255,0.08); }
        html.dark-mode .top-header { background: rgba(30,41,59,0.8); border-bottom-color: #334155; }
        html.dark-mode .top-header h1 { color: #f1f5f9; }
        html.dark-mode .admin-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .card-title { color: #f1f5f9; }
        html.dark-mode .card-subtitle { color: #94a3b8; }
        html.dark-mode table { color: #e2e8f0; }
        html.dark-mode thead { background: #1e293b; }
        html.dark-mode thead th { color: #94a3b8; border-bottom-color: #334155; }
        html.dark-mode tbody td { border-bottom-color: #334155; color: #e2e8f0; }
        html.dark-mode tbody tr:hover td { background: rgba(255,255,255,0.03); }
        html.dark-mode .status-link { color: #94a3b8; background: #1e293b; border-color: #334155; }
        html.dark-mode .status-link.active { background: #10b981; color: #fff; border-color: #10b981; }
        html.dark-mode .status-link:hover:not(.active) { background: #334155; }
        html.dark-mode .badge-submitted { background: rgba(37,99,235,0.15); color: #60a5fa; }
        html.dark-mode .badge-review { background: rgba(245,158,11,0.15); color: #fbbf24; }
        html.dark-mode .badge-recommended { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-approved { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-rejected { background: rgba(220,38,38,0.15); color: #f87171; }
        html.dark-mode .form-input, html.dark-mode .form-select {
            background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
        }
        html.dark-mode .btn-blue-sm { opacity: 0.9; }
        html.dark-mode .btn-red-sm { opacity: 0.9; }
        html.dark-mode .action-link { color: #5eead4; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
        html.dark-mode .language-switch { background: linear-gradient(135deg, #334155, #1e293b); border-color: #475569; }
        html.dark-mode .profile-link { background: #334155; border-color: #475569; }
        html.dark-mode .profile-dropdown-menu { background: #1e293b; border-color: #334155; }
        html.dark-mode .profile-dropdown-menu a:hover { background: #334155; }
        html.dark-mode .profile-dropdown-menu hr { border-top-color: #334155; }
        html.dark-mode .notif-btn { background: #334155; border-color: #475569; }
        html.dark-mode .btn-outline { border-color: #475569; color: #94a3b8; }
        html.dark-mode .btn-outline:hover { background: #334155; }
    </style>
     <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Applications'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex;align-items:center;">
                <div>
                    <h2 class="card-title">📁 <?php echo $sidebar_lang['page_title']; ?></h2>
                    <p class="card-subtitle"><?php echo $is_mm ? 'လျှောက်လွှာအားလုံးကြည့်ရန်' : 'Browse all submitted applications'; ?></p>
                </div>
                <!-- <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                    <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                    <span style="color:#cbd5e1;">|</span>
                    <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                </div> -->
            </div>

            <div class="filter-bar">
                <a href="applications.php?lang=<?php echo $lang_param; ?>" class="status-link <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                <a href="applications.php?status=Submitted&amp;lang=<?php echo $lang_param; ?>" class="status-link <?php echo $status_filter === 'Submitted' ? 'active' : ''; ?>">Submitted</a>
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
                                <td><?php
                                    $rec = $row['recommendation'] ?? '';
                                    if ($stat === 'Approved') echo '<span style="color:#15803d;font-weight:600;">👍 Recommended</span>';
                                    elseif ($stat === 'Rejected') echo '<span style="color:#b91c1c;font-weight:600;">👎 Not Recommended</span>';
                                    elseif ($rec === 'Recommended') echo '<span style="color:#15803d;font-weight:600;">👍 Recommended</span>';
                                    elseif ($rec === 'Not Recommended') echo '<span style="color:#b91c1c;font-weight:600;">👎 Not Recommended</span>';
                                    else echo '-';
                                ?></td>
                                <td style="display:flex;gap:6px;align-items:center;">
                                    <a href="view_app.php?id=<?php echo $row['id']; ?>&amp;lang=<?php echo $lang_param; ?>" class="btn-blue-sm" style="padding:4px 10px; font-size:10px; text-decoration:none;">🔍 View</a>
                                    <a href="?delete=<?php echo $row['id']; ?>&amp;lang=<?php echo $lang_param; ?>" class="btn-red-sm" style="padding:4px 10px; font-size:10px; text-decoration:none;" onclick="return confirm('<?php echo $is_mm ? 'ဤလျှောက်လွှာကိုဖျက်ရန်သေချာပါသလား။' : 'Are you sure you want to delete this application?'; ?>')">🗑️ Delete</a>
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

    
</div>

</body>
</html>
<?php $conn->close(); ?>
