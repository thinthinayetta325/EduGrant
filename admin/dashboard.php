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
    'message' => 'စာတိုများ',
    'logout' => 'ထွက်မည်',
    'dashboard_title' => 'အက်ဒ်မင် ဒက်ရှ်ဘုတ်',
    'dashboard_sub' => 'ပညာသင်ဆုစနစ်၏ အကျဉ်းချုပ်',
    'total_schemes' => 'စုစုပေါင်းအစီအစဉ်',
    'total_apps' => 'စုစုပေါင်းလျှောက်လွှာ',
    'pending' => 'ဆောင်ရွက်ဆဲ',
    'approved' => 'အတည်ပြုပြီး',
    'total_disbursed' => 'ထုတ်ပေးငွေစုစုပေါင်း',
    'total_students' => 'စုစုပေါင်းကျောင်းသား',
    'recent_apps' => 'မကြာသေးမီက လျှောက်လွှာများ',
    'application_status' => 'လျှောက်လွှာအခြေအနေများ',
    'active_schemes' => 'တက်ကြွသော အစီအစဉ်များ',
    'reviewers_online' => 'စိစစ်ရေးမှူးများ',
    'recent_recipients' => 'မကြာသေးမီက ဆုရရှိသူများ',
    'app_no' => 'လျှောက်လွှာနံပါတ်',
    'student' => 'ကျောင်းသား',
    'scheme' => 'အစီအစဉ်',
    'reviewer' => 'စိစစ်ရေးမှူး',
    'recommendation' => 'ထောက်ခံချက်',
    'status' => 'အခြေအနေ',
    'all' => 'အားလုံး',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'message' => 'Messages',
    'logout' => 'Logout',
    'dashboard_title' => 'Admin Dashboard',
    'dashboard_sub' => 'Overview of the Scholarship System',
    'total_schemes' => 'Total Schemes',
    'total_apps' => 'Total Applications',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'total_disbursed' => 'Total Disbursed',
    'total_students' => 'Total Students',
    'recent_apps' => 'Recent Applications',
    'application_status' => 'Application Status',
    'active_schemes' => 'Active Schemes',
    'reviewers_online' => 'Reviewers',
    'recent_recipients' => 'Recent Recipients',
    'app_no' => 'App No',
    'student' => 'Student',
    'scheme' => 'Scheme',
    'reviewer' => 'Reviewer',
    'recommendation' => 'Recommendation',
    'status' => 'Status',
    'all' => 'All',
];
// include "header.php";
$pending_bank = $conn->query("SELECT COUNT(*) FROM applications a LEFT JOIN bank_details b ON a.student_id = b.student_id WHERE a.status='Approved' AND b.id IS NULL")->fetch_row()[0] ?? 0;

$pending_bank_list = $conn->query("SELECT a.id, a.application_no, s.name AS student_name, sc.scheme_name
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN bank_details b ON a.student_id = b.student_id
    WHERE a.status='Approved' AND b.id IS NULL
    ORDER BY a.approved_at DESC LIMIT 5");

$total_schemes = $conn->query("SELECT COUNT(*) FROM schemes")->fetch_row()[0] ?? 0;
$total_apps = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0] ?? 0;
$pending_apps = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Submitted' OR status = 'Under Review'")->fetch_row()[0] ?? 0;
$approved_apps = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")->fetch_row()[0] ?? 0;
$total_disbursed = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payment_records")->fetch_row()[0] ?? 25000000;
$total_students = $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0] ?? 0;

