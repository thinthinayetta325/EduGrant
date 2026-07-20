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

            $validate = $conn->prepare("SELECT id FROM student WHERE id = ?");
            if ($validate) {
                $validate->bind_param("i", $student_id);
                $validate->execute();
                $v_result = $validate->get_result();
                $validate->close();
                if ($v_result->num_rows === 0) {
                    session_destroy();
                    header("Location: ../auth/login.php?error=" . urlencode("Your account was not found. Please register and login again."));
                    exit();
                }
            }

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
<script>if(localStorage.getItem('user_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<script>if(sessionStorage.getItem('scrollPos')){window.addEventListener('load',function(){setTimeout(function(){window.scrollTo(0,parseInt(sessionStorage.getItem('scrollPos')));sessionStorage.removeItem('scrollPos')},50)})}</script>
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
        .myanmar-font header,
        .myanmar-font header * {
            line-height: 1.2 !important;
            font-family: 'Inter', sans-serif !important;
        }
        .myanmar-font section h1 {
            letter-spacing: 0.02em;
        }
        body { font-family: 'Inter', sans-serif; }
        body.mm-font,
        body.mm-font * {
            font-family: 'Noto Sans Myanmar', sans-serif !important;
        }

        /* Theme Toggle Button */
        .theme-toggle {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 8px;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.8); cursor: pointer; transition: 0.2s ease;
        }
        .theme-toggle:hover { background: rgba(255,255,255,0.2); color: #fff; }
        .theme-toggle .icon-sun, .theme-toggle .icon-moon { display: none; }
        html.dark-mode .theme-toggle .icon-sun { display: block; }
        html:not(.dark-mode) .theme-toggle .icon-moon { display: block; }

        /* Dark Mode Overrides */
        html.dark-mode body,
        html.dark-mode .min-h-screen { background: #0f172a; color: #e2e8f0; }

        html.dark-mode section.bg-slate-100 { background: #1e293b !important; }

        html.dark-mode .bg-white { background: #1e293b !important; }
        html.dark-mode .bg-slate-50 { background: #0f172a !important; }
        html.dark-mode .bg-slate-100 { background: #1e293b !important; }
        html.dark-mode .bg-slate-200 { background: #334155 !important; }

        html.dark-mode .text-slate-900 { color: #f1f5f9 !important; }
        html.dark-mode .text-slate-800 { color: #e2e8f0 !important; }
        html.dark-mode .text-slate-700 { color: #cbd5e1 !important; }
        html.dark-mode .text-slate-600 { color: #94a3b8 !important; }
        html.dark-mode .text-slate-500 { color: #94a3b8 !important; }
        html.dark-mode .text-slate-100 { color: #94a3b8 !important; }

        html.dark-mode .border-slate-100 { border-color: #334155 !important; }
        html.dark-mode .border-slate-300 { border-color: #475569 !important; }

        html.dark-mode .shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important; }
        html.dark-mode .shadow-md { box-shadow: 0 4px 6px rgba(0,0,0,0.4) !important; }
        html.dark-mode .shadow-2xl { box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important; }

        html.dark-mode input[type="email"],
        html.dark-mode input[type="text"],
        html.dark-mode input[type="password"],
        html.dark-mode select,
        html.dark-mode textarea {
            background: rgba(255,255,255,0.05) !important;
            border-color: #475569 !important;
            color: #f1f5f9 !important;
        }
        html.dark-mode ::placeholder { color: #64748b; }

        html.dark-mode a { color: inherit; }
        html.dark-mode .text-teal-100 { color: #99f6e4 !important; }
        html.dark-mode .text-teal-200 { color: #99f6e4 !important; }
        html.dark-mode .text-teal-300 { color: #99f6e4 !important; }
        html.dark-mode .text-emerald-700 { color: #6ee7b7 !important; }
        html.dark-mode .bg-emerald-50 { background: rgba(16,185,129,0.15) !important; }
        html.dark-mode .text-yellow-400 { color: #facc15 !important; }

        html.dark-mode .line-clamp-2 { -webkit-line-clamp: 2; }

        html.dark-mode footer { background: #0c1a1a !important; }
        html.dark-mode footer .border-t { border-color: #1e293b !important; }
    </style>
    <script>
    function toggleTheme() {
        document.documentElement.classList.toggle('dark-mode');
        localStorage.setItem('user_theme', document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
    }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">

    <header class="bg-[#006D69] px-4 sm:px-6 shadow-md z-50" style="position:fixed; top:0; left:0; right:0; height:64px; display:flex; align-items:center; overflow:hidden; width:100%; box-sizing:border-box; flex-shrink:0;">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4 w-full" style="min-width:0;">

            <a href="index.php?lang=<?php echo $lang_param; ?>" class="flex-shrink-0 block hover:opacity-90 transition" style="min-width:0; max-width:180px;">
                <div class="flex items-center gap-2.5">
                    <div class="bg-white/10 p-1.5 rounded-lg shrink-0">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" style="color:#FFD700;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    </div>
                    <div style="min-width:0;">
                        <h1 class="text-white text-lg sm:text-xl font-bold leading-tight truncate">EduGrant</h1>
                        <p class="text-teal-200 text-[11px] sm:text-xs mt-0.5 opacity-90 tracking-wide truncate"><?php echo $lang['brand_sub']; ?></p>
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
                $status_link = $is_logged_in ? 'status.php' : '../user/status.php';
                $contact_link = $is_logged_in ? 'contact.php' : '../user/contact.php';
            ?>
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium" style="flex-shrink:1; min-width:0; <?php echo $is_mm ? 'font-size:13px; gap:5px;' : ''; ?>">
                <a href="index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition whitespace-nowrap <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="<?php echo $scholarships_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition whitespace-nowrap <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="<?php echo $status_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition whitespace-nowrap <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="<?php echo $contact_link; ?>?lang=<?php echo $lang_param; ?>" class="hover:text-white hover:underline transition whitespace-nowrap <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4" style="max-width:340px;">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10 flex-shrink-0">
                    <a href="?lang=en" onclick="sessionStorage.setItem('scrollPos',window.scrollY)" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition text-center" style="min-width:36px; <?php echo !$is_mm ? 'color:white;background:rgba(255,255,255,0.2);' : 'color:#5eead4;'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" onclick="sessionStorage.setItem('scrollPos',window.scrollY)" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition text-center" style="min-width:50px; <?php echo $is_mm ? 'color:white;background:rgba(255,255,255,0.2);' : 'color:#5eead4;'; ?>">မြန်မာ</a>
                </div>

                <button class="theme-toggle flex-shrink-0" onclick="toggleTheme()" title="Toggle dark mode">
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                </button>

                <?php if ($is_logged_in): ?>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="relative p-2 text-teal-100 hover:text-white bg-[#003D3B] border border-white/10 rounded-full transition shadow-sm group flex-shrink-0" aria-label="View Notifications">
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

                        <div class="relative" id="userProfileDropdown">
                            <button onclick="var m=document.getElementById('userProfileMenu'); m.classList.toggle('hidden'); if(!m.classList.contains('hidden')){var r=m.getBoundingClientRect(); if(r.right>window.innerWidth)m.style.right='0',m.style.left='auto';}" class="flex items-center gap-2 bg-[#004D4A] hover:bg-[#003D3B] text-white pl-1.5 pr-3 py-1 rounded-full border border-teal-500/30 transition shadow-sm cursor-pointer">
                                <div class="w-7 h-7 rounded-full bg-teal-500 flex items-center justify-center overflow-hidden border border-white/20">
                                    <?php if (!empty($profile_image) && file_exists('../uploads/profile_pics/' . $profile_image)): ?>
                                        <img src="../uploads/profile_pics/<?php echo $profile_image; ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs sm:text-sm font-semibold ml-1 hidden sm:inline">
                                    <?php echo htmlspecialchars($user_name ?? 'User'); ?>
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-teal-300">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>

                            <div id="userProfileMenu" class="hidden fixed right-4 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-[100]">
                                <a href="profile.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    My Profile
                                </a>
                                <a href="my_applications.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    My Applications
                                </a>
                                <hr class="my-1 border-slate-100">
                                <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>

                    <script>
                    document.addEventListener('click', function(e) {
                        var dropdown = document.getElementById('userProfileDropdown');
                        var menu = document.getElementById('userProfileMenu');
                        if (dropdown && !dropdown.contains(e.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                    </script>
                <?php else: ?>
                    <a href="../auth/login.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap flex-shrink-0" style="width:100px; text-align:center; <?php echo $is_mm ? 'font-size:12px;' : ''; ?>">
                        <?php echo $lang['btn_login']; ?>
                    </a>
                    <a href="../auth/register.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap flex-shrink-0" style="width:100px; text-align:center; <?php echo $is_mm ? 'font-size:12px;' : ''; ?>">
                        <?php echo $lang['btn_register']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div style="height:64px;"></div>
