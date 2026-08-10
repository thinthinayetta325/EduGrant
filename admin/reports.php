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
    'page_title' => 'အစီရင်ခံစာများ',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => 'Reports',
];
// include "header.php";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
function is_valid_date($d) { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); }

$app_date_where = '';
$pay_date_where = '';
if (is_valid_date($date_from)) {
    $app_date_where .= " AND a.apply_date >= '$date_from'";
    $pay_date_where .= " AND pr.payment_date >= '$date_from'";
}
if (is_valid_date($date_to)) {
    $app_date_where .= " AND a.apply_date <= '$date_to 23:59:59'";
    $pay_date_where .= " AND pr.payment_date <= '$date_to 23:59:59'";
}

$total_schemes = $conn->query("SELECT COUNT(*) FROM schemes")->fetch_row()[0] ?? 0;
$total_apps = $conn->query("SELECT COUNT(*) FROM applications a WHERE 1=1 $app_date_where")->fetch_row()[0] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0] ?? 0;
$total_reviewers = $conn->query("SELECT COUNT(*) FROM reviewers")->fetch_row()[0] ?? 0;
$total_recipients = $conn->query("SELECT COUNT(*) FROM scholarship_recipients")->fetch_row()[0] ?? 0;
$total_disbursed = $conn->query("SELECT COALESCE(SUM(pr.amount),0) FROM payment_records pr WHERE 1=1 $pay_date_where")->fetch_row()[0] ?? 0;

$status_breakdown = $conn->query("SELECT status, COUNT(*) AS cnt FROM applications a WHERE 1=1 $app_date_where GROUP BY status ORDER BY FIELD(status, 'Submitted','Under Review','Recommended','Approved','Rejected')");

$bar_labels = [];
$bar_values = [];
$bar_colors = [];
$status_colors_map = ['Submitted' => '#2563eb', 'Under Review' => '#f59e0b', 'Recommended' => '#10b981', 'Approved' => '#065f46', 'Rejected' => '#ef4444'];
if ($status_breakdown) {
    while ($s = $status_breakdown->fetch_assoc()) {
        $bar_labels[] = $s['status'];
        $bar_values[] = (int)$s['cnt'];
        $bar_colors[] = $status_colors_map[$s['status']] ?? '#94a3b8';
    }
}