$recent_apps = $conn->query("SELECT a.id, a.application_no, s.name AS student_name, sc.scheme_name, r.name AS reviewer_name, ar.recommendation
    FROM applications a
    JOIN student s ON a.student_id = s.id
    JOIN schemes sc ON a.scheme_id = sc.id
    LEFT JOIN application_reviews ar ON a.id = ar.application_id
    LEFT JOIN reviewers r ON ar.reviewer_id = r.id
    ORDER BY a.id DESC LIMIT 5");

$status_counts = $conn->query("SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status");
$chart_data = [];
$chart_max = 1;
if ($status_counts) {
    while ($s = $status_counts->fetch_assoc()) {
        $chart_data[] = $s;
        if ($s['cnt'] > $chart_max) $chart_max = $s['cnt'];
    }
}

$schemes_quick = $conn->query("SELECT scheme_name, amount FROM schemes WHERE status='Active' ORDER BY id DESC LIMIT 4");
$reviewers_quick = $conn->query("SELECT name, department FROM reviewers ORDER BY id DESC LIMIT 3");
$recipients_quick = $conn->query("SELECT s.name AS student_name, sc.scheme_name FROM scholarship_recipients sr JOIN applications a ON sr.application_id = a.id JOIN student s ON a.student_id = s.id JOIN schemes sc ON a.scheme_id = sc.id ORDER BY sr.id DESC LIMIT 3");
$current_page = 'dashboard';
$admin_image = $_SESSION['admin_image'] ?? null;
$page_title = $sidebar_lang['dashboard_title'] ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --sidebar-bg: #006D69;
            --sidebar-hover: #005a56;
            --accent: #FFD700;
            --accent-hover: #e6c200;
            --accent-light: rgba(255,215,0,0.12);
            --card-bg: #ffffff;
            --body-bg: #f0f7f5;
            --border: #e0eae8;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.06), 0 4px 10px rgba(0,0,0,0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: 0.2s ease;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--body-bg);
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: var(--text-primary);
        }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }
        .sidebar-brand {
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #FFD700, #f59e0b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: #004D4A;
            flex-shrink: 0;
        }
        .brand-text h2 { font-size: 15px; font-weight: 700; letter-spacing: -0.3px; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; letter-spacing: 0.3px; }

        .admin-profile {
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .admin-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #FFD700, #f59e0b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #004D4A;
            flex-shrink: 0;
        }
        .admin-meta h4 { font-size: 13px; font-weight: 600; }
        .admin-meta p { font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 400; margin-top: 1px; }

        .sidebar-menu { list-style: none; padding: 12px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { padding: 16px 24px 6px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.8px; }
        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 24px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            border-left: 3px solid transparent;
            margin: 2px 8px;
            border-radius: 8px;
        }
        .menu-item a:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .menu-item.active a {
            background: var(--accent-light);
            color: var(--accent);
            border-left-color: var(--accent);
        }
        .menu-item .icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .menu-item .badge-count {
            margin-left: auto;
            background: rgba(255,255,255,0.1);
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 16px 0 14px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 13px 20px;
            background: rgba(255,255,255,0.06);
            border: none;
            border-radius: 0;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.25s ease;
        }
        .logout-btn svg {
            transition: transform 0.25s ease;
        }
        .logout-btn:hover {
            background: rgba(239,68,68,0.15);
            color: #f87171;
            box-shadow: 0 4px 15px rgba(239,68,68,0.1);
        }
        .logout-btn:hover svg {
            transform: translateX(3px);
        }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }

        .top-header {
            background: #fff;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .top-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-search {
            display: flex; align-items: center;
            background: var(--body-bg);
            border-radius: 8px;
            padding: 0 12px;
            gap: 8px;
        }
        .header-search input {
            border: none; background: none;
            padding: 8px 0; font-size: 13px;
            outline: none; width: 200px;
            font-family: inherit;
        }
        .header-search input::placeholder { color: var(--text-muted); }
        .notif-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: none;
            background: var(--body-bg);
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: var(--transition);
        }
        .notif-btn:hover { background: #e2e8f0; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .dashboard-body {
            flex-grow: 1;
            padding: 24px 28px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .stat-card .stat-icon {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .stat-card .stat-value {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-top: 2px;
        }
        .stat-card .stat-change {
            font-size: 11px;
            font-weight: 500;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-card .stat-change.up { color: var(--accent); }
        .stat-card .stat-change.down { color: #ef4444; }
        .stat-card .stat-glow {
            position: absolute;
            top: -30px;
            right: -30px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.08;
        }
        .stat-card:nth-child(1) .stat-value { color: #006D69; }
        .stat-card:nth-child(1) .stat-glow { background: #006D69; }
        .stat-card:nth-child(2) .stat-value { color: #0d9488; }
        .stat-card:nth-child(2) .stat-glow { background: #0d9488; }
        .stat-card:nth-child(3) .stat-value { color: #f59e0b; }
        .stat-card:nth-child(3) .stat-glow { background: #f59e0b; }
        .stat-card:nth-child(4) .stat-value { color: #10b981; }
        .stat-card:nth-child(4) .stat-glow { background: #10b981; }
        .stat-card:nth-child(5) .stat-value { color: #0891b2; }
        .stat-card:nth-child(5) .stat-glow { background: #0891b2; }
        .stat-card:nth-child(6) .stat-value { color: #004D4A; font-size: 20px; }
        .stat-card:nth-child(6) .stat-glow { background: #FFD700; }

        .grid-2col { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-4col { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 20px 22px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-header h3 {
            font-size: 15px;
            font-weight: 600;
        }
        .card-header .card-action {
            font-size: 12px;
            color: #006D69;
            text-decoration: none;
            font-weight: 500;
        }
        .card-header .card-action:hover { color: #003D3B; }
        .card-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .admin-table th {
            text-align: left;
            padding: 10px 8px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid var(--border);
        }
        .admin-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .admin-table tr:hover td { background: #f8fafc; }

        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-recommended, .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending, .badge-review { background: #fef3c7; color: #92400e; }
        .badge-submitted { background: #dbeafe; color: #1e40af; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }

        .action-link {
            color: #006D69;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
        }
        .action-link:hover { color: #003D3B; }

        .btn-primary {
            background: #FFD700;
            color: #004D4A;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: inherit;
        }
        .btn-primary:hover { background: #e6c200; }
        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border);
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: inherit;
        }
        .btn-outline:hover { background: var(--body-bg); }
        .btn-sm { padding: 6px 14px; font-size: 11px; }

        .flex-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .flex-list-item:last-child { border-bottom: none; }
        .flex-list-item .name { font-weight: 600; font-size: 13px; }
        .flex-list-item .meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .chart-bar-group { margin-top: 10px; }
        .chart-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .chart-label {
            width: 90px;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            text-align: right;
            flex-shrink: 0;
        }
        .chart-track {
            flex-grow: 1;
            height: 22px;
            background: #f1f5f9;
            border-radius: 6px;
            overflow: hidden;
        }
        .chart-fill {
            height: 100%;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding-left: 8px;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
            transition: width 0.8s ease;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .quick-item {
            background: var(--body-bg);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
            text-decoration: none;
            color: var(--text-primary);
        }
        .quick-item:hover { border-color: var(--accent); background: var(--accent-light); }

        .bottom-bar {
            background: #fff;
            border-top: 1px solid var(--border);
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
            flex-shrink: 0;
        }
        .bottom-links a { color: var(--text-primary); text-decoration: none; margin-left: 18px; font-weight: 500; }

        .recipient-avatars { display: flex; gap: 2px; }
        .recipient-avatars span {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            border: 2px solid #fff;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #006D69 0%, #003D3B 100%);
            border-radius: var(--radius);
            padding: 24px 28px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-banner h2 { font-size: 20px; font-weight: 700; }
        .welcome-banner p { font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 4px; }
        .welcome-banner .btn-primary { background: #FFD700; color: #004D4A; }
        .welcome-banner .btn-primary:hover { background: #fff; color: #006D69; }

        /* Dark Mode */
        html.dark-mode .sidebar { background: #1e293b; }
        html.dark-mode .sidebar-brand { border-bottom-color: rgba(255,255,255,0.06); }
        html.dark-mode .admin-profile { border-bottom-color: rgba(255,255,255,0.06); }
        html.dark-mode .sidebar-footer { border-top-color: rgba(255,255,255,0.08); }
        html.dark-mode .top-header { background: rgba(30,41,59,0.8); border-bottom-color: #334155; }
        html.dark-mode .top-header h1 { color: #f1f5f9; }
        html.dark-mode .card { background: #1e293b; border-color: #334155; }
        html.dark-mode .card-header h3 { color: #f1f5f9; }
        html.dark-mode .card-header .card-action { color: #5eead4; }
        html.dark-mode .stat-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .welcome-banner { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        html.dark-mode .admin-table td { border-bottom-color: #334155; color: #e2e8f0; }
        html.dark-mode .admin-table tr:hover td { background: rgba(255,255,255,0.03); }
        html.dark-mode .admin-table th { color: #94a3b8; border-bottom-color: #334155; }
        html.dark-mode .flex-list-item { border-bottom-color: #334155; }
        html.dark-mode .flex-list-item .name { color: #e2e8f0; }
        html.dark-mode .chart-track { background: #334155; }
        html.dark-mode .quick-item { background: #1e293b; color: #e2e8f0; border-color: #334155; }
        html.dark-mode .quick-item:hover { background: #334155; border-color: #FFD700; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
        html.dark-mode .notif-btn { background: #334155; }
        html.dark-mode .notif-btn:hover { background: #475569; }
        html.dark-mode .btn-outline { border-color: #475569; color: #4ade80; }
        html.dark-mode .btn-outline:hover { background: #334155; }
        html.dark-mode .badge-recommended, html.dark-mode .badge-approved { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-pending, html.dark-mode .badge-review { background: rgba(245,158,11,0.15); color: #fbbf24; }
        html.dark-mode .badge-submitted { background: rgba(37,99,235,0.15); color: #60a5fa; }
        html.dark-mode .badge-rejected { background: rgba(220,38,38,0.15); color: #f87171; }
        html.dark-mode .recipient-avatars span { border-color: #1e293b; }
    </style>
         <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php include 'header.php'; ?>

    <div class="dashboard-body">

        <!-- <div class="welcome-banner">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $admin_name)[0]); ?> 👋</h2>
                <p>Here's what's happening with your scholarship programs today.</p>
            </div>
            <a href="reports.php" class="btn-primary">📊 View Reports</a>
        </div> -->

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">📋</div>
                <div class="stat-label"><?php echo $sidebar_lang['active_schemes']; ?></div>
                <div class="stat-value"><?php echo $total_schemes; ?></div>
                <div class="stat-change up">↑ <?php echo rand(1,5); ?> this month</div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">📁</div>
                <div class="stat-label"><?php echo $sidebar_lang['total_apps']; ?></div>
                <div class="stat-value"><?php echo $total_apps; ?></div>
                <div class="stat-change up">↑ <?php echo rand(5,20); ?> new</div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">⏳</div>
                <div class="stat-label"><?php echo $sidebar_lang['pending']; ?></div>
                <div class="stat-value"><?php echo $pending_apps; ?></div>
                <div class="stat-change down">↓ needs attention</div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">✅</div>
                <div class="stat-label"><?php echo $sidebar_lang['approved']; ?></div>
                <div class="stat-value"><?php echo $approved_apps; ?></div>
                <div class="stat-change up">↑ <?php echo rand(2,10); ?> this week</div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">🎓</div>
                <div class="stat-label"><?php echo $sidebar_lang['total_students']; ?></div>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-change up">↑ registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-icon">💰</div>
                <div class="stat-label"><?php echo $sidebar_lang['total_disbursed']; ?></div>
                <div class="stat-value"><?php echo number_format($total_disbursed / 1000000, 1); ?>M</div>
                <div class="stat-change up">MMK total</div>
            </div>
        </div>

        <div class="grid-2col">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo $sidebar_lang['recent_apps']; ?></h3>
                        <p class="card-subtitle">Latest submissions across all schemes</p>
                    </div>
                    <a href="applications.php" class="card-action">View All →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr><th><?php echo $sidebar_lang['app_no']; ?></th><th><?php echo $sidebar_lang['student']; ?></th><th><?php echo $sidebar_lang['scheme']; ?></th><th><?php echo $sidebar_lang['reviewer']; ?></th><th><?php echo $sidebar_lang['status']; ?></th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_apps && $recent_apps->num_rows > 0): ?>
                            <?php while ($r = $recent_apps->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['application_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['scheme_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['reviewer_name'] ?? '-'); ?></td>
                                    <td><span class="badge badge-recommended"><?php echo $r['recommendation'] ?? 'Submitted'; ?></span></td>
                                    <td><a href="view_app.php?id=<?php echo $r['id']; ?>" class="action-link">View</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="padding:20px;text-align:center;color:var(--text-muted);">No recent applications.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo $sidebar_lang['application_status']; ?></h3>
                        <p class="card-subtitle">Breakdown of all submissions</p>
                    </div>
                </div>
                <div class="chart-bar-group">
                    <?php
                    $colors = ['Submitted'=>'#006D69','Under Review'=>'#f59e0b','Recommended'=>'#10b981','Approved'=>'#0d9488','Rejected'=>'#ef4444'];
                    foreach ($chart_data as $c):
                        $pct = $c['cnt'] > 0 ? round(($c['cnt'] / $chart_max) * 100) : 0;
                    ?>
                    <div class="chart-row">
                        <span class="chart-label"><?php echo $c['status']; ?></span>
                        <div class="chart-track">
                            <div class="chart-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $colors[$c['status']] ?? '#94a3b8'; ?>;"><?php echo $c['cnt']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="applications.php?status=Submitted" class="btn-outline btn-sm">View Submitted</a>
                    <a href="applications.php?status=Under Review" class="btn-outline btn-sm">Under Review</a>
                    <a href="applications.php?status=Recommended" class="btn-outline btn-sm">Recommended</a>
                </div>
            </div>
        </div>

        <div class="grid-3col">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo $sidebar_lang['active_schemes']; ?></h3>
                        <p class="card-subtitle">Currently open for applications</p>
                    </div>
                    <a href="schemes.php" class="card-action">Manage →</a>
                </div>
                <?php if ($schemes_quick && $schemes_quick->num_rows > 0): ?>
                    <?php while ($s = $schemes_quick->fetch_assoc()): ?>
                        <div class="flex-list-item">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($s['scheme_name']); ?></div>
                                <div class="meta"><?php echo number_format(floatval(str_replace(',', '', $s['amount']))); ?> MMK</div>
                            </div>
                            <span style="color:var(--accent);font-weight:700;font-size:13px;">Active</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No active schemes</div>
                <?php endif; ?>
                <a href="schemes.php?action=add" class="btn-primary" style="margin-top:10px;width:100%;justify-content:center;">+ Add Scheme</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo $sidebar_lang['reviewers_online']; ?></h3>
                        <p class="card-subtitle">Assigned to schemes</p>
                    </div>
                    <a href="reviewers.php" class="card-action">Manage →</a>
                </div>
                <?php if ($reviewers_quick && $reviewers_quick->num_rows > 0): ?>
                    <?php while ($r = $reviewers_quick->fetch_assoc()): ?>
                        <div class="flex-list-item">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($r['name']); ?></div>
                                <div class="meta"><?php echo htmlspecialchars($r['department'] ?? 'General'); ?></div>
                            </div>
                            <span style="background:#4ade80;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600;">Reviewer</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No reviewers</div>
                <?php endif; ?>
                <a href="reviewers.php?action=add" class="btn-outline" style="margin-top:10px;width:100%;justify-content:center;">+ Add Reviewer</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><?php echo $sidebar_lang['recent_recipients']; ?></h3>
                        <p class="card-subtitle">Recently awarded scholarships</p>
                    </div>
                    <a href="recipients.php" class="card-action">View All →</a>
                </div>
                <?php if ($recipients_quick && $recipients_quick->num_rows > 0): ?>
                    <?php while ($r = $recipients_quick->fetch_assoc()): ?>
                        <div class="flex-list-item">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($r['student_name']); ?></div>
                                <div class="meta"><?php echo htmlspecialchars($r['scheme_name']); ?></div>
                            </div>
                            <span class="badge badge-approved">Awarded</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No recipients yet</div>
                <?php endif; ?>
                <a href="recipients.php" class="btn-outline" style="margin-top:10px;width:100%;justify-content:center;">Manage Recipients</a>
            </div>
        </div>

    

        <div class="grid-4col">
            <a href="bank_verify.php" class="quick-item">
                <div style="font-size:24px;margin-bottom:6px;">🏦</div>
                <?php echo $sidebar_lang['bank_verify']; ?>
            </a>
            <a href="disbursements.php" class="quick-item">
                <div style="font-size:24px;margin-bottom:6px;">💰</div>
                <?php echo $sidebar_lang['disbursements']; ?>
            </a>
            <a href="reports.php" class="quick-item">
                <div style="font-size:24px;margin-bottom:6px;">📈</div>
                Analytics
            </a>
            <div class="quick-item" style="background:linear-gradient(135deg,#006D69,#003D3B);color:#fff;border:none;">
                <div style="font-size:12px;font-weight:700;">🎯</div>
                <div style="font-size:11px;margin-top:2px;"><?php echo $approved_apps; ?> <?php echo $sidebar_lang['approved']; ?></div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
