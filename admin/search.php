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
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_term = $conn->real_escape_string($q);
$like = '%' . $search_term . '%';

$sidebar_lang = $is_mm ? [
    'dashboard' => 'ဒက်ရှ်ဘုတ်',
    'schemes' => 'ပညာသင်ဆုအစီအစဉ်များ',
    'reviewers' => 'စိစစ်ရေးမှူးများ',
    'applications' => 'လျှောက်လွှာများ',
    'bank_verify' => 'ဘဏ်စစ်ဆေးခြင်း',
    'recipients' => 'ဆုရရှိသူများ',
    'disbursements' => 'ငွေပေးချေမှုများ',
    'reports' => 'အစီရင်ခံစာများ',
    'my_profile' => 'ကိုယ်ရေးအချက်အလက်',
    'logout' => 'ထွက်မည်',
    'page_title' => 'ရှာဖွေမှုရလဒ်',
    'no_results' => 'ရလဒ်မရှိပါ',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verification',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'my_profile' => 'My Profile',
    'logout' => 'Logout',
    'page_title' => 'Search Results',
    'no_results' => 'No results found',
];

$total_schemes = $conn->query("SELECT COUNT(*) FROM schemes")->fetch_row()[0] ?? 0;
$total_apps = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0] ?? 0;

$results = ['applications' => [], 'students' => [], 'schemes' => [], 'reviewers' => []];
$total_results = 0;

