<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['reviewer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$reviewer_id = $_SESSION['reviewer_id'];
$reviewer_name = $_SESSION['reviewer_name'] ?? 'Reviewer';
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

if ($is_mm) {
    $page_lang = [
        'page_title' => 'အကြောင်းကြားချက်များ',
        'no_notif' => 'အကြောင်းကြားချက်များ မရှိသေးပါ။',
        'mark_all_read' => 'အားလုံးဖတ်ပြီးဟု မှတ်ပါ',
        'new_application' => 'လျှောက်လွှာအသစ်',
        'read' => 'ဖတ်ပြီး',
        'unread' => 'မဖတ်ရသေး',
        'mark_read' => 'ဖတ်ပြီးဟု မှတ်ပါ',
    ];
} else {
    $page_lang = [
        'page_title' => 'Notifications',
        'no_notif' => 'No notifications yet.',
        'mark_all_read' => 'Mark All as Read',
        'new_application' => 'New Application',
        'read' => 'Read',
        'unread' => 'Unread',
        'mark_read' => 'Mark as Read',
    ];
}

// Mark single notification as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_single' && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND reviewer_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $reviewer_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE reviewer_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $reviewer_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php?lang=" . $lang_param);
    exit();
}

// Fetch notifications
$notifications = $conn->prepare("SELECT * FROM notifications WHERE reviewer_id = ? ORDER BY created_at DESC");
$notifications->bind_param("i", $reviewer_id);
$notifications->execute();
$all_notifications = $notifications->get_result();
$notifications->close();

// Unread count
$unread_q = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE reviewer_id = ? AND is_read = 0");
$unread_q->bind_param("i", $reviewer_id);
$unread_q->execute();
$unread_count = $unread_q->get_result()->fetch_assoc()['cnt'] ?? 0;
$unread_q->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_lang['page_title']; ?> | EduGrant</title>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

    <?php $page_title = $page_lang['page_title']; include 'header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #006D69;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php echo $page_lang['page_title']; ?>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-count-badge" style="background:#ef4444;color:#fff;padding:1px 8px;border-radius:10px;font-size:11px;font-weight:600;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?action=mark_read&lang=<?php echo $lang_param; ?>" class="mark-all-btn" style="font-size:12px;color:#006D69;font-weight:600;text-decoration:none;padding:6px 14px;border:1px solid #006D69;border-radius:8px;transition:0.2s;" onmouseover="this.style.background='#006D69';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='#006D69';">
                        <?php echo $page_lang['mark_all_read']; ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($all_notifications && $all_notifications->num_rows > 0): ?>
                <?php while ($n = $all_notifications->fetch_assoc()): ?>
                    <div id="notif-<?php echo $n['id']; ?>" style="display:flex;align-items:flex-start;gap:12px;padding:14px 20px;border-bottom:1px solid #f1f5f9;<?php echo !$n['is_read'] ? 'background:#f0fdf4;' : ''; ?>">
                        <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:<?php
                            echo ($n['type'] ?? '') === 'new_application' ? '#dbeafe' : '#f1f5f9';
                        ?>;">
                            <?php echo ($n['type'] ?? '') === 'new_application' ? '📝' : '🔔'; ?>
                        </div>
                        <div style="flex-grow:1;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <strong style="font-size:13px;"><?php echo htmlspecialchars($n['title']); ?></strong>
                                <?php if (!$n['is_read']): ?>
                                    <span class="notif-badge" style="background:#22c55e;color:#fff;padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;"><?php echo $page_lang['unread']; ?></span>
                                <?php else: ?>
                                    <span class="notif-badge" style="background:#e2e8f0;color:#64748b;padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;"><?php echo $page_lang['read']; ?></span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:12px;color:#64748b;margin-top:3px;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="font-size:11px;color:#94a3b8;"><?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <button onclick="markSingleRead(this, <?php echo $n['id']; ?>)" style="flex-shrink:0;font-size:11px;color:#006D69;font-weight:600;padding:5px 12px;border:1px solid #006D69;border-radius:6px;background:transparent;cursor:pointer;transition:0.2s;white-space:nowrap;" onmouseover="this.style.background='#006D69';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='#006D69';">
                                ✓ <?php echo $page_lang['mark_read']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
                    <div style="font-size:40px;margin-bottom:10px;">🔔</div>
                    <p style="font-size:14px;"><?php echo $page_lang['no_notif']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function markSingleRead(btn, id) {
        fetch('notifications.php?action=mark_single&id=' + id + '&lang=<?php echo $lang_param; ?>', { method: 'GET' })
            .then(() => {
                const row = document.getElementById('notif-' + id);
                if (row) {
                    row.style.background = '#fff';
                    const badge = row.querySelector('.notif-badge');
                    if (badge) {
                        badge.style.background = '#e2e8f0';
                        badge.style.color = '#64748b';
                        badge.textContent = '<?php echo $page_lang["read"]; ?>';
                    }
                    btn.remove();
                }
                const countEl = document.querySelector('.notif-count-badge');
                if (countEl) {
                    let n = parseInt(countEl.textContent) - 1;
                    if (n <= 0) {
                        countEl.remove();
                        document.querySelector('.mark-all-btn')?.remove();
                    } else {
                        countEl.textContent = n;
                    }
                }
            });
    }
    </script>

</body>
</html>
<?php $conn->close(); ?>
