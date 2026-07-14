<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$sidebar_lang = (isset($_GET['lang']) && $_GET['lang'] === 'mm') ? [
    'page_title' => 'အကြောင်းကြားချက်များ',
    'mark_read' => 'ဖတ်ပြီးဟု မှတ်ပါ',
    'mark_all_read' => 'အားလုံးဖတ်ပြီးဟု မှတ်ပါ',
    'no_notif' => 'အကြောင်းကြားချက် မရှိသေးပါ',
    'bank_details' => 'ဘဏ်အချက်အလက်',
    'reviewer_recommend' => 'စိစစ်ရေးမှူး ထောက်ခံချက်',
    'approval' => 'အတည်ပြုခြင်း',
    'rejection' => 'ပယ်ဖျက်ခြင်း',
    'disbursement' => 'ငွေထုတ်ပေးခြင်း',
    'contact_message' => 'ဆက်သွယ်ရေးစာတို',
    'new_application' => 'လျှောက်လွှာအသစ်',
    'application_status' => 'လျှောက်လွှာအခြေအနေ',
    'read' => 'ဖတ်ပြီး',
    'unread' => 'မဖတ်ရသေး',
    'delete' => 'ဖျက်မည်',
    'delete_all' => 'အားလုံးဖျက်မည်',
] : [
    'page_title' => 'Notifications',
    'mark_read' => 'Mark as Read',
    'mark_all_read' => 'Mark All as Read',
    'no_notif' => 'No notifications yet.',
    'bank_details' => 'Bank Details',
    'reviewer_recommend' => 'Reviewer Recommendation',
    'approval' => 'Approval',
    'rejection' => 'Rejection',
    'disbursement' => 'Disbursement',
    'contact_message' => 'Contact Message',
    'new_application' => 'New Application',
    'application_status' => 'Application Status',
    'read' => 'Read',
    'unread' => 'Unread',
    'delete' => 'Delete',
    'delete_all' => 'Delete All',
];
$current_page = 'notifications';
$lang_param = (isset($_GET['lang']) && $_GET['lang'] === 'mm') ? 'mm' : 'en';
$is_mm = ($lang_param === 'mm');

// Mark single notification as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND admin_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read_all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE admin_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Delete single notification
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND admin_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Delete all notifications
if (isset($_GET['action']) && $_GET['action'] === 'delete_all') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE admin_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Fetch all admin notifications
$notifications = $conn->prepare("SELECT * FROM notifications WHERE admin_id = ? ORDER BY created_at DESC");
$notifications->bind_param("i", $admin_id);
$notifications->execute();
$all_notifications = $notifications->get_result();
$notifications->close();

