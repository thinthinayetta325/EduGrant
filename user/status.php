<?php 
include_once('../includes/header.php');

// Connect to DB (not done in header)
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) die("DB connection failed");

if ($is_mm) {
    $st_lang = [
        'badge' => 'လျှောက်လွှာအခြေအနေ',
        'title' => 'သင့်ပညာသင်ဆု အခြေအနေကို စစ်ဆေးရန်',
        'desc' => 'သင့်လျှောက်လွှာ၏ လက်ရှိအခြေအနေကို သိရှိနိုင်ရန် အောက်ပါအကွက်တွင် သင့်လျှောက်လွှာကုဒ်နံပါတ် (Application ID) ကို ရိုက်ထည့်၍ ရှာဖွေနိုင်ပါသည်။',
        'input_label' => 'လျှောက်လွှာနံပါတ် (Application ID)',
        'input_ph' => 'ဥပမာ - EG-2026-8974',
        'btn_track' => 'စစ်ဆေးရန်',
        'result_title' => 'ရှာဖွေတွေ့ရှိချက် အကျဉ်းချုပ်',
        'lbl_applicant' => 'လျှောက်ထားသူ',
        'lbl_grant' => 'ပညာသင်ဆု အမျိုးအစား',
        'lbl_date' => 'နောက်ဆုံးအပ်ဒိတ်နေ့စွဲ',
        'lbl_reviewer' => 'စိစစ်ရေးမှူး',
        'lbl_recommendation' => 'ထောက်ခံချက်',
        'lbl_approved_by' => 'အတည်ပြုသူ',
        'reviewed' => 'ပြန်လည်သုံးသပ်ပြီး',
        'not_reviewed' => 'မပြန်လည်သုံးသပ်ရသေး',
        'recommended' => 'ထောက်ခံသည်',
        'not_recommended' => 'ထောက်ခံမှုမရှိ',
        'approved' => 'အတည်ပြုပြီး',
        'step1_title' => 'လျှောက်လွှာတင်ပြီး',
        'step1_desc' => 'သင့်လျှောက်လွှာကို စနစ်ထဲသို့ အောင်မြင်စွာ လက်ခံရရှိပြီးဖြစ်သည်။',
        'step2_title' => 'စိစစ်နေဆဲ',
        'step2_desc' => 'ပညာရေးဘုတ်အဖွဲ့မှ သင့်စာရွက်စာတမ်းများကို စစ်ဆေးနေပါသည်။',
        'step3_title' => 'ထောက်ခံချက်',
        'step3_desc' => 'စိစစ်ရေးမှူးမှ ပြန်လည်သုံးသပ်ပြီး ထောက်ခံချက်ပေးအပ်ခြင်း။',
        'step4_title' => 'အတည်ပြုပြီး',
        'step4_desc' => 'ဂုဏ်ယူပါသည်။ သင့်ပညာသင်ဆု လျှောက်ထားမှု အတည်ပြုပြီးပါပြီ။',
        'no_result' => 'လျှောက်လွှာနံပါတ် မတွေ့ရှိပါ',
        'not_found_desc' => 'ကျေးဇူးပြု၍ သင့်လျှောက်လွှာနံပါတ်ကို မှန်ကန်စွာ စစ်ဆေးပြီး ထပ်မံကြိုးစားပါ။',
        'rejected' => 'ငြင်းပယ်ခံရ',
        'rejected_desc' => 'သင့်လျှောက်လွှာကို ငြင်းပယ်ထားပါသည်။ အသေးစိတ်အချက်အလက်များအတွက် ရုံးသို့ ဆက်သွယ်မေးမြန်းနိုင်ပါသည်။',
    ];
} else {
    $st_lang = [
        'badge' => 'APPLICATION TRACKING',
        'title' => 'Check Your Application Status',
        'desc' => 'Enter your unique application ID to check the real-time status of your scholarship application.',
        'input_label' => 'APPLICATION ID',
        'input_ph' => 'e.g., EG-2026-8974',
        'btn_track' => 'Track Status',
        'result_title' => 'Latest Update Summary',
        'lbl_applicant' => 'Applicant Name',
        'lbl_grant' => 'Scholarship Program',
        'lbl_date' => 'Last Updated On',
        'lbl_reviewer' => 'Reviewer',
        'lbl_recommendation' => 'Recommendation',
        'lbl_approved_by' => 'Approved By',
        'reviewed' => 'Reviewed',
        'not_reviewed' => 'Not Yet Reviewed',
        'recommended' => 'Recommended',
        'not_recommended' => 'Not Recommended',
        'approved' => 'Approved',
        'step1_title' => 'Submitted',
        'step1_desc' => 'Your documents have been successfully compiled and submitted.',
        'step2_title' => 'Under Review',
        'step2_desc' => 'The academic board is verifying your grade transcripts and eligibility.',
        'step3_title' => 'Recommendation',
        'step3_desc' => 'Reviewer evaluates and provides a recommendation.',
        'step4_title' => 'Approved',
        'step4_desc' => 'Congratulations! Your funding grant package has been finalized.',
        'no_result' => 'Application ID Not Found',
        'not_found_desc' => 'Please check your application ID and try again.',
        'rejected' => 'Rejected',
        'rejected_desc' => 'Your application has been rejected. Please contact the office for more details.',
    ];
}

