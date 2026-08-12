<?php
session_start();
require_once '../config/db.php';
require_once '../includes/mailer.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Auto-migration: email tracking + duplicate-prevention columns on notifications
$col_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'application_id'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN application_id INT DEFAULT NULL, ADD INDEX (application_id)");
}
$col_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'email_status'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN email_status ENUM('pending','sent','failed') DEFAULT 'pending'");
    $conn->query("ALTER TABLE notifications ADD COLUMN email_sent_at DATETIME DEFAULT NULL");
    $conn->query("ALTER TABLE notifications ADD COLUMN email_error TEXT DEFAULT NULL");
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
    'page_title' => 'ငွေပေးချေမှုမှတ်တမ်း',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => ' Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => 'Disbursements',
];
// include "header.php";
$sem_names = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'continue') {
        $recipient_id = (int)$_POST['recipient_id'];
        $last = $conn->query("SELECT * FROM payment_records WHERE recipient_id = $recipient_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if ($last) {
            $cur_i = array_search(trim(str_replace(' Semester', '', $last['semester'])), $sem_names);
            if ($cur_i !== false && $cur_i < count($sem_names) - 1) {
                $next_i = $cur_i + 1;
                $next_sem = $sem_names[$next_i] . ' Semester';
                $base_year = (int)substr($last['academic_year'], 0, 4);
                $next_start = $base_year + (int)floor($next_i / 2);
                $next_year = $next_start . '-' . ($next_start + 1);
                $dup = $conn->query("SELECT COUNT(*) FROM payment_records WHERE recipient_id = $recipient_id AND semester = '$next_sem'")->fetch_row()[0];
                if (!$dup) {
                    $conn->query("INSERT INTO payment_records (recipient_id, bank_id, amount, academic_year, semester, payment_date) VALUES ($recipient_id, " . (int)$last['bank_id'] . ", " . (float)$last['amount'] . ", '$next_year', '$next_sem', CURDATE())");

                    // Fetch student + scheme details for notification and email
                    $student_info = $conn->query("SELECT a.student_id, a.id AS application_id, a.application_no, s.name AS student_name, s.email AS student_email, sc.scheme_name FROM scholarship_recipients sr JOIN applications a ON sr.application_id = a.id JOIN student s ON a.student_id = s.id JOIN schemes sc ON a.scheme_id = sc.id WHERE sr.id = $recipient_id")->fetch_assoc();
                    if ($student_info) {
                        // Create notification for the student (one per new semester record)
                        $title = "Next Semester Payment Released";
                        $message = "Your next semester payment (" . $next_sem . ", " . $next_year . ") of " . number_format((float)$last['amount']) . " MMK for application #" . $student_info['application_no'] . " has been released.";
                        $stmt = $conn->prepare("INSERT INTO notifications (student_id, application_id, title, message, type, email_status) VALUES (?, ?, ?, ?, 'disbursement', 'pending')");
                        $stmt->bind_param("iiss", $student_info['student_id'], $student_info['application_id'], $title, $message);
                        $stmt->execute();
                        $notif_id = $stmt->insert_id;
                        $stmt->close();

                        // Send the real email to the student's registered address
                        $email_data = [
                            'student_name'   => $student_info['student_name'],
                            'scheme_name'    => $student_info['scheme_name'],
                            'application_no' => $student_info['application_no'],
                            'amount'         => $last['amount'],
                            'semester'       => $next_sem,
                            'academic_year'  => $next_year,
                        ];
                        $subject = "Next Semester Scholarship Payment Released";
                        $email_result = edugrant_send_email($student_info['student_email'], $subject, edugrant_disbursement_email_body($email_data));
                        if ($email_result === true) {
                            $conn->query("UPDATE notifications SET email_status = 'sent', email_sent_at = NOW() WHERE id = $notif_id");
                        } else {
                            $conn->query("UPDATE notifications SET email_status = 'failed', email_error = '" . $conn->real_escape_string($email_result) . "' WHERE id = $notif_id");
                        }
                    }
                }
            }
        }
        header("Location: disbursements.php?cont=1");
        exit();
    }

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
    ORDER BY pr.payment_date DESC, pr.id DESC");

