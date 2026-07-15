<?php
session_start();
include '../config/db.php';

// LOGIN CHECK (MUST BE BEFORE OUTPUT)
if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

/* LANGUAGE SYSTEM */
$lang = $_GET['lang'] ?? 'en';
$is_mm = ($lang === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

if ($lang == 'mm') {

    $txt = [
        "title" => "ကျွန်ုပ်၏ လျှောက်လွှာများ",
        "desc" => "ပညာသင်ဆု လျှောက်လွှာများနှင့် အခြေအနေများ",
        "app_no" => "လျှောက်လွှာနံပါတ်",
        "scholarship" => "ပညာသင်ဆု",
        "income" => "ဝင်ငွေ",
        "date" => "ရက်စွဲ",
        "status" => "အခြေအနေ",
        "action" => "ကြည့်ရန်",
        "empty" => "လျှောက်လွှာမရှိသေးပါ",
        "apply" => "ယခုလျှောက်မည်"
    ];

} else {

    $txt = [
        "title" => "My Applications",
        "desc" => "Track all scholarship applications and their status",
        "app_no" => "App No",
        "scholarship" => "Scholarship",
        "income" => "Income",
        "date" => "Apply Date",
        "status" => "Status",
        "action" => "View",
        "empty" => "No Applications Yet",
        "apply" => "Apply Now"
    ];
}

// Nav translation dictionary
if ($is_mm) {
    $nav = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'nav_logout' => 'ထွက်မည်',
    ];
} else {
    $nav = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'nav_logout' => 'Logout',
    ];
}

