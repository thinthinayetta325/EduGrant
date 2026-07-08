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

$student_id = $_SESSION['student_id']; // This acts as your recipient_id mapping

// 2. Set up language parameters
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Connect to database
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 4. Fetch student info for header synchronization (Using singular 'student')
$student_query = $conn->prepare("SELECT name FROM student WHERE id = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student_info = $student_query->get_result()->fetch_assoc();
$student_query->close();

// 5. Fetch Payment History Records matching your specific table layout
$payments = [];
$pay_query = $conn->prepare("
    SELECT 
        p.id AS payment_reference, 
        p.academic_year,
        p.semester,
        p.amount, 
        p.payment_date, 
        b.bank_name
    FROM payment_records p
    INNER JOIN bank_details b ON p.bank_id = b.id
    WHERE p.recipient_id = ? 
    ORDER BY p.payment_date DESC
");

if ($pay_query) {
    $pay_query->bind_param("i", $student_id);
    $pay_query->execute();
    $result = $pay_query->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $pay_query->close();
}

// 6. Define localized translation dictionaries
if ($is_mm) {
    $p_lang = [
        'title' => 'ငွေပေးချေမှုမှတ်တမ်း',
        'subtitle' => 'သင်လက်ခံရရှိထားသော ပညာသင်ဆုကြေးငွေ လွှဲပြောင်းမှုမှတ်တမ်းများကို ဤနေရာတွင် စစ်ဆေးနိုင်ပါသည်',
        'col_ref' => 'လွှဲပြောင်းမှုအမှတ် (Ref No)',
        'col_grant' => 'ပညာသင်နှစ် / စာသင်ကာလ',
        'col_bank' => 'ဘဏ် / လက်ခံသည့်အကောင့်',
        'col_amount' => 'ဆုကြေးငွေပမာဏ',
        'col_date' => 'လွှဲပြောင်းသည့်ရက်စွဲ',
        'col_status' => 'အခြေအနေ',
        'status_success' => 'လွှဲပြောင်းပြီး',
        'no_records' => 'ငွေပေးချေမှုမှတ်တမ်း မရှိသေးပါ။',
        'back_dash' => 'ပင်မစာမျက်နှာသို့ ပြန်သွားရန်',
        'nav_logout' => 'ထွက်မည်'
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
        'title' => 'Disbursement History',
        'subtitle' => 'Track and review all scholarship financial transfers routed to your account',
        'col_ref' => 'Reference ID',
        'col_grant' => 'Academic Term',
        'col_bank' => 'Receiving Institution',
        'col_amount' => 'Amount',
        'col_date' => 'Disbursement Date',
        'col_status' => 'Status',
        'status_success' => 'Completed',
        'no_records' => 'No payment history records found.',
        'back_dash' => 'Back to Dashboard',
        'nav_logout' => 'Logout'
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $p_lang['title']; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap');
        .myanmar-font {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
            line-height: 1.8;
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

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-teal-100">
                <a href="index.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition"><?php echo $lang['nav_home']; ?></a>
                <a href="scholarships.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition"><?php echo $lang['nav_scholarships']; ?></a>
                <a href="status.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition"><?php echo $lang['nav_status']; ?></a>
                <a href="contact.php?lang=<?php echo $lang_param; ?>" class="hover:text-white transition"><?php echo $lang['nav_contact']; ?></a>
            </nav>

            <!-- Language and Profile Actions -->
            <div class="flex items-center gap-4 shrink-0">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] font-semibold rounded transition <?php echo !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] font-medium rounded transition <?php echo $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="profile.php?lang=<?php echo $lang_param; ?>" class="flex items-center gap-2 bg-[#003D3B] text-white pl-1.5 pr-3.5 py-1 rounded-full border border-teal-400 transition shadow-sm">
                        <div class="w-7 h-7 rounded-full bg-teal-500 flex items-center justify-center border border-white/20">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold tracking-wide">
                            <?php echo htmlspecialchars($student_info['name'] ?? 'Student'); ?>
                        </span>
                    </a>
                    
                    <a href="../auth/logout.php?lang=<?php echo $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                        <?php echo $p_lang['nav_logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main History Table Area -->
    <main class="max-w-5xl mx-auto px-4 my-12 flex-grow w-full">
        <div class="mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003D3B]">
                <?php echo $p_lang['title']; ?>
            </h2>
            <p class="text-sm text-slate-500 mt-1.5"><?php echo $p_lang['subtitle']; ?></p>
        </div>

        <!-- History Card Layout Container -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <?php if (empty($payments)): ?>
                <!-- Empty State Message -->
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-slate-500 text-sm font-medium"><?php echo $p_lang['no_records']; ?></p>
                </div>
            <?php else: ?>
                <!-- Responsive Table element -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-4 px-6"><?php echo $p_lang['col_ref']; ?></th>
                                <th class="py-4 px-6"><?php echo $p_lang['col_grant']; ?></th>
                                <th class="py-4 px-6"><?php echo $p_lang['col_bank']; ?></th>
                                <th class="py-4 px-6 text-right"><?php echo $p_lang['col_amount']; ?></th>
                                <th class="py-4 px-6"><?php echo $p_lang['col_date']; ?></th>
                                <th class="py-4 px-6 text-center"><?php echo $p_lang['col_status']; ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                            <?php foreach ($payments as $pay): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <!-- Auto-increment record ID used as Ref No. padded safely -->
                                    <td class="py-4 px-6 font-mono text-xs text-slate-500">
                                        #TXN-<?php echo str_pad($pay['payment_reference'], 6, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <!-- Dynamic layout mixing academic year and semester -->
                                    <td class="py-4 px-6 text-slate-900">
                                        <div class="font-semibold"><?php echo htmlspecialchars($pay['academic_year'] ?? ''); ?></div>
                                        <div class="text-xs text-slate-400 font-normal"><?php echo htmlspecialchars($pay['semester'] ?? ''); ?></div>
                                    </td>
                                    <!-- Connected bank institution column -->
                                    <td class="py-4 px-6 text-slate-600 text-xs font-normal">
                                        <?php echo htmlspecialchars($pay['bank_name'] ?? 'Saved Account'); ?>
                                    </td>
                                    <!-- Numeric amounts aligned correctly -->
                                    <td class="py-4 px-6 text-right font-bold text-slate-900">
                                        <?php echo number_format($pay['amount']); ?> MMK
                                    </td>
                                    <td class="py-4 px-6 text-slate-500 text-xs">
                                        <?php echo p_date_format($pay['payment_date']); ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <?php echo $p_lang['status_success']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Back Button Navigation -->
        <div class="mt-8 text-center">
            <a href="index.php?lang=<?php echo $lang_param; ?>" class="text-teal-700 font-medium hover:underline text-sm">
                &larr; <?php echo $p_lang['back_dash']; ?>
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#003D3B] text-teal-200/60 text-xs text-center py-6 border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4">
            &copy; 2026 EduGrant Portal. All rights reserved.
        </div>
    </footer>

</div>

<?php
// Simple localized helper fallback formatting date layouts
function p_date_format($date_string) {
    if(!$date_string) return '-';
    return date("d M Y", strtotime($date_string));
}
?>
</body>
</html>