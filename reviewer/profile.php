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
                    $conn->query("UPDATE reviewers SET profile_image = '$filename' WHERE id=$reviewer_id");
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

        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            if (password_verify($current, $reviewer_data['password'])) {
                if ($new === $confirm) {
                    if (strlen($new) >= 6) {
                        $hash = password_hash($new, PASSWORD_DEFAULT);
                        $conn->query("UPDATE reviewers SET password='$hash' WHERE id=$reviewer_id");
                        $msg = 'Profile and password updated successfully.';
                    } else {
                        $msg = 'Profile updated. New password must be at least 6 characters.';
                        $msg_type = 'error';
                    }
                } else {
                    $msg = 'Profile updated. New passwords do not match.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'Profile updated. Current password is incorrect.';
                $msg_type = 'error';
            }
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
    <style>
        html.dark { color: #fff; }

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
        html.dark .glass { background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        html.dark .glass-strong { background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }

        /* Main layout */
        .page-wrapper {
            position: relative; z-index: 1;
            padding: 0;
            height: calc(100vh - 61px);
            overflow: hidden;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }

        /* Compact profile header */
        .profile-header {
            max-width: 600px; width: 100%; margin: 0 auto; padding: 16px 24px 12px;
            display: flex; align-items: center; gap: 14px;
            flex-shrink: 0;
        }
        .profile-header .avatar-ring {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, #006D69, #008B86, #8b5cf6);
            padding: 2px; cursor: pointer; position: relative;
            transition: transform 0.3s, box-shadow 0.3s; flex-shrink: 0;
        }
        .profile-header .avatar-ring:hover { transform: scale(1.05); box-shadow: 0 0 30px rgba(0,109,105,0.3); }
        .profile-header .avatar-inner {
            width: 100%; height: 100%; border-radius: 50%; background: #0f172a;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        html.dark .profile-header .avatar-inner { background: #1e293b; }
        .profile-header .avatar-inner img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .profile-header .avatar-letter {
            font-size: 22px; font-weight: 800;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .profile-header .avatar-edit-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s; backdrop-filter: blur(4px);
        }
        .profile-header .avatar-ring:hover .avatar-edit-overlay { opacity: 1; }
        .profile-header .avatar-edit-overlay svg { width: 20px; height: 20px; color: #fff; }
        .profile-header .avatar-inner {
            width: 100%; height: 100%; border-radius: 50%; background: #f0f7f5;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        html.dark .profile-header .avatar-inner { background: #1e293b; }
        .profile-header .avatar-inner img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .profile-header .avatar-letter {
            font-size: 22px; font-weight: 800;
            background: linear-gradient(135deg, #006D69, #008B86);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .profile-header-text { min-width: 0; }
        .profile-header-name { font-size: 18px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        html.dark .profile-header-name { color: #fff; }
        .profile-header-role { font-size: 12px; color: #64748b; margin-top: 2px; }
        html.dark .profile-header-role { color: rgba(255,255,255,0.4); }

        /* Content grid */
        .content-grid {
            max-width: 600px; width: 100%; margin: 0 auto; padding: 0 24px 16px;
            display: flex; flex-direction: column; gap: 16px;
            flex-shrink: 1; min-height: 0;
        }

        .card {
            background: #fff; border-radius: 16px; padding: 24px; transition: all 0.3s;
            border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        }
        .card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); transform: translateY(-1px); }
        html.dark .card { background: #1e293b; border: 1px solid rgba(255,255,255,0.08); }
        .card-full { grid-column: 1 / -1; }
        .content-grid hr { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }
        html.dark .content-grid hr { border-top: 1px solid rgba(255,255,255,0.08); }
        .card-title {
            font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;
            display: flex; align-items: center; gap: 10px;
        }
        html.dark .card-title { color: #fff; }
        .card-title .icon-box {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .icon-green { background: linear-gradient(135deg, rgba(0,109,105,0.12), rgba(0,139,134,0.12)); color: #006D69; }
        .icon-blue { background: rgba(6,182,212,0.12); color: #06b6d4; }
        .icon-purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .card-subtitle { font-size: 13px; color: #64748b; margin-bottom: 18px; }
        html.dark .card-subtitle { color: #94a3b8; }

        /* Form elements */
        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 600;
            color: #64748b; margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        html.dark .form-label { color: #94a3b8; }
        .form-input {
            width: 100%; padding: 11px 14px; border-radius: 10px;
            font-size: 14px; font-family: inherit; outline: none;
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            color: #0f172a; transition: all 0.25s;
        }
        html.dark .form-input { background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); color: #fff; }
        .form-input::placeholder { color: #94a3b8; }
        html.dark .form-input::placeholder { color: rgba(255,255,255,0.25); }
        .form-input:focus { border-color: #006D69; box-shadow: 0 0 0 3px rgba(0,109,105,0.1); background: #fff; }
        html.dark .form-input:focus { background: rgba(255,255,255,0.07); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* File upload */
        .upload-input { font-size: 12px; width: 100%; }
        .upload-input::file-selector-button {
            padding: 8px 16px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #006D69, #008B86);
            color: #fff; font-size: 12px; font-weight: 600; cursor: pointer;
            margin-right: 10px; transition: all 0.2s;
        }
        .upload-input::file-selector-button:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,109,105,0.3); }

        /* Buttons */
        .btn-submit {
            padding: 12px 24px; border-radius: 12px; border: none;
            font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: all 0.25s; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #006D69, #008B86);
            color: #fff; box-shadow: 0 4px 16px rgba(0,109,105,0.25);
        }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,109,105,0.35); }
        .btn-gradient:active { transform: translateY(0); }

        /* Password strength */
        .strength-bar { height: 4px; background: #e2e8f0; border-radius: 4px; margin-top: 8px; overflow: hidden; }
        html.dark .strength-bar { background: rgba(255,255,255,0.08); }
        .strength-fill { height: 100%; border-radius: 4px; transition: all 0.3s; width: 0; }
        .strength-text { font-size: 11px; margin-top: 6px; color: #94a3b8; }
        html.dark .strength-text { color: rgba(255,255,255,0.3); }

        /* Alert */
        .alert {
            max-width: 600px; width: 100%; margin: 10px auto 10px; padding: 0 24px;
            flex-shrink: 0;
        }
        .alert > span, .alert {
            padding: 10px 16px; border-radius: 10px;
            font-size: 12px; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            animation: slideDown 0.4s ease; width: 100%;
            box-sizing: border-box;
        }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        html.dark .alert-success { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        html.dark .alert-error { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }

        /* Back link */
        .back-link {
            max-width: 600px; width: 100%; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            font-size: 12px; color: #64748b;
            text-decoration: none; transition: color 0.2s;
            flex-shrink: 0;
        }
        .back-link:hover { color: #006D69; }
        .back-link svg { width: 16px; height: 16px; }
        html.dark .back-link { color: rgba(255,255,255,0.3); }
        html.dark .back-link:hover { color: #10b981; }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header { padding: 12px 16px 8px; }
            .content-grid { padding: 0 16px; }
            .form-row { grid-template-columns: 1fr; }
            .card { padding: 16px; }
        }
    </style>
</head>
<body>

    <?php $page_title = 'My Profile'; include 'header.php'; ?>

    <div class="page-wrapper">
        <!-- Alert -->
        <?php if ($msg): ?>
            <div class="alert <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $msg_type === 'success' ? '&#10003;' : '&#10007;'; ?></span>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Cards -->
        <div class="content-grid">
            <!-- Edit Profile + Change Password (Combined) -->
            <div class="card glass-strong card-full">
                <div class="card-title">
                    <div class="icon-box icon-green">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    </div>
                    Edit Profile & Security
                </div>
                <p class="card-subtitle">Update your personal information and password</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Profile Section -->
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

                    <hr>

                    <!-- Password Section -->
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="newPass" class="form-input" placeholder="Enter new password" oninput="checkStrength(this.value)">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <p class="strength-text" id="strengthText"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password">
                    </div>

                    <button type="submit" class="btn-submit btn-gradient">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Save All Changes
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
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('reviewer_theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }

    // Load saved theme (default light)
    if (localStorage.getItem('reviewer_theme') === 'dark') {
        document.documentElement.classList.add('dark');
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
