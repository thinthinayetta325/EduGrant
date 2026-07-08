<?php
session_start();

if (!isset($_SESSION['reviewer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$reviewer_id = (int)$_SESSION['reviewer_id'];
$reviewer_name = $_SESSION['reviewer_name'] ?? 'Reviewer';

$check_col = $conn->query("SHOW COLUMNS FROM reviewers LIKE 'profile_image'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE reviewers ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
}

$msg = '';
$msg_type = '';

$reviewer_data = $conn->query("SELECT * FROM reviewers WHERE id = $reviewer_id")->fetch_assoc();
$reviewer_img = $reviewer_data['profile_image'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $conn->query("UPDATE reviewers SET name='$name', email='$email' WHERE id=$reviewer_id");
        $_SESSION['reviewer_name'] = $name;
        $reviewer_data['name'] = $name;
        $reviewer_data['email'] = $email;
        $msg = 'Profile updated successfully.';
        $msg_type = 'success';

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/profile_pics/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $filename = 'reviewer_' . $reviewer_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    if (!empty($reviewer_data['profile_image'])) {
                        $old_file = $upload_dir . $reviewer_data['profile_image'];
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    $conn->query("UPDATE reviewers SET profile_image = '$filename' WHERE id = $reviewer_id");
                    $reviewer_data['profile_image'] = $filename;
                    $reviewer_img = $filename;
                    $msg = 'Profile and image updated successfully.';
                } else {
                    $msg = 'Profile updated but image upload failed.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'Profile updated but invalid image type.';
                $msg_type = 'error';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/profile_pics/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $filename = 'reviewer_' . $reviewer_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    if (!empty($reviewer_data['profile_image'])) {
                        $old_file = $upload_dir . $reviewer_data['profile_image'];
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    $conn->query("UPDATE reviewers SET profile_image = '$filename' WHERE id = $reviewer_id");
                    $reviewer_data['profile_image'] = $filename;
                    $reviewer_img = $filename;
                    $msg = 'Profile image updated successfully.';
                    $msg_type = 'success';
                } else {
                    $msg = 'Failed to upload image.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'Invalid file type.';
                $msg_type = 'error';
            }
        } else {
            $msg = 'No file selected or upload error.';
            $msg_type = 'error';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if (password_verify($current, $reviewer_data['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 6) {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $conn->query("UPDATE reviewers SET password='$hash' WHERE id=$reviewer_id");
                    $msg = 'Password changed successfully.';
                    $msg_type = 'success';
                } else {
                    $msg = 'New password must be at least 6 characters.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'New passwords do not match.';
                $msg_type = 'error';
            }
        } else {
            $msg = 'Current password is incorrect.';
            $msg_type = 'error';
        }
    }
}

$total_reviewed = $conn->query("SELECT COUNT(*) FROM application_reviews WHERE reviewer_id = $reviewer_id")->fetch_row()[0] ?? 0;
$total_apps = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | EduGrant</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; color: #fff; transition: background 0.3s; }
        body.light-mode { background: #f1f5f9; color: #1e293b; }

        .bg-mesh {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(0,109,105,0.35) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(16,185,129,0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(6,182,212,0.1) 0%, transparent 50%),
                #0f172a;
        }
        body.light-mode .bg-mesh { display: none; }

        .glass {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.07);
        }
        .glass-strong {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        body.light-mode .glass { background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        body.light-mode .glass-strong { background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }

        .theme-toggle { position: relative; width: 52px; height: 28px; border-radius: 14px; cursor: pointer; transition: background 0.3s; background: #006D69; }
        .theme-toggle .toggle-thumb { position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #0f172a; transition: transform 0.3s, background 0.3s; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        body.light-mode .theme-toggle .toggle-thumb { transform: translateX(24px); background: #fff; }

        /* Header */
        .top-bar {
            position: sticky; top: 0; z-index: 50;
            padding: 14px 32px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(15,23,42,0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        }
        body.light-mode .top-bar { background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .brand { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .brand-icon {
            width: 40px; height: 40px; border-radius: 12px;
            background: linear-gradient(135deg, #006D69, #008B86);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 800; color: #fff;
        }
        .brand-text h1 { font-size: 18px; font-weight: 700; color: #fff; }
        .brand-text span { font-size: 11px; color: rgba(255,255,255,0.35); font-weight: 500; letter-spacing: 0.5px; }

        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .nav-link {
            padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 500;
            color: rgba(255,255,255,0.55); text-decoration: none; transition: all 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-link.active { background: rgba(0,109,105,0.15); color: #10b981; }
        .nav-link img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.12); }
        .nav-link .avatar-fallback {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #006D69, #008B86);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff;
        }
        .btn-logout {
            padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 500;
            color: #fca5a5; text-decoration: none; transition: all 0.2s;
            border: 1px solid rgba(252,165,165,0.18); background: rgba(252,165,165,0.06);
        }
        .btn-logout:hover { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #f87171; }

        /* Main layout */
        .page-wrapper { position: relative; z-index: 1; padding: 0 24px 60px; }

        /* Hero section */
        .hero {
            max-width: 960px; margin: 0 auto; padding: 48px 0 24px;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .avatar-ring {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, #006D69, #008B86, #8b5cf6);
            padding: 4px; cursor: pointer; position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .avatar-ring:hover { transform: scale(1.05); box-shadow: 0 0 40px rgba(0,109,105,0.3); }
        .avatar-ring .avatar-inner {
            width: 100%; height: 100%; border-radius: 50%; background: #0f172a;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .avatar-ring img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .avatar-ring .avatar-letter {
            font-size: 42px; font-weight: 800;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .avatar-edit-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,0.55); display: flex; flex-direction: column;
            align-items: center; justify-content: center; opacity: 0;
            transition: opacity 0.3s; backdrop-filter: blur(4px);
        }
        .avatar-ring:hover .avatar-edit-overlay { opacity: 1; }
        .avatar-edit-overlay svg { width: 24px; height: 24px; color: #fff; }
        .avatar-edit-overlay span { font-size: 10px; color: rgba(255,255,255,0.8); margin-top: 4px; font-weight: 500; }

        .hero-name { font-size: 28px; font-weight: 800; color: #fff; margin-top: 20px; letter-spacing: -0.5px; }
        .hero-role {
            display: inline-flex; align-items: center; gap: 6px; margin-top: 10px;
            padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;
            background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2);
        }
        .hero-email { font-size: 14px; color: rgba(255,255,255,0.4); margin-top: 8px; }
        .hero-id { font-size: 12px; color: rgba(255,255,255,0.25); margin-top: 4px; font-family: monospace; }

        /* Stats row */
        .stats-row {
            max-width: 960px; margin: 0 auto 32px;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
        }
        .stat-card {
            border-radius: 16px; padding: 24px 20px; text-align: center;
            transition: all 0.3s; cursor: default;
        }
        .stat-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.07); }
        .stat-num { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #10b981, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Content grid */
        .content-grid {
            max-width: 960px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }

        .card {
            border-radius: 20px; padding: 28px; transition: all 0.3s;
        }
        .card:hover { background: rgba(255,255,255,0.07); }
        .card-full { grid-column: 1 / -1; }
        .card-title {
            font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px;
            display: flex; align-items: center; gap: 10px;
        }
        .card-title .icon-box {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .icon-green { background: rgba(16,185,129,0.12); color: #10b981; }
        .icon-blue { background: rgba(6,182,212,0.12); color: #06b6d4; }
        .icon-purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .card-subtitle { font-size: 13px; color: rgba(255,255,255,0.35); margin-bottom: 20px; }

        /* Form elements */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 600;
            color: rgba(255,255,255,0.4); margin-bottom: 8px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-input {
            width: 100%; padding: 12px 16px; border-radius: 12px;
            font-size: 14px; font-family: inherit; outline: none;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
            color: #fff; transition: all 0.25s;
        }
        .form-input::placeholder { color: rgba(255,255,255,0.2); }
        .form-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); background: rgba(255,255,255,0.07); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* File upload */
        .upload-zone {
            display: flex; align-items: center; gap: 16px; padding: 16px;
            border-radius: 14px; background: rgba(255,255,255,0.03);
            border: 2px dashed rgba(255,255,255,0.08); transition: all 0.25s;
        }
        .upload-zone:hover { border-color: rgba(16,185,129,0.3); background: rgba(255,255,255,0.05); }
        .upload-preview {
            width: 56px; height: 56px; border-radius: 14px; overflow: hidden;
            background: rgba(255,255,255,0.04); display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .upload-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-preview .preview-letter {
            font-size: 22px; font-weight: 700;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .upload-text { flex: 1; }
        .upload-text p { font-size: 13px; color: rgba(255,255,255,0.55); }
        .upload-text span { font-size: 11px; color: rgba(255,255,255,0.28); }
        .upload-input { font-size: 12px; }
        .upload-input::file-selector-button {
            padding: 8px 16px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #006D69, #008B86);
            color: #fff; font-size: 12px; font-weight: 600; cursor: pointer;
            margin-right: 10px; transition: all 0.2s;
        }
        .upload-input::file-selector-button:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,109,105,0.3); }

        /* Buttons */
        .btn-submit {
            padding: 13px 28px; border-radius: 12px; border: none;
            font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: all 0.25s; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #006D69, #008B86);
            color: #fff; box-shadow: 0 4px 16px rgba(0,109,105,0.25);
        }
        .btn-gradient:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,109,105,0.35); }

        /* Password strength */
        .strength-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; transition: all 0.3s; width: 0; }
        .strength-text { font-size: 11px; margin-top: 6px; color: rgba(255,255,255,0.3); }

        /* Alert */
        .alert {
            max-width: 960px; margin: 0 auto 24px;
            padding: 14px 20px; border-radius: 14px;
            font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            animation: slideDown 0.4s ease;
        }
        .alert-success { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .alert-error { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }

        /* Back link */
        .back-link {
            max-width: 960px; margin: 32px auto 0;
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; color: rgba(255,255,255,0.3);
            text-decoration: none; transition: color 0.2s;
        }
        .back-link:hover { color: #10b981; }
        .back-link svg { width: 16px; height: 16px; }
        body.light-mode .back-link { color: #64748b; }
        body.light-mode .back-link:hover { color: #006D69; }

        /* Light mode overrides */
        body.light-mode .hero-name { color: #0f172a; }
        body.light-mode .hero-email { color: #64748b; }
        body.light-mode .hero-id { color: #94a3b8; }
        body.light-mode .hero-role { background: rgba(0,109,105,0.08); color: #006D69; border: 1px solid rgba(0,109,105,0.15); }
        body.light-mode .card-title { color: #0f172a; }
        body.light-mode .card-subtitle { color: #94a3b8; }
        body.light-mode .form-label { color: #64748b; }
        body.light-mode .form-input { background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b; }
        body.light-mode .form-input::placeholder { color: #94a3b8; }
        body.light-mode .form-input:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.1); background: #fff; }
        body.light-mode .stat-num { background: linear-gradient(135deg, #006D69, #008B86); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        body.light-mode .stat-label { color: #94a3b8; }
        body.light-mode .avatar-ring .avatar-letter { background: linear-gradient(135deg, #006D69, #008B86); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        body.light-mode .avatar-ring .avatar-inner { background: #fff; }
        body.light-mode .strength-bar { background: #e2e8f0; }
        body.light-mode .strength-text { color: #94a3b8; }
        body.light-mode .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        body.light-mode .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Responsive */
        @media (max-width: 768px) {
            .top-bar { padding: 12px 16px; }
            .page-wrapper { padding: 0 16px 40px; }
            .hero { padding: 32px 0 16px; }
            .avatar-ring { width: 100px; height: 100px; }
            .avatar-ring .avatar-letter { font-size: 34px; }
            .hero-name { font-size: 22px; }
            .stats-row { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .stat-card { padding: 16px 12px; }
            .stat-num { font-size: 24px; }
            .content-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>

    <!-- Header -->
    <nav class="top-bar">
        <a href="dashboard.php" class="brand">
            <div class="brand-icon">E</div>
            <div class="brand-text">
                <h1>EduGrant</h1>
                <span>REVIEWER PORTAL</span>
            </div>
        </a>
        <div class="nav-actions">
            <div class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                <div class="toggle-thumb">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                    <svg class="w-3.5 h-3.5 text-blue-400 hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                </div>
            </div>
            <a href="profile.php" class="nav-link active">
                <?php if (!empty($reviewer_img) && file_exists('../uploads/profile_pics/' . $reviewer_img)): ?>
                    <img src="../uploads/profile_pics/<?php echo htmlspecialchars($reviewer_img); ?>" alt="">
                <?php else: ?>
                    <div class="avatar-fallback"><?php echo strtoupper(substr($reviewer_name, 0, 1)); ?></div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($reviewer_name); ?></span>
            </a>
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="page-wrapper">
        <!-- Alert -->
        <?php if ($msg): ?>
            <div class="alert <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $msg_type === 'success' ? '&#10003;' : '&#10007;'; ?></span>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Hero Profile -->
        <div class="hero">
            <div class="avatar-ring" onclick="document.getElementById('avatarInput').click()">
                <div class="avatar-inner">
                    <?php if (!empty($reviewer_data['profile_image']) && file_exists('../uploads/profile_pics/' . $reviewer_data['profile_image'])): ?>
                        <img src="../uploads/profile_pics/<?php echo htmlspecialchars($reviewer_data['profile_image']); ?>" alt="">
                    <?php else: ?>
                        <span class="avatar-letter"><?php echo strtoupper(substr($reviewer_data['name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="avatar-edit-overlay">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                    </svg>
                    <span>Change Photo</span>
                </div>
                <form method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="hidden" name="action" value="upload_image">
                    <input type="file" name="profile_image" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="this.form.submit()">
                </form>
            </div>
            <h2 class="hero-name"><?php echo htmlspecialchars($reviewer_data['name']); ?></h2>
            <div class="hero-role">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Active Reviewer
            </div>
            <p class="hero-email"><?php echo htmlspecialchars($reviewer_data['email']); ?></p>
            <p class="hero-id">ID: #<?php echo $reviewer_data['id']; ?> &middot; <?php echo htmlspecialchars($reviewer_data['department'] ?? 'General'); ?></p>
        </div>

        <!-- Cards -->
        <div class="content-grid">
            <!-- Edit Profile -->
            <div class="card glass-strong">
                <div class="card-title">
                    <div class="icon-box icon-green">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    </div>
                    Edit Profile
                </div>
                <p class="card-subtitle">Update your personal information</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($reviewer_data['name']); ?>" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($reviewer_data['email']); ?>" required class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Photo</label>
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" class="upload-input">
                    </div>
                    <button type="submit" class="btn-submit btn-gradient">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card glass-strong">
                <div class="card-title">
                    <div class="icon-box icon-purple">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    Change Password
                </div>
                <p class="card-subtitle">Keep your account secure</p>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" required class="form-input" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="newPass" required class="form-input" placeholder="Enter new password" oninput="checkStrength(this.value)">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <p class="strength-text" id="strengthText"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" required class="form-input" placeholder="Confirm new password">
                    </div>
                    <button type="submit" class="btn-submit btn-gradient">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Back -->
        <a href="dashboard.php" class="back-link">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to Dashboard
        </a>
    </div>

    <script>
    // Theme toggle
    function toggleTheme() {
        document.body.classList.toggle('light-mode');
        localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
    }

    // Load saved theme (default dark for profile)
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    function checkStrength(val) {
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        let score = 0, label = '';
        if (val.length >= 6) score += 25;
        if (val.length >= 10) score += 25;
        if (/[A-Z]/.test(val)) score += 25;
        if (/\d/.test(val)) score += 25;
        fill.style.width = score + '%';
        if (score < 25) { fill.style.background = '#ef4444'; label = 'Weak'; }
        else if (score < 50) { fill.style.background = '#f59e0b'; label = 'Fair'; }
        else if (score < 75) { fill.style.background = '#10b981'; label = 'Good'; }
        else { fill.style.background = '#059669'; label = 'Strong'; }
        text.textContent = val.length > 0 ? 'Password strength: ' + label : '';
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
