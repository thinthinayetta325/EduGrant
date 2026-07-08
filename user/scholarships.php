<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm'); 
$is_logged_in = isset($_SESSION['student_id']);
$lang_param = $is_mm ? 'mm' : 'en';

// --- Localization ---
$page_lang = $is_mm ? [
    'explore_title' => 'ရရှိနိုင်သော ပညာသင်ဆုများ',
    'explore_desc' => 'သင့်အနာဂတ်အတွက် အသင့်တော်ဆုံး ပညာသင်ဆုများကို ရှာဖွေလျှောက်ထားပါ။',
    'btn_apply_now' => 'လျှောက်ထားမည်',
    'btn_login_to_apply' => 'လော့ဂ်အင်ဝင်ပြီး လျှောက်ထားရန်',
    'funding_label' => 'ထောက်ပံ့မှု -၁၅၀,၀၀၀ ကျပ်',
    'badge_active' => 'အသက်ဝင်သည်',
    'no_records' => 'လောလောဆယ် လျှောက်ထားနိုင်သော ပညာသင်ဆုများ မရှိသေးပါ။'
] : [
    'explore_title' => 'Available Scholarships',
    'explore_desc' => 'Discover and apply for the most suitable scholarship paths tailored for your academic future.',
    'btn_apply_now' => 'Apply Now',
    'btn_login_to_apply' => 'Login to Apply',
    'funding_label' => 'Funding: 150,000 MMK',
    'badge_active' => 'Active',
    'no_records' => 'No active scholarship records available at the moment.'
];
 include("../includes/header.php");
// --- Database Connection ---
include '../config/db.php';

// --- Fetch Schemes ---
$schemes = $conn->query("SELECT * FROM schemes WHERE status='Active' ORDER BY scheme_name");
?>
<!DOCTYPE html>
<html lang="<?php echo $is_mm ? 'my' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships | EduGrant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&family=Inter:wght@400;600;800&display=swap');
    .myanmar-font { font-family: 'Padauk', sans-serif !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 <?php echo $is_mm ? 'myanmar-font' : 'font-sans'; ?>">
<!-- schemes -->
<div class="max-w-7xl mx-auto px-6 py-12">
    <div class="mb-10">
        <h3 class="text-3xl font-bold"><?php echo $page_lang['explore_title']; ?></h3>
        <p class="text-slate-500 mt-2"><?php echo $page_lang['explore_desc']; ?></p>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <?php while($scheme = $schemes->fetch_assoc()): 
            // Logic: Use uploaded image if file exists, else use random generator
            $upload_path = "../uploads/schemes/";
            $has_file = !empty($scheme['image']) && file_exists($upload_path . $scheme['image']);
            $img_src = $has_file ? ($upload_path . htmlspecialchars($scheme['image'])) : ('https://picsum.photos/seed/' . $scheme['id'] . '/600/400');
        ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex overflow-hidden hover:shadow-xl transition-all">
            <div class="w-2/5 relative bg-slate-200">
                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($scheme['scheme_name']); ?>" 
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            
            <div class="flex-1 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-lg text-slate-900"><?php echo htmlspecialchars($scheme['scheme_name']); ?></h4>
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded"><?php echo $page_lang['badge_active']; ?></span>
                    </div>
                    <p class="text-sm text-slate-500 mt-3 line-clamp-2"><?php echo htmlspecialchars($scheme['description'] ?? ''); ?></p>
                </div>
                
                <div class="mt-6 pt-4 border-t flex items-center justify-between">
                    <span class="text-xs font-bold text-teal-700"><?php echo $page_lang['funding_label']; ?></span>
                    
                    <?php if ($is_logged_in): ?>
                        <a href="apply.php?scheme_id=<?php echo $scheme['id']; ?>" class="bg-[#004D4A] text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-[#003D3B] transition">
                            <?php echo $page_lang['btn_apply_now']; ?>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="bg-[#004D4A] text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-[#003D3B] transition">
                            <?php echo $page_lang['btn_login_to_apply']; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php include_once("../includes/footer.php");?>
</body>
</html>