if ($q !== '') {
    $app_res = $conn->query("SELECT a.id, a.application_no, s.name AS student_name, sc.scheme_name, a.status
        FROM applications a
        JOIN student s ON a.student_id = s.id
        JOIN schemes sc ON a.scheme_id = sc.id
        WHERE a.application_no LIKE '$like' OR s.name LIKE '$like' OR sc.scheme_name LIKE '$like'
        LIMIT 20");
    if ($app_res) while ($r = $app_res->fetch_assoc()) { $results['applications'][] = $r; $total_results++; }

    $stu_res = $conn->query("SELECT id, name, email, phone FROM student WHERE name LIKE '$like' OR email LIKE '$like' OR phone LIKE '$like' LIMIT 10");
    if ($stu_res) while ($r = $stu_res->fetch_assoc()) { $results['students'][] = $r; $total_results++; }

    $sch_res = $conn->query("SELECT id, scheme_name, amount, status FROM schemes WHERE scheme_name LIKE '$like' LIMIT 10");
    if ($sch_res) while ($r = $sch_res->fetch_assoc()) { $results['schemes'][] = $r; $total_results++; }

    $rev_res = $conn->query("SELECT id, name, email, department FROM reviewers WHERE name LIKE '$like' OR email LIKE '$like' OR department LIKE '$like' LIMIT 10");
    if ($rev_res) while ($r = $rev_res->fetch_assoc()) { $results['reviewers'][] = $r; $total_results++; }
}
$current_page = 'search';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --sidebar-bg: #006D69; --sidebar-hover: #005a56; --accent: #FFD700;
            --accent-light: rgba(255,215,0,0.12); --card-bg: #ffffff; --body-bg: #f0f7f5;
            --border: #e0eae8; --text-primary: #0f172a; --text-secondary: #64748b;
            --text-muted: #94a3b8; --shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --radius: 12px; --transition: 0.2s ease;
        }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--body-bg); display: flex; height: 100vh; overflow: hidden; color: var(--text-primary); }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-brand { padding: 22px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .brand-icon { width: 38px; height: 38px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: #004D4A; flex-shrink: 0; }
        .brand-text h2 { font-size: 15px; font-weight: 700; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; }
        .sidebar-menu { list-style: none; padding: 12px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { padding: 16px 24px 6px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.8px; }
        .menu-item a { display: flex; align-items: center; gap: 12px; padding: 10px 24px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; font-weight: 500; transition: var(--transition); border-left: 3px solid transparent; margin: 2px 8px; border-radius: 8px; }
        .menu-item a:hover { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active a { background: var(--accent-light); color: #FFD700; border-left-color: #FFD700; }
        .menu-item .icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .menu-item .badge-count { margin-left: auto; background: rgba(255,255,255,0.1); padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .menu-item.logout { margin-top: auto; }
        .menu-item.logout a { color: #fca5a5; }
        .menu-item.logout a:hover { background: rgba(252,165,165,0.1); }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
        .top-header { background: #fff; padding: 12px 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .top-header h1 { font-size: 18px; font-weight: 700; }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); }
        .header-search { display: flex; align-items: center; background: var(--body-bg); border-radius: 8px; padding: 0 12px; gap: 8px; }
        .header-search input { border: none; background: none; padding: 8px 0; font-size: 13px; outline: none; width: 220px; font-family: inherit; }
        .header-search input::placeholder { color: var(--text-muted); }

        .dashboard-body { flex-grow: 1; padding: 24px 28px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }

        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 20px 22px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .card-header h3 { font-size: 15px; font-weight: 600; }
        .card-subtitle { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .admin-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .admin-table th { text-align: left; padding: 10px 8px; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border); }
        .admin-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .admin-table tr:hover td { background: #f8fafc; }

        .badge { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; display: inline-block; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-submitted { background: #dbeafe; color: #1e40af; }

        .action-link { color: #006D69; text-decoration: none; font-weight: 600; font-size: 12px; }
        .action-link:hover { color: #003D3B; }

        .bottom-bar { background: #fff; border-top: 1px solid var(--border); padding: 12px 28px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-secondary); flex-shrink: 0; }

        .search-summary { font-size: 13px; color: var(--text-secondary); margin-bottom: 4px; }
        .search-summary strong { color: var(--text-primary); }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state h3 { font-size: 16px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Search Results'; include 'header.php'; ?>

    <div class="dashboard-body">

        <?php if ($q === ''): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h3>Search the Portal</h3>
                <p>Search for applications, students, schemes, or reviewers</p>
            </div>
        <?php elseif ($total_results === 0): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3><?php echo $sidebar_lang['no_results']; ?></h3>
                <p>No results match "<strong><?php echo htmlspecialchars($q); ?></strong>"</p>
            </div>
        <?php else: ?>
            <p class="search-summary">Found <strong><?php echo $total_results; ?></strong> result(s) for "<strong><?php echo htmlspecialchars($q); ?></strong>"</p>

            <?php if (!empty($results['applications'])): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>📁 Applications (<?php echo count($results['applications']); ?>)</h3>
                        <p class="card-subtitle">Matching applications</p>
                    </div>
                    <a href="applications.php?search=<?php echo urlencode($q); ?>" class="action-link">View All →</a>
                </div>
                <table class="admin-table">
                    <thead><tr><th>App No</th><th>Student</th><th>Scheme</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($results['applications'] as $a): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($a['application_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($a['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['scheme_name']); ?></td>
                            <td>
                                <?php
                                $cls = 'badge-pending';
                                if (in_array($a['status'], ['Approved','Recommended'])) $cls = 'badge-approved';
                                elseif ($a['status'] === 'Rejected') $cls = 'badge-rejected';
                                elseif ($a['status'] === 'Submitted') $cls = 'badge-submitted';
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo $a['status']; ?></span>
                            </td>
                            <td><a href="view_app.php?id=<?php echo $a['id']; ?>" class="action-link">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['students'])): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>🎓 Students (<?php echo count($results['students']); ?>)</h3>
                        <p class="card-subtitle">Matching students</p>
                    </div>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($results['students'] as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                            <td><a href="../user/profile.php?id=<?php echo $s['id']; ?>" class="action-link">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['schemes'])): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>📋 Schemes (<?php echo count($results['schemes']); ?>)</h3>
                        <p class="card-subtitle">Matching schemes</p>
                    </div>
                    <a href="schemes.php?search=<?php echo urlencode($q); ?>" class="action-link">View All →</a>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Scheme Name</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($results['schemes'] as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['scheme_name']); ?></strong></td>
                            <td><?php echo number_format(floatval(str_replace(',', '', $s['amount']))); ?> MMK</td>
                            <td><span class="badge <?php echo $s['status'] === 'Active' ? 'badge-approved' : 'badge-pending'; ?>"><?php echo $s['status']; ?></span></td>
                            <td><a href="schemes.php?edit=<?php echo $s['id']; ?>" class="action-link">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['reviewers'])): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>👥 Reviewers (<?php echo count($results['reviewers']); ?>)</h3>
                        <p class="card-subtitle">Matching reviewers</p>
                    </div>
                    <a href="reviewers.php?search=<?php echo urlencode($q); ?>" class="action-link">View All →</a>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Department</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($results['reviewers'] as $r): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['department'] ?? '-'); ?></td>
                            <td><a href="reviewers.php?edit=<?php echo $r['id']; ?>" class="action-link">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal</strong></div>
        <div>© <?php echo date('Y'); ?> Computer University (Meiktila)</div>
    </footer> -->
</div>

</body>
</html>
<?php $conn->close(); ?>
