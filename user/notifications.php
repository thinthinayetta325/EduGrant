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
<?php include_once('../includes/header.php'); ?>

<div class="min-h-screen flex flex-col">
    <main class="flex-grow max-w-4xl mx-auto w-full px-4 sm:px-6 my-6 sm:my-8">
        <h2 class="text-xl sm:text-2xl font-extrabold text-[#003D3B] mb-4 sm:mb-6"><?php echo $page_title; ?></h2>

        <div class="space-y-3">
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <?php while ($n = $notifications->fetch_assoc()): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md transition <?php echo !$n['is_read'] ? 'border-l-4 border-l-teal-500' : ''; ?>">
                        <div class="flex items-start gap-3 sm:gap-4">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0 text-lg">
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
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <?php if (!$n['is_read']): ?>
                                    <a href="mark_read.php?id=<?php echo $n['id']; ?>&lang=<?php echo $lang_param; ?>" class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[10px] sm:text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        Unread
                                    </a>
                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium px-3 py-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Read
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white border border-dashed border-slate-200 rounded-xl p-8 sm:p-12 text-center">
                    <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p class="text-slate-500 font-medium"><?php echo $no_notif; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</div>

<?php include_once('../includes/footer.php'); ?>
<?php $conn->close(); ?>
