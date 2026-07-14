<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

// Fetch unread notifications count
$unread_count = 0;
$count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
if ($count_query) {
    $count_query->bind_param("i", $student_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();
    $unread_count = $count_result['unread'] ?? 0;
    $count_query->close();
}

// Fetch notifications
$query = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$notifications = $stmt->get_result();

$page_title = $is_mm ? 'အကြောင်းကြားချက်များ' : 'Notifications';
$no_notif = $is_mm ? 'အကြောင်းကြားချက်များ မရှိသေးပါ။' : 'No notifications yet.';
$back_link = $is_mm ? 'နောက်သို့' : 'Back to Profile';
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        @font-face {
            font-family: 'MyanmarTaungyi';
            src: url('../MyanmarTaungyi/MyanmarTaungyi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'MyanmarTaungyi', 'Padauk', 'Pyidaungsu', sans-serif !important;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col">
    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="profile.php?lang=<?php echo $lang_param; ?>" class="text-teal-100 hover:text-white flex items-center gap-2 text-sm font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <?php echo $back_link; ?>
            </a>
            <div class="flex items-center gap-3">
                <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="relative p-2 text-teal-100 hover:text-white bg-[#003D3B] border border-white/10 rounded-full transition shadow-sm group">
                    <svg class="w-5 h-5 transition transform group-hover:rotate-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <?php if ($unread_count > 0): ?>
                        <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-[10px] font-extrabold text-white items-center justify-center shadow-sm">
                                <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                            </span>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?lang=en" class="text-xs font-semibold px-2 py-1 rounded <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200'; ?>">ENG</a>
                <span class="text-teal-300/40">|</span>
                <a href="?lang=mm" class="text-xs font-medium px-2 py-1 rounded <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200'; ?>">မြန်မာ</a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-4xl mx-auto w-full px-4 sm:px-6 my-8">
        <h2 class="text-2xl font-extrabold text-[#003D3B] mb-6"><?php echo $page_title; ?></h2>

        <div class="space-y-3">
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <?php while ($n = $notifications->fetch_assoc()): ?>
                    <a href="mark_read.php?id=<?php echo $n['id']; ?>&lang=<?php echo $lang_param; ?>" class="block bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition <?php echo !$n['is_read'] ? 'border-l-4 border-l-teal-500' : ''; ?>">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0 text-lg">
                                <?php
                                $type = $n['type'] ?? '';
                                if ($type === 'application_status') echo '📋';
                                elseif ($type === 'approval') echo '✅';
                                elseif ($type === 'rejection') echo '❌';
                                else echo '🔔';
                                ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-900 text-sm"><?php echo htmlspecialchars($n['title'] ?? ''); ?></h4>
                                <p class="text-slate-600 text-sm mt-1"><?php echo htmlspecialchars($n['message'] ?? ''); ?></p>
                                <p class="text-slate-400 text-xs mt-2"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></p>
                            </div>
                            <?php if (!$n['is_read']): ?>
                                <span class="w-2 h-2 rounded-full bg-teal-500 flex-shrink-0 mt-2"></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white border border-dashed border-slate-200 rounded-xl p-12 text-center">
                    <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p class="text-slate-500 font-medium"><?php echo $no_notif; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</div>

</body>
</html>
<?php $conn->close(); ?>
