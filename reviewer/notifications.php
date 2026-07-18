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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

    <?php $page_title = $page_lang['page_title']; include 'header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-10">
        <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-[#004D4A] transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php echo $page_lang['page_title']; ?>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-count-badge bg-red-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?action=mark_read&lang=<?php echo $lang_param; ?>" class="mark-all-btn text-xs font-semibold text-[#006D69] border border-[#006D69] px-3.5 py-1.5 rounded-lg hover:bg-[#006D69] hover:text-white transition">
                        <?php echo $page_lang['mark_all_read']; ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($all_notifications && $all_notifications->num_rows > 0): ?>
                <?php while ($n = $all_notifications->fetch_assoc()): ?>
                    <div id="notif-<?php echo $n['id']; ?>" class="flex items-start gap-3 px-5 py-3.5 border-b border-slate-100 transition <?php echo !$n['is_read'] ? 'bg-green-50 hover:bg-green-100' : 'hover:bg-slate-50'; ?>">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-base flex-shrink-0 <?php echo ($n['type'] ?? '') === 'new_application' ? 'bg-blue-100' : 'bg-slate-100'; ?>">
                            <?php echo ($n['type'] ?? '') === 'new_application' ? '📝' : '🔔'; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <strong class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($n['title']); ?></strong>
                                <?php if (!$n['is_read']): ?>
                                    <span class="notif-badge bg-green-500 text-white px-2 py-0.5 rounded-full text-[10px] font-semibold"><?php echo $page_lang['unread']; ?></span>
                                <?php else: ?>
                                    <span class="notif-badge bg-slate-200 text-slate-500 px-2 py-0.5 rounded-full text-[10px] font-semibold"><?php echo $page_lang['read']; ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span class="text-[11px] text-slate-400"><?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <button onclick="markSingleRead(this, <?php echo $n['id']; ?>)" class="flex-shrink-0 text-[11px] font-semibold text-[#006D69] border border-[#006D69] px-3 py-1.5 rounded-md hover:bg-[#006D69] hover:text-white transition whitespace-nowrap">
                                ✓ <?php echo $page_lang['mark_read']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-16 text-slate-400">
                    <div class="text-5xl mb-3">🔔</div>
                    <p class="text-sm font-medium"><?php echo $page_lang['no_notif']; ?></p>
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
                    row.classList.remove('bg-green-50', 'hover:bg-green-100');
                    row.classList.add('hover:bg-slate-50');
                    const badge = row.querySelector('.notif-badge');
                    if (badge) {
                        badge.classList.remove('bg-green-500', 'text-white');
                        badge.classList.add('bg-slate-200', 'text-slate-500');
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
