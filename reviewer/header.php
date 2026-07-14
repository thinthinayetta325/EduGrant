<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

$page_title = $page_title ?? 'Reviewer Workspace';
$reviewer_name = $_SESSION['reviewer_name'] ?? 'Reviewer';
$reviewer_id = $_SESSION['reviewer_id'] ?? 0;
$reviewer_img = null;
$unread_count = 0;

// Fetch unread notification count for reviewer
if ($reviewer_id && isset($conn)) {
    $notif_q = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE reviewer_id = ? AND is_read = 0");
    if ($notif_q) {
        $notif_q->bind_param("i", $reviewer_id);
        $notif_q->execute();
        $unread_count = $notif_q->get_result()->fetch_assoc()['unread'] ?? 0;
        $notif_q->close();
    }
}

if ($reviewer_id && isset($conn)) {
    $pic_query = $conn->prepare("SELECT profile_image FROM reviewers WHERE id = ?");
    if ($pic_query) {
        $pic_query->bind_param("i", $reviewer_id);
        $pic_query->execute();
        $pic_result = $pic_query->get_result()->fetch_assoc();
        $reviewer_img = $pic_result['profile_image'] ?? null;
        $pic_query->close();
    }
}
?>
<script>if(localStorage.getItem('reviewer_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
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

    /* Top Header */
    .top-header {
        background: #006D69;
        padding: 14px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .top-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; color: #fff; }
    .top-header .sub { font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 400; }
    .header-actions { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

    /* Language Switch */
    .language-switch {
        display: flex;
        align-items: center;
        background: #003D3B;
        border-radius: 8px;
        padding: 3px;
        gap: 2px;
        border: 1px solid rgba(255,255,255,0.1);
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
    .language-switch a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.1); }
    .language-switch a.active-lang {
        color: #006D69;
        background: #FFD700;
        font-weight: 700;
    }
    .language-switch span { color: rgba(255,255,255,0.2); font-size: 12px; }

    /* Profile Dropdown */
    .profile-dropdown { position: relative; }
    .profile-link {
        display: flex; align-items: center; gap: 10px;
        padding: 6px 12px 6px 6px;
        background: #003D3B;
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 40px;
        text-decoration: none;
        transition: var(--transition);
    }
    .profile-link:hover { background: #004D4A; border-color: rgba(255,255,255,0.25); }
    .profile-image {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #006D69;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 14px;
        overflow: hidden; border: 2px solid rgba(255,255,255,0.2);
        flex-shrink: 0;
    }
    .profile-image img { width: 100%; height: 100%; object-fit: cover; }
    .profile-info { text-align: left; }
    .profile-name { font-size: 13px; font-weight: 600; color: #fff; line-height: 1.2; }
    .profile-role { font-size: 11px; color: rgba(255,255,255,0.6); font-weight: 400; }

    .profile-dropdown-menu {
        position: absolute; top: calc(100% + 8px); right: 0;
        background: #fff; border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        min-width: 200px;
        opacity: 0; visibility: hidden; transform: translateY(-8px);
        transition: var(--transition);
        z-index: 1000; overflow: hidden;
    }
    .profile-dropdown:hover .profile-dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
    .profile-dropdown-menu a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 16px; font-size: 13px;
        color: var(--text-primary); text-decoration: none;
        transition: var(--transition);
    }
    .profile-dropdown-menu a:hover { background: var(--body-bg); }
    .profile-dropdown-menu .menu-icon { width: 20px; text-align: center; color: var(--text-secondary); }
    .profile-dropdown-menu hr { border: none; border-top: 1px solid var(--border); margin: 4px 0; }
    .profile-dropdown-menu a.logout-link { color: #dc2626; }
    .profile-dropdown-menu a.logout-link:hover { background: #fef2f2; }

    /* Notification Bell */
    .notif-btn {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #003D3B;
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        transition: var(--transition);
    }
    .notif-btn:hover { background: #004D4A; color: #fff; }
    .notif-count {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(239,68,68,0.3);
        line-height: 1;
    }
    .notif-count.zero { display: none; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Theme Toggle */
    .theme-toggle {
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        background: #003D3B;
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.7);
        cursor: pointer; transition: var(--transition);
    }
    .theme-toggle:hover { background: #004D4A; color: #fff; }
    .theme-toggle .icon-sun, .theme-toggle .icon-moon { display: none; }
    html.dark-mode .theme-toggle .icon-sun { display: block; }
    html:not(.dark-mode) .theme-toggle .icon-moon { display: block; }

    /* Dark Mode */
    html.dark-mode {
        --card-bg: #1e293b;
        --body-bg: #0f172a;
        --border: #334155;
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --text-muted: #64748b;
        --shadow: 0 1px 3px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.3);
        --shadow-lg: 0 10px 25px rgba(0,0,0,0.3), 0 4px 10px rgba(0,0,0,0.2);
        color-scheme: dark;
    }
    html.dark-mode body { background: var(--body-bg); color: var(--text-primary); }
    html.dark-mode .top-header { background: #1e293b; }
    html.dark-mode .profile-link { background: #334155; border-color: #475569; }
    html.dark-mode .profile-link:hover { background: #475569; }
    html.dark-mode .profile-dropdown-menu { background: #1e293b; border-color: #334155; }
    html.dark-mode .profile-dropdown-menu a:hover { background: #334155; }
    html.dark-mode .profile-dropdown-menu hr { border-top-color: #334155; }
    html.dark-mode .notif-btn { background: #334155; border-color: #475569; }
    html.dark-mode .notif-btn:hover { background: #475569; border-color: #006D69; }
    html.dark-mode .language-switch { background: #334155; border-color: #475569; }
    html.dark-mode .theme-toggle { background: #334155; border-color: #475569; }
    html.dark-mode .theme-toggle:hover { background: #475569; border-color: #006D69; }
    html.dark-mode .card { background: #1e293b; border-color: #334155; }
    html.dark-mode .card-title { color: #f1f5f9; }
    html.dark-mode .card-subtitle { color: #94a3b8; }
    html.dark-mode .form-input { background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9; }
    html.dark-mode .form-input:focus { background: rgba(255,255,255,0.07); }
    html.dark-mode ::placeholder { color: #64748b; }
    html.dark-mode hr { border-top-color: #334155; }

    /* Responsive */
    @media (max-width: 768px) {
        .top-header { padding: 10px 16px; flex-wrap: wrap; gap: 8px; }
        .top-header h1 { font-size: 15px; }
        .top-header .sub { font-size: 11px; }
        .header-actions { gap: 8px; }
        .language-switch a { padding: 5px 8px; font-size: 10px; }
        .profile-info { display: none; }
        .profile-link { padding: 4px; }
        .profile-image { width: 32px; height: 32px; }
    }
</style>

<div class="top-header">
    <div style="display:flex;align-items:center;gap:14px;">
        <a href="dashboard.php?lang=<?php echo $lang_param; ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <svg width="32" height="32" style="color:#FFD700;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
            <div>
                <div style="font-size:16px;font-weight:700;color:#fff;line-height:1.2;">EduGrant</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:400;">Reviewer</div>
            </div>
        </a>
        <div>
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <span class="sub"><?php echo date("l, F j, Y"); ?></span>
        </div>
    </div>

    <div class="header-actions">
        <div class="language-switch">
            <?php $clean_get = array_diff_key($_GET, ['lang' => '']); ?>
            <a href="?lang=en<?php echo !empty($clean_get) ? '&' . http_build_query($clean_get) : ''; ?>"
               class="<?php echo !$is_mm ? 'active-lang' : ''; ?>">
                ENG
            </a>
            <span>|</span>
            <a href="?lang=mm<?php echo !empty($clean_get) ? '&' . http_build_query($clean_get) : ''; ?>"
               class="<?php echo $is_mm ? 'active-lang' : ''; ?>">
                မြန်မာ
            </a>
        </div>

        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode">
            <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>

        <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="notif-btn" title="Notifications">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notif-count <?php echo $unread_count === 0 ? 'zero' : ''; ?>"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
        </a>

        <div class="profile-dropdown">
            <a href="profile.php?lang=<?php echo $lang_param; ?>" class="profile-link">
                <div class="profile-image">
                    <?php if (!empty($reviewer_img) && file_exists("../uploads/profile_pics/" . $reviewer_img)): ?>
                        <img src="../uploads/profile_pics/<?php echo htmlspecialchars($reviewer_img); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($reviewer_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($reviewer_name); ?></div>
                    <div class="profile-role">Reviewer</div>
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
                <a href="dashboard.php?lang=<?php echo $lang_param; ?>">
                    <span class="menu-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </span>
                    Dashboard
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
<script>
function toggleTheme() {
    document.documentElement.classList.toggle('dark-mode');
    localStorage.setItem('reviewer_theme', document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
}
</script>
