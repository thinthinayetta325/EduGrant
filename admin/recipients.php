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
    'page_title' => 'ဆုရရှိသူများစာရင်း',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verification',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'logout' => 'Logout',
    'page_title' => 'Recipients',
];
// include "header.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $app_id = (int)$_POST['application_id'];
        $year = $conn->real_escape_string($_POST['start_year']);
        $conn->query("INSERT INTO scholarship_recipients (application_id, start_year) VALUES ($app_id, '$year')");
        $conn->query("UPDATE applications SET status='Approved' WHERE id=$app_id");
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM scholarship_recipients WHERE id=$id");
    }
    header("Location: recipients.php");
    exit();
}

$recipients = $conn->query("SELECT sr.*, s.name AS student_name, s.roll_no, sc.scheme_name, a.application_no
    FROM scholarship_recipients sr
    JOIN applications a ON sr.application_id = a.id
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    ORDER BY sr.id DESC");

$approved_apps = $conn->query("SELECT a.id, a.application_no, s.name AS student_name, sc.scheme_name
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    WHERE a.status='Approved' AND a.id NOT IN (SELECT application_id FROM scholarship_recipients)
    ORDER BY a.id DESC");
$current_page = 'recipients';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipients Matrix - Admin</title>
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
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-red-sm { background-color: #dc2626; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; }
        .form-input, .form-select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box; margin-bottom: 10px; }
        .field-lbl { display: block; font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 4px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .inline-form { display: inline; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 10px; padding: 25px; width: 500px; }
        .modal-box h3 { margin-bottom: 15px; font-size: 16px; }
        .close-btn { float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
    </style>
         <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div>
                        <h2 class="card-title">🏅 <?php echo $sidebar_lang['page_title']; ?></h2>
                        <p class="card-subtitle"><?php echo $is_mm ? 'ပညာသင်ဆုရရှိသူများစာရင်း' : 'Scholarship recipients list'; ?></p>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                        <span style="color:#cbd5e1;">|</span>
                        <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                    </div>
                </div>
                <button class="btn-green-sm" onclick="openModal('addModal')">+ Add Recipient</button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Scheme</th>
                        <th>App No</th>
                        <th>Academic Year</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recipients && $recipients->num_rows > 0): ?>
                        <?php while ($row = $recipients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['scheme_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['application_no']); ?></td>
                                <td><span class="badge badge-active"><?php echo $row['start_year']; ?></span></td>
                                <td>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('Remove this recipient?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-red-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No recipients found.</td></tr>
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

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
        <h3>➕ Add Scholarship Recipient</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <label class="field-lbl">Approved Application</label>
            <select name="application_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php if ($approved_apps): while ($a = $approved_apps->fetch_assoc()): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['application_no'] . ' - ' . $a['student_name'] . ' (' . $a['scheme_name'] . ')'); ?></option>
                <?php endwhile; endif; ?>
            </select>
            <label class="field-lbl">Academic Year</label>
            <select name="start_year" class="form-select">
                <option value="2026">2026</option>
                <option value="2027">2027</option>
            </select>
            <button type="submit" class="btn-green-sm" style="width:100%;">Create Recipient</button>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
