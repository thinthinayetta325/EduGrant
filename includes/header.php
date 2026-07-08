<?php 
// 1. Check if the current language is Myanmar (mm)
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 

// 2. Translation dictionary
if ($is_mm) {
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'btn_login' => 'အကောင့်ဝင်ရန်',
        'btn_register' => 'အကောင့်ဖွင့်ရန်',
        'badge_available' => 'ရရှိနိုင်သော အစီအစဉ်များ',
        'hero_title' => 'ပညာသင်ဆု လမ်းညွှန်',
        'hero_desc' => 'မြန်မာနိုင်ငံနှင့် ပြည်ပတွင် သင်၏ပညာရေးခရီးလမ်းကို ပံ့ပိုးပေးရန်အတွက် ပညာသင်ဆု အခွင့်အလမ်းများကို ရှာဖွေပါ။',
        'search_placeholder' => 'ပညာသင်ဆုအမည် သို့မဟုတ် သော့ချက်စာလုံးဖြင့် ရှာဖွေပါ...',
        'filter_all' => 'ကဏ္ဍအားလုံး',
        'filter_merit' => 'ထူးချွန်ဆု',
        'filter_need' => 'လူမှုကူညီရေးဆု',
        'filter_gov' => 'အစိုးရပံ့ပိုးမှု',
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
        'badge_available' => 'AVAILABLE PROGRAMS',
        'hero_title' => 'Scholarship Directory',
        'hero_desc' => 'Find and explore funding opportunities tailored to support your academic path in Myanmar and abroad.',
        'search_placeholder' => 'Search by scholarship name or keyword...',
        'filter_all' => 'All Categories',
        'filter_merit' => 'Merit-Based',
        'filter_need' => 'Need-Based',
        'filter_gov' => 'Government',
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduGrant Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

<div class="min-h-screen bg-turquoise-500 flex flex-col justify-between">
    
    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            
            <a href="index.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="min-w-0 flex-shrink block hover:opacity-90 transition">
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
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="../user/index.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="../user/scholarships.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="../common/status.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="../common/contact.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white hover:underline transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>
                <a href="../auth/login.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap">
                    <?php echo $lang['btn_login']; ?>
                </a>
                <a href="../auth/register.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="bg-[#FFD700] text-[#004D4A] text-xs sm:text-sm font-bold px-3.5 sm:px-5 py-2 rounded-md hover:bg-slate-100 transition whitespace-nowrap">
                    <?php echo $lang['btn_register']; ?>
                </a>
            </div>
        </div>
    </header>