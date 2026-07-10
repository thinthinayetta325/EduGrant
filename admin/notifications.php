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
];
$current_page = 'notifications';
$lang_param = (isset($_GET['lang']) && $_GET['lang'] === 'mm') ? 'mm' : 'en';
$is_mm = ($lang_param === 'mm');

// Mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE admin_id = ? AND is_read = 0");
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sidebar_lang['page_title']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title']; include 'header.php'; ?>

    <div class="dashboard-body">
        <div class="card" style="max-width:800px;">
            <div class="card-header">
                <div>
                    <h3>🔔 <?php echo $sidebar_lang['page_title']; ?> <?php if ($unread_count > 0): ?><span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:6px;"><?php echo $unread_count; ?> new</span><?php endif; ?></h3>
                </div>
                <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?action=mark_read&lang=<?php echo $lang_param; ?>" class="btn-primary" style="font-size:11px;padding:7px 14px;">✓ <?php echo $sidebar_lang['mark_all_read']; ?></a>
                <?php endif; ?>
            </div>

            <?php if ($all_notifications->num_rows > 0): ?>
                <?php while ($n = $all_notifications->fetch_assoc()): ?>
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-bottom:1px solid #f1f5f9;<?php echo !$n['is_read'] ? 'background:#f0fdf4;' : ''; ?>">
                        <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:<?php
                            echo match($n['type'] ?? '') {
                                'bank_details' => '#dbeafe',
                                'reviewer_recommend' => '#fef3c7',
                                'approval' => '#dcfce7',
                                'rejection' => '#fee2e2',
                                'disbursement' => '#e0e7ff',
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
                                default => '🔔',
                            };
                            ?>
                        </div>
                        <div style="flex-grow:1;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <strong style="font-size:13px;"><?php echo htmlspecialchars($n['title']); ?></strong>
                                <?php if (!$n['is_read']): ?>
                                    <span style="width:7px;height:7px;background:#22c55e;border-radius:50;display:inline-block;"></span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:12px;color:#64748b;margin-top:3px;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="font-size:11px;color:#94a3b8;"><?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></span>
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
</html>
<?php $conn->close(); ?>
