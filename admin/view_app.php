
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
    'my_profile' => 'ကိုယ်ရေးအချက်အလက်',
    'logout' => 'ထွက်မည်',
    'app_details' => 'လျှောက်လွှာအသေးစိတ်',
    'back' => 'နောက်သို့',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verification',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'my_profile' => 'My Profile',
    'logout' => 'Logout',
    'app_details' => 'Application Details',
    'back' => '← Back',
];
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$app = $conn->query("SELECT a.*, s.name AS student_name, s.roll_no, s.email AS student_email, s.phone, s.gender, s.address,
    sc.scheme_name, sc.amount, sc.description AS scheme_desc, sc.eligibility,
    r.name AS reviewer_name, r.department AS reviewer_dept,
    ar.recommendation, ar.remarks, ar.reviewed_at
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN application_reviews ar ON a.id = ar.application_id
    LEFT JOIN reviewers r ON ar.reviewer_id = r.id
    WHERE a.id = $app_id")->fetch_assoc();

if (!$app) {
    header("Location: applications.php");
    exit();
}

$bd = $conn->query("SELECT * FROM bank_details WHERE student_id = {$app['student_id']}")->fetch_assoc();
$sr = $conn->query("SELECT sr.* FROM scholarship_recipients sr JOIN applications a2 ON sr.application_id = a2.id WHERE a2.id = $app_id")->fetch_assoc();
$payments = $conn->query("SELECT pr.* FROM payment_records pr JOIN scholarship_recipients sr2 ON pr.recipient_id = sr2.id JOIN applications a3 ON sr2.application_id = a3.id WHERE a3.id = $app_id");
$current_page = 'applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application #<?php echo htmlspecialchars($app['application_no']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --sidebar-bg: #006D69; --sidebar-hover: #005a56;
            --accent: #FFD700; --accent-light: rgba(255,215,0,0.12);
            --card-bg: #fff; --body-bg: #f0f7f5;
            --border: #e0eae8; --text-primary: #0f172a;
            --text-secondary: #64748b; --text-muted: #94a3b8;
            --shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.06), 0 4px 10px rgba(0,0,0,0.04);
            --radius: 12px; --transition: 0.2s ease;
        }
        body { font-family: 'Inter', sans-serif; background: var(--body-bg); display: flex; height: 100vh; overflow: hidden; color: var(--text-primary); }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-brand { padding: 22px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .brand-icon { width: 38px; height: 38px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: #004D4A; }
        .brand-text h2 { font-size: 15px; font-weight: 700; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; }
        .admin-profile { padding: 18px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #004D4A; }
        .admin-meta h4 { font-size: 13px; font-weight: 600; }
        .admin-meta p { font-size: 10px; color: rgba(255,255,255,0.5); margin-top: 1px; }
        .sidebar-menu { list-style: none; padding: 12px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { padding: 16px 24px 6px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.8px; }
        .menu-item a { display: flex; align-items: center; gap: 12px; padding: 10px 24px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; font-weight: 500; transition: var(--transition); border-left: 3px solid transparent; margin: 2px 8px; border-radius: 8px; }
        .menu-item a:hover { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active a { background: var(--accent-light); color: #FFD700; border-left-color: #FFD700; }
        .menu-item .icon { font-size: 16px; width: 20px; text-align: center; }
        .menu-item.logout { margin-top: auto; }
        .menu-item.logout a { color: #fca5a5; }
        .menu-item.logout a:hover { background: rgba(252,165,165,0.1); }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
        .top-header { background: #fff; padding: 12px 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .top-header h1 { font-size: 18px; font-weight: 700; }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); }

        .dashboard-body { flex-grow: 1; padding: 24px 28px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }

        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 22px 24px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .card-header h3 { font-size: 15px; font-weight: 600; }
        .card-subtitle { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

        .info-row { display: flex; padding: 11px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 150px; font-weight: 600; color: var(--text-secondary); flex-shrink: 0; font-size: 12px; }
        .info-value { font-weight: 500; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-submitted { background: #dbeafe; color: #1e40af; }
        .badge-review { background: #fef3c7; color: #92400e; }
        .badge-recommended { background: #dcfce7; color: #15803d; }
        .badge-approved { background: #10b981; color: #fff; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-recommend { background: #dcfce7; color: #15803d; }
        .badge-not-recommend { background: #fee2e2; color: #b91c1c; }

        .btn-primary { background: #006D69; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: #005a56; }
        .btn-gold { background: #FFD700; color: #004D4A; border: none; padding: 9px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-gold:hover { background: #e6c200; }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border); padding: 9px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-outline:hover { background: var(--body-bg); }
        .btn-red { background: #ef4444; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition); text-decoration: none; }
        .btn-red:hover { background: #dc2626; }

        .admin-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .admin-table th { text-align: left; padding: 10px 8px; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border); }
        .admin-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; }
        .admin-table tr:hover td { background: #f8fafc; }

        .section-title { font-size: 13px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }

        .bottom-bar { background: #fff; border-top: 1px solid var(--border); padding: 12px 28px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-secondary); flex-shrink: 0; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="top-header">
        <div style="display:flex;align-items:center;">
            <div>
                <h1><?php echo $sidebar_lang['app_details']; ?></h1>
                <span class="sub"><?php echo htmlspecialchars($app['application_no']); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-left:12px;">
                <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
                <span style="color:#cbd5e1;">|</span>
                <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:12px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="applications.php?lang=<?php echo $lang_param; ?>" class="btn-outline"><?php echo $sidebar_lang['back']; ?></a>
            <?php if ($app['status'] !== 'Approved' && $app['status'] !== 'Rejected'): ?>
                <a href="dashboard.php?lang=<?php echo $lang_param; ?>#decision" class="btn-gold">Make Decision</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-body">

        <div class="card" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:50px;height:50px;background:var(--body-bg);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;">📄</div>
                <div>
                    <h2 style="font-size:18px;font-weight:700;"><?php echo htmlspecialchars($app['student_name']); ?></h2>
                    <p style="font-size:13px;color:var(--text-secondary);"><?php echo htmlspecialchars($app['application_no']); ?> · <?php echo htmlspecialchars($app['roll_no']); ?></p>
                </div>
            </div>
            <div style="text-align:right;">
                <?php
                $s = $app['status'];
                $c = 'badge-submitted';
                if ($s === 'Under Review') $c = 'badge-review';
                elseif ($s === 'Recommended') $c = 'badge-recommended';
                elseif ($s === 'Approved') $c = 'badge-approved';
                elseif ($s === 'Rejected') $c = 'badge-rejected';
                ?>
                <span class="badge <?php echo $c; ?>" style="font-size:13px;padding:5px 16px;"><?php echo $s; ?></span>
                <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">Applied: <?php echo $app['apply_date'] ? date('d M Y', strtotime($app['apply_date'])) : 'N/A'; ?></p>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3 class="section-title">Student Information</h3>
                <div class="info-row"><span class="info-label">Full Name</span><span class="info-value"><?php echo htmlspecialchars($app['student_name']); ?></span></div>
                <div class="info-row"><span class="info-label">Roll Number</span><span class="info-value"><?php echo htmlspecialchars($app['roll_no']); ?></span></div>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?php echo htmlspecialchars($app['student_email']); ?></span></div>
                <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?php echo htmlspecialchars($app['phone'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Gender</span><span class="info-value"><?php echo htmlspecialchars($app['gender'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Address</span><span class="info-value"><?php echo htmlspecialchars($app['address'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Family Income</span><span class="info-value"><?php echo $app['family_income'] ? number_format($app['family_income']) . ' MMK' : 'N/A'; ?></span></div>
            </div>

            <div class="card">
                <h3 class="section-title">Scheme Information</h3>
                <div class="info-row"><span class="info-label">Scheme Name</span><span class="info-value"><strong><?php echo htmlspecialchars($app['scheme_name']); ?></strong></span></div>
                <div class="info-row"><span class="info-label">Amount</span><span class="info-value" style="color:#006D69;font-weight:700;"><?php echo number_format($app['amount']); ?> MMK</span></div>
                <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="info-label">Description</span>
                    <span class="info-value" style="font-size:12px;color:var(--text-secondary);"><?php echo nl2br(htmlspecialchars($app['scheme_desc'] ?? 'N/A')); ?></span>
                </div>
                <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="info-label">Eligibility</span>
                    <span class="info-value" style="font-size:12px;color:var(--text-secondary);"><?php echo nl2br(htmlspecialchars($app['eligibility'] ?? 'N/A')); ?></span>
                </div>
            </div>
        </div>

        <?php if ($app['reviewer_name']): ?>
        <div class="grid-3">
            <div class="card">
                <h3 class="section-title">Review & Recommendation</h3>
                <div class="info-row"><span class="info-label">Reviewer</span><span class="info-value"><strong><?php echo htmlspecialchars($app['reviewer_name']); ?></strong> (<?php echo htmlspecialchars($app['reviewer_dept'] ?? 'General'); ?>)</span></div>
                <div class="info-row"><span class="info-label">Recommendation</span><span class="info-value">
                    <?php if ($app['recommendation']): ?>
                        <span class="badge <?php echo $app['recommendation'] === 'Recommended' ? 'badge-recommend' : 'badge-not-recommend'; ?>"><?php echo $app['recommendation']; ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">Pending</span>
                    <?php endif; ?>
                </span></div>
                <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="info-label">Remarks</span>
                    <span class="info-value" style="font-size:12px;color:var(--text-secondary);background:var(--body-bg);padding:10px;border-radius:8px;width:100%;"><?php echo nl2br(htmlspecialchars($app['remarks'] ?? 'No remarks provided.')); ?></span>
                </div>
                <?php if ($app['reviewed_at']): ?>
                    <div class="info-row"><span class="info-label">Reviewed At</span><span class="info-value"><?php echo date('d M Y, h:i A', strtotime($app['reviewed_at'])); ?></span></div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="section-title">Bank Details</h3>
                <?php if ($bd): ?>
                    <div class="info-row"><span class="info-label">Bank</span><span class="info-value"><?php echo htmlspecialchars($bd['bank_name'] ?? 'N/A'); ?></span></div>
                    <div class="info-row"><span class="info-label">Account No</span><span class="info-value"><?php echo htmlspecialchars($bd['account_number'] ?? 'N/A'); ?></span></div>
                    <div class="info-row"><span class="info-label">Account Holder</span><span class="info-value"><?php echo htmlspecialchars($bd['account_holder'] ?? 'N/A'); ?></span></div>
                <?php else: ?>
                    <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px;">No bank details on file.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($payments && $payments->num_rows > 0): ?>
        <div class="card">
            <h3 class="section-title">Payment / Disbursement History</h3>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Amount</th><th>Academic Year</th><th>Semester</th><th>Payment Date</th></tr></thead>
                <tbody>
                    <?php while ($p = $payments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><strong><?php echo number_format($p['amount']); ?> MMK</strong></td>
                        <td><?php echo $p['academic_year']; ?></td>
                        <td><?php echo $p['semester']; ?></td>
                        <td><?php echo $p['payment_date']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($app['status'] !== 'Approved' && $app['status'] !== 'Rejected'): ?>
        <div class="card" style="border:2px solid #FFD700;background:#fffbe6;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h3 style="font-size:15px;font-weight:700;color:#004D4A;">Administrative Decision</h3>
                    <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Approve or reject this application</p>
                </div>
                <form method="POST" action="applications.php" style="display:flex;gap:10px;">
                    <input type="hidden" name="ids[]" value="<?php echo $app_id; ?>">
                    <button type="submit" name="action" value="approve" class="btn-primary" onclick="return confirm('Approve this application?')">✓ Approve</button>
                    <button type="submit" name="action" value="reject" class="btn-red" onclick="return confirm('Reject this application?')">✕ Reject</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal</strong></div>
        <div>© <?php echo date('Y'); ?> Computer University (Meiktila)</div>
    </footer>
</div>

</body>
</html>
<?php $conn->close(); ?>
