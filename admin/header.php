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

// Fetch unread admin notification count
if (isset($_SESSION['admin_id']) && isset($conn)) {
    $admin_id = $_SESSION['admin_id'];
    $notif_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE admin_id = ? AND is_read = 0");
    if ($notif_query) {
        $notif_query->bind_param("i", $admin_id);
        $notif_query->execute();
        $notif_result = $notif_query->get_result()->fetch_assoc();
        $unread_count = $notif_result['unread'] ?? 0;
        $notif_query->close();
    }
}

if (!isset($pending_bank_count)) {
    $pending_bank_count = $pending_bank;
}

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
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<script>if(sessionStorage.getItem('scrollPos')){window.addEventListener('load',function(){setTimeout(function(){window.scrollTo(0,parseInt(sessionStorage.getItem('scrollPos')));sessionStorage.removeItem('scrollPos')},50)})}</script>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<div class="top-header">
    <div class="header-left">
        <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <div>
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <span class="sub"><?php echo date("l, F j, Y"); ?></span>
        </div>
    </div>

    <div class="header-actions">
        <div class="language-switch">
            <a href="?lang=en&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>"
               onclick="sessionStorage.setItem('scrollPos',window.scrollY)"
               class="<?php echo !$is_mm ? 'active-lang' : ''; ?>">
                ENG
            </a>
            <span>|</span>
            <a href="?lang=mm&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>"
               onclick="sessionStorage.setItem('scrollPos',window.scrollY)"
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
            <?php if ($unread_count > 0): ?>
                <span class="notif-count"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
            <?php endif; ?>
        </a>

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
<style>
    .header-left { display: flex; align-items: center; gap: 12px; }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 90;
        backdrop-filter: blur(2px);
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active { display: block; }

    .theme-toggle {
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid var(--border); color: var(--text-secondary);
        cursor: pointer; transition: 0.2s ease;
    }
    .theme-toggle:hover { background: #fff; border-color: var(--sidebar-bg); color: var(--sidebar-bg); }
    .theme-toggle .icon-sun, .theme-toggle .icon-moon { display: none; }
    html.dark-mode .theme-toggle .icon-sun { display: block; }
    html:not(.dark-mode) .theme-toggle .icon-moon { display: block; }

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
    html.dark-mode .top-header { background: rgba(30,41,59,0.8); border-bottom-color: #334155; }
    html.dark-mode .profile-link { background: #334155; border-color: #475569; }
    html.dark-mode .profile-link:hover { background: #475569; }
    html.dark-mode .profile-dropdown-menu { background: #1e293b; border-color: #334155; }
    html.dark-mode .profile-dropdown-menu a:hover { background: #334155; }
    html.dark-mode .profile-dropdown-menu hr { border-top-color: #334155; }
    html.dark-mode .notif-btn { background: #334155; border-color: #475569; }
    html.dark-mode .notif-btn:hover { background: #475569; border-color: #006D69; }
    html.dark-mode .language-switch { background: linear-gradient(135deg, #334155, #1e293b); border-color: #475569; }
    html.dark-mode .theme-toggle { background: #334155; border-color: #475569; }
    html.dark-mode .theme-toggle:hover { background: #475569; border-color: #006D69; }
    html.dark-mode .sidebar { background: #1e293b; }
    html.dark-mode .menu-item a { color: rgba(255,255,255,0.55); }
    html.dark-mode .menu-item a:hover { background: #334155; color: #fff; }
    html.dark-mode .menu-item.active a { background: rgba(255,215,0,0.08); color: #FFD700; }
    html.dark-mode .card { background: #1e293b; border-color: #334155; }
    html.dark-mode .form-input { background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9; }
    html.dark-mode .form-input:focus { background: rgba(255,255,255,0.07); }
    html.dark-mode .btn-outline { border-color: #475569; color: #94a3b8; }
    html.dark-mode .msg-success { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
    html.dark-mode .msg-error { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.2); }
    html.dark-mode table { color: #e2e8f0; }
    html.dark-mode thead { background: #1e293b; }
    html.dark-mode tbody tr { border-color: #334155; }
    html.dark-mode tbody tr:hover { background: rgba(255,255,255,0.03); }
    html.dark-mode .notif-row { border-color: #334155; }
    html.dark-mode .notif-row:hover { background: rgba(255,255,255,0.03); }
    html.dark-mode .notif-unread { background: rgba(16,185,129,0.08); }
    html.dark-mode input[type="text"], html.dark-mode input[type="email"], html.dark-mode input[type="password"], html.dark-mode select, html.dark-mode textarea {
        background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
    }
    html.dark-mode ::placeholder { color: #64748b; }
    html.dark-mode .file-upload { background: #1e293b; border-color: #475569; }
</style>
<script>
function toggleTheme() {
    document.documentElement.classList.toggle('dark-mode');
    localStorage.setItem('admin_theme', document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
}
function toggleSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}
</script>
