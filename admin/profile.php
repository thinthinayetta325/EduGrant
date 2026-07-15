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
     'messages' => 'စာတိုပေးစာများ',
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
        $admin_name = $name;
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
                    $_SESSION['admin_image'] = $filename;
                    $msg = 'Profile and image updated successfully.';
                } else {
                    $msg = 'Profile updated but image upload failed.';
                }
            } else {
                $msg = 'Profile updated but invalid image type. Allowed: jpg, jpeg, png, gif, webp.';
            }
        }

        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            if (password_verify($current, $admin_data['password'])) {
                if ($new === $confirm) {
                    if (strlen($new) >= 6) {
                        $hash = password_hash($new, PASSWORD_DEFAULT);
                        $conn->query("UPDATE admin SET password='$hash' WHERE id=$admin_id");
                        $msg = 'Profile and password updated successfully.';
                    } else {
                        $msg = 'Profile updated. New password must be at least 6 characters.';
                    }
                } else {
                    $msg = 'Profile updated. New passwords do not match.';
                }
            } else {
                $msg = 'Profile updated. Current password is incorrect.';
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
                    $_SESSION['admin_image'] = $filename;
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
    }
}

$total_actions = $conn->query("SELECT COUNT(*) FROM applications WHERE approved_by = $admin_id")->fetch_row()[0] ?? 0;

