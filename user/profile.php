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

// 2. Set up language parameters
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$is_logged_in = true; // Secured context
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Define translation dictionary strings
if ($is_mm) {
    $p_lang = [
        'title' => 'ကျောင်းသား ကိုယ်ရေးအချက်အလက်',
        'subtitle' => 'သင်၏ ပရိုဖိုင် အချက်အလက်များနှင့် လျှောက်လွှာမှတ်တမ်းများကို စစ်ဆေးပါ',
        'label_name' => 'အမည် အပြည့်အစုံ',
        'label_email' => 'အီးမေးလ် လိပ်စာ',
        'label_phone' => 'ဖုန်းနံပါတ်',
        'label_id' => 'ကျောင်းသား ကုဒ်နံပါတ်',
        'label_join' => 'အကောင့်ဖွင့်ခဲ့သည့် ရက်စွဲ',
        'section_info' => 'အကောင့် အချက်အလက်များ',
        'section_history' => 'ကျွန်ုပ်၏ လျှောက်လွှာများ',
        'nav_logout' => 'ထွက်မည်',
        'col_app_no' => 'လျှောက်လွှာနံပါတ်',
        'col_scheme' => 'ပညာသင်ဆု',
        'col_income' => 'ဝင်ငွေ',
        'col_date' => 'လျှောက်ထားသည့်ရက်',
        'col_status' => 'အခြေအနေ',
        'col_action' => 'ကြည့်ရန်',
        'no_applications' => 'လျှောက်လွှာမရှိသေးပါ။',
        'view_btn' => 'ကြည့်ရန်',
        'edit_btn' => 'ပရိုဖိုင် ပြင်ဆင်ရန်',
        'edit_title' => 'ပရိုဖိုင် ပြင်ဆင်မည်',
        'edit_name' => 'နာမည် အပြည့်အစုံ',
        'edit_phone' => 'ဖုန်းနံပါတ်',
        'edit_save' => 'သိမ်းမည်',
        'edit_cancel' => 'မလုပ်တော့ပါ',
        'edit_success' => 'ပရိုဖိုင် အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
        'edit_error' => 'ပရိုဖိုင် ပြင်ဆင်ရာတွင် အမှားရှိနေပါသည်။ ထပ်မံကြိုးစားပါ။',
        'change_pw' => 'စကားဝှက် ပြောင်းရန်',
        'pw_current' => 'လက်ရှိ စကားဝှက်',
        'pw_new' => 'အသစ် စကားဝှက်',
        'pw_confirm' => 'စကားဝှက် အတည်ပြုရန်',
        'pw_update' => 'စကားဝှက် အပ်ဒိတ်လုပ်ရန်',
        'pw_success' => 'စကားဝှက် အောင်မြင်စွာ ပြောင်းလဲပြီးပါပြီ။',
        'pw_error_current' => 'လက်ရှိ စကားဝှက် မှားနေပါသည်။',
        'pw_error_match' => 'အသစ် စကားဝှက် နှစ်ခု မတူညီပါ။',
        'pw_error_length' => 'စကားဝှက်သည် အနည်းဆုံး အက္ခရာ ၆ လုံး ရှိရပါမည်။',
        'pw_error' => 'စကားဝှက် ပြောင်းလဲရာတွင် အမှားရှိနေပါသည်။'
    ];
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
    ];
} else {
    $p_lang = [
        'title' => 'Student Profile',
        'subtitle' => 'Manage your personal account details and review your applied schemes',
        'label_name' => 'Full Name',
        'label_email' => 'Email Address',
        'label_phone' => 'Phone Number',
        'label_id' => 'Student ID Reference',
        'label_join' => 'Account Created At',
        'section_info' => 'Account Overview',
        'section_history' => 'My Applications',
        'nav_logout' => 'Logout',
        'col_app_no' => 'App No',
        'col_scheme' => 'Scholarship',
        'col_income' => 'Income',
        'col_date' => 'Apply Date',
        'col_status' => 'Status',
        'col_action' => 'View',
        'no_applications' => 'No Applications Yet',
        'view_btn' => 'View',
        'edit_btn' => 'Edit Profile',
        'edit_title' => 'Edit Profile',
        'edit_name' => 'Full Name',
        'edit_phone' => 'Phone Number',
        'edit_save' => 'Save Changes',
        'edit_cancel' => 'Cancel',
        'edit_success' => 'Profile updated successfully.',
        'edit_error' => 'An error occurred while updating your profile. Please try again.',
        'change_pw' => 'Change Password',
        'pw_current' => 'Current Password',
        'pw_new' => 'New Password',
        'pw_confirm' => 'Confirm New Password',
        'pw_update' => 'Update Password',
        'pw_success' => 'Password changed successfully.',
        'pw_error_current' => 'Current password is incorrect.',
        'pw_error_match' => 'New passwords do not match.',
        'pw_error_length' => 'Password must be at least 6 characters.',
        'pw_error' => 'An error occurred while changing password.'
    ];
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
    ];
}

