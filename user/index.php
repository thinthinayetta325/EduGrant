<?php 
// Start session at the very top before any HTML rendering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, skip index.php and go directly to home.php
if (isset($_SESSION['user_id']) || isset($_SESSION['student_id'])) {
    header("Location: home.php");
    exit();
}

// Include the header
include_once('../includes/header.php');
// DB connection
include_once '../config/db.php';

// Define content words for bilingual support
if (isset($is_mm) && $is_mm) {
    $page_lang = [
        'badge' => 'သင်၏အနာဂတ်ကို ရင်းနှီးမြှုပ်နှံပါ',
        'hero_title' => 'ပညာရေးကို မြှင့်တင်ခြင်း၊<br class="hidden md:inline"/> ပိုမိုကောင်းမွန်သော အနာဂတ်ကို တည်ဆောက်ခြင်း',
        'hero_desc' => 'မြန်မာနိုင်ငံ၏ နောက်မျိုးဆက်သစ် ခေါင်းဆောင်များအတွက် အထူးရည်ရွယ်ထားသော အစိုးရနှင့် အဖွဲ့အစည်းဆိုင်ရာ ပညာသင်ဆုများကို ရယူလိုက်ပါ။',
        'btn_apply' => 'လျှောက်ထားရန် →',
        'btn_view' => 'ပညာသင်ဆုများ ကြည့်ရန်',
        'grant_title' => 'JAN 2025 ပညာသင်ဆု',
        'grant_status' => 'အတည်ပြုပြီး၊ အသုံးပြုနိုင်သည်',
        'explore_title' => 'ပညာသင်ဆု အခွင့်အလမ်းများကို ရှာဖွေပါ',
        'explore_desc' => 'ကျောင်းသားများအတွက် အထူးရည်ရွယ်ထားသော ပညာသင်ဆုများကို ရှာဖွေပါ။',
        'card_popular' => 'လူကြိုက်များ',
        'title_merit' => 'ထူးချွန်ပညာသင်ဆု (Merit Scholarship)',
        'desc_merit' => 'ထူးချွန်သော ကျောင်းသားကျောင်းသူများအတွက် ချီးမြှင့်သည်။',
        'funding_merit' => '၁၅၀,၀၀၀ ကျပ်အထိ',
        'card_new' => 'အသစ်',
        'title_need' => 'လိုအပ်ချက်အပေါ်အခြေခံသော ပညာသင်ဆု',
        'desc_need' => 'ဝင်ငွေနည်းသော မိသားစုမှ ကျောင်းသားများအတွက် ပံ့ပိုးပေးခြင်း။',
        'funding_need' => '၁၅၀,၀၀၀ ကျပ်အထိ',
        'card_gov' => 'အစိုးရ',
        'title_gov' => 'အစိုးရပညာသင်ဆု',
        'desc_gov' => 'မြန်မာကျောင်းသားများအတွက် အပြည့်အဝ ပံ့ပိုးပေးသော ပညာသင်ဆု။',
        'funding_gov' => 'အပြည့်အဝ ပံ့ပိုးသည်',
        'btn_view_all' => 'ပညာသင်ဆုအားလုံး ကြည့်ရန်',
        'impact_title' => 'ထိရောက်သော အကျိုးသက်ရောက်မှုများ',
        'imp_students' => 'ကျောင်းသားများအား ပံ့ပိုးပေးမှု',
        'imp_countries' => 'နိုင်ငံပေါင်း',
        'imp_funds' => 'ပံ့ပိုးငွေစုစုပေါင်း',
        'imp_partners' => 'နိုင်ငံတကာ မိတ်ဖက်များ',
        'story_title' => 'ကျောင်းသားများ၏ အောင်မြင်မှုမှတ်တမ်းများ',
        'story_desc' => 'ပညာသင်ဆုများက ဘဝများကို ပြောင်းလဲပေးနိုင်ပုံကို ကြည့်ရှုပါ။',
        'story_1_title' => 'ပညာရေး ထူးချွန်မှု',
        'story_1_desc' => 'ပညာရေး ရည်မှန်းချက်များ အောင်မြင်စေရန်အတွက် ကျောင်းသားများကို ငွေကြေးနှင့် အရင်းအမြစ်များဖြင့် ပံ့ပိုးပေးခြင်း။',
        'story_2_title' => 'ဘွဲ့ရရှိခြင်း အောင်မြင်မှု',
        'story_2_desc' => 'ပညာရေးခရီးလမ်းကို အောင်မြင်စွာ ဖြတ်သန်းခဲ့ကြသော ပညာသင်ဆုရ ကျောင်းသားများကို ဂုဏ်ပြုခြင်း။',
        'sub_title' => 'အချက်အလက်များကို အမြဲသိရှိနေပါ',
        'sub_desc' => 'ပညာသင်ဆုအသစ်များအကြောင်း သိရှိရန် စာရင်းသွင်းပါ။',
        'sub_placeholder' => 'သင့်အီးမေးလ်ကို ထည့်ပါ',
        'sub_btn' => 'စာရင်းသွင်းရန်'
    ];
} else {
    $page_lang = [
        'badge' => 'Investing In Your Future',
        'hero_title' => 'Empowering Education,<br class="hidden md:inline"/>Building Better Futures',
        'hero_desc' => 'Unlock exclusive access to government and institutional grants tailored for Myanmar\'s next generation of leaders.',
        'btn_apply' => 'Apply →',
        'btn_view' => 'View Scholarships',
        'grant_title' => 'JAN 2025 Grant',
        'grant_status' => 'Approved & Active',
        'explore_title' => 'Explore Scholarship Opportunities',
        'explore_desc' => 'Discover funding programs designed for students.',
        'card_popular' => 'Popular',
        'title_merit' => 'Merit Scholarship',
        'desc_merit' => 'Awarded to academically outstanding students with exceptional performance.',
        'funding_merit' => 'Up to 150,000',
        'card_new' => 'New',
        'title_need' => 'Need-Based Scholarship',
        'desc_need' => 'Supporting students from low-income families to continue their education.',
        'funding_need' => 'Up to 150,000',
        'card_gov' => 'Government',
        'title_gov' => 'Government Scholarship',
        'desc_gov' => 'Fully funded national scholarship program for Myanmar students.',
        'funding_gov' => 'Fully Funded',
        'btn_view_all' => 'View All Scholarships',
        'impact_title' => 'Making an Impact',
        'imp_students' => 'Students Supported',
        'imp_countries' => 'Countries',
        'imp_funds' => 'Funds Awarded',
        'imp_partners' => 'Global Partners',
        'story_title' => 'Student Success Stories',
        'story_desc' => 'See how scholarships are transforming lives and opening doors to new opportunities.',
        'story_1_title' => 'Academic Excellence',
        'story_1_desc' => 'Empowering students with resources and financial support to achieve their educational goals.',
        'story_2_title' => 'Graduation Achievement',
        'story_2_desc' => 'Celebrating scholarship recipients who successfully completed their academic journey.',
        'sub_title' => 'Stay Informed',
        'sub_desc' => 'Subscribe to receive updates about new scholarships.',
        'sub_placeholder' => 'Enter your email',
        'sub_btn' => 'Subscribe Now'
    ];
}
?>

