<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';
$current_page = 'messages';
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$sidebar_lang = $is_mm ? [
    'dashboard' => 'ဒက်ရှ်ဘုတ်',
    'schemes' => 'ပညာသင်ဆုအစီအစဉ်များ',
    'reviewers' => 'စိစစ်ရေးမှူးများ',
    'applications' => 'လျှောက်လွှာများ',
    'bank_verify' => 'ဘဏ်စစ်ဆေးခြင်း',
    'recipients' => 'ဆုရရှိသူများ',
    'disbursements' => 'ငွေပေးချေမှုများ',
    'reports' => 'အစီရင်ခံစာများ',
    'messages' => 'စာတိုပေးစာများ',
    'logout' => 'ထွက်မည်',
    'page_title' => 'စာတိုပေးစာများ',
    'mark_all_read' => 'အားလုံးဖတ်ပြီးဟု မှတ်ပါ',
    'no_messages' => 'စာတိုပေးစာ မရှိသေးပါ',
    'from' => 'ပေးပို့သူ',
    'subject' => 'အကြောင်းအရာ',
    'date' => 'ရက်စွဲ',
    'message' => 'စာတို',
    'view' => 'ကြည့်ရန်',
    'close' => 'ပိတ်ရန်',
    'mark_read' => 'ဖတ်ပြီးဟု မှတ်ပါ',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verification',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => 'Contact Messages',
    'mark_all_read' => 'Mark All as Read',
    'no_messages' => 'No messages yet.',
    'from' => 'From',
    'subject' => 'Subject',
    'date' => 'Date',
    'message' => 'Message',
    'view' => 'View',
    'close' => 'Close',
    'mark_read' => 'Mark as Read',
];

// Handle mark single message as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $msg_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $msg_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: messages.php?lang=" . $lang_param);
    exit();
}

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $conn->query("UPDATE contact_messages SET is_read = 1 WHERE is_read = 0");
    header("Location: messages.php?lang=" . $lang_param);
    exit();
}

// Fetch unread count
$unread_q = $conn->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
$unread_count = $unread_q->fetch_assoc()['cnt'] ?? 0;

// Fetch all messages
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sidebar_lang['page_title']; ?> - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <?php include_once 'admin-style.php'; ?>
    <style>
        .msg-card { background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 10px; overflow: hidden; transition: box-shadow 0.2s; }
        .msg-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .msg-card.unread { border-left: 3px solid #22c55e; background: #f0fdf4; }
        .msg-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; cursor: pointer; }
        .msg-header:hover { background: #f8fafc; }
        .msg-sender { font-weight: 600; font-size: 13px; color: #0f172a; }
        .msg-subject { font-size: 12px; color: #475569; margin-top: 2px; }
        .msg-date { font-size: 11px; color: #94a3b8; white-space: nowrap; }
        .msg-body { display: none; padding: 0 16px 14px 16px; border-top: 1px solid #f1f5f9; }
        .msg-body.open { display: block; padding-top: 12px; }
        .msg-body p { font-size: 13px; color: #334155; line-height: 1.7; white-space: pre-wrap; }
        .msg-meta { display: flex; gap: 12px; margin-top: 8px; font-size: 11px; color: #64748b; }
        .badge-new { background: #22c55e; color: #fff; padding: 1px 7px; border-radius: 10px; font-size: 10px; font-weight: 600; margin-left: 6px; }
        .btn-mark-read { background: #006D69; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-mark-read:hover { background: #005753; }
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
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
        html.dark-mode .message-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .message-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        html.dark-mode .message-card.unread { border-left-color: #10b981; background: rgba(16,185,129,0.06); }
        html.dark-mode .message-header { border-bottom-color: #334155; }
        html.dark-mode .message-sender { color: #f1f5f9; }
        html.dark-mode .message-subject { color: #94a3b8; }
        html.dark-mode .message-date { color: #64748b; }
        html.dark-mode .message-body { border-top-color: #334155; color: #cbd5e1; }
        html.dark-mode .message-meta { color: #64748b; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
    </style>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title']; include 'header.php'; ?>

    <div class="dashboard-body">
        <div class="card" style="max-width:900px;">
            <div class="card-header">
                <div>
                    <h3>✉️ <?php echo $sidebar_lang['page_title']; ?><?php if ($unread_count > 0): ?><span class="badge-new"><?php echo $unread_count; ?> new</span><?php endif; ?></h3>
                </div>
                <?php if ($unread_count > 0): ?>
                    <a href="messages.php?action=mark_all_read&lang=<?php echo $lang_param; ?>" class="btn-mark-read">✓ <?php echo $sidebar_lang['mark_all_read']; ?></a>
                <?php endif; ?>
            </div>

            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="msg-card <?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                        <div class="msg-header" onclick="this.nextElementSibling.classList.toggle('open');<?php if (!$msg['is_read']): ?>fetch('messages.php?action=mark_read&id=<?php echo $msg['id']; ?>&lang=<?php echo $lang_param; ?>',{method:'GET'}).then(()=>{this.closest('.msg-card').classList.remove('unread');let b=document.querySelector('.badge-new');if(b){let n=parseInt(b.textContent)-1;if(n<=0){b.remove();document.querySelector('.btn-mark-read')?.remove();}else{b.textContent=n+' new';}}});<?php endif; ?>">
                            <div>
                                <div class="msg-sender"><?php echo htmlspecialchars($msg['full_name']); ?><?php if (!$msg['is_read']): ?><span class="badge-new">NEW</span><?php endif; ?></div>
                                <div class="msg-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                            </div>
                            <div class="msg-date"><?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?></div>
                        </div>
                        <div class="msg-body">
                            <p><?php echo htmlspecialchars($msg['message']); ?></p>
                            <div class="msg-meta">
                                <span>📧 <?php echo htmlspecialchars($msg['email']); ?></span>
                                <?php if ($msg['student_id']): ?>
                                    <span>🎓 Student ID: <?php echo $msg['student_id']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <p style="font-size:14px;"><?php echo $sidebar_lang['no_messages']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
