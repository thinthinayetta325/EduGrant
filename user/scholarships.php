<?php
// 1. Initialize the session before any HTML output so auth tokens are accessible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Setup language localization and session tracking states
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$is_logged_in = isset($_SESSION['student_id']);
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Define local translations for our page header elements
if ($is_mm) {
    $page_lang = [
        'explore_title' => 'ရရှိနိုင်သော ပညာသင်ဆုများ',
        'explore_desc' => 'သင့်အနာဂတ်အတွက် အသင့်တော်ဆုံး ပညာသင်ဆု အမျိုးအစားများကို ရှာဖွေလျှောက်ထားပါ။',
        'btn_apply_now' => 'လျှောက်ထားမည်',
        'btn_login_to_apply' => 'လော့ဂ်အင်ဝင်ပြီး လျှောက်ထားရန်',
        'btn_view_all' => 'ပညာသင်ဆုအားလုံးကိုကြည့်ရန်',
        'no_records' => 'လောလောဆယ် လျှောက်ထားနိုင်သော ပညာသင်ဆုများ မရှိသေးပါ။',
        'nav_logout' => 'ထွက်မည်',
        'funding_label' => 'ထောက်ပံ့မှုပမာဏ -၁၅၀,၀၀၀ ကျပ်',
        'badge_active' => 'အသက်ဝင်သည်'
    ];
} else {
    $page_lang = [
        'explore_title' => 'Available Scholarships',
        'explore_desc' => 'Discover and apply for the most suitable scholarship paths tailored for your academic future.',
        'btn_apply_now' => 'Apply Now',
        'btn_login_to_apply' => 'Login to Apply',
        'btn_view_all' => 'View All Schemes',
        'no_records' => 'No active scholarship records available at the moment.',
        'nav_logout' => 'Logout',
        'funding_label' => 'Funding:  150,000 MMK',
        'badge_active' => 'Active'
    ];
}

// 4. Connect to your MySQL database
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 5. Global Navigation Header translation pairs
if ($is_mm) {
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'btn_register' => 'အကောင့်ဖွင့်ရန်',
    ];
} else {
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'btn_register' => 'Sign Up',
    ];
}