$scheme_apps = $conn->query("SELECT sc.scheme_name, COUNT(a.id) AS app_count, COALESCE(SUM(pr.amount),0) AS disbursed
    FROM schemes sc
    LEFT JOIN applications a ON sc.id = a.scheme_id
    LEFT JOIN scholarship_recipients sr ON a.id = sr.application_id
    LEFT JOIN payment_records pr ON sr.id = pr.recipient_id
    WHERE 1=1 $app_date_where
    GROUP BY sc.id");

$monthly = $conn->query("SELECT DATE_FORMAT(a.apply_date, '%Y-%m') AS month, COUNT(*) AS apps FROM applications a WHERE a.apply_date IS NOT NULL $app_date_where GROUP BY month ORDER BY month DESC LIMIT 12");

$disbursements = $conn->query("SELECT pr.*, s.name AS student_name, s.roll_no, sc.scheme_name, bd.bank_name, bd.account_number
    FROM payment_records pr
    JOIN scholarship_recipients sr ON pr.recipient_id = sr.id
    JOIN applications a ON sr.application_id = a.id
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN bank_details bd ON pr.bank_id = bd.id
    WHERE 1=1 $pay_date_where
    ORDER BY pr.payment_date DESC");
$current_page = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <title>Analytics Reports - Admin</title>
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
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; text-align: center; }
        .stat-box .num { font-size: 24px; font-weight: 800; color: #0f172a; }
        .stat-box .lbl { font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-top: 4px; }
        .stat-box.blue { border-left: 3px solid #2563eb; }
        .stat-box.green { border-left: 3px solid #10b981; }
        .stat-box.orange { border-left: 3px solid #f59e0b; }
        .stat-box.purple { border-left: 3px solid #8b5cf6; }
        .stat-box.teal { border-left: 3px solid #14b8a6; }
        .stat-box.red { border-left: 3px solid #ef4444; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .bar-wrapper { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .bar-label { width: 100px; font-size: 11px; font-weight: bold; color: #475569; text-align: right; }
        .bar-track { flex-grow: 1; height: 20px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 4px; display: flex; align-items: center; padding-left: 6px; font-size: 10px; color: #fff; font-weight: bold; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }
        .date-input { padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; background: #fff; color: #1e293b; }
        .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 15px 0; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .filter-label { font-size: 11px; font-weight: bold; color: #64748b; }
        .filter-range { font-size: 11px; font-weight: bold; color: #006D69; background: rgba(0,109,105,0.08); padding: 4px 10px; border-radius: 20px; }
        .preset-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #334155; text-decoration: none; padding: 7px 14px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: all 0.15s ease; }
        .preset-btn:hover { border-color: #006D69; color: #006D69; background: #f0fdfa; box-shadow: 0 2px 6px rgba(0,109,105,0.15); transform: translateY(-1px); }
        .preset-btn:active { transform: translateY(0); }
        html.dark-mode .preset-btn { background: #1e293b; border-color: #334155; color: #e2e8f0; }
        html.dark-mode .preset-btn:hover { border-color: #10b981; color: #4ade80; background: rgba(16,185,129,0.1); }
        html.dark-mode .date-input { background: #1e293b; border-color: #475569; color: #f1f5f9; }
        html.dark-mode .filter-bar { background: #1e293b; border-color: #334155; }
        html.dark-mode .filter-label { color: #94a3b8; }
        html.dark-mode .filter-range { background: rgba(16,185,129,0.15); color: #4ade80; }

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
        html.dark-mode .stat-box { background: #1e293b; border-color: #334155; }
        html.dark-mode .stat-box h4 { color: #f1f5f9; }
        html.dark-mode .stat-box .number { color: #f1f5f9; }
        html.dark-mode .stat-box .label { color: #94a3b8; }
        html.dark-mode .chart-track { background: #334155; }
        html.dark-mode .bar-label { color: #94a3b8; }
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
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Reports'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex;align-items:center;gap:8px;">
                <h2 class="card-title" style="margin:0;">📊 <?php echo $sidebar_lang['page_title']; ?></h2>
               
            </div>
            <!-- <p class="card-subtitle">အစီရင်ခံစာများနှင့်စာရင်းအင်းများ</p> -->

            <div class="filter-bar">
                <form method="get" action="reports.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="lang" value="<?php echo $lang_param; ?>">
                    <span class="filter-label">From</span>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="date-input" id="dateFrom">
                    <span class="filter-label">To</span>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="date-input" id="dateTo">
                    <button type="submit" class="btn-blue-sm" style="padding:8px 18px;">Filter</button>
                    <a href="?lang=<?php echo $lang_param; ?>" class="btn-green-sm">Reset</a>
                    <span style="color:#cbd5e1; font-size:11px;">|</span>
                    <a href="javascript:void(0)" class="preset-btn" onclick="setRange('today')">◉ Today</a>
                    <a href="javascript:void(0)" class="preset-btn" onclick="setRange('week')">▦ This Week</a>
                    <a href="javascript:void(0)" class="preset-btn" onclick="setRange('month')">▤ This Month</a>
                    <a href="javascript:void(0)" class="preset-btn" onclick="setRange('year')">◧ This Year</a>
                    <?php if (is_valid_date($date_from) || is_valid_date($date_to)): ?>
                        <span class="filter-range">
                            <?php
                            $disp_from = is_valid_date($date_from) ? date('d.m.Y', strtotime($date_from)) : '...';
                            $disp_to = is_valid_date($date_to) ? date('d.m.Y', strtotime($date_to)) : '...';
                            echo 'Showing: ' . $disp_from . ' – ' . $disp_to;
                            ?>
                        </span>
                    <?php endif; ?>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-box blue"><div class="num"><?php echo $total_schemes; ?></div><div class="lbl">Total Schemes</div></div>
                <div class="stat-box purple"><div class="num"><?php echo $total_apps; ?></div><div class="lbl">Total Applications</div></div>
                <div class="stat-box green"><div class="num"><?php echo $total_students; ?></div><div class="lbl">Registered Students</div></div>
                <div class="stat-box orange"><div class="num"><?php echo $total_reviewers; ?></div><div class="lbl">Reviewers</div></div>
                <div class="stat-box teal"><div class="num"><?php echo $total_recipients; ?></div><div class="lbl">Recipients</div></div>
                <div class="stat-box red"><div class="num"><?php echo number_format($total_disbursed); ?></div><div class="lbl">Total Disbursed (MMK)</div></div>
            </div>

            <div class="grid-2">
                <div>
                    <h3 style="font-size:14px; margin-bottom:10px; color:#0f172a;">📋 Applications by Status</h3>
                    <?php if (!empty($bar_labels)): ?>
                        <div style="height:260px; position:relative;">
                            <canvas id="statusBarChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">No data</div>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 style="font-size:14px; margin-bottom:10px; color:#0f172a;">📊 Scheme-wise Applications</h3>
                    <table class="admin-table">
                        <thead><tr><th>Scheme</th><th>Apps</th><th>Disbursed</th></tr></thead>
                        <tbody>
                            <?php if ($scheme_apps && $scheme_apps->num_rows > 0): ?>
                                <?php while ($s = $scheme_apps->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($s['scheme_name']); ?></strong></td>
                                        <td><?php echo $s['app_count']; ?></td>
                                        <td><?php echo number_format($s['disbursed']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8;">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <h3 style="font-size:14px; margin:15px 0 10px 0; color:#0f172a;">📅 Monthly Applications</h3>
                    <table class="admin-table">
                        <thead><tr><th>Month</th><th>Applications</th></tr></thead>
                        <tbody>
                            <?php if ($monthly && $monthly->num_rows > 0): ?>
                                <?php while ($m = $monthly->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $m['month']; ?></td>
                                        <td><?php echo $m['apps']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center; color:#94a3b8;">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="admin-card" id="disbursements-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div>
                    <h2 class="card-title">💵 Disbursements</h2>
                    <p class="card-subtitle">All payment records</p>
                </div>
                <button onclick="printDisbursements()" class="btn-blue-sm" style="display:flex;align-items:center;gap:6px;">
                    🖨️ Print
                </button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Scheme</th>
                        <th>Bank</th>
                        <th>Amount (MMK)</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($disbursements && $disbursements->num_rows > 0): ?>
                        <?php $no = 1; while ($d = $disbursements->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($d['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['roll_no'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($d['scheme_name']); ?></td>
                                <td><?php echo htmlspecialchars($d['bank_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format(floatval($d['amount'])); ?></td>
                                <td><?php echo htmlspecialchars($d['academic_year'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($d['semester'] ?? '-'); ?></td>
                                <td><?php echo $d['payment_date'] ?? '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:20px; color:#94a3b8;">No disbursement records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- <div class="admin-card" style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="reports.php?export=apps" class="btn-blue-sm">📄 Export Applications Report</a>
            <a href="reports.php?export=disbursement" class="btn-green-sm">💵 Export Disbursement Report</a>
            <a href="reports.php?export=recipients" class="btn-blue-sm">🏅 Export Recipient Ledger</a>
        </div> -->

    </div>

</div>

<script>
(function () {
    var ctx = document.getElementById('statusBarChart');
    if (!ctx || !window.Chart) return;
    var labels = <?php echo json_encode($bar_labels); ?>;
    var values = <?php echo json_encode($bar_values); ?>;
    var colors = <?php echo json_encode($bar_colors); ?>;
    if (!values.length) return;
    var dark = document.documentElement.classList.contains('dark-mode');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Applications',
                data: values,
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: dark ? '#1e293b' : '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (c) { return ' Applications: ' + c.parsed.y; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: dark ? '#94a3b8' : '#64748b', font: { size: 11 } },
                    grid: { color: dark ? '#334155' : '#e2e8f0' }
                },
                x: {
                    ticks: { color: dark ? '#e2e8f0' : '#334155', font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
})();

function setRange(type) {
    var from = document.getElementById('dateFrom');
    var to = document.getElementById('dateTo');
    var now = new Date();
    function fmt(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }
    if (type === 'today') {
        from.value = fmt(now);
        to.value = fmt(now);
    } else if (type === 'week') {
        var day = now.getDay() || 7;
        var start = new Date(now);
        start.setDate(now.getDate() - day + 1);
        from.value = fmt(start);
        to.value = fmt(now);
    } else if (type === 'month') {
        from.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
        to.value = fmt(now);
    } else if (type === 'year') {
        from.value = now.getFullYear() + '-01-01';
        to.value = fmt(now);
    }
    from.form.submit();
}

document.getElementById('liveSearch').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var rows = document.querySelectorAll('#disbursements-section .admin-table tbody tr');
    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});

function printDisbursements() {
    var content = document.getElementById('disbursements-section').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Disbursements Report</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: sans-serif; padding: 20px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 12px; }');
    printWindow.document.write('th, td { padding: 8px; border: 1px solid #e2e8f0; text-align: left; }');
    printWindow.document.write('th { background: #f8fafc; font-weight: bold; }');
    printWindow.document.write('h2 { margin-bottom: 10px; }');
    printWindow.document.write('p { color: #64748b; margin-bottom: 15px; }');
    printWindow.document.write('button { display: none; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>

</body>
</html>
<?php $conn->close(); ?>