$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --accent: #FFD700;
            --accent-light: rgba(255,215,0,0.12);
            --card-bg: #ffffff;
            --body-bg: #f1f5f9;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow: 0 1px 2px rgba(0,0,0,0.04), 0 1px 4px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.08);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--body-bg); display: flex; height: 100vh; overflow: hidden; color: var(--text-primary); }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar { width: 260px; background: #006D69; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; position: relative; z-index: 10; }
        .sidebar-brand { padding: 22px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .brand-icon { width: 38px; height: 38px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: #004D4A; flex-shrink: 0; }
        .brand-text h2 { font-size: 15px; font-weight: 700; color: #fff; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; }
        .admin-profile { display: flex; align-items: center; gap: 12px; padding: 16px 20px; margin: 8px 12px; background: rgba(255,255,255,0.05); border-radius: 12px; }
        .admin-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #FFD700, #f59e0b); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: #004D4A; flex-shrink: 0; }
        .admin-meta h4 { font-size: 13px; font-weight: 600; color: #fff; }
        .admin-meta p { font-size: 10px; color: rgba(255,255,255,0.5); }
        .sidebar-menu { list-style: none; padding: 12px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { padding: 16px 24px 6px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.8px; }
        .menu-item a { display: flex; align-items: center; gap: 12px; padding: 10px 24px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.2s ease; border-left: 3px solid transparent; margin: 2px 8px; border-radius: 8px; }
        .menu-item a:hover { background: #005a56; color: #fff; }
        .menu-item.active a { background: rgba(255,215,0,0.12); color: #FFD700; border-left-color: #FFD700; }
        .menu-item .icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .menu-item.logout { margin-top: auto; }
        .menu-item.logout a { color: #fca5a5; }
        .menu-item.logout a:hover { background: rgba(252,165,165,0.1); }
        .badge-count { background: #FFD700; color: #004D4A; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px; margin-left: auto; }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--body-bg); }
        .top-header { background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-shrink: 0; position: sticky; top: 0; z-index: 20; }
        .top-header h1 { font-size: 20px; font-weight: 700; background: linear-gradient(135deg, #0f172a, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .top-header .sub { font-size: 13px; color: var(--text-secondary); margin-top: 2px; display: block; }

        .lang-switch { display: flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 3px; border-radius: 8px; }
        .lang-switch a { text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; transition: var(--transition); color: var(--text-muted); }
        .lang-switch a.active { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

        .dashboard-body { flex-grow: 1; padding: 24px 32px; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; }

        .profile-alerts { margin-bottom: 20px; }
        .msg { padding: 14px 20px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease; }
        .msg-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .msg-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

        .profile-left { display: flex; flex-direction: column; gap: 20px; }
        .profile-right { display: flex; flex-direction: column; gap: 20px; }

        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 24px; transition: var(--transition); }
        .card:hover { box-shadow: var(--shadow-lg); }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-header h3 { font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .card-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .profile-hero { display: flex; align-items: center; gap: 18px; }
        .profile-avatar { width: 72px; height: 72px; background: linear-gradient(135deg, #006D69, #003D3B); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; color: #FFD700; flex-shrink: 0; position: relative; transition: var(--transition); cursor: pointer; overflow: hidden; }
        .profile-avatar:hover .avatar-overlay { opacity: 1; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 20px; }
        .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center; font-size: 22px; border-radius: 20px; opacity: 0; transition: var(--transition); backdrop-filter: blur(4px); }
        .avatar-badge { position: absolute; bottom: -2px; right: -2px; width: 22px; height: 22px; background: #10b981; border-radius: 50%; border: 3px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff; box-shadow: 0 2px 8px rgba(16,185,129,0.3); }
        .profile-info h2 { font-size: 18px; font-weight: 700; }
        .profile-info .role { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; margin-top: 3px; }
        .role-badge { background: var(--accent-light); color: #006D69; padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .profile-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 10px; }
        .profile-meta span { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; }

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .stat-item { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: var(--radius-sm); padding: 16px 12px; text-align: center; border: 1px solid var(--border); transition: var(--transition); }
        .stat-item:hover { border-color: #006D69; background: linear-gradient(135deg, #f0fdfa, #ecfdf5); }
        .stat-item .num { font-size: 22px; font-weight: 800; color: #006D69; }
        .stat-item .lbl { font-size: 11px; color: var(--text-secondary); font-weight: 500; margin-top: 4px; }

        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 130px; font-size: 12px; font-weight: 600; color: var(--text-secondary); flex-shrink: 0; }
        .info-value { font-size: 13px; font-weight: 500; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .form-input { width: 100%; padding: 11px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; outline: none; transition: var(--transition); background: #fff; color: var(--text-primary); }
        .form-input:focus { border-color: #006D69; box-shadow: 0 0 0 4px rgba(0,109,105,0.1); }
        .form-input::placeholder { color: var(--text-muted); }

        .btn { padding: 11px 24px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; border: none; display: inline-flex; align-items: center; gap: 8px; justify-content: center; }
        .btn-primary { background: #006D69; color: #fff; }
        .btn-primary:hover { background: #005a56; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,109,105,0.3); }
        .btn-gold { background: #FFD700; color: #004D4A; }
        .btn-gold:hover { background: #e6c200; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,215,0,0.3); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-sm { padding: 8px 16px; font-size: 12px; }
        .btn-block { width: 100%; }

        .password-strength { height: 4px; background: #f1f5f9; border-radius: 4px; margin-top: 8px; overflow: hidden; }
        .password-strength .fill { height: 100%; border-radius: 4px; transition: width 0.3s, background 0.3s; width: 0; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.5); z-index: 1000; display: none; justify-content: center; align-items: center; backdrop-filter: blur(4px); animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-box { background: #fff; border-radius: var(--radius); padding: 32px; width: 500px; max-width: 90vw; box-shadow: 0 25px 60px rgba(0,0,0,0.15); animation: scaleIn 0.25s ease; }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h3 { font-size: 18px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); transition: var(--transition); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { background: #f1f5f9; color: var(--text-primary); }

        .file-upload { display: flex; align-items: center; gap: 14px; padding: 12px 16px; background: #f8fafc; border: 1.5px dashed var(--border); border-radius: var(--radius-sm); }
        .file-upload input[type="file"] { font-size: 12px; color: var(--text-secondary); }
        .file-upload input[type="file"]::file-selector-button { padding: 6px 14px; border-radius: 6px; border: none; background: #006D69; color: #fff; font-size: 11px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: var(--transition); }
        .file-upload input[type="file"]::file-selector-button:hover { background: #005a56; }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 40px; margin-bottom: 12px; opacity: 0.4; }
        .empty-state p { font-size: 13px; }

        @media (max-width: 1024px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-left { width: 100%; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }

        /* Dark Mode */
        html.dark-mode { --card-bg: #1e293b; --body-bg: #0f172a; --border: #334155; --text-primary: #f1f5f9; --text-secondary: #94a3b8; --text-muted: #64748b; }
        html.dark-mode body { background: #0f172a; color: #e2e8f0; }
        html.dark-mode .sidebar { background: #1e293b; }
        html.dark-mode .sidebar-brand { border-bottom-color: rgba(255,255,255,0.06); }
        html.dark-mode .menu-item a { color: rgba(255,255,255,0.55); }
        html.dark-mode .menu-item a:hover, html.dark-mode .menu-item.active a { background: #334155; color: #fff; }
        html.dark-mode .menu-item.active a { background: rgba(255,215,0,0.08); color: #FFD700; }
        html.dark-mode .sidebar-footer { border-top-color: rgba(255,255,255,0.08); }
        html.dark-mode .top-header { background: rgba(30,41,59,0.8); border-bottom-color: #334155; }
        html.dark-mode .top-header h1 { color: #f1f5f9; }
        html.dark-mode .card { background: #1e293b; border-color: #334155; }
        html.dark-mode .card-header { border-bottom-color: #334155; }
        html.dark-mode .card-header h3 { color: #f1f5f9; }
        html.dark-mode .profile-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-color: #334155; }
        html.dark-mode .avatar-wrapper { border-color: #1e293b; }
        html.dark-mode .avatar-badge { border-color: #1e293b; }
        html.dark-mode .stat-item { background: rgba(255,255,255,0.03); border-color: #334155; }
        html.dark-mode .stat-number { color: #5eead4; }
        html.dark-mode .stat-label { color: #94a3b8; }
        html.dark-mode .msg-success { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
        html.dark-mode .msg-error { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.2); }
        html.dark-mode input[type="text"], html.dark-mode input[type="email"], html.dark-mode input[type="password"], html.dark-mode input[type="file"], html.dark-mode select, html.dark-mode textarea {
            background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
        }
        html.dark-mode ::placeholder { color: #64748b; }
        html.dark-mode label { color: #94a3b8; }
        html.dark-mode .file-upload { background: #1e293b; border-color: #475569; }
        html.dark-mode .badge-approved { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-rejected { background: rgba(220,38,38,0.15); color: #f87171; }
        html.dark-mode .badge-pending { background: rgba(245,158,11,0.15); color: #fbbf24; }
        html.dark-mode .badge-submitted { background: rgba(37,99,235,0.15); color: #60a5fa; }
        html.dark-mode .modal-overlay { background: rgba(15,23,42,0.7); }
        html.dark-mode .modal { background: #1e293b; border-color: #334155; }
        html.dark-mode .modal-header { border-bottom-color: #334155; }
        html.dark-mode .modal-header h3 { color: #f1f5f9; }
        html.dark-mode .close-btn { color: #94a3b8; }
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

<div class="workspace overflow-hidden">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Admin Profile'; include 'header.php'; ?>

    <div class="dashboard-body">
        <div class="profile-alerts">
            <?php if ($msg): ?>
                <div class="msg <?php echo strpos($msg, 'success') !== false ? 'msg-success' : 'msg-error'; ?>">
                    <span><?php echo strpos($msg, 'success') !== false ? '✓' : '✕'; ?></span>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>i
        </div>

        <div style="max-width:700px;">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>Edit Profile & Security</h3>
                        <p class="card-subtitle">Update your personal information and password</p>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label>Profile Photo</label>
                        <div class="file-upload">
                            <?php if (!empty($admin_data['profile_image']) && file_exists('../uploads/profile_pics/' . $admin_data['profile_image'])): ?>
                                <img src="../uploads/profile_pics/<?php echo $admin_data['profile_image']; ?>" alt="" style="width:44px;height:44px;border-radius:12px;object-fit:cover;border:2px solid var(--border);flex-shrink:0;">
                            <?php else: ?>
                                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#006D69,#003D3B);display:flex;align-items:center;justify-content:center;color:#FFD700;font-weight:700;font-size:18px;flex-shrink:0;"><?php echo strtoupper(substr($admin_data['name'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                        </div>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-input" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-input" placeholder="Enter new password" oninput="checkStrength(this.value)">
                        <div class="password-strength"><div class="fill" id="strengthFill"></div></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Save All Changes</button>
                </form>
            </div>
        </div>
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
</script>
</body>
</html>
<?php $conn->close(); ?>
