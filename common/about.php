<?php
// 1. Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Set up language and session
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$is_logged_in = isset($_SESSION['student_id']);
$lang_param = $is_mm ? 'mm' : 'en';

// 3. Define language strings
if ($is_mm) {
    $c_lang = [
        'title' => 'ကျွန်ုပ်တို့အကြောင်း',
        'subtitle' => 'EduGrant ပညာသင်ဆုစီမံခန့်ခွဲမှုစနစ်အကြောင်း',
        'about_title' => 'ကျွန်ုပ်တို့၏ ရည်ရွယ်ချက်',
        'about_desc' => 'EduGrant သည် မြန်မာနိုင်ငံရှိ ကျောင်းသား/သူများအတွက် ပညာသင်ဆုလျှောက်ထားခြင်း လုပ်ငန်းစဉ်များကို ပိုမိုလွယ်ကူ၊ မြန်ဆန်ပြီး ပွင့်လင်းမြင်သာမှုရှိစေရန် ရည်ရွယ်၍ တည်ထောင်ထားသော စနစ်တစ်ခုဖြစ်သည်။',
        'vision_title' => 'ကျွန်ုပ်တို့၏ မျှော်မှန်းချက်',
        'vision_desc' => 'နည်းပညာကို အသုံးပြု၍ ပညာရေးအခွင့်အလမ်းများကို လူတိုင်းလက်လှမ်းမီစေပြီး၊ အနာဂတ်၏ ခေါင်းဆောင်ကောင်းများကို မွေးထုတ်ပေးနိုင်ရန်ဖြစ်သည်။',
    ];
    $lang = [
        'brand_sub' => 'မြန်မာ',
        'nav_home' => 'ပင်မစာမျက်နှာ',
        'nav_scholarships' => 'ပညာသင်ဆုများ',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_about' => 'ကျွန်ုပ်တို့အကြောင်း',
        'nav_logout' => 'ထွက်မည်',
    ];
} else {
    $c_lang = [
        'title' => 'About Us',
        'subtitle' => 'Everything you need to know about the EduGrant Scholarship Management System.',
        'about_title' => 'Our Mission',
        'about_desc' => 'EduGrant is a centralized platform designed to simplify, accelerate, and bring transparency to the scholarship application process for students in Myanmar. We are dedicated to supporting the academic journey of talented individuals.',
        'vision_title' => 'Our Vision',
        'vision_desc' => 'To make education accessible to everyone through innovative technology, fostering the next generation of leaders who will contribute to our society.',
    ];
    $lang = [
        'brand_sub' => 'Myanmar',
        'nav_home' => 'Home',
        'nav_scholarships' => 'Scholarships',
        'nav_status' => 'Application Status',
        'nav_about' => 'About Us',
        'nav_logout' => 'Logout',
    ];
}

// 4. Auth/DB Logic (Adjusted for common/ directory)
$student_name = $_SESSION['fullname'] ?? 'Student';
if ($is_logged_in) {
    $conn = new mysqli("localhost", "root", "", "grant_portal");
    if ($conn && $res = $conn->query("SELECT name FROM student WHERE id = " . (int)$_SESSION['student_id'])) {
        $row = $res->fetch_assoc();
        if ($row) $student_name = $row['name'];
    }
    @$conn->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $c_lang['title']; ?> - EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        <?php if ($is_mm): ?> body, body * { font-family: 'Padauk', sans-serif !important; } <?php endif; ?>
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<header class="bg-[#006D69] px-6 py-4 shadow-md">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <a href="../user/index.php" class="text-white text-xl font-bold">EduGrant</a>
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-teal-100">
            <a href="../user/index.php?lang=<?php echo $lang_param; ?>">Home</a>
            <a href="../user/scholarships.php?lang=<?php echo $lang_param; ?>">Scholarships</a>
            <a href="../user/status.php?lang=<?php echo $lang_param; ?>">Status</a>
            <a href="about.php?lang=<?php echo $lang_param; ?>" class="text-[#FFD700] underline"><?php echo $lang['nav_about']; ?></a>
        </nav>
        <div class="text-white text-sm font-semibold"><?php echo htmlspecialchars($student_name); ?></div>
    </div>
</header>

<main class="max-w-4xl mx-auto px-6 py-12">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-extrabold text-[#003D3B]"><?php echo $c_lang['title']; ?></h2>
        <p class="text-slate-500 mt-2"><?php echo $c_lang['subtitle']; ?></p>
    </div>

    <div class="space-y-6">
        <div class="bg-white p-8 rounded-2xl border shadow-sm">
            <h3 class="text-lg font-bold text-[#006D69] mb-2"><?php echo $c_lang['about_title']; ?></h3>
            <p class="text-slate-600 leading-relaxed"><?php echo $c_lang['about_desc']; ?></p>
        </div>
        <div class="bg-white p-8 rounded-2xl border shadow-sm">
            <h3 class="text-lg font-bold text-[#006D69] mb-2"><?php echo $c_lang['vision_title']; ?></h3>
            <p class="text-slate-600 leading-relaxed"><?php echo $c_lang['vision_desc']; ?></p>
        </div>
    </div>
</main>

<?php include("../includes/footer.php");?>

</body>
</html>