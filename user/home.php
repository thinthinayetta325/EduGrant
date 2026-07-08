<?php 
// Start session at the very top before any HTML rendering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Gate: Protect page from guests. Kick unauthenticated users back to landing
if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

// Language and auth setup
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$is_logged_in = true;
$lang_param = $is_mm ? 'mm' : 'en';

// Nav translation dictionary
if ($is_mm) {
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'nav_logout' => 'ထွက်မည်',
    ];
} else {
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'nav_logout' => 'Logout',
    ];
}

// DB connection for notifications count
$conn = new mysqli("localhost", "root", "", "grant_portal");
$unread_count = 0;
if (isset($_SESSION['student_id'])) {
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
    <title>EduGrant Portal - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        .myanmar-font {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
        }
        .myanmar-font section h1 {
            letter-spacing: 0.04em;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">

    <!-- Authenticated Navbar -->
    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

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
                <a href="scholarships.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="status.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="contact.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

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
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold ml-2">
                            <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>
                        </span>
                    </a>
                    <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs sm:text-sm font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                        <?php echo $lang['nav_logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

<?php
// Define content words for bilingual support
if (isset($is_mm) && $is_mm) {
    $page_lang = [
        'badge' => 'သင်၏အနာဂတ်ကို ရင်းနှီးမြှုပ်နှံပါ',
        'hero_title' => 'ပညာရေးကို မြှင့်တင်ခြင်း၊ <br class="hidden md:inline"/> ပိုမိုကောင်းမွန်သော အနာဂတ်ကို တည်ဆောက်ခြင်း',
        'hero_desc' => 'မြန်မာနိုင်ငံ၏ နောက်မျိုးဆက်သစ် ခေါင်းဆောင်များအတွက် အထူးရည်ရွယ်ထားသော အစိုးရနှင့် အဖွဲ့အစည်းဆိုင်ရာ ပညာသင်ဆုများကို ရယူလိုက်ပါ။',
        'btn_apply' => 'ယခုလျှောက်ထားရန် →',
        'btn_view' => 'ပညာသင်ဆုများ ကြည့်ရန်',
        'grant_title' => 'JAN 2025 ပညာသင်ဆု',
        'grant_status' => 'အတည်ပြုပြီး၊ အသုံးပြုနိုင်သည်',
        'explore_title' => 'ပညာသင်ဆု အခွင့်အလမ်းများကို ရှာဖွေပါ',
        'explore_desc' => 'ကျောင်းသားများအတွက် အထူးရည်ရွယ်ထားသော ပညာသင်ဆုများကို ရှာဖွေပါ။',
        'card_popular' => 'လူကြိုက်များ',
        'title_merit' => 'ထူးချွန်ပညာသင်ဆု (Merit Scholarship)',
        'desc_merit' => 'ထူးချွန်သော ကျောင်းသားကျောင်းသူများအတွက် ချီးမြှင့်သည်။',
        'funding_merit' => '၁၅၀,၀၀၀ ကျပ်အထိ',
        'card_new' => 'အသစ်',
        'title_need' => 'လိုအပ်ချက်အပေါ်အခြေခံသော ပညာသင်ဆု',
        'desc_need' => 'ဝင်ငွေနည်းသော မိသားစုမှ ကျောင်းသားများအတွက် ပံ့ပိုးပေးခြင်း။',
        'funding_need' => '၁၅၀,၀၀၀ ကျပ်အထိ',
        'card_gov' => 'အစိုးရ',
        'title_gov' => 'အစိုးရပညာသင်ဆု',
        'desc_gov' => 'မြန်မာကျောင်းသားများအတွက် အပြည့်အဝ ပံ့ပိုးပေးသော ပညာသင်ဆု။',
        'funding_gov' => 'အပြည့်အဝ ပံ့ပိုးသည်',
        'btn_view_all' => 'ပညာသင်ဆုအားလုံး ကြည့်ရန်',
        'impact_title' => 'ထိရောက်သော အကျိုးသက်ရောက်မှုများ',
        'imp_students' => 'ကျောင်းသားများအား ပံ့ပိုးပေးမှု',
        'imp_countries' => 'နိုင်ငံပေါင်း',
        'imp_funds' => 'ပံ့ပိုးငွေစုစုပေါင်း',
        'imp_partners' => 'နိုင်ငံတကာ မိတ်ဖက်များ',
        'story_title' => 'ကျောင်းသားများ၏ အောင်မြင်မှုမှတ်တမ်းများ',
        'story_desc' => 'ပညာသင်ဆုများက ဘဝများကို ပြောင်းလဲပေးနိုင်ပုံကို ကြည့်ရှုပါ။',
        'story_1_title' => 'ပညာရေး ထူးချွန်မှု',
        'story_1_desc' => 'ပညာရေး ရည်မှန်းချက်များ အောင်မြင်စေရန်အတွက် ကျောင်းသားများကို Ngweကြေးနှင့် အရင်းအမြစ်များဖြင့် ပံ့ပိုးပေးခြင်း။',
        'story_2_title' => 'ဘွဲ့ရရှိခြင်း အောင်မြင်မှု',
        'story_2_desc' => 'ပညာရေးခရီးလမ်းကို အောင်မြင်စွာ ဖြတ်သန်းခဲ့ကြသော ပညာသင်ဆုရ ကျောင်းသားများကို ဂုဏ်ပြုခြင်း။',
        'sub_title' => 'အချက်အလက်များကို အမြဲသိရှိနေပါ',
        'sub_desc' => 'ပညာသင်ဆုအသစ်များအကြောင်း သိရှိရန် စာရင်းသွင်းပါ။',
        'sub_placeholder' => 'သင့်အီးမေးလ်ကို ထည့်ပါ',
        'sub_btn' => 'စာရင်းသွင်းရန်'
    ];
} else {
    $page_lang = [
        'badge' => 'Investing In Your Future',
        'hero_title' => 'Empowering Education, <br class="hidden md:inline"/>Building Better Futures',
        'hero_desc' => 'Unlock exclusive access to government and institutional grants tailored for Myanmar\'s next generation of leaders.',
        'btn_apply' => 'Apply Now →',
        'btn_view' => 'View Scholarships',
        'grant_title' => 'JAN 2025 Grant',
        'grant_status' => 'Approved & Active',
        'explore_title' => 'Explore Scholarship Opportunities',
        'explore_desc' => 'Discover funding programs designed for students.',
        'card_popular' => 'Popular',
        'title_merit' => 'Merit Scholarship',
        'desc_merit' => 'Awarded to academically outstanding students with exceptional performance.',
        'funding_merit' =>'Up to 150,000',
        'card_new' => 'New',
        'title_need' => 'Need-Based Scholarship',
        'desc_need' => 'Supporting students from low-income families to continue their education.',
        'funding_need' => 'Up to 150,000',
        'card_gov' => 'Government',
        'title_gov' => 'Government Scholarship',
        'desc_gov' => 'Fully funded national scholarship program for Myanmar students.',
        'funding_gov' => 'Fully Funded',
        'btn_view_all' => 'View All Scholarships',
        'impact_title' => 'Making an Impact',
        'imp_students' => 'Students Supported',
        'imp_countries' => 'Countries',
        'imp_funds' => 'Funds Awarded',
        'imp_partners' => 'Global Partners',
        'story_title' => 'Student Success Stories',
        'story_desc' => 'See how scholarships are transforming lives and opening doors to new opportunities.',
        'story_1_title' => 'Academic Excellence',
        'story_1_desc' => 'Empowering students with resources and financial support to achieve their educational goals.',
        'story_2_title' => 'Graduation Achievement',
        'story_2_desc' => 'Celebrating scholarship recipients who successfully completed their academic journey.',
        'sub_title' => 'Stay Informed',
        'sub_desc' => 'Subscribe to receive updates about new scholarships.',
        'sub_placeholder' => 'Enter your email',
        'sub_btn' => 'Subscribe Now'
    ];
}
?>

<section class="relative w-full overflow-hidden">
<?php
$images = [
    "https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80",
    "https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80",
    "https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1600&q=80"
];
?>

<div class="absolute inset-0 z-0">
    <?php foreach($images as $i => $img): ?>
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000"
             style="background-image:url('<?php echo $img; ?>');
                    background-size:cover;
                    background-position:center;
                    filter: blur(6px);
                    opacity: <?php echo $i == 0 ? '1' : '0'; ?>;">
        </div>
    <?php endforeach; ?>
</div>

<div class="relative z-20 max-w-7xl mx-auto px-6 lg:px-12 py-20">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div class="text-slate-900">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase bg-teal-600 text-white px-3 py-1 rounded-full">
                <?php echo $page_lang['badge']; ?>
            </span>
            <h1 class="mt-4 text-4xl md:text-5xl font-bold leading-tight">
                <?php echo $page_lang['hero_title']; ?>
            </h1>
            <p class="mt-4 text-slate-100 max-w-xl text-lg">
                <?php echo $page_lang['hero_desc']; ?>
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4">
                <!-- Logged in members directly reach system portals -->
                <a href="apply.php"
                   class="bg-[#004D4A] hover:bg-[#003D3B] text-white px-8 py-3 rounded-lg font-semibold shadow-lg transition text-center">
                    <?php echo $page_lang['btn_apply']; ?>
                </a>
                <a href="scholarships.php"
                   class="border border-slate-300 bg-[#004D4A] hover:bg-[#003D3B] text-slate-100 px-8 py-3 rounded-lg transition text-center">
                    <?php echo $page_lang['btn_view']; ?>
                </a>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-100">
                <img id="heroImage" src="<?php echo $images[0]; ?>" class="w-full h-72 md:h-96 object-cover transition-all duration-700">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center">🏅</div>
                        <div>
                            <h3 class="font-semibold text-slate-800"><?php echo $page_lang['grant_title']; ?></h3>
                            <p class="text-slate-500 text-sm"><?php echo $page_lang['grant_status']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- categories section -->
<section class="px-6 lg:px-12 py-12 bg-slate-50/50">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-slate-900"><?php echo $page_lang['explore_title']; ?></h3>
            <p class="text-sm text-slate-500 mt-1"><?php echo $page_lang['explore_desc']; ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $schemes = $conn->query("SELECT id, scheme_name FROM schemes WHERE status='Active' ORDER BY scheme_name");
            $card_index = 0;
            $emojis = ['🎓', '💰', '🏛️', '🌟', '📜', '🌍'];
            $bg_colors = ['bg-blue-100', 'bg-purple-100', 'bg-red-100', 'bg-amber-100'];

            if ($schemes && $schemes->num_rows > 0):
                while($scheme = $schemes->fetch_assoc()):
                    $current_emoji = $emojis[$card_index % count($emojis)];
                    $current_bg = $bg_colors[$card_index % count($bg_colors)];
                    $card_index++;

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
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between transition-all  hover:shadow-xl">
                        <div>
                            <div class="flex justify-between items-start">
                                <div class="w-12 h-12 rounded-xl <?php echo $current_bg; ?> flex items-center justify-center text-2xl">
                                    <?php echo $current_emoji; ?>
                                </div>
                                <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Active</span>
                            </div>
                            <h4 class="mt-4 text-xl font-bold text-slate-800"><?php echo htmlspecialchars($scheme['scheme_name']); ?></h4>
                            <p class="mt-2 text-sm text-slate-500 leading-relaxed"><?php echo htmlspecialchars($current_desc); ?></p>
                        </div>
                        <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-sm font-bold text-teal-700"><?php echo $is_mm ? 'ထောက်ပံ့မှုပမာဏ -၁၅၀,၀၀၀ ကျပ်' : 'Funding: 150,000 MMK'; ?></span>
                            <a href="apply.php?lang=<?php echo $lang_param; ?>&scheme_id=<?php echo $scheme['id']; ?>" class="bg-[#004D4A] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#003D3B]"><?php echo $page_lang['btn_apply']; ?></a>
                        </div>
                    </div>
            <?php
                endwhile;
            else:
            ?>
                <div class="col-span-full text-center py-12 bg-white border border-slate-200 rounded-2xl">
                    <p class="text-sm text-slate-500"><?php echo $is_mm ? 'လောလောဆယ် လျှောက်ထားနိုင်သော ပညာသင်ဆုများ မရှိသေးပါ။' : 'No active scholarship records available at the moment.'; ?></p>
                </div>
            <?php
                endif;
            ?>
        </div>
    </div>
</section>

<!-- impact section -->
<section id="impact-section" class="px-6 lg:px-12 py-16 bg-slate-100">
    <div class="max-w-7xl mx-auto text-center mb-10">
        <h3 class="text-3xl font-bold text-slate-900"><?php echo $page_lang['impact_title']; ?></h3>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="11870">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_students']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="42">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_countries']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="8300000">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_funds']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="23">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_partners']; ?></p>
        </div>
    </div>
</section>

<!-- success stories -->
<section class="px-6 lg:px-12 py-16 max-w-7xl mx-auto">
    <div class="mb-10">
        <h3 class="text-2xl font-bold text-slate-900"><?php echo $page_lang['story_title']; ?></h3>
        <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_desc']; ?></p>
    </div>
    <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100">
            <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200" class="w-full h-64 object-cover">
            <div class="p-6">
                <h4 class="font-bold text-xl text-slate-800"><?php echo $page_lang['story_1_title']; ?></h4>
                <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_1_desc']; ?></p>
            </div>
        </div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100">
            <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=1200" class="w-full h-64 object-cover">
            <div class="p-6">
                <h4 class="font-bold text-xl text-slate-800"><?php echo $page_lang['story_2_title']; ?></h4>
                <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_2_desc']; ?></p>
            </div>
        </div>
    </div>
</section>

<!-- stay informed -->
<section class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto w-full">
    <!-- Changed max-w-4xl to max-w-6xl for a beautifully balanced wide-width display card layout -->
    <div class="bg-[#004D4A]/70 rounded-3xl p-8 sm:p-12 text-center shadow-lg max-w-6xl mx-auto mb-16 border border-white/5">
        <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
            <?php echo $page_lang['sub_title']; ?>
        </h3>
        <p class="text-teal-100 text-sm sm:text-base mt-4 max-w-2xl mx-auto opacity-90 leading-relaxed">
            <?php echo $page_lang['sub_desc']; ?>
        </p>
        
        <!-- Input field wrapper form area -->
        <div class="mt-8 flex flex-col sm:flex-row gap-3.5 max-w-xl mx-auto w-full">
            <input type="email" 
                   placeholder="<?php echo $page_lang['sub_placeholder']; ?>" 
                   class="w-full px-5 py-4 rounded-xl border-0 outline-none text-slate-800 bg-white/95 focus:bg-white text-sm shadow-inner focus:ring-2 focus:ring-teal-300 transition duration-150">
            
            <button class="bg-white text-[#004D4A] hover:bg-teal-50 px-8 py-4 rounded-xl font-bold text-sm tracking-wide shadow-md transition transform active:scale-[0.99] shrink-0">
                <?php echo $page_lang['sub_btn']; ?>
            </button>
        </div>
    </div>
</section>

<script>
let slides = document.querySelectorAll(".hero-slide");
let images = <?php echo json_encode($images); ?>;
let heroImg = document.getElementById("heroImage");
let current = 0;

function changeSlide() {
    slides[current].style.opacity = "0";
    current = (current + 1) % slides.length;
    slides[current].style.opacity = "1";
    heroImg.style.opacity = "0";
    setTimeout(() => {
        heroImg.src = images[current];
        heroImg.style.opacity = "1";
    }, 300);
}
setInterval(changeSlide, 4000);

// Counter script
const counters = document.querySelectorAll('.counter');
const impactSection = document.querySelector('#impact-section');
let isAnimated = false;

const startCounter = (counter) => {
    const target = +counter.getAttribute('data-target');
    const updateCount = () => {
        const count = +counter.innerText.replace(/,/g, '');
        const increment = target / 100;

        if (count < target) {
            counter.innerText = Math.ceil(count + increment).toLocaleString();
            setTimeout(updateCount, 20);
        } else {
            counter.innerText = target.toLocaleString() + '+';
        }
    };
    updateCount();
};

const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !isAnimated) {
        counters.forEach(counter => startCounter(counter));
        isAnimated = true;
    }
}, { threshold: 0.5 });

observer.observe(impactSection);
</script>

<?php $conn->close(); ?>
<?php include_once('../includes/footer.php'); ?>