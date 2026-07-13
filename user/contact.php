<?php
// 1. Initialize session and check authentication status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Set up language parameters
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$is_logged_in = isset($_SESSION['student_id']);
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Define translation dictionary strings for Contact Page
if ($is_mm) {
    $c_lang = [
        'title' => 'ဆက်သွယ်ရန်',
        'subtitle' => 'သင်သိရှိလိုသည်များကို ကျွန်ုပ်တို့ထံ အချိန်မရွေး မေးမြန်းစုံစမ်းနိုင်ပါသည်',
        'card_address_title' => 'ရုံးချုပ်လိပ်စာ',
        'card_address_desc' => 'အမှတ် (၄၅)၊ တက္ကသိုလ်ရိပ်သာလမ်း၊ ကမာရွတ်မြို့နယ်၊ ရန်ကုန်မြို့။',
        'card_phone_title' => 'ဖုန်းနံပါတ်',
        'card_email_title' => 'အီးမေးလ် လိပ်စာ',
        'form_title' => 'တိုက်ရိုက် သဝဏ်လွှာပေးပို့ရန်',
        'label_name' => 'အမည် အပြည့်အစုံ',
        'label_email' => 'အီးမေးလ်',
        'label_subject' => 'အကြောင်းအရာ',
        'label_message' => 'ရေးသားလိုသည့်အကြောင်းအရာ',
        'btn_send' => 'ပေးပို့မည်',
        'placeholder_message' => 'ဤနေရာတွင် စတင်ရေးသားပါ...',
    ];
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'nav_logout' => 'ထွက်မည်',
    ];
} else {
    $c_lang = [
        'title' => 'Contact Us',
        'subtitle' => 'Have questions or need assistance? Reach out to our response team.',
        'card_address_title' => 'Our Office Address',
        'card_address_desc' => 'No. 45, University Avenue Road, Kamayut Township, Yangon, Myanmar.',
        'card_phone_title' => 'Phone Support',
        'card_email_title' => 'Email Correspondence',
        'form_title' => 'Send an Instant Message',
        'label_name' => 'Full Name',
        'label_email' => 'Email Address',
        'label_subject' => 'Subject Heading',
        'label_message' => 'Your Detailed Message',
        'btn_send' => 'Send Message',
        'placeholder_message' => 'Type your message details here...',
    ];
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'nav_logout' => 'Logout',
    ];
}

// 4. Fetch dynamic unread notifications count if user is authenticated
$unread_count = 0;
$student_data = ['name' => ''];