<section class="relative w-full overflow-hidden">
<?php
$images = [
    "https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80",
    "https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80",
    "https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1600&q=80"
];
?>

<div class="absolute inset-0 z-0">
    <?php foreach($images as $i => $img): ?>
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000"
             style="background-image:url('<?php echo $img; ?>');
                    background-size:cover;
                    background-position:center;
                    filter: blur(2px);
                    opacity: <?php echo $i == 0 ? '1' : '0'; ?>;">
        </div>
    <?php endforeach; ?>
</div>

<div class="relative z-20 max-w-7xl mx-auto px-6 lg:px-12 py-20">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div class="text-slate-900">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase bg-teal-600 text-white px-3 py-1 rounded-full">
                <?php echo $page_lang['badge']; ?>
            </span>
            <h1 class="mt-4 text-4xl md:text-5xl font-bold leading-tight">
                <?php echo $page_lang['hero_title']; ?>
            </h1>
            <p class="mt-4 text-white max-w-xl text-lg">
                <?php echo $page_lang['hero_desc']; ?>
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4">
                <!-- Redirects unauthenticated guest users to Register -->
                <a href="/grant_portal/auth/register.php"
                   class="bg-[#004D4A] hover:bg-[#003D3B] text-white px-8 py-3 rounded-lg font-semibold shadow-lg transition text-center">
                    <?php echo $page_lang['btn_apply']; ?>
                </a>
                <a href="/grant_portal/auth/register.php"
                   class="bg-[#004D4A] border border-slate-300  text-white px-8 py-3 rounded-lg transition text-center">
                    <?php echo $page_lang['btn_view']; ?>
                </a>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-100">
                <img id="heroImage" src="<?php echo $images[0]; ?>" class="w-full h-72 md:h-96 object-cover transition-all duration-700">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center">🏅</div>
                        <div>
                            <h3 class="font-semibold text-slate-800"><?php echo $page_lang['grant_title']; ?></h3>
                            <p class="text-slate-500 text-sm"><?php echo $page_lang['grant_status']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- scholarships section -->
