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
    'page_title' => 'ငွေပေးချေမှုမှတ်တမ်း',
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
    'page_title' => 'Disbursements Log',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $recipient_id = (int)$_POST['recipient_id'];
        $bank_id = (int)$_POST['bank_id'];
        $amount = $conn->real_escape_string($_POST['amount']);
        $year = $conn->real_escape_string($_POST['academic_year']);
        $sem = $conn->real_escape_string($_POST['semester']);
        $date = $conn->real_escape_string($_POST['payment_date']);
$conn->query("INSERT INTO payment_records (recipient_id, bank_id, amount, academic_year, semester, payment_date) VALUES ($recipient_id, $bank_id, '$amount', '$year', '$sem', '$date')");    }
    header("Location: disbursements.php");
    exit();
}

$disbursements = $conn->query("SELECT pr.*, s.name AS student_name, s.roll_no, sc.scheme_name, bd.bank_name, bd.account_number
    FROM payment_records pr
    JOIN scholarship_recipients sr ON pr.recipient_id = sr.id
    JOIN applications a ON sr.application_id = a.id
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN bank_details bd ON pr.bank_id = bd.id
    ORDER BY pr.payment_date DESC");

$recipients_list = $conn->query("SELECT sr.id, s.name AS student_name, a.application_no
    FROM scholarship_recipients sr
    JOIN applications a ON sr.application_id = a.id
    JOIN student s ON a.student_id = s.id");
$banks = $conn->query("SELECT bd.id, bd.bank_name, bd.account_number, s.name AS student_name
    FROM bank_details bd JOIN student s ON bd.student_id = s.id");
$current_page = 'disbursements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <title>Disbursements Log - Admin</title>
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
        .badge-paid { background-color: #dcfce7; color: #15803d; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .form-input, .form-select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box; margin-bottom: 10px; }
        .field-lbl { display: block; font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 4px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 10px; padding: 25px; width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal-box h3 { margin-bottom: 15px; font-size: 16px; }
        .close-btn { float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .summary-strip { display: flex; gap: 15px; margin-bottom: 20px; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; flex: 1; text-align: center; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        .summary-box .num { font-size: 22px; font-weight: 800; color: #0f172a; }
        .summary-box .lbl { font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div>
                    <h2 class="card-title">💵 <?php echo $sidebar_lang['page_title']; ?></h2>
                    <p class="card-subtitle"><?php echo $is_mm ? 'ငွေထုတ်ပေးမှုမှတ်တမ်း' : 'Disbursements Log'; ?></p>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                    <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                    <span style="color:#cbd5e1;">|</span>
                    <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
                </div>
                <button class="btn-green-sm" onclick="openModal('addModal')">+ New Disbursement</button>
            </div>

            <?php
            $total_disbursed = 0;
            $count = 0;
            if ($disbursements) {
                $disbursements->data_seek(0);
                while ($d = $disbursements->fetch_assoc()) {
                    $total_disbursed += $d['amount'];
                    $count++;
                }
                $disbursements->data_seek(0);
            }
            ?>
            <div class="summary-strip">
                <div class="summary-box">
                    <div class="num"><?php echo $count; ?></div>
                    <div class="lbl">Total Transactions</div>
                </div>
                <div class="summary-box">
                    <div class="num"><?php echo number_format($total_disbursed); ?></div>
                    <div class="lbl">Total Disbursed (MMK)</div>
                </div>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Scheme</th>
                        <th>Bank</th>
                        <th>Amount</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($count > 0): ?>
                        <?php while ($row = $disbursements->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['scheme_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['bank_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($row['amount']); ?></td>
                                <td><?php echo $row['academic_year']; ?></td>
                                <td><?php echo $row['semester']; ?></td>
                                <td><?php echo $row['payment_date']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:20px; color:#94a3b8;">No disbursement records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
<!-- footer -->
    <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal Workspace</strong></div>
        <div style="font-weight: 500;">စီမံခန့်ခွဲရေး ကွန်ပျူတာတက္ကသိုလ် (မိတ္ထီလာ)</div>
        <div class="bottom-links">
            <span>📞 +95 9 123 456 789</span>
            <a href="mailto:info@ucsmt.edu.mm">📧 info@ucsmt.edu.mm</a>
            <span style="margin-left:15px;">© 2026 Computer University</span>
        </div>
    </footer>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
        <h3>➕ New Disbursement</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="grid-2">
                <div>
                    <label class="field-lbl">Recipient</label>
                    <select name="recipient_id" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php if ($recipients_list): while ($r = $recipients_list->fetch_assoc()): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['student_name'] . ' (' . $r['application_no'] . ')'); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div>
                    <label class="field-lbl">Bank Account</label>
                    <select name="bank_id" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php if ($banks): $banks->data_seek(0); while ($b = $banks->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bank_name'] . ' - ' . $b['account_number'] . ' (' . $b['student_name'] . ')'); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div>
                    <label class="field-lbl">Amount (MMK)</label>
                    <input type="number" name="amount" class="form-input" required>
                </div>
                <div>
                    <label class="field-lbl">Academic Year</label>
                    <select name="academic_year" class="form-select">
                        <option value="2026-2027">2026-2027</option>
                        <option value="2025-2026">2025-2026</option>
                    </select>
                </div>
                <div>
                    <label class="field-lbl">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                    </select>
                </div>
                <div>
                    <label class="field-lbl">Payment Date</label>
                    <input type="date" name="payment_date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <button type="submit" class="btn-blue-sm" style="width:100%;">Create Disbursement</button>
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