if ($is_logged_in) {
    include '../config/db.php';
    $student_id = $_SESSION['student_id'];
    
    // Get name and email
    $stmt = $conn->prepare("SELECT name, email FROM student WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $student_data['name'] = $res['name'];
        $student_data['email'] = $res['email'];
    }
    $stmt->close();

    // Get unread notification counts
    $count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
    if ($count_query) {
        $count_query->bind_param("i", $student_id);
        $count_query->execute();
        $unread_count = $count_query->get_result()->fetch_assoc()['unread'] ?? 0;
        $count_query->close();
    }

    // Handle contact form submission
    $form_success = '';
    $form_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
        $c_full_name = trim($_POST['full_name'] ?? '');
        $c_email = trim($_POST['email'] ?? '');
        $c_subject = trim($_POST['subject'] ?? '');
        $c_message = trim($_POST['message'] ?? '');

        if ($c_full_name !== '' && $c_email !== '' && $c_subject !== '' && $c_message !== '') {
            $insert_msg = $conn->prepare("INSERT INTO contact_messages (student_id, full_name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
            if ($insert_msg) {
                $insert_msg->bind_param("issss", $student_id, $c_full_name, $c_email, $c_subject, $c_message);
                if ($insert_msg->execute()) {
                    $insert_msg->close();

                    // Create notification for admin (admin_id = 1)
                    $admin_id = 1;
                    $notif_title = 'New Contact Message';
                    $notif_msg = htmlspecialchars($c_full_name) . ' sent a message: ' . htmlspecialchars($c_subject);
                    $insert_notif = $conn->prepare("INSERT INTO notifications (admin_id, title, message, type, is_read) VALUES (?, ?, ?, 'contact_message', 0)");
                    if ($insert_notif) {
                        $insert_notif->bind_param("iss", $admin_id, $notif_title, $notif_msg);
                        $insert_notif->execute();
                        $insert_notif->close();
                    }

                    $form_success = $is_mm ? 'သင့်စာတစ်�ပို့ပြီးပါပြီ။' : 'Your message has been sent successfully!';
                } else {
                    $form_error = $is_mm ? 'စာပို့ရာတွင် အမှားရှိနေပါသည်။' : 'Failed to send message. Please try again.';
                }
            } else {
                $form_error = $is_mm ? 'စာပို့ရာတွင် အမှားရှိနေပါသည်။' : 'Failed to send message. Please try again.';
            }
        } else {
            $form_error = $is_mm ? 'ကျေးဇူးပြု၍ အချက်အလက်အားလုံးကို ဖြည့်ပါ။' : 'Please fill in all fields.';
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $c_lang['title']; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .myanmar-font, .myanmar-font * {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">
    
    <!-- Navbar Header Sync Element -->
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
            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="../user/index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="../user/scholarships.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="../user/status.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="../user/contact.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <!-- Language Switcher and Status Actions -->
            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ($is_logged_in): ?>
                        <!-- Notification Bell Dropdown Button Element -->
                        <a href="notifications.php?lang=<?php echo $lang_param; ?>" class="relative p-2 text-teal-100 hover:text-white bg-[#003D3B] hover:bg-[#002B2A] border border-white/10 rounded-full transition shadow-sm group" aria-label="View Notifications">
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

                        <!-- User Profile Element Badge -->
                        <a href="../user/profile.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-2 bg-[#003D3B] text-white pl-1.5 pr-3.5 py-1 rounded-full border border-teal-400 transition shadow-sm group">
                            <div class="w-7 h-7 rounded-full bg-teal-500 flex items-center justify-center overflow-hidden border border-white/20">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                            <span class="text-xs sm:text-sm font-semibold tracking-wide">
                                <?php echo htmlspecialchars($student_data['name'] !== '' ? $student_data['name'] : ($_SESSION['fullname'] ?? 'Student')); ?>
                            </span>
                        </a>
                        
                        <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 hover:text-red-200 text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 rounded-md transition border border-red-500/20">
                            <?php echo $lang['nav_logout']; ?>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] hover:bg-[#003D3B] hover:text-white text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 rounded-md transition shadow-sm">Sign In</a>
                        <a href="../auth/register.php?lang=<?php echo $lang_param; ?>" class="bg-[#FFD700] text-[#004D4A] hover:bg-[#003D3B] hover:text-white text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 rounded-md transition shadow-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

<!-- Main Contact Section (Configured with Expanded width layout max-w-6xl) -->
<main class="max-w-6xl mx-auto px-4 sm:px-6 my-12 flex-grow w-full">
    <div class="mb-10 text-center md:text-left">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003D3B] tracking-tight">
            <?php echo $c_lang['title']; ?>
        </h2>
        <p class="text-sm text-slate-500 mt-1.5"><?php echo $c_lang['subtitle']; ?></p>
    </div>

    <!-- Layout Container Grid Split -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
        
        <!-- Left Side: Directory Contact Info Cards (Spans 2 columns) -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Address Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_address_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-medium leading-relaxed"><?php echo $c_lang['card_address_desc']; ?></p>
                </div>
            </div>

            <!-- Phone Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.72l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.72.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_phone_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-semibold tracking-wide">+95 1 234 5678, +95 9 8765 4321</p>
                </div>
            </div>

            <!-- Email Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_email_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-semibold tracking-wide hover:text-[#006D69]"><a href="mailto:support@edugrant.gov.mm">support@edugrant.gov.mm</a></p>
                </div>
            </div>

        </div>

        <!-- Right Side: Contact Mail Dispatch Form Module (Spans 3 columns) -->
        <div class="lg:col-span-3">
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-6 sm:p-8">
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg class="w-5 h-5 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <?php echo $c_lang['form_title']; ?>
                </h3>

                <?php if ($form_success): ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl font-medium"><?php echo $form_success; ?></div>
                <?php endif; ?>
                <?php if ($form_error): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl font-medium"><?php echo $form_error; ?></div>
                <?php endif; ?>

                <form action="?lang=<?php echo $lang_param; ?>" method="POST" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_name']; ?></label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($student_data['name'] ?? ''); ?>" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_email']; ?></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_subject']; ?></label>
                        <input type="text" name="subject" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_message']; ?></label>
                        <textarea name="message" rows="5" required placeholder="<?php echo $c_lang['placeholder_message']; ?>" class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none resize-none"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="contact_submit" class="w-full sm:w-auto bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition transform active:scale-[0.99]">
                            <?php echo $c_lang['btn_send']; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<!-- Footer Component Frame -->
<?php include_once("../includes/footer.php");?>
</div>
</body>
</html>