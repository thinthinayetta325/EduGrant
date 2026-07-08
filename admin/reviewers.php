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
    'page_title' => 'စိစစ်ရေးမှူးများစီမံရန်',
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
    'page_title' => 'Manage Reviewers',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $conn->real_escape_string($_POST['name']);
        $dept = $conn->real_escape_string($_POST['department']);
        $email = $conn->real_escape_string($_POST['email']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO reviewers (name, department, email, password) VALUES ('$name', '$dept', '$email', '$pass')");
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM reviewers WHERE id=$id");
    } elseif ($_POST['action'] === 'assign_scheme') {
        $rid = (int)$_POST['reviewer_id'];
        $sid = (int)$_POST['scheme_id'];
        $conn->query("INSERT IGNORE INTO reviewer_scheme (reviewer_id, scheme_id) VALUES ($rid, $sid)");
    }
    header("Location: reviewers.php");
    exit();
}

$reviewers = $conn->query("SELECT r.*, GROUP_CONCAT(s.scheme_name SEPARATOR ', ') AS assigned_schemes FROM reviewers r LEFT JOIN reviewer_scheme rs ON r.id = rs.reviewer_id LEFT JOIN schemes s ON rs.scheme_id = s.id GROUP BY r.id ORDER BY r.id DESC");
$schemes = $conn->query("SELECT * FROM schemes WHERE status='Active'");
$current_page = 'reviewers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviewers - Admin</title>
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
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .btn-red-sm { background-color: #dc2626; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; }
        .form-input, .form-select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box; margin-bottom: 10px; }
        .field-lbl { display: block; font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 4px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .inline-form { display: inline; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 10px; padding: 25px; width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal-box h3 { margin-bottom: 15px; font-size: 16px; }
        .close-btn { float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div>
                    <h2 class="card-title">👥 <?php echo $sidebar_lang['page_title']; ?></h2>
                    <p class="card-subtitle"><?php echo $is_mm ? 'စိစစ်ရေးမှူးများစီမံခန့်ခွဲရန်' : 'Manage Reviewers'; ?></p>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                    <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                    <span style="color:#cbd5e1;">|</span>
                    <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                </div>
                <button class="btn-green-sm" onclick="openModal('addModal')">+ Add Reviewer</button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Assigned Schemes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reviewers && $reviewers->num_rows > 0): ?>
                        <?php while ($row = $reviewers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['assigned_schemes'] ?? 'None'); ?></td>
                                <td>
                                    <button class="btn-blue-sm" style="padding:4px 10px; font-size:10px;" onclick="openAssign(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')">📎 Assign</button>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('Delete this reviewer?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-red-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">No reviewers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal Workspace</strong></div>
        <div style="font-weight: 500;">စီမံခန့်ခွဲရေး ကွန်ပျူတာတက္ကသိုလ် (မိတ္ထီလာ)</div>
        <div class="bottom-links">
            <span>📞 +95 9 123 456 789</span>
            <a href="mailto:info@ucsmt.edu.mm">📧 info@ucsmtla.edu.mm</a>
            <span style="margin-left:15px;">© 2026 Computer University</span>
        </div>
    </footer>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
        <h3>➕ Add New Reviewer</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="grid-2">
                <div>
                    <label class="field-lbl">Full Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Department</label>
                    <input type="text" name="department" class="form-input">
                </div>
                <div>
                    <label class="field-lbl">Email</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
            </div>
            <button type="submit" class="btn-green-sm" style="width:100%;">Create Reviewer</button>
        </form>
    </div>
</div>

<div id="assignModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('assignModal')">&times;</button>
        <h3>📎 Assign Scheme to <span id="assign-name"></span></h3>
        <form method="POST">
            <input type="hidden" name="action" value="assign_scheme">
            <input type="hidden" name="reviewer_id" id="assign-id">
            <label class="field-lbl">Select Scheme</label>
            <select name="scheme_id" class="form-select" required>
                <option value="">-- Choose Scheme --</option>
                <?php if ($schemes): $schemes->data_seek(0); while ($s = $schemes->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['scheme_name']); ?></option>
                <?php endwhile; endif; ?>
            </select>
            <button type="submit" class="btn-blue-sm" style="width:100%;">Assign</button>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function openAssign(id, name) {
    document.getElementById('assign-id').value = id;
    document.getElementById('assign-name').textContent = name;
    openModal('assignModal');
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
