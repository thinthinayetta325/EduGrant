<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = (int)$_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? "Admin Clerk";
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');

$check_col = $conn->query("SHOW COLUMNS FROM admin LIKE 'profile_image'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
}
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
    'my_profile' => 'ကိုယ်ရေးအချက်အလက်',
    'logout' => 'ထွက်မည်',
    'page_title' => 'အက်ဒ်မင် ကိုယ်ရေးအချက်အလက်',
    'page_sub' => 'သင့်အကောင့်အချက်အလက်များကို စီမံပါ',
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
    'page_title' => 'Admin Profile',
    'page_sub' => 'Manage your account information',
];
$msg = '';

$admin_data = $conn->query("SELECT * FROM admin WHERE id = $admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $conn->query("UPDATE admin SET name='$name', email='$email' WHERE id=$admin_id");
        $_SESSION['admin_name'] = $name;
        $admin_data['name'] = $name;
        $admin_data['email'] = $email;
        $msg = 'Profile updated successfully.';

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/profile_pics/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    if (!empty($admin_data['profile_image'])) {
                        $old_file = $upload_dir . $admin_data['profile_image'];
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    $conn->query("UPDATE admin SET profile_image = '$filename' WHERE id = $admin_id");
                    $admin_data['profile_image'] = $filename;
                    $msg = 'Profile and image updated successfully.';
                } else {
                    $msg = 'Profile updated but image upload failed.';
                }
            } else {
                $msg = 'Profile updated but invalid image type. Allowed: jpg, jpeg, png, gif, webp.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/profile_pics/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    if (!empty($admin_data['profile_image'])) {
                        $old_file = $upload_dir . $admin_data['profile_image'];
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    $conn->query("UPDATE admin SET profile_image = '$filename' WHERE id = $admin_id");
                    $admin_data['profile_image'] = $filename;
                    $msg = 'Profile image updated successfully.';
                } else {
                    $msg = 'Failed to upload image.';
                }
            } else {
                $msg = 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp.';
            }
        } else {
            $msg = 'No file selected or upload error.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if (password_verify($current, $admin_data['password'])) {
            if ($new === $confirm) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE admin SET password='$hash' WHERE id=$admin_id");
                $msg = 'Password changed successfully.';
            } else {
                $msg = 'New passwords do not match.';
            }
        } else {
            $msg = 'Current password is incorrect.';
        }
    }
}

