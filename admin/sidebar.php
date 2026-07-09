<?php
$current_page = $current_page ?? 'dashboard';
$total_schemes = $total_schemes ?? 0;
$total_apps = $total_apps ?? 0;
$pending_bank = $pending_bank ?? 0;
$lang_param = $lang_param ?? 'en';
$is_mm = $is_mm ?? false;
$admin_image = $_SESSION['admin_image'] ?? null;
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><?php echo strtoupper(substr($admin_name ?? 'A', 0, 1)); ?></div>
        <div class="brand-text">
            <h2>GrantPortal</h2>
            <p>Admin Panel</p>
        </div>
    </div>
    <div class="admin-profile">
        <div class="admin-avatar" style="overflow:hidden;">
            <?php if (!empty($admin_image) && file_exists('../uploads/profile_pics/' . $admin_image)): ?>
                <img src="../uploads/profile_pics/<?php echo $admin_image; ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
            <?php else: ?>
                <?php echo strtoupper(substr($admin_name ?? 'A', 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="admin-meta">
            <h4><?php echo htmlspecialchars($admin_name ?? 'Admin'); ?></h4>
            <p>System Administrator</p>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-label">Main</li>
        <li class="menu-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><a href="dashboard.php?lang=<?php echo $lang_param; ?>"><span class="icon">📊</span> <?php echo $sidebar_lang['dashboard'] ?? 'Dashboard'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'schemes' ? 'active' : ''; ?>"><a href="schemes.php?lang=<?php echo $lang_param; ?>"><span class="icon">📋</span> <?php echo $sidebar_lang['schemes'] ?? 'Schemes'; ?><?php if ($total_schemes > 0): ?><span class="badge-count"><?php echo $total_schemes; ?></span><?php endif; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'reviewers' ? 'active' : ''; ?>"><a href="reviewers.php?lang=<?php echo $lang_param; ?>"><span class="icon">👥</span> <?php echo $sidebar_lang['reviewers'] ?? 'Reviewers'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'applications' ? 'active' : ''; ?>"><a href="applications.php?lang=<?php echo $lang_param; ?>"><span class="icon">📁</span> <?php echo $sidebar_lang['applications'] ?? 'Applications'; ?><?php if ($total_apps > 0): ?><span class="badge-count"><?php echo $total_apps; ?></span><?php endif; ?></a></li>
        <li class="menu-label">Finance</li>
        <li class="menu-item <?php echo $current_page === 'bank_verify' ? 'active' : ''; ?>"><a href="bank_verify.php?lang=<?php echo $lang_param; ?>"><span class="icon">🏦</span> <?php echo $sidebar_lang['bank_verify'] ?? 'Bank Verification'; ?><?php if ($pending_bank > 0): ?><span class="badge-count"><?php echo $pending_bank; ?></span><?php endif; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'recipients' ? 'active' : ''; ?>"><a href="recipients.php?lang=<?php echo $lang_param; ?>"><span class="icon">🏅</span> <?php echo $sidebar_lang['recipients'] ?? 'Recipients'; ?></a></li>
        <li class="menu-item <?php echo $current_page === 'disbursements' ? 'active' : ''; ?>"><a href="disbursements.php?lang=<?php echo $lang_param; ?>"><span class="icon">💰</span> <?php echo $sidebar_lang['disbursements'] ?? 'Disbursements'; ?></a></li>
        <li class="menu-label">Analytics</li>
        <li class="menu-item <?php echo $current_page === 'reports' ? 'active' : ''; ?>"><a href="reports.php?lang=<?php echo $lang_param; ?>"><span class="icon">📈</span> <?php echo $sidebar_lang['reports'] ?? 'Reports'; ?></a></li>
        <li class="menu-label">Account</li>
        <li class="menu-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>"><a href="profile.php?lang=<?php echo $lang_param; ?>"><span class="icon">👤</span> <?php echo $sidebar_lang['my_profile'] ?? 'My Profile'; ?></a></li>
        <li class="menu-item logout"><a href="../auth/logout.php"><span class="icon">🚪</span> <?php echo $sidebar_lang['logout'] ?? 'Logout'; ?></a></li>
    </ul>
</div>