// 4. Connect to database
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch current logged-in student info using your EXACT table name 'student'
$student_id = $_SESSION['student_id'];
$student_query = $conn->prepare("SELECT name, email, phone, profile_image, created_at FROM student WHERE id = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student_data = $student_query->get_result()->fetch_assoc();
$student_query->close();

// 5. Fetch dynamic unread notifications count for the nav bell badge indicator
$unread_count = 0;
$count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
if ($count_query) {
    $count_query->bind_param("i", $student_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();
    $unread_count = $count_result['unread'] ?? 0;
    $count_query->close();
}

// Ensure profile_image column exists
$col_check = $conn->query("SHOW COLUMNS FROM student LIKE 'profile_image'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE student ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER phone");
}

// 6. Handle profile update form submission
$update_success = false;
$update_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_name = trim($_POST['name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if ($new_name === '' || $new_email === '' || $new_phone === '') {
        $update_error = $is_mm ? 'အကွက်အားလုံးကို ဖြည့်ပါ။' : 'Please fill in all required fields.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $update_error = $is_mm ? 'အီးမေးလ် မှားနေပါသည်။' : 'Invalid email address.';
    } else {
        // Check if email is taken by another student
        $email_check = $conn->prepare("SELECT id FROM student WHERE email = ? AND id != ?");
        $email_check->bind_param("si", $new_email, $student_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $update_error = $is_mm ? 'ဤအီးမေးလ်ကို အခြားသူ အသုံးပြုနေပါသည်။' : 'This email is already in use by another account.';
            $email_check->close();
        } else {
            $email_check->close();
            // Handle password change if provided
            $pw_changed = false;
            if ($current_pw !== '' || $new_pw !== '' || $confirm_pw !== '') {
                if ($new_pw === '' || $confirm_pw === '') {
                    $update_error = $is_mm ? 'စကားဝှက်အသစ်နှင့် အတည်ပြုချက်ကို ဖြည့်ပါ။' : 'Please fill in new password and confirmation.';
                } elseif (strlen($new_pw) < 6) {
                    $update_error = $is_mm ? 'စကားဝှက်သည် အနည်းဆုံး အက္ခရာ ၆ လုံး ရှိရပါမည်။' : 'Password must be at least 6 characters.';
                } elseif ($new_pw !== $confirm_pw) {
                    $update_error = $is_mm ? 'စကားဝှက် နှစ်ခု မတူညီပါ။' : 'New passwords do not match.';
                } else {
                    $pw_check = $conn->prepare("SELECT password FROM student WHERE id = ?");
                    $pw_check->bind_param("i", $student_id);
                    $pw_check->execute();
                    $pw_row = $pw_check->get_result()->fetch_assoc();
                    $pw_check->close();
                    if ($pw_row && (password_verify($current_pw, $pw_row['password']) || $current_pw === $pw_row['password'])) {
                        $pw_changed = true;
                    } else {
                        $update_error = $is_mm ? 'လက်ရှိ စကားဝှက် မှားနေပါသည်။' : 'Current password is incorrect.';
                    }
                }
            }

            if ($update_error === '') {
                if ($pw_changed) {
                    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE student SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $update_stmt->bind_param("ssssi", $new_name, $new_email, $new_phone, $new_hash, $student_id);
                } else {
                    $update_stmt = $conn->prepare("UPDATE student SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $update_stmt->bind_param("sssi", $new_name, $new_email, $new_phone, $student_id);
                }
                if ($update_stmt->execute()) {
                    $update_success = true;
                    $student_data['name'] = $new_name;
                    $student_data['email'] = $new_email;
                    $student_data['phone'] = $new_phone;
                } else {
                    $update_error = $is_mm ? 'ပရိုဖိုင် ပြင်ဆင်ရာတွင် အမှားရှိနေပါသည်။' : 'An error occurred while updating your profile.';
                }
                $update_stmt->close();
            }
        }
    }
}

// 7. Check approved applications requiring bank details
$approved_count = 0;
$has_bank_details = false;
$approved_query = $conn->prepare("SELECT COUNT(*) AS cnt FROM applications WHERE student_id = ? AND status = 'Approved'");
if ($approved_query) {
    $approved_query->bind_param("i", $student_id);
    $approved_query->execute();
    $approved_count = $approved_query->get_result()->fetch_assoc()['cnt'] ?? 0;
    $approved_query->close();
}
$bank_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM bank_details WHERE student_id = ?");
if ($bank_check) {
    $bank_check->bind_param("i", $student_id);
    $bank_check->execute();
    $has_bank_details = ($bank_check->get_result()->fetch_assoc()['cnt'] ?? 0) > 0;
    $bank_check->close();
}
$needs_bank = $approved_count > 0 && !$has_bank_details;

// Check if student has a receipt to download
$receipt_data = null;
$receipt_query = $conn->prepare("SELECT r.filename, sc.scheme_name, r.created_at
    FROM receipts r
    JOIN applications a ON r.application_id = a.id
    JOIN schemes sc ON a.scheme_id = sc.id
    WHERE r.student_id = ?
    ORDER BY r.created_at DESC LIMIT 1");
if ($receipt_query) {
    $receipt_query->bind_param("i", $student_id);
    $receipt_query->execute();
    $receipt_data = $receipt_query->get_result()->fetch_assoc();
    $receipt_query->close();
}

// 8. Handle profile image upload
$upload_success = false;
$upload_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/profile_pics/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            $filename = 'student_' . $student_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                // Delete old image if exists
                if (!empty($student_data['profile_image'])) {
                    $old_file = $upload_dir . $student_data['profile_image'];
                    if (file_exists($old_file)) unlink($old_file);
                }
                $img_stmt = $conn->prepare("UPDATE student SET profile_image = ? WHERE id = ?");
                $img_stmt->bind_param("si", $filename, $student_id);
                if ($img_stmt->execute()) {
                    $upload_success = true;
                    $student_data['profile_image'] = $filename;
                }
                $img_stmt->close();
            } else {
                $upload_error = true;
            }
        } else {
            $upload_error = true;
        }
    } else {
        $upload_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $p_lang['title']; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'MyanmarTaungyi';
            src: url('../MyanmarTaungyi/MyanmarTaungyi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'MyanmarTaungyi', 'Padauk', 'Pyidaungsu', sans-serif !important;
        }
        body { font-family: 'Inter', sans-serif; }
        <?php if ($is_mm): ?>
        body, body * { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.8; }
        <?php endif; ?>
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div class="min-h-screen flex flex-col justify-between">
    
    <!-- Navbar Header Sync Element -->
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
                            <?php if (!empty($student_data['profile_image']) && file_exists('../uploads/profile_pics/' . $student_data['profile_image'])): ?>
                                <img src="../uploads/profile_pics/<?php echo $student_data['profile_image']; ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold tracking-wide">
                            <?php echo htmlspecialchars($student_data['name'] ?? $_SESSION['fullname'] ?? 'Aung'); ?>
                        </span>
                    </a>
                    
                    <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 hover:text-red-200 text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 rounded-md transition border border-red-500/20">
                        <?php echo $p_lang['nav_logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

<!-- Main Profile Management Content Grid -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 my-12 flex-grow w-full">
    <div class="mb-8">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003D3B] tracking-tight">
            <?php echo $p_lang['title']; ?>
        </h2>
        <p class="text-sm text-slate-500 mt-1.5"><?php echo $p_lang['subtitle']; ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Column: Student Bio Metadata Card -->
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-6 text-center">
            <form method="POST" action="" enctype="multipart/form-data" id="profileImageForm">
                <input type="hidden" name="action" value="upload_image">
                <div class="relative w-24 h-24 rounded-full bg-teal-50 border-2 border-teal-500/20 flex items-center justify-center mx-auto text-[#006D69] overflow-hidden shadow-md group cursor-pointer" onclick="document.getElementById('profileImageInput').click()">
                    <?php if (!empty($student_data['profile_image']) && file_exists('../uploads/profile_pics/' . $student_data['profile_image'])): ?>
                        <img src="../uploads/profile_pics/<?php echo $student_data['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    <?php endif; ?>
                    <div class="absolute bottom-0 right-0 bg-[#006D69] flex items-center justify-center w-8 h-8 rounded-full border-2 border-white shadow-md">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <input type="file" name="profile_image" id="profileImageInput" accept="image/*" class="hidden" onchange="document.getElementById('profileImageForm').submit()">
                </div>
            </form>

            <h3 class="text-xl font-bold text-slate-900 mt-5">
                <?php echo htmlspecialchars($student_data['name'] ?? $_SESSION['fullname'] ?? 'N/A'); ?>
            </h3>
            <span class="inline-block bg-slate-100 text-slate-600 text-xs font-semibold px-3 py-1 rounded-full mt-2 border border-slate-200">
                <?php echo $p_lang['label_id']; ?>: #<?php echo str_pad($student_id, 5, '0', STR_PAD_LEFT); ?>
            </span>
            
            <div class="mt-6 pt-6 border-t border-slate-100 text-left space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5"><?php echo $p_lang['label_email']; ?></label>
                    <p class="text-sm font-medium text-slate-800 break-all"><?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5"><?php echo $p_lang['label_phone']; ?></label>
                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($student_data['phone'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5"><?php echo $p_lang['label_join']; ?></label>
                    <p class="text-sm font-medium text-slate-800">
                        <?php echo isset($student_data['created_at']) ? date('d M Y', strtotime($student_data['created_at'])) : date('d M Y'); ?>
                    </p>
                </div>
            </div>

            <button onclick="document.getElementById('editModal').classList.remove('hidden')" class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-[#006D69] hover:bg-[#004F4B] text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-sm border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <?php echo $p_lang['edit_btn']; ?>
            </button>
        </div>

        <!-- Right Column: Scholarship Pipeline History Ledger -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <?php echo $p_lang['section_history']; ?>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <?php
                    $history_query = $conn->prepare("
                        SELECT a.id, a.application_no, a.apply_date, a.family_income, a.status, s.scheme_name
                        FROM applications a
                        JOIN schemes s ON a.scheme_id = s.id
                        WHERE a.student_id = ?
                        ORDER BY a.apply_date DESC
                    ");
                    $history_query->bind_param("i", $student_id);
                    $history_query->execute();
                    $history = $history_query->get_result();

                    if ($history && $history->num_rows > 0):
                    ?>
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="py-3.5 px-6"><?php echo $p_lang['col_app_no']; ?></th>
                                    <th class="py-3.5 px-6"><?php echo $p_lang['col_scheme']; ?></th>
                                    <th class="py-3.5 px-6"><?php echo $p_lang['col_income']; ?></th>
                                    <th class="py-3.5 px-6 whitespace-nowrap"><?php echo $p_lang['col_date']; ?></th>
                                    <th class="py-3.5 px-6"><?php echo $p_lang['col_status']; ?></th>
                                    <th class="py-3.5 px-6 text-center"><?php echo $p_lang['col_action']; ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php while($row = $history->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition duration-150">
                                        <td class="py-4 px-6 font-semibold text-slate-800">
                                            <?php echo htmlspecialchars($row['application_no']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-slate-800 max-w-[200px] truncate">
                                            <?php echo htmlspecialchars($row['scheme_name']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-slate-600">
                                            <?php echo number_format($row['family_income']); ?> MMK
                                        </td>
                                        <td class="py-4 px-6 text-slate-500 whitespace-nowrap">
                                            <?php echo date('M d, Y', strtotime($row['apply_date'])); ?>
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap">
                                            <?php
                                            $status = strtolower($row['status']);
                                            if ($status === 'approved'):
                                                echo '<span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-full border border-emerald-200">Approved</span>';
                                            elseif ($status === 'rejected'):
                                                echo '<span class="inline-flex items-center bg-rose-50 text-rose-700 text-xs font-bold px-2.5 py-1 rounded-full border border-rose-200">Rejected</span>';
                                            elseif ($status === 'under review'):
                                                echo '<span class="inline-flex items-center bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full border border-blue-200">Under Review</span>';
                                            elseif ($status === 'recommended'):
                                                echo '<span class="inline-flex items-center bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-200">Recommended</span>';
                                            else:
                                                echo '<span class="inline-flex items-center bg-amber-50 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-full border border-amber-200">Submitted</span>';
                                            endif;
                                            ?>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <a href="application_details.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center gap-1 bg-[#006D69] hover:bg-[#004F4B] text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                                <?php echo $p_lang['view_btn']; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-12 px-6">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-4m-8 0H4"></path></svg>
                            </div>
                            <p class="text-sm text-slate-500 font-medium"><?php echo $p_lang['no_applications']; ?></p>
                        </div>
                    <?php
                    endif;
                    $history_query->close();
                    $conn->close();
                    ?>
                </div>
            </div>

            <?php if ($receipt_data): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-emerald-50/50">
                        <h3 class="font-bold text-emerald-800 text-base flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <?php echo $is_mm ? 'ငွေထုတ်ပေးပြီးပါပြီ' : 'Funds Released'; ?> 🎉
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600">
                            <?php echo $is_mm ? 'သင်၏ ' : 'Your scholarship for '; ?><strong><?php echo htmlspecialchars($receipt_data['scheme_name']); ?></strong><?php echo $is_mm ? ' အတွက် ငွေကြေးထောက်ပံ့မှု ထုတ်ပေးပြီးပါပြီ။' : ' has been disbursed.'; ?>
                        </p>
                        <a href="../uploads/receipts/<?php echo $receipt_data['filename']; ?>" download class="mt-4 inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <?php echo $is_mm ? 'ငွေလက်ခံဖြတ်ပိုင်း ဒေါင်းလုဒ်' : 'Download Receipt'; ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($needs_bank): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-amber-50/50">
                        <h3 class="font-bold text-amber-800 text-base flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Bank Details Required
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600">Your application has been approved. Please provide your bank account details to receive the scholarship disbursement.</p>
                        <a href="bank_details.php?lang=<?php echo $lang_param; ?>" class="mt-4 inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Fill Bank Details
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</main>

<!-- Edit Profile Modal -->
<div id="editModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm hidden transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 border border-slate-200 relative">
        <button onclick="document.getElementById('editModal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-xl font-bold text-slate-900 mb-6"><?php echo $p_lang['edit_title']; ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['edit_name']; ?></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($student_data['name'] ?? ''); ?>" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['label_email']; ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['edit_phone']; ?></label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($student_data['phone'] ?? ''); ?>" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                </div>
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3"><?php echo $p_lang['change_pw']; ?> <span class="normal-case tracking-normal">(<?php echo $is_mm ? 'ရွေးချယ်စရာ' : 'optional'; ?>)</span></p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['pw_current']; ?></label>
                            <input type="password" name="current_password" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none" placeholder="<?php echo $is_mm ? 'စကားဝှက် ပြောင်းလိုပါကသာ ဖြည့်ပါ' : 'Only fill if changing password'; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['pw_new']; ?></label>
                            <input type="password" name="new_password" minlength="6" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1.5"><?php echo $p_lang['pw_confirm']; ?></label>
                            <input type="password" name="confirm_password" minlength="6" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-[#006D69] hover:bg-[#004F4B] text-white font-semibold text-sm px-4 py-3 rounded-xl transition shadow-sm"><?php echo $p_lang['edit_save']; ?></button>
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm px-4 py-3 rounded-xl transition"><?php echo $p_lang['edit_cancel']; ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Success / Error Toast -->
<?php if ($update_success): ?>
<div id="successToast" class="fixed bottom-6 right-6 z-[110] bg-emerald-600 text-white px-5 py-3.5 rounded-xl shadow-xl flex items-center gap-3 text-sm font-semibold animate-bounce">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?php echo $p_lang['edit_success']; ?>
</div>
<script>setTimeout(() => document.getElementById('successToast')?.remove(), 3000);</script>
<?php elseif ($update_error !== ''): ?>
<div id="errorToast" class="fixed bottom-6 right-6 z-[110] bg-red-600 text-white px-5 py-3.5 rounded-xl shadow-xl flex items-center gap-3 text-sm font-semibold">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?php echo htmlspecialchars($update_error); ?>
</div>
<script>setTimeout(() => document.getElementById('errorToast')?.remove(), 3000);</script>
<?php endif; ?>

<?php if ($upload_success): ?>
<div id="uploadToast" class="fixed bottom-6 right-6 z-[110] bg-emerald-600 text-white px-5 py-3.5 rounded-xl shadow-xl flex items-center gap-3 text-sm font-semibold animate-bounce">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Profile photo updated successfully.
</div>
<script>setTimeout(() => document.getElementById('uploadToast')?.remove(), 3000);</script>
<?php elseif ($upload_error): ?>
<div id="uploadErrorToast" class="fixed bottom-6 right-6 z-[110] bg-red-600 text-white px-5 py-3.5 rounded-xl shadow-xl flex items-center gap-3 text-sm font-semibold">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Failed to upload image. Please try again.
</div>
<script>setTimeout(() => document.getElementById('uploadErrorToast')?.remove(), 3000);</script>
<?php endif; ?>

<!-- Footer Component Frame -->
<?php include_once("../includes/footer.php");?>

<script>
<?php if ($update_error !== ''): ?>
document.getElementById('editModal').classList.remove('hidden');
<?php endif; ?>
</script>

</div>
</body>
</html>