// Unread notifications count
$unread_count = 0;
$count_query = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE student_id = ? AND is_read = 0");
if ($count_query) {
    $count_query->bind_param("i", $student_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();
    $unread_count = $count_result['unread'] ?? 0;
    $count_query->close();
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $txt["title"] ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

<body class="bg-slate-50 text-slate-800 <?= $is_mm ? 'myanmar-font' : ''; ?>">

<div class="min-h-screen flex flex-col justify-between">

    <!-- Authenticated Navbar -->
     <?php include_once("../includes/header.php");?>
    <!-- <header class="bg-[#006D69] px-4 sm:px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

            <a href="index.php?lang=<?= $lang_param; ?>" class="min-w-0 flex-shrink block hover:opacity-90 transition">
                <div class="flex items-center gap-2.5">
                    <div class="bg-white/10 p-1.5 rounded-lg text-teal-300 shrink-0">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-white text-lg sm:text-xl font-bold leading-tight truncate">EduGrant</h1>
                        <p class="text-teal-200 text-[11px] sm:text-xs mt-0.5 opacity-90 tracking-wide"><?= $nav['brand_sub']; ?></p>
                    </div>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-teal-100">
                <a href="home.php?lang=<?= $lang_param; ?>" class="hover:text-white transition"><?= $nav['nav_home']; ?></a>
                <a href="scholarships.php?lang=<?= $lang_param; ?>" class="hover:text-white transition"><?= $nav['nav_scholarships']; ?></a>
                <a href="my_applications.php?lang=<?= $lang_param; ?>" class="hover:text-white transition"><?= $nav['nav_status']; ?></a>
                <a href="contact.php?lang=<?= $lang_param; ?>" class="hover:text-white transition"><?= $nav['nav_contact']; ?></a>
            </nav>

            <div class="flex items-center flex-shrink-0 gap-3 sm:gap-4">
                <div class="flex items-center bg-[#003D3B] rounded-md p-0.5 border border-white/10">
                    <a href="?lang=en" class="px-2 py-1 text-[11px] sm:text-xs font-semibold rounded transition <?= !$is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">ENG</a>
                    <span class="text-teal-300/40 px-0.5 text-xs font-light">|</span>
                    <a href="?lang=mm" class="px-2 py-1 text-[11px] sm:text-xs font-medium rounded transition <?= $is_mm ? 'text-white bg-white/20' : 'text-teal-200 hover:text-white'; ?>">မြန်မာ</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="notifications.php?lang=<?= $lang_param; ?>" class="relative p-2 text-teal-100 hover:text-white bg-[#003D3B] border border-white/10 rounded-full transition shadow-sm group" aria-label="View Notifications">
                        <svg class="w-5 h-5 transition transform group-hover:rotate-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-[10px] font-extrabold text-white items-center justify-center shadow-sm">
                                    <?= $unread_count > 9 ? '9+' : $unread_count; ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="../auth/logout.php?lang=<?= $lang_param; ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-300 text-xs sm:text-sm font-bold px-3 py-2 rounded-md transition border border-red-500/20">
                        <?= $nav['nav_logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header> -->

<main class="max-w-7xl mx-auto px-4 py-10">

    <!-- HEADER -->
    <div class="mb-8">

        <h1 class="text-3xl font-bold text-slate-900">
            <?= $txt["title"] ?>
        </h1>

        <p class="text-slate-500 mt-2">
            <?= $txt["desc"] ?>
        </p>

    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500">
                            <?= $txt["app_no"] ?>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500">
                            <?= $txt["scholarship"] ?>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500">
                            <?= $txt["income"] ?>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500">
                            <?= $txt["date"] ?>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500">
                            <?= $txt["status"] ?>
                        </th>

                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500">
                            <?= $txt["action"] ?>
                        </th>
                    </tr>

                </thead>

                <tbody>

                <?php
                $sql = "
                    SELECT a.*, s.scheme_name
                    FROM applications a
                    JOIN schemes s ON a.scheme_id = s.id
                    WHERE a.student_id = ?
                    ORDER BY a.apply_date DESC
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>

                <?php if($result->num_rows > 0): ?>

                    <?php while($row = $result->fetch_assoc()): ?>

                    <tr class="border-t hover:bg-slate-50">

                        <td class="px-6 py-4 font-semibold">
                            <?= htmlspecialchars($row['application_no']) ?>
                        </td>

                        <td class="px-6 py-4">
                            <?= htmlspecialchars($row['scheme_name']) ?>
                        </td>

                        <td class="px-6 py-4">
                            <?= number_format($row['family_income']) ?> MMK
                        </td>

                        <td class="px-6 py-4">
                            <?= date("d M Y", strtotime($row['apply_date'])) ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php
                            $status = htmlspecialchars($row['status']);
                            $statusLower = strtolower($row['status']);
                            if ($statusLower === 'approved'):
                                echo '<span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-full border border-emerald-200">' . $status . '</span>';
                            elseif ($statusLower === 'recommended'):
                                echo '<span class="inline-flex items-center bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-200">' . $status . '</span>';
                            elseif ($statusLower === 'rejected'):
                                echo '<span class="inline-flex items-center bg-rose-50 text-rose-700 text-xs font-bold px-2.5 py-1 rounded-full border border-rose-200">' . $status . '</span>';
                            elseif ($statusLower === 'under review'):
                                echo '<span class="inline-flex items-center bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full border border-blue-200">' . $status . '</span>';
                            else:
                                echo '<span class="inline-flex items-center bg-amber-50 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-full border border-amber-200">' . $status . '</span>';
                            endif;
                            ?>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <a href="application_details.php?id=<?= $row['id'] ?>"
                               class="bg-[#003D3B] text-white px-4 py-2 rounded-lg text-sm">
                                <?= $txt["action"] ?>
                            </a>
                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center py-12">

                            <div class="text-6xl mb-3">📄</div>

                            <h3 class="font-bold text-lg">
                                <?= $txt["empty"] ?>
                            </h3>

                            <a href="apply.php"
                               class="mt-4 inline-block bg-[#003D3B] text-white px-5 py-3 rounded-xl">
                                <?= $txt["apply"] ?>
                            </a>

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php
$stmt->close();
include '../includes/footer.php';
?>