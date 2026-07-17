<?php
// 1. Initialize session and enforce authentication guard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect back to login if no active user session exists
if (!isset($_SESSION['student_id'])) {
    $lang_param = (isset($_GET['lang']) && $_GET['lang'] === 'mm') ? 'mm' : 'en';
    header("Location: ../auth/login.php?lang=" . $lang_param);
    exit();
}

$student_id = $_SESSION['student_id'];

// 2. Set up language parameters
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Connect to database
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 4. Handle Form Submission (Insert or Update Bank Details)
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $account_holder = trim($_POST['account_holder']);

    if (strlen($account_number) < 10 || strlen($account_number) > 17) {
        $error_msg = $is_mm ? "ဘဏ်အကောင့်နံပါတ်သည် ဂဏန်း ၁၀ မှ ၁၇ ကြား ဖြစ်ရပါမည်။" : "Account number must be between 10 and 17 digits.";
    } elseif (!preg_match('/^\d+$/', $account_number)) {
        $error_msg = $is_mm ? "ဘဏ်အကောင့်နံပါတ်သည် ဂဏန်းများသာ ဖြစ်ရပါမည်။" : "Account number must contain only digits.";
    } elseif (!empty($bank_name) && !empty($account_number) && !empty($account_holder)) {
        // Check if records already exist for this student
        $check_query = $conn->prepare("SELECT id FROM bank_details WHERE student_id = ?");
        $check_query->bind_param("i", $student_id);
        $check_query->execute();
        $result = $check_query->get_result();

        if ($result->num_rows > 0) {
            // Update existing record
            $update_query = $conn->prepare("UPDATE bank_details SET bank_name = ?, account_number = ?, account_holder = ? WHERE student_id = ?");
            $update_query->bind_param("sssi", $bank_name, $account_number, $account_holder, $student_id);
            if ($update_query->execute()) {
                $success_msg = $is_mm ? "ဘဏ်အချက်အလက်များကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။" : "Bank details updated successfully!";
                $update_query->close();
                // Notify admin
                $student_name_q = $conn->prepare("SELECT name FROM student WHERE id = ?");
                $student_name_q->bind_param("i", $student_id);
                $student_name_q->execute();
                $sname = $student_name_q->get_result()->fetch_assoc()['name'] ?? 'A student';
                $student_name_q->close();
                $admin_notify = $conn->prepare("INSERT INTO notifications (admin_id, title, message, type) VALUES (1, ?, ?, 'bank_details')");
                if ($admin_notify) {
                    $notify_title = "Bank Details Updated";
                    $notify_msg = "$sname has updated their bank details.";
                    $admin_notify->bind_param("ss", $notify_title, $notify_msg);
                    $admin_notify->execute();
                    $admin_notify->close();
                }
            } else {
                $error_msg = $is_mm ? "အချက်အလက်ပြင်ဆင်ခြင်း မအောင်မြင်ပါ။" : "Failed to update record.";
                $update_query->close();
            }
        } else {
            // Insert brand new record
            $insert_query = $conn->prepare("INSERT INTO bank_details (student_id, bank_name, account_number, account_holder) VALUES (?, ?, ?, ?)");
            $insert_query->bind_param("isss", $student_id, $bank_name, $account_number, $account_holder);
            if ($insert_query->execute()) {
                $success_msg = $is_mm ? "ဘဏ်အချက်အလက်များကို အောင်မြင်စွာ သိမ်းဆည်းပြီးပါပြီ။" : "Bank details saved successfully!";
                $insert_query->close();
                // Notify admin
                $student_name_q = $conn->prepare("SELECT name FROM student WHERE id = ?");
                $student_name_q->bind_param("i", $student_id);
                $student_name_q->execute();
                $sname = $student_name_q->get_result()->fetch_assoc()['name'] ?? 'A student';
                $student_name_q->close();
                $admin_notify = $conn->prepare("INSERT INTO notifications (admin_id, title, message, type) VALUES (1, ?, ?, 'bank_details')");
                if ($admin_notify) {
                    $notify_title = "New Bank Details";
                    $notify_msg = "$sname has submitted bank details for verification.";
                    $admin_notify->bind_param("ss", $notify_title, $notify_msg);
                    $admin_notify->execute();
                    $admin_notify->close();
                }
            } else {
                $error_msg = $is_mm ? "အချက်အလက်သိမ်းဆည်းခြင်း မအောင်မြင်ပါ။" : "Failed to save record.";
                $insert_query->close();
            }
        }
        $check_query->close();
    } else {
        $error_msg = $is_mm ? "ကျေးဇူးပြု၍ အကွက်အားလုံးကို ဖြည့်စွက်ပေးပါ။" : "Please fill in all required fields.";
    }
}

