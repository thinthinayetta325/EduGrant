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

<?php include_once("../includes/header.php");?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10">

    <!-- HEADER -->
    <div class="mb-6 sm:mb-8">

        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
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
                        <th class="px-3 sm:px-6 py-4 text-left text-[10px] sm:text-xs font-bold text-slate-500">
                            <?= $txt["app_no"] ?>
                        </th>

                        <th class="px-3 sm:px-6 py-4 text-left text-[10px] sm:text-xs font-bold text-slate-500">
                            <?= $txt["scholarship"] ?>
                        </th>

                        <th class="hidden sm:table-cell px-3 sm:px-6 py-4 text-left text-[10px] sm:text-xs font-bold text-slate-500">
                            <?= $txt["income"] ?>
                        </th>

                        <th class="px-3 sm:px-6 py-4 text-left text-[10px] sm:text-xs font-bold text-slate-500">
                            <?= $txt["date"] ?>
                        </th>

                        <th class="px-3 sm:px-6 py-4 text-left text-[10px] sm:text-xs font-bold text-slate-500">
                            <?= $txt["status"] ?>
                        </th>

                        <th class="px-3 sm:px-6 py-4 text-center text-[10px] sm:text-xs font-bold text-slate-500">
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

                        <td class="px-3 sm:px-6 py-3 sm:py-4 font-semibold text-xs sm:text-sm">
                            <?= htmlspecialchars($row['application_no']) ?>
                        </td>

                        <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm">
                            <?= htmlspecialchars($row['scheme_name']) ?>
                        </td>

                        <td class="hidden sm:table-cell px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm">
                            <?= number_format($row['family_income']) ?> MMK
                        </td>

                        <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm">
                            <?= date("d M Y", strtotime($row['apply_date'])) ?>
                        </td>

                        <td class="px-3 sm:px-6 py-3 sm:py-4">
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

                        <td class="px-3 sm:px-6 py-3 sm:py-4 text-center">
                            <a href="application_details.php?id=<?= $row['id'] ?>"
                               class="bg-[#003D3B] text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm">
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