$app_no = isset($_GET['app_no']) ? trim($_GET['app_no']) : '';
$application = null;
$error = false;

if ($app_no !== '') {
    $stmt = $conn->prepare("
        SELECT a.*, s.name AS student_name, sc.scheme_name,
               ar.recommendation, ar.remarks AS review_remarks, ar.reviewed_at,
               r.name AS reviewer_name,
               adm.name AS admin_name
        FROM applications a
        JOIN student s ON a.student_id = s.id
        JOIN schemes sc ON a.scheme_id = sc.id
        LEFT JOIN application_reviews ar ON a.id = ar.application_id
        LEFT JOIN reviewers r ON ar.reviewer_id = r.id
        LEFT JOIN admin adm ON a.approved_by = adm.id
        WHERE a.application_no = ?
    ");
    $stmt->bind_param("s", $app_no);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    if (!$application) $error = true;
}
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-12 w-full flex-grow">
    
    <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-[#003D3B]/70 bg-[#003D3B]/5 px-2.5 py-1 rounded">
            <?php echo $st_lang['badge']; ?>
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3 mb-4">
            <?php echo $st_lang['title']; ?>
        </h2>
        <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
            <?php echo $st_lang['desc']; ?>
        </p>
    </div>

    <div class="max-w-xl mx-auto bg-white border border-slate-100 p-6 rounded-2xl shadow-sm mb-12">
        <form method="GET" class="space-y-4">
            <input type="hidden" name="lang" value="<?php echo $is_mm ? 'mm' : 'en'; ?>">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <?php echo $st_lang['input_label']; ?>
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="app_no" placeholder="<?php echo $st_lang['input_ph']; ?>" value="<?php echo htmlspecialchars($app_no); ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#003D3B]/20 focus:border-[#003D3B] text-sm">
                    <button type="submit" class="bg-[#006D69] text-white font-bold text-sm px-6 py-2.5 rounded-lg hover:bg-[#002625] transition whitespace-nowrap flex items-center gap-2">
                        🔍 <?php echo $st_lang['btn_track']; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($app_no !== ''): ?>

        <?php if ($error): ?>
        <div class="max-w-4xl mx-auto bg-slate-50/50 border border-slate-100 rounded-2xl p-6 sm:p-8 text-center">
            <div class="text-5xl mb-4">🔍</div>
            <h3 class="text-xl font-bold text-slate-900 mb-2"><?php echo $st_lang['no_result']; ?></h3>
            <p class="text-slate-500"><?php echo $st_lang['not_found_desc']; ?></p>
        </div>
        <?php elseif ($application): ?>

        <?php
        $status = $application['status'];
        $is_rejected = $status === 'Rejected';
        $is_approved = $status === 'Approved';
        $is_recommended = $status === 'Recommended';
        $is_under_review = $status === 'Under Review';
        $is_submitted = $status === 'Submitted';

        $steps = [
            ['done' => true, 'active' => false],
            ['done' => $is_under_review || $is_recommended || $is_approved, 'active' => $is_submitted],
            ['done' => $is_recommended || $is_approved, 'active' => $is_under_review],
            ['done' => $is_approved, 'active' => $is_recommended],
        ];
        if ($is_rejected) {
            $steps[1]['done'] = true;
            $steps[1]['active'] = false;
        }
        ?>

        <div class="max-w-4xl mx-auto bg-slate-50/50 border border-slate-100 rounded-2xl p-6 sm:p-8">
            <h3 class="text-base font-bold text-slate-900 mb-6 flex items-center gap-2">
                📊 <span><?php echo $st_lang['result_title']; ?></span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10 text-xs sm:text-sm bg-white p-4 border border-slate-100 rounded-xl">
                <div>
                    <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_applicant']; ?></span>
                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($application['student_name']); ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_grant']; ?></span>
                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($application['scheme_name']); ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_date']; ?></span>
                    <span class="font-bold text-slate-800"><?php echo date('M d, Y', strtotime($application['apply_date'] ?? 'now')); ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_reviewer']; ?></span>
                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($application['reviewer_name'] ?? $st_lang['not_reviewed']); ?></span>
                </div>
            </div>

            <?php if ($application['recommendation']): ?>
            <div class="mb-6 bg-white p-4 border border-slate-100 rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs sm:text-sm">
                    <div>
                        <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_recommendation']; ?></span>
                        <span class="inline-flex items-center gap-1.5 font-bold px-2.5 py-0.5 rounded-full text-xs <?php echo $application['recommendation'] === 'Recommended' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                            <?php if ($application['recommendation'] === 'Recommended'): ?>✅<?php else: ?>❌<?php endif; ?>
                            <?php echo $application['recommendation'] === 'Recommended' ? $st_lang['recommended'] : $st_lang['not_recommended']; ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_reviewer']; ?></span>
                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($application['reviewer_name'] ?? '-'); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5"><?php echo $is_mm ? 'ပြန်လည်သုံးသပ်သည့်ရက်' : 'Reviewed On'; ?></span>
                        <span class="font-bold text-slate-800"><?php echo $application['reviewed_at'] ? date('M d, Y', strtotime($application['reviewed_at'])) : '-'; ?></span>
                    </div>
                </div>
                <?php if ($application['review_remarks']): ?>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block mb-1 text-xs"><?php echo $is_mm ? 'မှတ်ချက်' : 'Remarks'; ?></span>
                    <p class="text-sm text-slate-700"><?php echo htmlspecialchars($application['review_remarks']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($application['admin_name']): ?>
            <div class="mb-6 bg-white p-4 border border-slate-100 rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                    <div>
                        <span class="text-slate-400 block mb-0.5"><?php echo $st_lang['lbl_approved_by']; ?></span>
                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($application['admin_name']); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5"><?php echo $is_mm ? 'အတည်ပြုသည့်ရက်' : 'Approved On'; ?></span>
                        <span class="font-bold text-slate-800"><?php echo $application['approved_at'] ? date('M d, Y', strtotime($application['approved_at'])) : '-'; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_rejected): ?>
            <div class="mb-6 bg-rose-50 border border-rose-200 rounded-xl p-5 text-center">
                <div class="text-4xl mb-2">😞</div>
                <h4 class="text-lg font-bold text-rose-800"><?php echo $st_lang['rejected']; ?></h4>
                <p class="text-sm text-rose-600 mt-1"><?php echo $st_lang['rejected_desc']; ?></p>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                <?php $step_titles = ['step1_title', 'step2_title', 'step3_title', 'step4_title']; ?>
                <?php $step_descs = ['step1_desc', 'step2_desc', 'step3_desc', 'step4_desc']; ?>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <?php
                    $done = $steps[$i]['done'];
                    $active = $steps[$i]['active'];
                    if ($is_rejected && $i < 2) {
                        $done = true; $active = false;
                    } elseif ($is_rejected && $i >= 2) {
                        $done = false; $active = false;
                    }
                    if ($done) {
                        $border = 'border-emerald-400 bg-emerald-50';
                        $circle = 'bg-emerald-600 text-white';
                        $title_cls = 'text-emerald-800';
                        $desc_cls = 'text-emerald-700';
                        $icon = '✓';
                    } elseif ($active) {
                        $border = 'border-[#003D3B] bg-white shadow-sm';
                        $circle = 'bg-[#003D3B] text-white animate-pulse';
                        $title_cls = 'text-slate-900';
                        $desc_cls = 'text-slate-600';
                        $icon = ($i + 1);
                    } else {
                        $border = 'border-slate-100 bg-white/60';
                        $circle = 'bg-slate-200 text-slate-400';
                        $title_cls = 'text-slate-400';
                        $desc_cls = 'text-slate-400';
                        $icon = ($i + 1);
                    }
                    ?>
                    <div class="relative flex flex-col items-start p-4 rounded-xl border-2 <?php echo $border; ?>">
                        <span class="w-6 h-6 rounded-full <?php echo $circle; ?> flex items-center justify-center text-xs font-bold mb-3"><?php echo $icon; ?></span>
                        <h4 class="text-sm font-bold <?php echo $title_cls; ?>"><?php echo $st_lang[$step_titles[$i]]; ?></h4>
                        <p class="text-xs <?php echo $desc_cls; ?> mt-1 leading-relaxed"><?php echo $st_lang[$step_descs[$i]]; ?></p>
                    </div>
                <?php endfor; ?>
            </div>

        </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php 
$conn->close();
include_once('../includes/footer.php'); 
?>
