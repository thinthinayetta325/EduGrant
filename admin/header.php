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
<div class="top-header">
    <div>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <span class="sub"><?php echo date("l, F j, Y"); ?></span>
    </div>

    <div class="header-actions">
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

        <!-- <form class="header-search" action="search.php" method="GET">
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
        </form> -->

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
