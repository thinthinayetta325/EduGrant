<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

$page_title = $page_title ?? 'Admin Dashboard';
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_image = $_SESSION['admin_image'] ?? null;

$pending_bank = $pending_bank ?? 0;
$unread_count = $unread_count ?? 0;

// Fetch pending bank count if not set
if (!isset($pending_bank_count)) {
    $pending_bank_count = $pending_bank;
}

// Get admin profile image from database
if (empty($admin_image) && isset($_SESSION['admin_id'])) {
    $check_conn = isset($conn) ? $conn : null;
    if ($check_conn) {
        $pic_query = $check_conn->prepare("SELECT profile_image FROM admin WHERE id = ?");
        if ($pic_query) {
            $pic_query->bind_param("i", $_SESSION['admin_id']);
            $pic_query->execute();
            $pic_result = $pic_query->get_result()->fetch_assoc();
            $admin_image = $pic_result['profile_image'] ?? null;
            $pic_query->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
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
            color: var(--text-primary);
        }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }

        /* Top Header Styles */
        .top-header {
            background: #fff;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .top-header h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-primary);
        }
        .top-header .sub {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Language Switch */
        .language-switch {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #006D69 0%, #004D4A 100%);
            border-radius: 8px;
            padding: 3px;
            gap: 2px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 2px 4px rgba(0,109,105,0.2);
        }
        .language-switch a {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
            letter-spacing: 0.3px;
        }
        .language-switch a:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.1);
        }
        .language-switch a.active-lang {
            color: #006D69;
            background: #FFD700;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(255,215,0,0.3);
        }
        .language-switch span {
            color: rgba(255,255,255,0.2);
            font-size: 12px;
        }

        /* Search Box */
        .header-search {
            display: flex;
            align-items: center;
            background: var(--body-bg);
            border-radius: var(--radius-sm);
            padding: 0 14px;
            gap: 8px;
            border: 1px solid transparent;
            transition: var(--transition);
            width: 240px;
        }
        .header-search:focus-within {
            border-color: var(--sidebar-bg);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,109,105,0.1);
            width: 280px;
        }
        .header-search span {
            color: var(--text-muted);
            font-size: 14px;
        }
        .header-search input {
            border: none;
            background: none;
            padding: 10px 0;
            font-size: 13px;
            outline: none;
            width: 100%;
            font-family: inherit;
            color: var(--text-primary);
        }
        .header-search input::placeholder {
            color: var(--text-muted);
        }

        /* Notification Button */
        .notif-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
        }
        .notif-btn:hover {
            background: var(--body-bg);
            border-color: var(--sidebar-bg);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        .notif-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #fff;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .notif-count {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(239,68,68,0.3);
        }

        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
        }
        .profile-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid var(--border);
            border-radius: 40px;
            text-decoration: none;
            transition: var(--transition);
        }
        .profile-link:hover {
            background: #fff;
            border-color: var(--sidebar-bg);
            box-shadow: var(--shadow);
        }
        .profile-image {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #006D69 0%, #004D4A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD700;
            font-weight: 700;
            font-size: 14px;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-info {
            text-align: left;
        }
        .profile-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .profile-role {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Profile Dropdown Menu */
        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
        }
        .profile-dropdown:hover .profile-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .profile-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 13px;
            color: var(--text-primary);
            text-decoration: none;
            transition: var(--transition);
        }
        .profile-dropdown-menu a:hover {
            background: var(--body-bg);
        }
        .profile-dropdown-menu .menu-icon {
            width: 20px;
            text-align: center;
            color: var(--text-secondary);
        }
        .profile-dropdown-menu hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 4px 0;
        }
        .profile-dropdown-menu a.logout-link {
            color: #dc2626;
        }
        .profile-dropdown-menu a.logout-link:hover {
            background: #fef2f2;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="top-header">
    <div>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <span class="sub"><?php echo date("l, F j, Y"); ?></span>
    </div>

    <div class="header-actions">
        <!-- Language Switch -->
        <div class="language-switch">
            <a href="?lang=en&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>"
               class="<?php echo !$is_mm ? 'active-lang' : ''; ?>">
                ENG
            </a>
            <span>|</span>
            <a href="?lang=mm&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>"
               class="<?php echo $is_mm ? 'active-lang' : ''; ?>">
                မြန်မာ
            </a>
        </div>

        <!-- Search Box -->
        <form class="header-search" action="search.php" method="GET">
            <span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </span>
            <input type="text" name="q" placeholder="Search applications, students..."
                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            <?php if ($lang_param): ?>
                <input type="hidden" name="lang" value="<?php echo $lang_param; ?>">
            <?php endif; ?>
        </form>

        <!-- Notification -->
        <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="notif-btn" title="Notifications">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <?php if ($pending_bank_count > 0): ?>
                <span class="notif-count"><?php echo $pending_bank_count > 99 ? '99+' : $pending_bank_count; ?></span>
            <?php endif; ?>
            <?php if ($unread_count > 0): ?>
                <span class="notif-dot"></span>
            <?php endif; ?>
        </a>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown">
            <a href="profile.php?lang=<?php echo $lang_param; ?>" class="profile-link">
                <div class="profile-image">
                    <?php if (!empty($admin_image) && file_exists("../uploads/profile_pics/" . $admin_image)): ?>
                        <img src="../uploads/profile_pics/<?php echo htmlspecialchars($admin_image); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-muted);">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </a>

            <div class="profile-dropdown-menu">
                <a href="profile.php?lang=<?php echo $lang_param; ?>">
                    <span class="menu-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    My Profile
                </a>
                <a href="settings.php?lang=<?php echo $lang_param; ?>">
                    <span class="menu-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </span>
                    Settings
                </a>
                <a href="reports.php?lang=<?php echo $lang_param; ?>">
                    <span class="menu-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                        </svg>
                    </span>
                    Reports
                </a>
                <hr>
                <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="logout-link">
                    <span class="menu-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>