$total_actions = $conn->query("SELECT COUNT(*) FROM applications WHERE approved_by = $admin_id")->fetch_row()[0] ?? 0;
$recent_actions = $conn->query("SELECT a.application_no, s.name AS student_name, a.status, a.approved_at
    FROM applications a JOIN student s ON a.student_id = s.id
    WHERE a.approved_by = $admin_id ORDER BY a.approved_at DESC");

// Funds released tracking
if (isset($_GET['dismiss_funds'])) {
    $_SESSION['funds_dismissed'] = true;
    header("Location: profile.php");
    exit();
}
$show_funds = !isset($_SESSION['funds_dismissed']);
$funds_released = [];
if ($show_funds) {
    $funds_q = $conn->query("
        SELECT r.id, r.filename, r.created_at, a.application_no, s.name AS student_name, sc.scheme_name, pr.amount
        FROM receipts r
        JOIN applications a ON r.application_id = a.id
        JOIN student s ON a.student_id = s.id
        JOIN schemes sc ON a.scheme_id = sc.id
        JOIN payment_records pr ON r.application_id = (SELECT application_id FROM scholarship_recipients WHERE id = pr.recipient_id LIMIT 1)
        WHERE a.approved_by = $admin_id AND a.status = 'Approved'
        ORDER BY r.created_at DESC
    ");
    if ($funds_q) {
        while ($f = $funds_q->fetch_assoc()) {
            $funds_released[] = $f;
        }
    }
}
if (isset($_GET['dl'])) {
    $dl_id = (int)$_GET['dl'];
    $dl = $conn->query("SELECT filename FROM receipts WHERE id = $dl_id")->fetch_assoc();
    if ($dl && file_exists('../uploads/receipts/' . $dl['filename'])) {
        $_SESSION['funds_dismissed'] = true;
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $dl['filename'] . '"');
        readfile('../uploads/receipts/' . $dl['filename']);
        exit();
    }
    header("Location: profile.php");
    exit();
}

$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --sidebar-bg: #006D69;
            --sidebar-hover: #005a56;
            --accent: #FFD700;
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
            --transition: 0.2s ease;
        }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--body-bg); display: flex; height: 100vh; overflow: hidden; color: var(--text-primary); }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; flex-shrink: 0; position: relative; z-index: 10; }
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
        .menu-item.logout { margin-top: auto; }
        .menu-item.logout a { color: #fca5a5; }
        .menu-item.logout a:hover { background: rgba(252,165,165,0.1); }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
        .top-header { background: #fff; padding: 12px 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .top-header h1 { font-size: 18px; font-weight: 700; }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); }

        .dashboard-body { flex-grow: 1; padding: 16px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto; }

        .profile-alerts { flex-shrink: 0; display: flex; flex-direction: column; gap: 10px; }
        .profile-grid { display: flex; gap: 16px; flex-grow: 1; }

        .profile-left {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .profile-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .profile-right .card {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .profile-right .card-body {
            flex-grow: 1;
        }

        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 18px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .card-header h3 { font-size: 15px; font-weight: 600; }
        .card-subtitle { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .profile-hero { display: flex; align-items: center; gap: 16px; }
        .profile-avatar { width: 64px; height: 64px; background: linear-gradient(135deg, #006D69, #003D3B); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: #FFD700; flex-shrink: 0; position: relative; transition: var(--transition); }
        .profile-avatar:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; font-size: 24px; border-radius: 16px; opacity: 0; transition: var(--transition); }
        .avatar-badge { position: absolute; bottom: -2px; right: -2px; width: 20px; height: 20px; background: #10b981; border-radius: 50%; border: 3px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #fff; }
        .profile-info h2 { font-size: 18px; font-weight: 700; }
        .profile-info .role { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; margin-top: 2px; }
        .role-badge { background: var(--accent-light); color: #006D69; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .profile-meta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px; }
        .profile-meta span { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .stat-item { background: var(--body-bg); border-radius: 8px; padding: 12px 8px; text-align: center; border: 1px solid var(--border); }
        .stat-item .num { font-size: 18px; font-weight: 800; color: #006D69; }
        .stat-item .lbl { font-size: 10px; color: var(--text-secondary); font-weight: 500; margin-top: 2px; }

        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 120px; font-size: 11px; font-weight: 600; color: var(--text-secondary); flex-shrink: 0; }
        .info-value { font-size: 12px; font-weight: 500; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; }
        .form-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; transition: var(--transition); background: #fff; }
        .form-input:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.1); }
        .form-input::placeholder { color: var(--text-muted); }

        .btn-primary { background: #006D69; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; }
        .btn-primary:hover { background: #005a56; }
        .btn-gold { background: #FFD700; color: #004D4A; border: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; }
        .btn-gold:hover { background: #e6c200; }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border); padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; }
        .btn-outline:hover { background: var(--body-bg); }
        .btn-sm { padding: 8px 16px; font-size: 12px; }

        .admin-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .admin-table th { text-align: left; padding: 10px 8px; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border); }
        .admin-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .admin-table tr:hover td { background: #f8fafc; }

        .badge { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; display: inline-block; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .msg { padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 16px; }
        .msg-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .msg-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .bottom-bar { background: #fff; border-top: 1px solid var(--border); padding: 12px 28px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-secondary); flex-shrink: 0; }

        .password-strength { height: 4px; background: #f1f5f9; border-radius: 4px; margin-top: 8px; overflow: hidden; }
        .password-strength .fill { height: 100%; border-radius: 4px; transition: width 0.3s; width: 0; }

        .funds-box {
            display: flex;
            align-items: center;
            gap: 16px;
            background: linear-gradient(135deg, #065f46, #047857);
            color: #fff;
            border-radius: var(--radius);
            padding: 18px 22px;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .funds-icon { font-size: 32px; flex-shrink: 0; }
        .funds-body { flex-grow: 1; }
        .funds-title { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
        .funds-desc { font-size: 12px; color: rgba(255,255,255,0.75); }
        .funds-app { display: inline-block; margin-left: 6px; padding: 1px 8px; background: rgba(255,255,255,0.15); border-radius: 4px; font-size: 10px; font-weight: 600; }
        .funds-btn {
            flex-shrink: 0;
            background: rgba(255,255,255,0.2);
            color: #fff;
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s ease;
            white-space: nowrap;
        }
        .funds-btn:hover { background: rgba(255,255,255,0.35); }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">
<?php include 'sidebar.php'; ?>

<div class="workspace">
    <div class="top-header">
        <div>
            <h1><?php echo $sidebar_lang['page_title']; ?></h1>
            <span class="sub"><?php echo $sidebar_lang['page_sub']; ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="?lang=en" style="text-decoration:none;color:<?php echo !$is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo !$is_mm ? '700' : '400'; ?>;font-size:13px;padding:2px 8px;border-radius:4px;background:<?php echo !$is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">ENG</a>
            <span style="color:#cbd5e1;">|</span>
            <a href="?lang=mm" style="text-decoration:none;color:<?php echo $is_mm ? '#006D69' : '#94a3b8'; ?>;font-weight:<?php echo $is_mm ? '700' : '400'; ?>;font-size:13px;padding:2px 8px;border-radius:4px;background:<?php echo $is_mm ? 'rgba(0,109,105,0.1)' : 'transparent'; ?>">မြန်မာ</a>
        </div>
    </div>

    <div class="dashboard-body">

        <div class="profile-alerts">
            <?php if ($msg): ?>
                <div class="msg <?php echo strpos($msg, 'success') !== false ? 'msg-success' : 'msg-error'; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($funds_released)): ?>
                <?php foreach ($funds_released as $fr): ?>
                <div class="funds-box" id="funds-<?php echo $fr['id']; ?>">
                    <div class="funds-icon">💰</div>
                    <div class="funds-body">
                        <div class="funds-title">Funds Released</div>
                        <div class="funds-desc">
                            <strong><?php echo htmlspecialchars($fr['student_name']); ?></strong> —
                            <?php echo htmlspecialchars($fr['scheme_name']); ?> —
                            <?php echo number_format($fr['amount']); ?> MMK
                            <span class="funds-app">#<?php echo htmlspecialchars($fr['application_no']); ?></span>
                        </div>
                    </div>
                    <a href="?dl=<?php echo $fr['id']; ?>" target="_blank" class="funds-btn" onclick="this.closest('.funds-box').remove()">📥 Download Receipt</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="profile-grid">
            <div class="profile-left">

            <div class="card">
                <div class="profile-hero">
                    <div class="profile-avatar" id="avatarUpload" style="overflow:hidden;cursor:pointer;" onclick="document.getElementById('avatarInput').click()">
                        <?php if (!empty($admin_data['profile_image']) && file_exists('../uploads/profile_pics/' . $admin_data['profile_image'])): ?>
                            <img src="../uploads/profile_pics/<?php echo $admin_data['profile_image']; ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($admin_data['name'], 0, 1)); ?>
                        <?php endif; ?>
                        <div class="avatar-badge">✓</div>
                        <div class="avatar-overlay">📷</div>
                        <form method="POST" enctype="multipart/form-data" style="display:none;">
                            <input type="hidden" name="action" value="upload_image">
                            <input type="file" name="profile_image" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($admin_data['name']); ?></h2>
                        <div class="role">
                            <span>System Administrator</span>
                            <span class="role-badge">Active</span>
                        </div>
                        <div class="profile-meta">
                            <span>📧 <?php echo htmlspecialchars($admin_data['email']); ?></span>
                            <span>🆔 #<?php echo $admin_data['id']; ?></span>
                            <span>⚡ <?php echo $total_actions; ?> approvals</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <div class="num"><?php echo $total_actions; ?></div>
                    <div class="lbl">Total Approvals</div>
                </div>
                <div class="stat-item">
                    <div class="num"><?php echo $total_schemes = $conn->query("SELECT COUNT(*) FROM schemes")->fetch_row()[0] ?? 0; ?></div>
                    <div class="lbl">Active Schemes</div>
                </div>
                <div class="stat-item">
                    <div class="num"><?php echo $total_apps = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0] ?? 0; ?></div>
                    <div class="lbl">Applications</div>
                </div>
                <div class="stat-item">
                    <div class="num"><?php echo $total_rec = $conn->query("SELECT COUNT(*) FROM scholarship_recipients")->fetch_row()[0] ?? 0; ?></div>
                    <div class="lbl">Recipients</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>Account Details</h3>
                        <p class="card-subtitle">Your personal information</p>
                    </div>
                    <button class="btn-gold btn-sm" onclick="document.getElementById('editModal').style.display='flex'">✏️ Edit</button>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($admin_data['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($admin_data['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value">System Administrator</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admin ID</span>
                    <span class="info-value">#<?php echo $admin_data['id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Status</span>
                    <span class="info-value"><span class="badge badge-approved">Active</span></span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-input" id="newPass" required oninput="checkStrength(this.value)">
                        <div class="password-strength"><div class="fill" id="strengthFill"></div></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;">Update Password</button>
                </form>
            </div>

        </div>

        <div class="profile-right">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>Recent Activity</h3>
                        <p class="card-subtitle">Your latest approval actions</p>
                    </div>
                </div>
                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>App No</th><th>Student</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_actions && $recent_actions->num_rows > 0): ?>
                                <?php while ($a = $recent_actions->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($a['application_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['student_name']); ?></td>
                                        <td>
                                            <?php
                                            $cls = 'badge-pending';
                                            if ($a['status'] === 'Approved') $cls = 'badge-approved';
                                            elseif ($a['status'] === 'Rejected') $cls = 'badge-rejected';
                                            ?>
                                            <span class="badge <?php echo $cls; ?>"><?php echo $a['status']; ?></span>
                                        </td>
                                        <td><?php echo $a['approved_at'] ? date('d M Y', strtotime($a['approved_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">No recent activity</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

    </div>

    <footer class="bottom-bar">
        <div>⚡ <strong>UCSMT Education Grant Portal</strong></div>
        <div>© <?php echo date('Y'); ?> Computer University (Meiktila)</div>
    </footer>
</div>

<div id="editModal" class="modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;justify-content:center;align-items:center;">
    <div class="modal-box" style="background:#fff;border-radius:12px;padding:28px;width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:17px;font-weight:700;">✏️ Edit Profile</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
                <label>Profile Photo</label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <?php if (!empty($admin_data['profile_image']) && file_exists('../uploads/profile_pics/' . $admin_data['profile_image'])): ?>
                        <img src="../uploads/profile_pics/<?php echo $admin_data['profile_image']; ?>" alt="" style="width:48px;height:48px;border-radius:12px;object-fit:cover;border:2px solid var(--border);">
                    <?php else: ?>
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#006D69,#003D3B);display:flex;align-items:center;justify-content:center;color:#FFD700;font-weight:700;font-size:18px;border:2px solid var(--border);"><?php echo strtoupper(substr($admin_data['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size:12px;">
                </div>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn-primary" style="flex:1;">Save Changes</button>
                <button type="button" class="btn-outline" onclick="document.getElementById('editModal').style.display='none'" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    let score = 0;
    if (val.length >= 6) score += 25;
    if (val.length >= 10) score += 25;
    if (/[A-Z]/.test(val)) score += 25;
    if (/\d/.test(val)) score += 25;
    fill.style.width = score + '%';
    if (score < 25) fill.style.background = '#ef4444';
    else if (score < 50) fill.style.background = '#f59e0b';
    else if (score < 75) fill.style.background = '#10b981';
    else fill.style.background = '#059669';
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
<?php $conn->close(); ?>