$unread_count = 0;
if ($is_logged_in && isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
    if ($count_query) {
        $count_query->bind_param("i", $student_id);
        $count_query->execute();
        $count_result = $count_query->get_result()->fetch_assoc();
        $unread_count = $count_result['unread'] ?? 0;
        $count_query->close();
    }
} 
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduGrant Portal - Scholarships</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght=400;700&display=swap');
        .myanmar-font {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">
    
    <!-- Top Header Navigation Bar -->
    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <a href="index.php?lang=<?php echo $lang_param; ?>" class="min-w-0 flex-shrink block hover:opacity-90 transition">
                <div class="flex items-center gap-2.5">
                    <div class="bg-white/10 p-1.5 rounded-lg text-teal-300 shrink-0">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"></path></svg>
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
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="../user/index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="../user/scholarships.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="../user/status.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="../user/contact.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <!-- Language and Authentication Actions Section -->
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
                        <a href="../user/profile.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-2 bg-[#004D4A] hover:bg-[#003D3B] text-white pl-1.5 pr-3.5 py-1 rounded-full border border-teal-500/30 transition shadow-sm">
                            <div class="w-7 h-7 rounded-full bg-teal-500 flex items-center justify-center overflow-hidden border border-white/20">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                            <span class="text-xs sm:text-sm font-semibold ml-2">
                                <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>
                            </span>
                        </a>
                        <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs sm:text-sm font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                            <?php echo $page_lang['nav_logout']; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../auth/register.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap">
                        <?php echo $lang['btn_register']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Categories Dynamic Section -->
    <section class="px-6 lg:px-12 py-12 bg-slate-50/50 flex-grow">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-slate-900"><?php echo $page_lang['explore_title']; ?></h3>
                <p class="text-sm text-slate-500 mt-1"><?php echo $page_lang['explore_desc']; ?></p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6">
                <?php
                $schemes = $conn->query("SELECT id, scheme_name FROM schemes WHERE status='Active' ORDER BY scheme_name");

                if ($schemes && $schemes->num_rows > 0):
                    while($scheme = $schemes->fetch_assoc()):
                        $db_name = strtolower(trim($scheme['scheme_name']));

                        if ($db_name === 'merit scholarship') {
                            $current_desc = $is_mm ? "ထူးချွန်ထက်မြက်ပြီး ပညာရေးရလဒ် အလွန်ကောင်းမွန်သော ကျောင်းသားများအတွက် ချီးမြှင့်သည့် ပညာသင်ဆုဖြစ်ပါသည်။" : "Awarded to academically outstanding students with exceptional performance.";
                        } elseif ($db_name === 'need-based scholarship') {
                            $current_desc = $is_mm ? "ဝင်ငွေနည်းပါးသော မိသားစုများမှ ကျောင်းသားကျောင်းသူများကို ထောက်ပံ့ကူညီပေးရန် ဖြစ်ပါသည်။" : "Supporting students from low-income families.";
                        } elseif ($db_name === 'government scholarship') {
                            $current_desc = $is_mm ? "တက္ကသိုလ်ပထမဘွဲ့ သင်တန်းသားများအတွက် နိုင်ငံတော်မှ အပြည့်အဝ ထောက်ပံ့ပေးသော အစီအစဉ်ဖြစ်ပါသည်။" : "Fully funded national program for undergraduate students.";
                        } else {
                            $current_desc = $is_mm ? "မိတ်ဖက်ကွန်ရက်များရှိ အရည်အချင်းပြည့်မီသော တက္ကသိုလ်ကျောင်းသားများကို ထောက်ပံ့ရန် ရည်ရွယ်ထားသော ပညာသင်ဆုဖြစ်ပါသည်။" : "Financial assistance scheme constructed to support eligible high-potential university students inside partner networks.";
                        }
                ?>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-row transition-all hover:shadow-xl overflow-hidden">
                            <div style="flex:0 0 40%; min-height:200px; background:#e2e8f0 url('https://picsum.photos/seed/<?php echo $scheme['id']; ?>/400/300') center/cover no-repeat;"></div>
                            <div class="flex-1 p-5 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start gap-2">
                                        <h4 class="text-base font-bold text-slate-800 leading-tight">
                                            <?php echo htmlspecialchars($scheme['scheme_name']); ?>
                                        </h4>
                                        <span class="bg-green-100 text-green-700 text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0">
                                            <?php echo $page_lang['badge_active']; ?>
                                        </span>
                                    </div>
                                     <p class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                                        <?php echo htmlspecialchars($current_desc); ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-between gap-2 mt-4 pt-3 border-t border-slate-100">
                                    <span class="text-[11px] font-bold text-teal-700">
                                        <?php echo $page_lang['funding_label']; ?>
                                    </span>
                                    
                                    <?php if ($is_logged_in): ?>
                                        <a href="apply.php?lang=<?php echo $lang_param; ?>&scheme_id=<?php echo $scheme['id']; ?>" class="bg-[#004D4A] text-white px-3 py-1.5 rounded-lg text-[11px] font-bold hover:bg-[#003D3B] transition whitespace-nowrap">
                                            <?php echo $page_lang['btn_apply_now']; ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="/grant_portal/auth/login.php?redirect=<?php echo urlencode('apply.php?lang=' . $lang_param . '&scheme_id=' . $scheme['id']); ?>" class="bg-[#004D4A] text-white px-3 py-1.5 rounded-lg text-[11px] font-bold hover:bg-[#003D3B] transition whitespace-nowrap">
                                            <?php echo $page_lang['btn_login_to_apply']; ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php 
                    endwhile; 
                else:
                ?>
                    <div class="col-span-full text-center py-12 bg-white border border-slate-200 rounded-2xl">
                        <p class="text-sm text-slate-500"><?php echo $page_lang['no_records']; ?></p>
                    </div>
                <?php 
                endif; 
                $conn->close();
                ?>
            </div>
            
            <!-- Bottom Call to Action Button element -->
            <!-- <a href="/grant_portal/auth/register.php?lang=<?php echo $lang_param; ?>" class="w-full md:w-auto mx-auto block mt-8 border border-slate-300 px-8 py-3 rounded-xl text-slate-700 font-medium hover:bg-slate-50 transition text-center">
                <?php echo $page_lang['btn_view_all']; ?>
            </a> -->
        </div>
    </section>

    <!-- Footer Copyright Block -->

    <?php include_once("../includes/footer.php");?>

</div>
</body>
</html>