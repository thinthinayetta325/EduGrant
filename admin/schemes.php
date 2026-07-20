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
    'page_title' => 'ပညာသင်ဆုအစီအစဉ်များစီမံရန်',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => ' Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients ',
    'disbursements' => 'Disbursements',
    'reports' => ' Reports',
     'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => ' Schemes',
];
// include "header.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $conn->real_escape_string($_POST['scheme_name']);
        $amount = $conn->real_escape_string($_POST['amount']);
        $deadline = $conn->real_escape_string($_POST['deadline']);
        $status = $conn->real_escape_string($_POST['status']);
        $desc = $conn->real_escape_string($_POST['description']);
        $elig = $conn->real_escape_string($_POST['eligibility']);
        $image = '';
        if (isset($_FILES['scheme_image']) && $_FILES['scheme_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['scheme_image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('scheme_') . '.' . $ext;
            move_uploaded_file($_FILES['scheme_image']['tmp_name'], '../uploads/schemes/' . $filename);
            $image = $conn->real_escape_string($filename);
        }
        $conn->query("INSERT INTO schemes (scheme_name, amount, deadline, status, description, eligibility, image) VALUES ('$name', '$amount', '$deadline', '$status', '$desc', '$elig', '$image')");
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['scheme_name']);
        $amount = $conn->real_escape_string($_POST['amount']);
        $deadline = $conn->real_escape_string($_POST['deadline']);
        $status = $conn->real_escape_string($_POST['status']);
        $desc = $conn->real_escape_string($_POST['description']);
        $elig = $conn->real_escape_string($_POST['eligibility']);
        $image_sql = '';
        if (isset($_FILES['scheme_image']) && $_FILES['scheme_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['scheme_image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('scheme_') . '.' . $ext;
            move_uploaded_file($_FILES['scheme_image']['tmp_name'], '../uploads/schemes/' . $filename);
            $image = $conn->real_escape_string($filename);
            $image_sql = ", image='$image'";
        }
        $conn->query("UPDATE schemes SET scheme_name='$name', amount='$amount', deadline='$deadline', status='$status', description='$desc', eligibility='$elig'$image_sql WHERE id=$id");
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM schemes WHERE id=$id");
    }
    header("Location: schemes.php");
    exit();
}