// Unread count
$unread_q = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE admin_id = ? AND is_read = 0");
$unread_q->bind_param("i", $admin_id);
$unread_q->execute();
$unread_count = $unread_q->get_result()->fetch_assoc()['cnt'] ?? 0;
$unread_q->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sidebar_lang['page_title']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <?php include_once 'admin-style.php'; ?>
    <style>
        .notif-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        .notif-row:hover { background: #f8fafc; }
        .notif-unread { background: #f0fdf4; }
        .notif-unread:hover { background: #dcfce7; }
        .notif-actions { display: flex; gap: 0; flex-shrink: 0; align-items: center; margin-left: -12px; }

        /* Dark Mode */
        html.dark-mode body { background: #0f172a; color: #e2e8f0; }
        html.dark-mode .sidebar { background: #1e293b; }
        html.dark-mode .sidebar-brand { border-bottom-color: rgba(255,255,255,0.06); }
        html.dark-mode .menu-item a { color: rgba(255,255,255,0.55); }
        html.dark-mode .menu-item a:hover { background: #334155; color: #fff; }
        html.dark-mode .menu-item.active a { background: rgba(255,215,0,0.08); color: #FFD700; }
        html.dark-mode .sidebar-footer { border-top-color: rgba(255,255,255,0.08); }
        html.dark-mode .card { background: #1e293b; border-color: #334155; }
        html.dark-mode .card-header { border-bottom-color: #334155; }
        html.dark-mode .card-header h3, html.dark-mode .card-header .title { color: #f1f5f9; }
        html.dark-mode .notif-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .notif-card:hover { background: #253349; }
        html.dark-mode .notif-card.unread { background: rgba(16,185,129,0.08); border-left-color: #10b981; }
        html.dark-mode .notif-card.unread:hover { background: rgba(16,185,129,0.12); }
        html.dark-mode .notif-icon { background: #334155; }
        html.dark-mode .notif-title { color: #f1f5f9; }
        html.dark-mode .notif-message { color: #94a3b8; }
        html.dark-mode .notif-meta { color: #64748b; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
        html.dark-mode .notif-row { border-bottom-color: #334155; }
        html.dark-mode .notif-row:hover { background: #1a2744; }
        html.dark-mode .notif-unread { background: rgba(16,185,129,0.08); }
        html.dark-mode .notif-unread:hover { background: rgba(16,185,129,0.12); }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title']; include 'header.php'; ?>

    <div class="dashboard-body">
        <div class="card" style="max-width:800px;">
            <div class="card-header">
                <div>
                <?php if ($unread_count > 0): ?>
                    <h3>🔔 <?php echo $sidebar_lang['page_title']; ?> <span class="notif-count-badge" style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:6px;"><?php echo $unread_count; ?></span></h3>
                <?php else: ?>
                    <h3>🔔 <?php echo $sidebar_lang['page_title']; ?></h3>
                <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;">
                    <?php if ($unread_count > 0): ?>
                        <a href="notifications.php?action=mark_read_all&lang=<?php echo $lang_param; ?>" class="btn-primary mark-all-btn" style="font-size:11px;padding:7px 14px;">✓ <?php echo $sidebar_lang['mark_all_read']; ?></a>
                    <?php endif; ?>
                    <?php if ($all_notifications->num_rows > 0): ?>
                        <a href="notifications.php?action=delete_all&lang=<?php echo $lang_param; ?>" class="btn-primary" style="font-size:11px;padding:7px 14px;background:#ef4444;" onclick="return confirm('<?php echo $is_mm ? 'အားလုံးကို ဖျက်မည်လား။' : 'Delete all notifications?'; ?>')">🗑 <?php echo $sidebar_lang['delete_all']; ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($all_notifications->num_rows > 0): ?>
                <?php while ($n = $all_notifications->fetch_assoc()): ?>
                    <div class="notif-row <?php echo !$n['is_read'] ? 'notif-unread' : ''; ?>" onclick="markNotifRead(this, <?php echo $n['id']; ?>)" style="cursor:pointer;">
                        <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:<?php
                            echo match($n['type'] ?? '') {
                                'bank_details' => '#dbeafe',
                                'reviewer_recommend' => '#fef3c7',
                                'approval' => '#dcfce7',
                                'rejection' => '#fee2e2',
                                'disbursement' => '#e0e7ff',
                                'contact_message' => '#fef9c3',
                                'new_application' => '#dbeafe',
                                'application_status' => '#e0e7ff',
                                default => '#f1f5f9',
                            };
                        ?>;">
                            <?php
                            echo match($n['type'] ?? '') {
                                'bank_details' => '🏦',
                                'reviewer_recommend' => '👨‍⚖️',
                                'approval' => '✅',
                                'rejection' => '❌',
                                'disbursement' => '💰',
                                'contact_message' => '✉️',
                                'new_application' => '📄',
                                'application_status' => '📋',
                                default => '🔔',
                            };
                            ?>
                        </div>
                        <div style="flex-grow:1;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <strong style="font-size:13px;"><?php echo htmlspecialchars($n['title']); ?></strong>
                                <?php if (!$n['is_read']): ?>
                                    <span class="notif-badge" style="background:#22c55e;color:#fff;padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;"><?php echo $sidebar_lang['unread']; ?></span>
                                <?php else: ?>
                                    <span class="notif-badge" style="background:#e2e8f0;color:#64748b;padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;"><?php echo $sidebar_lang['read']; ?></span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:12px;color:#64748b;margin-top:3px;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="font-size:11px;color:#94a3b8;"><?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                        <div class="notif-actions" style="margin-left:-8px;">
                            <?php if (!$n['is_read']): ?>
                            <button onclick="event.stopPropagation(); markNotifRead(this.closest('.notif-row'), <?php echo $n['id']; ?>)" title="<?php echo $sidebar_lang['mark_read']; ?>" style="width:30px;height:30px;border-radius:8px;border:none;background:#dcfce7;color:#16a34a;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:0.2s;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <?php endif; ?>
                            <button onclick="event.stopPropagation(); deleteNotif(this, <?php echo $n['id']; ?>)" title="<?php echo $sidebar_lang['delete']; ?>" style="width:30px;height:30px;border-radius:8px;border:none;background:#fee2e2;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:0.2s;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center;padding:50px 20px;color:#94a3b8;">
                    <div style="font-size:40px;margin-bottom:10px;">🔔</div>
                    <p style="font-size:14px;"><?php echo $sidebar_lang['no_notif']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
<script>
function markNotifRead(el, id) {
    if (el.classList.contains('notif-unread')) {
        fetch('notifications.php?action=mark_read&id=' + id + '&lang=<?php echo $lang_param; ?>', { method: 'GET' })
            .then(() => {
                el.classList.remove('notif-unread');
                const badge = el.querySelector('.notif-badge');
                if (badge) {
                    badge.style.background = '#e2e8f0';
                    badge.style.color = '#64748b';
                    badge.textContent = '<?php echo $sidebar_lang["read"]; ?>';
                }
                // Update page count badge
                const pageBadge = document.querySelector('.notif-count-badge');
                if (pageBadge) {
                    let n = parseInt(pageBadge.textContent) - 1;
                    if (n <= 0) {
                        pageBadge.remove();
                        document.querySelector('.mark-all-btn')?.remove();
                    } else {
                        pageBadge.textContent = n;
                    }
                }
                // Update header bell badge
                const headerBadge = document.querySelector('.notif-count');
                if (headerBadge) {
                    let h = parseInt(headerBadge.textContent) - 1;
                    if (h <= 0) {
                        headerBadge.remove();
                    } else {
                        headerBadge.textContent = h > 99 ? '99+' : h;
                    }
                }
            });
    }
}
function deleteNotif(btn, id) {
    if (!confirm('<?php echo $is_mm ? 'ဖျက်မည်လား။' : 'Delete this notification?'; ?>')) return;
    fetch('notifications.php?action=delete&id=' + id + '&lang=<?php echo $lang_param; ?>', { method: 'GET' })
        .then(() => {
            const row = btn.closest('.notif-row');
            const wasUnread = row.classList.contains('notif-unread');
            row.style.transition = 'opacity 0.3s, max-height 0.3s';
            row.style.opacity = '0';
            row.style.maxHeight = '0';
            row.style.overflow = 'hidden';
            row.style.padding = '0';
            row.style.border = 'none';
            setTimeout(() => row.remove(), 300);
            if (wasUnread) {
                const pageBadge = document.querySelector('.notif-count-badge');
                if (pageBadge) {
                    let n = parseInt(pageBadge.textContent) - 1;
                    if (n <= 0) {
                        pageBadge.remove();
                        document.querySelector('.mark-all-btn')?.remove();
                    } else {
                        pageBadge.textContent = n;
                    }
                }
                const headerBadge = document.querySelector('.notif-count');
                if (headerBadge) {
                    let h = parseInt(headerBadge.textContent) - 1;
                    if (h <= 0) {
                        headerBadge.remove();
                    } else {
                        headerBadge.textContent = h > 99 ? '99+' : h;
                    }
                }
            }
        });
}
</script>
</html>
<?php $conn->close(); ?>