// 5. Fetch existing bank information if available
$bank_query = $conn->prepare("SELECT bank_name, account_number, account_holder FROM bank_details WHERE student_id = ?");
$bank_query->bind_param("i", $student_id);
$bank_query->execute();
$bank_data = $bank_query->get_result()->fetch_assoc();
$bank_query->close();

// Fetch student info for header synchronization (Using singular 'student')
$student_query = $conn->prepare("SELECT name FROM student WHERE id = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student_info = $student_query->get_result()->fetch_assoc();
$student_query->close();

// 6. Define localized translation dictionaries
if ($is_mm) {
    $b_lang = [
        'title' => 'ဘဏ်အကောင့် အချက်အလက်များ',
        'subtitle' => 'ပညာသင်ဆုကြေးငွေများ လွှဲပြောင်းလက်ခံရန် သင်၏ဘဏ်အချက်အလက်ကို တိကျစွာဖြည့်သွင်းပါ',
        'card_title' => 'ဘဏ်အကောင့် စီမံရန်',
        'label_bank' => 'ဘဏ်အမျိုးအမည်',
        'label_holder' => 'အကောင့်ပိုင်ရှင်အမည်',
        'label_number' => 'ဘဏ်အကောင့်နံပါတ်',
        'btn_save' => 'အချက်အလက်များ သိမ်းဆည်းမည်',
        'placeholder_bank' => 'ဥပမာ - KBZ Bank, CB Bank',
        'placeholder_holder' => 'ဘဏ်စာအုပ်ပါအတိုင်း အမည်အပြည့်အစုံ ဖြည့်ပါ',
        'placeholder_number' => 'ဘဏ်အကောင့်နံပါတ် အမှန်ကို ရိုက်ထည့်ပါ',
        'nav_logout' => 'ထွက်မည်',
        'warning_text' => 'သတိပြုရန် - ဖြည့်စွက်ထားသော ဘဏ်အချက်အလက်များ လွဲမှားပါက ဆုကြေးငွေလွှဲပြောင်းခြင်း လုပ်ငန်းစဉ် နှောင့်နှေးနိုင်ပါသည်။',
        'back_profile' => 'Dashboard သို့ ပြန်သွားရန်'
    ];
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
    ];
} else {
    $b_lang = [
        'title' => 'Bank Account Details',
        'subtitle' => 'Provide your bank disbursement credentials safely to receive scholarship transactions',
        'card_title' => 'Manage Disbursement Channel',
        'label_bank' => 'Bank Institution Name',
        'label_holder' => 'Account Holder Full Name',
        'label_number' => 'Account / Wallet Number',
        'btn_save' => 'Save Configuration',
        'placeholder_bank' => 'e.g., KBZ Bank, AYA Bank, CB Bank',
        'placeholder_holder' => 'Exactly as written on your bank book',
        'placeholder_number' => 'Enter digits without dashes or spaces',
        'nav_logout' => 'Logout',
        'warning_text' => 'Important: Ensure that your account name matches your student ID identity registration data to prevent automatic disbursement failure.',
        'back_profile' => 'Back to Profile'
    ];
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
    ];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<script>if(sessionStorage.getItem('scrollPos')){window.addEventListener('load',function(){setTimeout(function(){window.scrollTo(0,parseInt(sessionStorage.getItem('scrollPos')));sessionStorage.removeItem('scrollPos')},50)})}</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $b_lang['title']; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        @font-face {
            font-family: 'MyanmarTaungyi';
            src: url('../MyanmarTaungyi/MyanmarTaungyi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        .myanmar-font {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'MyanmarTaungyi', 'Padauk', 'Pyidaungsu', sans-serif !important;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">
    
    <!-- Navbar Header -->
    <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
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
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active(['index.php','home.php']) ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_home']; ?></a>
                <a href="scholarships.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('scholarships.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="status.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('status.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_status']; ?></a>
                <a href="contact.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition <?php echo $nav_is_active('contact.php') ? $nav_active_class : 'text-teal-100'; ?>"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <!-- Language and Profile Actions -->
            <div class="flex items-center gap-4 shrink-0">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" onclick="sessionStorage.setItem('scrollPos',window.scrollY)" class="px-2 py-1 text-[11px] font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" onclick="sessionStorage.setItem('scrollPos',window.scrollY)" class="px-2 py-1 text-[11px] font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                        <?php echo $b_lang['nav_logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Form Section -->
    <main class="max-w-2xl mx-auto px-4 my-12 flex-grow w-full">
        <div class="mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003D3B]">
                <?php echo $b_lang['title']; ?>
            </h2>
            <p class="text-sm text-slate-500 mt-1.5"><?php echo $b_lang['subtitle']; ?></p>
        </div>

        <!-- Alert Notification Banners -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Form Card Container -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
            <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4 mb-6 flex items-center gap-2.5">
                <svg class="w-5 h-5 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3-3v8a3 3 0 003 3z"></path></svg>
                <?php echo $b_lang['card_title']; ?>
            </h3>

            <form action="bank_details.php?lang=<?php echo $lang_param; ?>" method="POST" class="space-y-6">
                
                <!-- Input: Bank Name -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                        <?php echo $b_lang['label_bank']; ?> <span class="text-rose-500">*</span>
                    </label>
                    <select name="bank_name" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-[#006D69] focus:bg-white transition text-sm">
                        <option value=""><?php echo $is_mm ? 'ဘဏ်ကိုရွေးချယ်ပါ' : 'Select a bank'; ?></option>
                        <?php
                        $banks = [
                            'KBZ Bank',
                            'CB Bank',
                            'AYA Bank',
                        ];
                        $saved = $bank_data['bank_name'] ?? '';
                        foreach ($banks as $b):
                            $selected = ($b === $saved) ? ' selected' : '';
                        ?>
                            <option value="<?php echo $b; ?>"<?php echo $selected; ?>><?php echo $b; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Input: Account Holder Name -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                        <?php echo $b_lang['label_holder']; ?> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="account_holder" required
                           placeholder="<?php echo htmlspecialchars($b_lang['placeholder_holder']); ?>" 
                           value="<?php echo htmlspecialchars($bank_data['account_holder'] ?? ''); ?>"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-[#006D69] focus:bg-white transition text-sm">
                </div>

                <!-- Input: Account Number -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                        <?php echo $b_lang['label_number']; ?> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="account_number" required minlength="10" maxlength="17" pattern="\d{10,17}"
                           placeholder="<?php echo htmlspecialchars($b_lang['placeholder_number']); ?>" 
                           value="<?php echo htmlspecialchars($bank_data['account_number'] ?? ''); ?>"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-[#006D69] focus:bg-white transition text-sm">
                </div>

                <?php if (empty($success_msg)): ?>
                <!-- Warning Callout Box -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs font-medium text-amber-800 flex gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="leading-relaxed"><?php echo $b_lang['warning_text']; ?></p>
                </div>
                <?php endif; ?>

                <!-- Action Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm py-3.5 px-6 rounded-xl transition shadow-sm duration-150">
                        <?php echo $b_lang['btn_save']; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Back Link Navigation Footer -->
        <div class="mt-8 text-center">
            <a href="profile.php?lang=<?php echo $lang_param; ?>" class="text-teal-700 font-medium hover:underline text-sm">
                &larr; <?php echo $b_lang['back_profile']; ?>
            </a>
        </div>
    </main>

    <!-- Footer Area Container -->
   <?php include_once("../includes/footer.php");?>
</div>
</body>
</html>