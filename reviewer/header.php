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
        background: #fff;
        padding: 14px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .top-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; color: var(--text-primary); }
    .top-header .sub { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
    .header-actions { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

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
    .language-switch a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.1); }
    .language-switch a.active-lang {
        color: #006D69;
        background: #FFD700;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(255,215,0,0.3);
    }
    .language-switch span { color: rgba(255,255,255,0.2); font-size: 12px; }

    /* Profile Dropdown */
    .profile-dropdown { position: relative; }
    .profile-link {
        display: flex; align-items: center; gap: 10px;
        padding: 6px 12px 6px 6px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid var(--border);
        border-radius: 40px;
        text-decoration: none;
        transition: var(--transition);
    }
    .profile-link:hover { background: #fff; border-color: var(--sidebar-bg); box-shadow: var(--shadow); }
    .profile-image {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #006D69 0%, #004D4A 100%);
        display: flex; align-items: center; justify-content: center;
        color: #FFD700; font-weight: 700; font-size: 14px;
        overflow: hidden; border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }
    .profile-image img { width: 100%; height: 100%; object-fit: cover; }
    .profile-info { text-align: left; }
    .profile-name { font-size: 13px; font-weight: 600; color: var(--text-primary); line-height: 1.2; }
    .profile-role { font-size: 11px; color: var(--text-secondary); font-weight: 400; }

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

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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
    <div>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <span class="sub"><?php echo date("l, F j, Y"); ?></span>
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