$schemes = $conn->query("SELECT * FROM schemes ORDER BY id DESC");
$current_page = 'schemes';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schemes - Admin</title>
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
        .badge-closed { background-color: #fee2e2; color: #b91c1c; }
        .badge-draft { background-color: #fef3c7; color: #92400e; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .btn-red-sm { background-color: #dc2626; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box; margin-bottom: 10px; }
        .form-textarea { min-height: 60px; resize: vertical; }
        .field-lbl { display: block; font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 4px; }
        .action-link { color: #2563eb; text-decoration: none; font-weight: bold; font-size: 12px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .inline-form { display: inline; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 10px; padding: 25px; width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal-box h3 { margin-bottom: 15px; font-size: 16px; }
        .close-btn { float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }
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
        html.dark-mode .badge-active { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-closed { background: rgba(220,38,38,0.15); color: #f87171; }
        html.dark-mode .badge-draft { background: rgba(245,158,11,0.15); color: #fbbf24; }
        html.dark-mode .btn-green-sm { opacity: 0.9; }
        html.dark-mode .btn-blue-sm { opacity: 0.9; }
        html.dark-mode .btn-red-sm { opacity: 0.9; }
        html.dark-mode .action-link { color: #5eead4; }
        html.dark-mode .form-input, html.dark-mode .form-select, html.dark-mode .form-textarea {
            background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
        }
        html.dark-mode .field-lbl { color: #94a3b8; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
        html.dark-mode .modal-overlay { background: rgba(15,23,42,0.7); }
        html.dark-mode .modal-box { background: #1e293b; border-color: #334155; }
        html.dark-mode .modal-box h3 { color: #f1f5f9; }
        html.dark-mode .close-btn { color: #94a3b8; }
        html.dark-mode .language-switch { background: linear-gradient(135deg, #334155, #1e293b); border-color: #475569; }
        html.dark-mode .profile-link { background: #334155; border-color: #475569; }
        html.dark-mode .profile-dropdown-menu { background: #1e293b; border-color: #334155; }
        html.dark-mode .profile-dropdown-menu a:hover { background: #334155; }
        html.dark-mode .profile-dropdown-menu hr { border-top-color: #334155; }
        html.dark-mode .notif-btn { background: #334155; border-color: #475569; }
        html.dark-mode .btn-outline { border-color: #475569; color: #94a3b8; }
        html.dark-mode .btn-outline:hover { background: #334155; }
    </style>
    <?php include 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Schemes'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h2 class="card-title">📜 <?php echo $sidebar_lang['page_title']; ?></h2>
                </div>
                <!-- <p class="card-subtitle" style="margin:0;">စီမံကိန်းအစီအစဉ်များစီမံခန့်ခွဲရန်</p> -->
                <button class="btn-green-sm" onclick="openModal('addModal')">+ Add New Scheme</button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Image</th>
                        <th>Scheme Name</th>
                        <th>Amount (MMK)</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($schemes && $schemes->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $schemes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="../uploads/schemes/<?php echo htmlspecialchars($row['image']); ?>" style="width:50px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0;">
                                    <?php else: ?>
                                        <span style="color:#94a3b8;font-size:10px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['scheme_name']); ?></strong></td>
                                <td><?php echo number_format(floatval(str_replace(',', '', $row['amount']))); ?></td>
                                <td><?php echo $row['deadline'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php
                                    $cls = 'badge-draft';
                                    if ($row['status'] === 'Active') $cls = 'badge-active';
                                    elseif ($row['status'] === 'Closed') $cls = 'badge-closed';
                                    ?>
                                    <span class="badge <?php echo $cls; ?>"><?php echo $row['status']; ?></span>
                                </td>
                                <td>
                                    <button class="btn-blue-sm" style="padding:4px 10px; font-size:10px;" onclick="editScheme(<?php echo htmlspecialchars(json_encode($row)); ?>)">✏️ Edit</button>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('Delete this scheme?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-red-sm" style="padding:4px 10px; font-size:10px;">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No schemes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
        <h3>➕ Add New Scheme</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="grid-2">
                <div>
                    <label class="field-lbl">Scheme Name</label>
                    <input type="text" name="scheme_name" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Amount (MMK)</label>
                    <input type="number" name="amount" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Deadline</label>
                    <input type="date" name="deadline" class="form-input">
                </div>
                <div>
                    <label class="field-lbl">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                        <option value="Draft">Draft</option>
                    </select>
                </div>
            </div>
            <label class="field-lbl">Scheme Image</label>
            <input type="file" name="scheme_image" class="form-input" accept="image/*">
            <label class="field-lbl">Description</label>
            <textarea name="description" class="form-textarea"></textarea>
            <label class="field-lbl">Eligibility Criteria</label>
            <textarea name="eligibility" class="form-textarea"></textarea>
            <button type="submit" class="btn-green-sm" style="width:100%;">Create Scheme</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        <h3>✏️ Edit Scheme</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid-2">
                <div>
                    <label class="field-lbl">Scheme Name</label>
                    <input type="text" name="scheme_name" id="edit-name" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Amount (MMK)</label>
                    <input type="number" name="amount" id="edit-amount" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Deadline</label>
                    <input type="date" name="deadline" id="edit-deadline" class="form-input">
                </div>
                <div>
                    <label class="field-lbl">Status</label>
                    <select name="status" id="edit-status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>
            <label class="field-lbl">Scheme Image</label>
            <input type="file" name="scheme_image" class="form-input" accept="image/*">
            <div id="edit-image-preview" style="margin-bottom:10px;"></div>
            <label class="field-lbl">Description</label>
            <textarea name="description" id="edit-desc" class="form-textarea"></textarea>
            <label class="field-lbl">Eligibility Criteria</label>
            <textarea name="eligibility" id="edit-elig" class="form-textarea"></textarea>
            <button type="submit" class="btn-blue-sm" style="width:100%;">Update Scheme</button>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function editScheme(row) {
    document.getElementById('edit-id').value = row.id;
    document.getElementById('edit-name').value = row.scheme_name;
    document.getElementById('edit-amount').value = row.amount;
    document.getElementById('edit-deadline').value = row.deadline || '';
    document.getElementById('edit-status').value = row.status;
    document.getElementById('edit-desc').value = row.description || '';
    document.getElementById('edit-elig').value = row.eligibility || '';
    var preview = document.getElementById('edit-image-preview');
    if (row.image) {
        preview.innerHTML = '<img src="../uploads/schemes/' + row.image + '" style="max-width:120px;max-height:80px;border-radius:4px;border:1px solid #e2e8f0;">';
    } else {
        preview.innerHTML = '';
    }
    openModal('editModal');
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
