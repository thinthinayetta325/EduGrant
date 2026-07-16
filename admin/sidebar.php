<?php
$current_page = $current_page ?? 'dashboard';
$total_schemes = $total_schemes ?? 0;
$total_apps = $total_apps ?? 0;
$pending_bank = $pending_bank ?? 0;
$lang_param = $lang_param ?? 'en';
$is_mm = $is_mm ?? false;
$admin_image = $_SESSION['admin_image'] ?? null;

// Query unread contact messages count directly
$unread_messages = 0;
if (isset($conn) && $conn instanceof mysqli) {
    $msg_result = $conn->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
    if ($msg_result) {
        $unread_messages = $msg_result->fetch_assoc()['cnt'] ?? 0;
    }
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon" style="background:transparent;padding:0;">
            <svg class="w-8 h-8" style="color:#FFD700;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
        </div>
        <div class="brand-text">
            <h2>EduGrant</h2>
            <p>Admin Panel</p>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-label">Main</li>
        <li class="menu-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><a href="dashboard.php?lang=<?php echo $lang_param; ?>"><span class="icon">📊</span> <?php echo $sidebar_lang['dashboard'] ?? 'Dashboard'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'schemes' ? 'active' : ''; ?>"><a href="schemes.php?lang=<?php echo $lang_param; ?>"><span class="icon">📋</span> <?php echo $sidebar_lang['schemes'] ?? 'Schemes'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'reviewers' ? 'active' : ''; ?>"><a href="reviewers.php?lang=<?php echo $lang_param; ?>"><span class="icon">👥</span> <?php echo $sidebar_lang['reviewers'] ?? 'Reviewers'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'applications' ? 'active' : ''; ?>"><a href="applications.php?lang=<?php echo $lang_param; ?>"><span class="icon">📁</span> <?php echo $sidebar_lang['applications'] ?? 'Applications'; ?><?php if ($total_apps > 0): ?><span class="badge-count"><?php echo $total_apps; ?></span><?php endif; ?></a></li>
        <li class="menu-label">Finance</li>
        <li class="menu-item <?php echo $current_page === 'bank_verify' ? 'active' : ''; ?>"><a href="bank_verify.php?lang=<?php echo $lang_param; ?>"><span class="icon">🏦</span> <?php echo $sidebar_lang['bank_verify'] ?? 'Bank Verifications'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'recipients' ? 'active' : ''; ?>"><a href="recipients.php?lang=<?php echo $lang_param; ?>"><span class="icon">🏅</span> <?php echo $sidebar_lang['recipients'] ?? 'Recipients'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'disbursements' ? 'active' : ''; ?>"><a href="disbursements.php?lang=<?php echo $lang_param; ?>"><span class="icon">💰</span> <?php echo $sidebar_lang['disbursements'] ?? 'Disbursements'; ?></a></li>
        <li class="menu-label">Analytics</li>
        <li class="menu-item <?php echo $current_page === 'reports' ? 'active' : ''; ?>"><a href="reports.php?lang=<?php echo $lang_param; ?>"><span class="icon">📈</span> <?php echo $sidebar_lang['reports'] ?? 'Reports'; ?></a></li>
        <li class="menu-label">Communication</li>
        <li class="menu-item <?php echo $current_page === 'messages' ? 'active' : ''; ?>"><a href="messages.php?lang=<?php echo $lang_param; ?>"><span class="icon">✉️</span> <?php echo $sidebar_lang['messages'] ?? 'Messages'; ?><?php if ($unread_messages > 0): ?><span class="badge-count"><?php echo $unread_messages; ?></span><?php endif; ?></a></li>
    </ul>
    <div class="sidebar-footer border-spacing-4 rounded-xl">
        <a href="../auth/logout.php" class="logout-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span><?php echo $sidebar_lang['logout'] ?? 'Logout'; ?></span>
        </a>
    </div>
</div>
