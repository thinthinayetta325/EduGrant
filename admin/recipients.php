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
    'page_title' => 'ဆုရရှိသူများစာရင်း',
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
    'page_title' => 'Recipients',
];
// include "header.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM scholarship_recipients WHERE id=$id");
    header("Location: recipients.php");
    exit();
}

$search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
$scheme_filter = isset($_GET['scheme']) ? (int)$_GET['scheme'] : 0;
$where = [];
if ($search !== '') {
    $where[] = "(s.name LIKE '%$search%' OR sc.scheme_name LIKE '%$search%' OR a.application_no LIKE '%$search%' OR s.roll_no LIKE '%$search%')";
}
if ($scheme_filter > 0) {
    $where[] = "sc.id = $scheme_filter";
}
$where_clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$all_schemes = $conn->query("SELECT id, scheme_name FROM schemes ORDER BY scheme_name");

$scheme_stats = $conn->query("SELECT sc.id, sc.scheme_name, sc.status,
    (SELECT COUNT(*) FROM applications a2 WHERE a2.scheme_id = sc.id) AS app_count,
    (SELECT COUNT(*) FROM scholarship_recipients sr2 JOIN applications a3 ON sr2.application_id = a3.id WHERE a3.scheme_id = sc.id) AS recipient_count
    FROM schemes sc ORDER BY sc.scheme_name");
$scheme_icons = ['🎓','🏆','📚','💡','🔬','🎨','⚖️','🌏','🧠','📐','💻','🩺'];

$recipients = $conn->query("SELECT sr.*, s.name AS student_name, s.roll_no, sc.scheme_name, a.application_no
    FROM scholarship_recipients sr
    JOIN applications a ON sr.application_id = a.id
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id$where_clause
    ORDER BY sr.id DESC");
$current_page = 'recipients';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
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
        .scheme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; margin-bottom: 18px; }
        .scheme-card {
            background: #fff; border-radius: 14px; padding: 16px;
            border: 1px solid #e2e8f0; position: relative; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .scheme-card:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(0,0,0,0.10), 0 4px 10px rgba(0,0,0,0.06); }
        .scheme-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #006D69, #10b981);
        }
        .scheme-card .scheme-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .scheme-card .scheme-icon {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
            background: linear-gradient(135deg, #006D69, #0d9488);
            box-shadow: 0 4px 10px rgba(0,109,105,0.25);
        }
        .scheme-card .scheme-name { font-size: 13px; font-weight: 700; line-height: 1.3; color: #0f172a; }
        .scheme-card .scheme-meta { font-size: 10px; color: #94a3b8; margin-top: 1px; }
        .scheme-card .stat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 7px 0; border-top: 1px solid #f1f5f9;
        }
        .scheme-card .stat-row:first-of-type { border-top: none; }
        .scheme-card .stat-label { font-size: 11px; color: #64748b; font-weight: 500; }
        .scheme-card .stat-value { font-size: 15px; font-weight: 800; color: #0f172a; }
        .scheme-card .bar-track { height: 5px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-top: 8px; }
        .scheme-card .bar-fill { height: 100%; border-radius: 10px; background: linear-gradient(90deg, #006D69, #10b981); transition: width .6s ease; }
        .scheme-card .bar-lbl { font-size: 9px; color: #94a3b8; margin-top: 5px; text-align: right; }
        .scheme-card:nth-child(3n+1)::before { background: linear-gradient(90deg, #006D69, #0d9488); }
        .scheme-card:nth-child(3n+1) .scheme-icon { background: linear-gradient(135deg, #006D69, #0d9488); box-shadow: 0 4px 10px rgba(0,109,105,0.25); }
        .scheme-card:nth-child(3n+2)::before { background: linear-gradient(90deg, #f59e0b, #f97316); }
        .scheme-card:nth-child(3n+2) .scheme-icon { background: linear-gradient(135deg, #f59e0b, #f97316); box-shadow: 0 4px 10px rgba(245,158,11,0.25); }
        .scheme-card:nth-child(3n+3)::before { background: linear-gradient(90deg, #8b5cf6, #6366f1); }
        .scheme-card:nth-child(3n+3) .scheme-icon { background: linear-gradient(135deg, #8b5cf6, #6366f1); box-shadow: 0 4px 10px rgba(139,92,246,0.25); }
        html.dark-mode .scheme-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .scheme-card .scheme-name { color: #f1f5f9; }
        html.dark-mode .scheme-card .stat-row { border-top-color: #334155; }
        html.dark-mode .scheme-card .stat-value { color: #f1f5f9; }
        html.dark-mode .scheme-card .bar-track { background: #334155; }
        html.dark-mode .scheme-card .scheme-meta { color: #64748b; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 10px; padding: 25px; width: 500px; }
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
        html.dark-mode .btn-green-sm { opacity: 0.9; }
        html.dark-mode .btn-red-sm { opacity: 0.9; }
        html.dark-mode .form-input, html.dark-mode .form-select {
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
         <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Recipients'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="scheme-grid">
            <?php if ($scheme_stats && $scheme_stats->num_rows > 0): ?>
                <?php $ci = 0; while ($sc = $scheme_stats->fetch_assoc()): ?>
                    <?php
                    $sicon = $scheme_icons[$ci % count($scheme_icons)];
                    $ci++;
                    $rec_pct = $sc['app_count'] > 0 ? round(($sc['recipient_count'] / $sc['app_count']) * 100) : 0;
                    ?>
                    <div class="scheme-card">
                        <div class="scheme-head">
                            <div class="scheme-icon"><?php echo $sicon; ?></div>
                            <div>
                                <div class="scheme-name"><?php echo htmlspecialchars($sc['scheme_name']); ?></div>
                                <div class="scheme-meta"><?php echo $sc['status'] === 'Active' ? '🟢 Active' : '⚪ ' . htmlspecialchars($sc['status']); ?></div>
                            </div>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">📁 Applications</span>
                            <span class="stat-value"><?php echo (int)$sc['app_count']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">🏅 Recipients</span>
                            <span class="stat-value"><?php echo (int)$sc['recipient_count']; ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:<?php echo $rec_pct; ?>%;"></div>
                        </div>
                        <div class="bar-lbl"><?php echo $rec_pct; ?>% awarded</div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div>
                        <h2 class="card-title">🏅 <?php echo $sidebar_lang['page_title']; ?></h2>
                    </div>
                </div>
                <form method="get" action="recipients.php" style="display:flex; gap:8px; align-items:center; margin:0;">
                    <input type="hidden" name="lang" value="<?php echo $lang_param; ?>">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-input" placeholder="🔍 Search name, scheme or ID..." style="width:200px; margin:0;" autocomplete="off">
                    <select name="scheme" class="form-input" style="width:180px; margin:0; cursor:pointer;">
                        <option value="">All Schemes</option>
                        <?php while ($sch = $all_schemes->fetch_assoc()): ?>
                            <option value="<?php echo $sch['id']; ?>" <?php echo $scheme_filter == $sch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sch['scheme_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-green-sm">Filter</button>
                    <?php if ($search !== '' || $scheme_filter > 0): ?>
                        <a href="recipients.php?lang=<?php echo $lang_param; ?>" class="action-link" style="font-size:11px; text-decoration:none; color:#006D69;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No.</th>
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
                        <?php $no = 1; while ($row = $recipients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
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

</body>
</html>
<?php $conn->close(); ?>