$current_page = 'disbursements';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
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
        .badge-paid { background-color: #dcfce7; color: #15803d; }
        .btn-green-sm { background-color: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-blue-sm { background-color: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .form-input { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box; margin-bottom: 10px; }
        .bottom-bar { background-color: #003D3B; color: #94a3b8; font-size: 11px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .bottom-links a { color: #fff; text-decoration: none; margin-left: 15px; }
        .summary-strip { display: flex; gap: 15px; margin-bottom: 20px; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; flex: 1; text-align: center; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }
        .summary-box .num { font-size: 22px; font-weight: 800; color: #0f172a; }
        .summary-box .lbl { font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase; }
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
        html.dark-mode .badge-paid { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .summary-box { background: #1e293b; border-color: #334155; }
        html.dark-mode .summary-box .number { color: #f1f5f9; }
        html.dark-mode .summary-box .label { color: #94a3b8; }
        html.dark-mode .form-input, html.dark-mode .form-select, html.dark-mode .form-textarea {
            background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
        }
        html.dark-mode .field-lbl { color: #94a3b8; }
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
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Disbursements'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div>
                    <h2 class="card-title">💵 <?php echo $sidebar_lang['page_title']; ?></h2>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="text" id="liveSearch" class="form-input" placeholder="🔍 Search name, scheme, semester..." style="width:220px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;margin-bottom:0;" autocomplete="off">
                    <select id="semFilter" class="form-input" style="width:150px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;margin-bottom:0;cursor:pointer;">
                        <option value="">All Semesters</option>
                        <?php foreach ($sem_names as $sn): ?>
                            <option value="<?php echo $sn; ?> Semester"><?php echo $sn; ?> Semester</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (isset($_GET['cont'])): ?>
                <div style="background:#dcfce7; color:#15803d; padding:10px 14px; border-radius:6px; font-size:12px; margin-bottom:15px;">✔ Next semester disbursement created successfully.</div>
            <?php endif; ?>

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
                        <th>No.</th>
                        <th>Student</th>
                        <th>Scheme</th>
                        <th>Bank</th>
                        <th>Amount</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($count > 0): ?>
                        <?php $no = 1; $seen_cont = []; while ($row = $disbursements->fetch_assoc()): ?>
                            <?php
                            $rid = (int)$row['recipient_id'];
                            $cur_i = array_search(trim(str_replace(' Semester', '', $row['semester'] ?? '')), $sem_names);
                            $show_cont = !isset($seen_cont[$rid]) && $cur_i !== false && $cur_i < count($sem_names) - 1;
                            $seen_cont[$rid] = true;
                            $next_sem_label = $show_cont ? $sem_names[$cur_i + 1] . ' Semester' : '';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['scheme_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['bank_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($row['amount']); ?></td>
                                <td><?php echo $row['academic_year']; ?></td>
                                <td><?php echo $row['semester']; ?></td>
                                <td><?php echo $row['payment_date']; ?></td>
                                <td>
                                    <?php if ($show_cont): ?>
                                        <form method="POST" style="display:inline;margin:0;">
                                            <input type="hidden" name="action" value="continue">
                                            <input type="hidden" name="recipient_id" value="<?php echo $rid; ?>">
                                            <button type="submit" class="btn-green-sm" style="padding:4px 10px; font-size:10px; margin:0;">Continue ➜ <?php echo htmlspecialchars($next_sem_label); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1; font-size:11px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:20px; color:#94a3b8;">No disbursement records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function applyFilter() {
    var query = document.getElementById('liveSearch').value.toLowerCase();
    var sem = document.getElementById('semFilter').value.toLowerCase();
    var semIndex = -1;
    var headers = document.querySelectorAll('.admin-table thead th');
    for (var h = 0; h < headers.length; h++) {
        if (headers[h].textContent.trim().toLowerCase() === 'semester') { semIndex = h; break; }
    }
    var rows = document.querySelectorAll('.admin-table tbody tr');
    var visibleCount = 0;
    var visibleTotal = 0;
    var no = 1;
    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;
        var text = '';
        for (var i = 0; i < row.children.length - 1; i++) {
            text += row.children[i].textContent.toLowerCase() + ' ';
        }
        var semCell = semIndex >= 0 && row.children[semIndex] ? row.children[semIndex].textContent.toLowerCase() : '';
        var matchText = text.includes(query);
        var matchSem = sem === '' || semCell.includes(sem);
        var show = matchText && matchSem;
        row.style.display = show ? '' : 'none';
        if (show) {
            var amount = parseFloat((row.children[4] ? row.children[4].textContent : '').replace(/,/g, '')) || 0;
            visibleCount++;
            visibleTotal += amount;
            row.children[0].textContent = no++;
        }
    });
    var boxes = document.querySelectorAll('.summary-box .num');
    if (boxes.length >= 2) {
        boxes[0].textContent = visibleCount;
        boxes[1].textContent = visibleTotal.toLocaleString('en-US');
    }
}

document.getElementById('liveSearch').addEventListener('input', applyFilter);
document.getElementById('semFilter').addEventListener('change', applyFilter);
</script>
</body>
</html>
<?php $conn->close(); ?>
