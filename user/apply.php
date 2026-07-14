
<?php
ob_start();
session_start();

$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';

if ($is_mm) {
    $page_lang = [
        'title' => 'ပညာသင်ဆု လျှောက်လွှာ',
        'applying_for' => 'လျှောက်ထားသည့် ပညာသင်ဆု',
        'subtitle' => 'အောက်ပါပုံစံကို ဖြည့်သွင်းပြီး လျှောက်ထားပါ။',
        'label_name' => 'အမည်',
        'label_roll' => 'ခုံအမှတ်',
        'label_email' => 'အီးမေးလ်',
        'label_scheme' => 'ပညာသင်ဆု အစီအစဉ်',
        'label_income' => 'မိသားစု လစဉ်ဝင်ငွေ (ကျပ်)',
        'placeholder_income' => 'ဥပမာ - ၃၀၀၀၀၀၀',
        'btn_submit' => 'လျှောက်လွှာတင်သွင်းမည်',
        'select_scheme' => 'ပညာသင်ဆုရွေးချယ်ပါ',
        'success' => 'လျှောက်လွှာ အောင်မြင်စွာ တင်သွင်းပြီးပါပြီ။',
        'error_fields' => 'ကျေးဇူးပြု၍ အကွက်အားလုံးကို ဖြည့်ပါ။',
        'error_income' => 'ဝင်ငွေသည် ကိန်းဂဏန်း မှန်ကန်ရပါမည်။',
        'error_duplicate' => 'ဤပညာသင်ဆုအတွက် သင်လျှောက်ထားပြီးဖြစ်ပါသည်။',
        'error_submit' => 'လျှောက်လွှာ တင်သွင်းရန် မအောင်မြင်ပါ။',
    ];
} else {
    $page_lang = [
        'title' => 'Scholarship Application',
        'applying_for' => 'Applying for',
        'subtitle' => 'Complete the form below to apply.',
        'label_name' => 'Name',
        'label_roll' => 'Roll Number',
        'label_email' => 'Email',
        'label_scheme' => 'Scholarship Scheme',
        'label_income' => 'Family Monthly Income (MMK)',
        'placeholder_income' => 'e.g. 3000000',
        'btn_submit' => 'Submit Application',
        'select_scheme' => 'Select Scholarship Scheme',
        'success' => 'Application submitted successfully.',
        'error_fields' => 'Please fill all fields.',
        'error_income' => 'Income must be a valid number (no leading zeros).',
        'error_duplicate' => 'You have already applied for this scholarship.',
        'error_submit' => 'Failed to submit application.',
    ];
}

require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    $redirect_url = 'apply.php';
    if (isset($_GET['scheme_id'])) {
        $redirect_url .= '?scheme_id=' . (int)$_GET['scheme_id'];
        if (isset($_GET['lang'])) {
            $redirect_url .= '&lang=' . $_GET['lang'];
        }
    }
    header("Location: ../auth/login.php?redirect=" . urlencode($redirect_url));
    exit();
}

include '../includes/header.php';

$student_id = $_SESSION['student_id'];

/* Get selected scheme from query string */
$selected_scheme_id = isset($_GET['scheme_id']) ? (int)$_GET['scheme_id'] : 0;
$selected_scheme_name = '';
if ($selected_scheme_id > 0) {
    $scheme_stmt = $conn->prepare("SELECT scheme_name FROM schemes WHERE id = ? AND `status`='Active'");
    if ($scheme_stmt) {
        $scheme_stmt->bind_param("i", $selected_scheme_id);
        $scheme_stmt->execute();
        $scheme_result = $scheme_stmt->get_result()->fetch_assoc();
        if ($scheme_result) {
            $selected_scheme_name = $scheme_result['scheme_name'];
        }
        $scheme_stmt->close();
    }
}

$success = "";
$error = "";