<section class="max-w-7xl mx-auto px-6 py-12">
    <div class="mb-10">
        <h3 class="text-3xl font-bold text-slate-900"><?php echo $page_lang['explore_title']; ?></h3>
        <p class="text-slate-500 mt-2"><?php echo $page_lang['explore_desc']; ?></p>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <?php
        $schemes = $conn->query("SELECT * FROM schemes WHERE status='Active' ORDER BY scheme_name");
        while($scheme = $schemes->fetch_assoc()):
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
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded">Active</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-3 line-clamp-2"><?php echo htmlspecialchars($scheme['description'] ?? ''); ?></p>
                </div>
                
                <div class="mt-6 pt-4 border-t flex items-center justify-between">
                    <span class="text-xs font-bold text-teal-700"><?php echo $is_mm ? 'ထောက်ပံ့မှု -၁၅၀,၀၀၀ ကျပ်' : 'Funding: 150,000 MMK'; ?></span>
                    <a href="../auth/login.php" class="bg-[#004D4A] text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-[#003D3B] transition">
                        <?php echo $is_mm ? 'လော့ဂ်အင်ဝင်ပြီး လျှောက်ထားရန်' : 'Login to Apply'; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- impact section -->
<section id="impact-section" class="px-6 lg:px-12 py-16 bg-slate-100">
    <div class="max-w-7xl mx-auto text-center mb-10">
        <h3 class="text-3xl font-bold text-slate-900"><?php echo $page_lang['impact_title']; ?></h3>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="11870">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_students']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="42">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_countries']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="8300000">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_funds']; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
            <div class="text-3xl lg:text-4xl font-bold text-[#004D4A] counter" data-target="23">0</div>
            <p class="text-sm text-slate-500 mt-2 font-medium"><?php echo $page_lang['imp_partners']; ?></p>
        </div>
    </div>
</section>

<!-- success stories -->
<section class="px-6 lg:px-12 py-16 max-w-7xl mx-auto">
    <div class="mb-10">
        <h3 class="text-2xl font-bold text-slate-900"><?php echo $page_lang['story_title']; ?></h3>
        <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_desc']; ?></p>
    </div>
    <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100">
            <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200" class="w-full h-64 object-cover">
            <div class="p-6">
                <h4 class="font-bold text-xl text-slate-800"><?php echo $page_lang['story_1_title']; ?></h4>
                <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_1_desc']; ?></p>
            </div>
        </div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100">
            <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=1200" class="w-full h-64 object-cover">
            <div class="p-6">
                <h4 class="font-bold text-xl text-slate-800"><?php echo $page_lang['story_2_title']; ?></h4>
                <p class="text-sm text-slate-500 mt-2"><?php echo $page_lang['story_2_desc']; ?></p>
            </div>
        </div>
    </div>
</section>

<!-- stay informed -->
<section class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto w-full">
    <!-- Changed max-w-4xl to max-w-6xl for a beautifully balanced wide-width display card layout -->
    <div class="bg-[#006D69] rounded-3xl p-8 sm:p-12 text-center shadow-lg max-w-6xl mx-auto mb-16 border border-white/5">
        <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
            <?php echo $page_lang['sub_title']; ?>
        </h3>
        <p class="text-teal-100 text-sm sm:text-base mt-4 max-w-2xl mx-auto opacity-90 leading-relaxed">
            <?php echo $page_lang['sub_desc']; ?>
        </p>
        
        <!-- Input field wrapper form area -->
        <div class="mt-8 flex flex-col sm:flex-row gap-3.5 max-w-xl mx-auto w-full">
            <input type="email" 
                   placeholder="<?php echo $page_lang['sub_placeholder']; ?>" 
                   class="w-full px-5 py-4 rounded-xl border-0 outline-none text-slate-800 bg-white/95 focus:bg-white text-sm shadow-inner focus:ring-2 focus:ring-teal-300 transition duration-150">
            
            <button class="bg-white text-[#004D4A] hover:bg-teal-50 px-8 py-4 rounded-xl font-bold text-sm tracking-wide shadow-md transition transform active:scale-[0.99] shrink-0">
                <?php echo $page_lang['sub_btn']; ?>
            </button>
        </div>
    </div>
</section>


<script>
let slides = document.querySelectorAll(".hero-slide");
let images = <?php echo json_encode($images); ?>;
let heroImg = document.getElementById("heroImage");
let current = 0;

function changeSlide() {
    slides[current].style.opacity = "0";
    current = (current + 1) % slides.length;
    slides[current].style.opacity = "1";
    heroImg.style.opacity = "0";
    setTimeout(() => {
        heroImg.src = images[current];
        heroImg.style.opacity = "1";
    }, 300);
}
setInterval(changeSlide, 4000);

// Counter script
const counters = document.querySelectorAll('.counter');
const impactSection = document.querySelector('#impact-section');
let isAnimated = false;

const startCounter = (counter) => {
    const target = +counter.getAttribute('data-target');
    const updateCount = () => {
        const count = +counter.innerText.replace(/,/g, '');
        const increment = target / 100;

        if (count < target) {
            counter.innerText = Math.ceil(count + increment).toLocaleString();
            setTimeout(updateCount, 20);
        } else {
            counter.innerText = target.toLocaleString() + '+';
        }
    };
    updateCount();
};

const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !isAnimated) {
        counters.forEach(counter => startCounter(counter));
        isAnimated = true;
    }
}, { threshold: 0.5 });

observer.observe(impactSection);
</script>

<?php $conn->close(); ?>
<?php include_once('../includes/footer.php'); ?>