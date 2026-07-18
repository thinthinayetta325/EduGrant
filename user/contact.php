<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['student_id']);
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';
$student_data = ['name' => '', 'email' => ''];
$form_success = '';
$form_error = '';

$contact_locked = false;
$allowed_student = false;

if ($is_logged_in) {
    include '../config/db.php';
    $student_id = $_SESSION['student_id'];

    $stmt = $conn->prepare("SELECT name, email FROM student WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $student_data = $res ? ['name' => $res['name'], 'email' => $res['email']] : ['name' => '', 'email' => ''];
    $stmt->close();

    $allowed_student = (isset($student_data['name']) && strtolower(trim($student_data['name'])) === 'mya mya');

    if ($allowed_student) {
        $lock_check = $conn->prepare("SELECT student_id FROM contact_locks WHERE student_id != ? LIMIT 1");
        $lock_check->bind_param("i", $student_id);
        $lock_check->execute();
        $lock_result = $lock_check->get_result();
        $lock_check->close();

        if ($lock_result->num_rows > 0) {
            $contact_locked = true;
        } else {
            $contact_locked = false;
            $upsert = $conn->prepare("INSERT INTO contact_locks (student_id, locked_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE locked_at = NOW()");
            $upsert->bind_param("i", $student_id);
            $upsert->execute();
            $upsert->close();
        }
    } else {
        $lock_check = $conn->prepare("SELECT student_id FROM contact_locks LIMIT 1");
        $lock_check->execute();
        $lock_result = $lock_check->get_result();
        $lock_check->close();
        $contact_locked = ($lock_result->num_rows > 0);
    }

    if ($allowed_student && !$contact_locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
        $c_full_name = trim($_POST['full_name'] ?? '');
        $c_email = trim($_POST['email'] ?? '');
        $c_subject = trim($_POST['subject'] ?? '');
        $c_message = trim($_POST['message'] ?? '');

        if ($c_full_name !== '' && $c_email !== '' && $c_subject !== '' && $c_message !== '') {
            $insert_msg = $conn->prepare("INSERT INTO contact_messages (student_id, full_name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
            if ($insert_msg) {
                $insert_msg->bind_param("issss", $student_id, $c_full_name, $c_email, $c_subject, $c_message);
                if ($insert_msg->execute()) {
                    $insert_msg->close();

                    $admin_id = 1;
                    $notif_title = 'New Contact Message';
                    $notif_msg = htmlspecialchars($c_full_name) . ' sent a message: ' . htmlspecialchars($c_subject);
                    $insert_notif = $conn->prepare("INSERT INTO notifications (admin_id, title, message, type, is_read) VALUES (?, ?, ?, 'contact_message', 0)");
                    if ($insert_notif) {
                        $insert_notif->bind_param("iss", $admin_id, $notif_title, $notif_msg);
                        $insert_notif->execute();
                        $insert_notif->close();
                    }

                    $form_success = $is_mm ? 'သင့်စာတစ်စောင်ပို့ပြီးပါပြီ။' : 'Your message has been sent successfully!';
                } else {
                    $form_error = $is_mm ? 'စာပို့ရာတွင် အမှားရှိနေပါသည်။' : 'Failed to send message. Please try again.';
                }
            } else {
                $form_error = $is_mm ? 'စာပို့ရာတွင် အမှားရှိနေပါသည်။' : 'Failed to send message. Please try again.';
            }
        } else {
            $form_error = $is_mm ? 'ကျေးဇူးပြု၍ အချက်အလက်အားလုံးကို ဖြည့်ပါ။' : 'Please fill in all fields.';
        }
    }

    $conn->close();
}

if ($is_mm) {
    $c_lang = [
        'title' => 'ဆက်သွယ်ရန်',
        'subtitle' => 'သင်သိရှိလိုသည်များကို ကျွန်ုပ်တို့ထံ အချိန်မရွေး မေးမြန်းစုံစမ်းနိုင်ပါသည်',
        'card_address_title' => 'ရုံးချုပ်လိပ်စာ',
        'card_address_desc' => 'အမှတ် (၄၅)၊ တက္ကသိုလ်ရိပ်သာလမ်း၊ ကမာရွတ်မြို့နယ်၊ ရန်ကုန်မြို့။',
        'card_phone_title' => 'ဖုန်းနံပါတ်',
        'card_email_title' => 'အီးမေးလ် လိပ်စာ',
        'form_title' => 'တိုက်ရိုက် သဝဏ်လွှာပေးပို့ရန်',
        'label_name' => 'အမည် အပြည့်အစုံ',
        'label_email' => 'အီးမေးလ်',
        'label_subject' => 'အကြောင်းအရာ',
        'label_message' => 'ရေးသားလိုသည့်အကြောင်းအရာ',
        'btn_send' => 'ပေးပို့မည်',
        'placeholder_message' => 'ဤနေရာတွင် စတင်ရေးသားပါ...',
    ];
} else {
    $c_lang = [
        'title' => 'Contact Us',
        'subtitle' => 'Have questions or need assistance? Reach out to our response team.',
        'card_address_title' => 'Our Office Address',
        'card_address_desc' => 'No. 45, University Avenue Road, Kamayut Township, Yangon, Myanmar.',
        'card_phone_title' => 'Phone Support',
        'card_email_title' => 'Email Correspondence',
        'form_title' => 'Send an Instant Message',
        'label_name' => 'Full Name',
        'label_email' => 'Email Address',
        'label_subject' => 'Subject Heading',
        'label_message' => 'Your Detailed Message',
        'btn_send' => 'Send Message',
        'placeholder_message' => 'Type your message details here...',
    ];
}

 include("../includes/header.php");
?>

<!-- Main Contact Section -->
<main class="max-w-6xl mx-auto px-4 sm:px-6 my-12 flex-grow w-full">
    <div class="mb-10 text-center md:text-left">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#003D3B] tracking-tight">
            <?php echo $c_lang['title']; ?>
        </h2>
        <p class="text-sm text-slate-500 mt-1.5"><?php echo $c_lang['subtitle']; ?></p>
    </div>

    <!-- Layout Container Grid Split -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
        
        <!-- Left Side: Directory Contact Info Cards -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Address Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_address_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-medium leading-relaxed"><?php echo $c_lang['card_address_desc']; ?></p>
                </div>
            </div>

            <!-- Phone Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.72l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.72.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_phone_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-semibold tracking-wide">+95 1 234 5678, +95 9 8765 4321</p>
                </div>
            </div>

            <!-- Email Item Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <div class="bg-teal-50 text-[#006D69] p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm tracking-wide uppercase opacity-75"><?php echo $c_lang['card_email_title']; ?></h4>
                    <p class="text-sm text-slate-600 mt-1 font-semibold tracking-wide hover:text-[#006D69]"><a href="mailto:support@edugrant.gov.mm">support@edugrant.gov.mm</a></p>
                </div>
            </div>

        </div>

        <!-- Right Side: Contact Form -->
        <div class="lg:col-span-3">
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-6 sm:p-8">
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg class="w-5 h-5 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <?php echo $c_lang['form_title']; ?>
                </h3>

                <?php if (!empty($form_success)): ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl font-medium"><?php echo $form_success; ?></div>
                <?php endif; ?>
                <?php if (!empty($form_error)): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl font-medium"><?php echo $form_error; ?></div>
                <?php endif; ?>

                <?php if ($is_logged_in && $allowed_student && !$contact_locked): ?>
                <form action="?lang=<?php echo $lang_param; ?>" method="POST" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_name']; ?></label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($student_data['name'] ?? ''); ?>" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_email']; ?></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_subject']; ?></label>
                        <input type="text" name="subject" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $c_lang['label_message']; ?></label>
                        <textarea name="message" rows="5" required placeholder="<?php echo $c_lang['placeholder_message']; ?>" class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none resize-none"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="contact_submit" class="w-full sm:w-auto bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition transform active:scale-[0.99]">
                            <?php echo $c_lang['btn_send']; ?>
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-10">
                    <div class="text-4xl mb-4">
                        <?php
                        if ($contact_locked) echo '⏳';
                        elseif ($is_logged_in) echo '🚫';
                        else echo '🔒';
                        ?>
                    </div>
                    <p class="text-slate-600 text-sm mb-4">
                        <?php
                        if ($contact_locked && $is_logged_in) {
                            echo $is_mm ? 'အခြားကျောင်းသားတစ်ဦးက စာတိုပေးစာပို့နေပါသည်။ ကျေးဇူးပြု၍ နောက်မှ ထပ်ကြိုးစားပါ။' : 'Another student is currently sending a message. Please try again later.';
                        } elseif ($is_logged_in) {
                            echo $is_mm ? 'သင့်အား စာတိုပေးစာပို့ခွင့် မရှိပါ။' : 'You are not authorized to send messages.';
                        } else {
                            echo $is_mm ? 'စာတိုပေးစာပို့ရန် အကောင့်ဝင်ရန် လိုအပ်ပါသည်။' : 'Please log in to send a message.';
                        }
                        ?>
                    </p>
                    <?php if (!$is_logged_in): ?>
                    <a href="login.php?lang=<?php echo $lang_param; ?>" class="inline-block bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm px-8 py-3 rounded-xl shadow-md hover:shadow-lg transition"><?php echo $is_mm ? 'အကောင့်ဝင်ရန်' : 'Log In'; ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php include_once("../includes/footer.php");?>
