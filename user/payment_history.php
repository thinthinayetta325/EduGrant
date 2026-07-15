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
          <?php include_once("../includes/header.php");?>

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
  
     <?php include_once("../includes/footer.php");?>

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