/* Get Student Information */
$stmt = $conn->prepare("
    SELECT id,name,email,roll_no
    FROM student
    WHERE id = ?
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Apply Form Submit */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $scheme_id = (int)$_POST['scheme_id'];
    $family_income = trim($_POST['family_income']);

    if (empty($scheme_id) || empty($family_income)) {

        $error = $page_lang['error_fields'];

    } elseif (!ctype_digit($family_income) || $family_income[0] === '0') {

        $error = $page_lang['error_income'];

    } else {

        /* Check Duplicate */
        $check = $conn->prepare("
            SELECT id
            FROM applications
            WHERE student_id = ?
            AND scheme_id = ?
        ");

        $check->bind_param("ii", $student_id, $scheme_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {

            $error = $page_lang['error_duplicate'];
            $check->close();

        } else {

            $check->close();
            /* Generate Application Number */
            $application_no =
                "APP-" .
                strtoupper(substr(md5(uniqid()), 0, 8));

            $status = "Submitted";

            $insert = $conn->prepare("
                INSERT INTO applications
                (
                    student_id,
                    scheme_id,
                    application_no,
                    family_income,
                    apply_date,
                    status,
                    approved_by,
                    approved_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    NULL,
                    NULL
                )
            ");

            $insert->bind_param(
                "iisds",
                $student_id,
                $scheme_id,
                $application_no,
                $family_income,
                $status
            );

            if ($insert->execute()) {

                // Notify reviewers assigned to this scheme
                $app_id = $insert->insert_id;
                $insert->close();
                $student_name = $student['name'] ?? 'A student';
                $reviewers = $conn->prepare("SELECT reviewer_id FROM reviewer_scheme WHERE scheme_id = ?");
                if ($reviewers) {
                    $reviewers->bind_param("i", $scheme_id);
                    $reviewers->execute();
                    $rev_result = $reviewers->get_result();
                    $reviewer_ids = [];
                    while ($rev = $rev_result->fetch_assoc()) {
                        $reviewer_ids[] = $rev['reviewer_id'];
                    }
                    $reviewers->close();
                }

                // Fallback: if no reviewers assigned to scheme, notify all reviewers
                if (empty($reviewer_ids)) {
                    $all_rev = $conn->query("SELECT id FROM reviewers");
                    if ($all_rev) {
                        while ($r = $all_rev->fetch_assoc()) {
                            $reviewer_ids[] = $r['id'];
                        }
                    }
                }

                foreach ($reviewer_ids as $rid) {
                    $notify = $conn->prepare("INSERT INTO notifications (reviewer_id, title, message, type) VALUES (?, ?, ?, 'new_application')");
                    if ($notify) {
                        $title = "New Application Submitted";
                        $msg = "$student_name submitted a new application (#$application_no) for review.";
                        $notify->bind_param("iss", $rid, $title, $msg);
                        $notify->execute();
                        $notify->close();
                    }
                }

                $_SESSION['success_message'] =
                    $page_lang['success'];

                header("Location: profile.php?lang=" . $lang_param);
                exit();

            } else {

                $error = $page_lang['error_submit'];
            }
        }
    }
}
?>

<main class="max-w-4xl mx-auto px-4 py-10">

    <?php if ($is_mm): ?>
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
        input, select, textarea, button, label, h1, p, div, option {
            font-family: 'Padauk', 'Pyidaungsu', sans-serif !important;
        }
    </style>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8">

        <div class="mb-8">

            <h1 class="text-3xl font-bold text-slate-900">
                <?= $page_lang['title'] ?>
            </h1>

            <?php if ($selected_scheme_name): ?>
                <p class="text-teal-700 font-semibold mt-2 text-lg">
                    <?= $page_lang['applying_for']; ?>: <?= htmlspecialchars($selected_scheme_name); ?>
                </p>
            <?php endif; ?>

            <p class="text-slate-500 mt-2">
                <?= $page_lang['subtitle'] ?>
            </p>

        </div>

        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Student Information -->
        <div class="grid md:grid-cols-3 gap-4 mb-8">

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    <?= $page_lang['label_name'] ?>
                </label>

                <input
                    type="text"
                    readonly
                    value="<?= htmlspecialchars($student['name']) ?>"
                    class="w-full bg-slate-100 border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    <?= $page_lang['label_roll'] ?>
                </label>

                <input
                    type="text"
                    readonly
                    value="<?= htmlspecialchars($student['roll_no']) ?>"
                    class="w-full bg-slate-100 border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    <?= $page_lang['label_email'] ?>
                </label>

                <input
                    type="email"
                    readonly
                    value="<?= htmlspecialchars($student['email']) ?>"
                    class="w-full bg-slate-100 border rounded-xl px-4 py-3">
            </div>

        </div>

        <!-- Application Form -->
        <form method="POST" class="space-y-6">

            <!-- Scheme -->
            <div>

                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                    <?= $page_lang['label_scheme'] ?>
                </label>

                <?php if ($selected_scheme_name): ?>
                    <input type="hidden" name="scheme_id" value="<?= $selected_scheme_id; ?>">
                    <div class="w-full bg-teal-50 border border-teal-200 rounded-xl px-4 py-3 text-teal-800 font-semibold">
                        <?= htmlspecialchars($selected_scheme_name); ?>
                    </div>
                <?php else: ?>
                    <select
                        name="scheme_id"
                        required
                        class="w-full border border-slate-200 rounded-xl px-4 py-3">

                        <option value="">
                            <?= $page_lang['select_scheme'] ?>
                        </option>

                        <?php
                        $schemes = $conn->query("
                            SELECT id, scheme_name
                            FROM schemes
                            WHERE `status`='Active'
                            ORDER BY scheme_name
                        ");

                        while($scheme = $schemes->fetch_assoc()):
                        ?>

                            <option value="<?= $scheme['id']; ?>">
                                <?= htmlspecialchars($scheme['scheme_name']); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>
                <?php endif; ?>

            </div>

            <!-- Family Income -->
            <div>

                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                    <?= $page_lang['label_income'] ?>
                </label>

                <input
                    type="text"
                    name="family_income"
                    inputmode="numeric"
                    pattern="[1-9][0-9]*"
                    value=""
                    required
                    oninput="this.value = this.value.replace(/^0+|[^0-9]/g, '')"
                    placeholder="<?= $page_lang['placeholder_income'] ?>"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3">

            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="w-full bg-[#004D4A] hover:bg-[#003D3B] text-white py-3 rounded-xl font-bold transition">

                <?= $page_lang['btn_submit'] ?>

            </button>

        </form>

    </div>

</main>

<?php include '../includes/footer.php'; ?>

