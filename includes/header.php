<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

$is_logged_in = isset($_SESSION['student_id']) || isset($_SESSION['reviewer_id']) || isset($_SESSION['admin_id']);

if ($is_logged_in) {
    if (isset($_SESSION['student_id'])) {
        $user_name = $_SESSION['fullname'] ?? 'User';
        $user_id = $_SESSION['student_id'];
    } elseif (isset($_SESSION['reviewer_id'])) {
        $user_name = $_SESSION['reviewer_name'] ?? 'User';
        $user_id = null;
    } else {
        $user_name = $_SESSION['admin_name'] ?? 'User';
        $user_id = null;
    }
}

if ($is_mm) {
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'btn_login' => 'အကောင့်ဝင်ရန်',
        'btn_register' => 'အကောင့်ဖွင့်ရန်',
        'nav_logout' => 'ထွက်မည်',
    ];
} else {
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'btn_login' => 'Sign In',
        'btn_register' => 'Sign Up',
        'nav_logout' => 'Logout',
    ];
}

    $unread_count = 0;
    $profile_image = null;
    if ($is_logged_in && isset($_SESSION['student_id'])) {
        $conn = new mysqli("localhost", "root", "", "grant_portal");
        if (!$conn->connect_error) {
            $student_id = $_SESSION['student_id'];
            $count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
            if ($count_query) {
                $count_query->bind_param("i", $student_id);
                $count_query->execute();
                $count_result = $count_query->get_result()->fetch_assoc();
                $unread_count = $count_result['unread'] ?? 0;
                $count_query->close();
            }
            $pic_query = $conn->prepare("SELECT profile_image FROM student WHERE id = ?");
            if ($pic_query) {
                $pic_query->bind_param("i", $student_id);
                $pic_query->execute();
                $pic_result = $pic_query->get_result()->fetch_assoc();
                $profile_image = $pic_result['profile_image'] ?? null;
                $pic_query->close();
            }
            // $conn->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduGrant Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        .myanmar-font {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
        }
        .myanmar-font section h1 {
            letter-spacing: 0.02em;
        }
        body { font-family: 'Inter', sans-serif; }
        body.mm-font,
        body.mm-font * {
            font-family: 'Noto Sans Myanmar', sans-serif !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">

    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

            <a href="index.php?lang=<?php echo $lang_param; ?>" class="min-w-0 flex-shrink block hover:opacity-90 transition">
                <div class="flex items-center gap-2.5">
                    <div class="bg-white/10 p-1.5 rounded-lg text-teal-300 shrink-0">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-white text-lg sm:text-xl font-bold leading-tight truncate">EduGrant</h1>
                        <p class="text-teal-200 text-[11px] sm:text-xs mt-0.5 opacity-90 tracking-wide"><?php echo $lang['brand_sub']; ?></p>
                    </div>
                </div>
            </a>

            <?php
                $nav_page = basename($_SERVER['PHP_SELF']);
                $nav_is_active = fn($pages) => in_array($nav_page, (array)$pages);
                $nav_active_class = 'text-[#FFD700] underline underline-offset-4 decoration-2';
            ?>
            <?php
                $scholarships_link = $is_logged_in ? 'scholarships.php' : 'scholarships.php';
                $status_link = $is_logged_in ? 'status.php' : '../common/status.php';
                $contact_link = $is_logged_in ? 'contact.php' : '../common/contact.php';
            ?>
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="<?php echo $scholarships_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="<?php echo $status_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="<?php echo $contact_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

                <?php if ($is_logged_in): ?>
                    <div class="flex items-center gap-3">
                        <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="relative p-2 text-teal-100 hover:text-white bg-[#003D3B] border border-white/10 rounded-full transition shadow-sm group" aria-label="View Notifications">
                            <svg class="w-5 h-5 transition transform group-hover:rotate-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-[10px] font-extrabold text-white items-center justify-center shadow-sm">
                                        <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                                    </span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-2 bg-[#004D4A] hover:bg-[#003D3B] text-white pl-1.5 pr-3.5 py-1 rounded-full border border-teal-500/30 transition shadow-sm">
                            <div class="w-7 h-7 rounded-full bg-teal-500 flex items-center justify-center overflow-hidden border border-white/20">
                                <?php if (!empty($profile_image) && file_exists('../uploads/profile_pics/' . $profile_image)): ?>
                                    <img src="../uploads/profile_pics/<?php echo $profile_image; ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs sm:text-sm font-semibold ml-2">
                                <?php echo htmlspecialchars($user_name ?? 'User'); ?>
                            </span>
                        </a>
                        <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs sm:text-sm font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                            <?php echo $lang['nav_logout']; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap">
                        <?php echo $lang['btn_login']; ?>
                    </a>
                    <a href="../auth/register.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap">
                        <?php echo $lang['btn_register']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
