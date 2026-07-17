<?php
ob_start(); // Buffer the output
session_start();
// ... rest of your code ...
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in as student, redirect to home
if (isset($_SESSION['student_id'])) {
    header("Location: ../user/home.php");
    exit();
}

include '../includes/header.php';

// 2. Define Language Dictionary
if ($is_mm) {
    $r_lang = [
        'title' => 'ကျောင်းသား အကောင့်သစ်ဖွင့်ရန်',
        'roll' => 'Roll No',
        'name' => 'အမည်အပြည့်အစုံ',
        'email' => 'အီးမေးလ်',
        'phone' => 'ဖုန်းနံပါတ်',
        'pass' => 'စကားဝှက် (အနည်းဆုံး ၈ လုံး)',
        'gender' => 'ကျား / မ',
        'male' => 'ကျား',
        'female' => 'မ',
        'address' => 'နေရပ်လိပ်စာ',
        'btn' => 'အကောင့်ဖွင့်မည်',
       'login_link' => 'အကောင့်ရှိပြီးသားလား? ဝင်မည်။',
        'status_empty' => 'စကားဝှက် မရိုက်ရသေးပါ',
        'status_weak' => 'အားနည်းသည် (အနည်းဆုံး ၈ လုံး)',
        'status_med' => 'အသင့်အတင့်',
        'status_strong' => 'အားကောင်းသည်',
        'msg_success' => 'စာရင်းသွင်းခြင်း အောင်မြင်ပါသည်။'
    ];
} else {
    $r_lang = [
        'title' => 'Create Student Account',
        'roll' => 'Roll No',
        'name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'pass' => 'Password (Min 8 characters)',
        'gender' => 'Gender',
        'male' => 'Male',
        'female' => 'Female',
        'address' => 'Address',
        'btn' => 'Register Now',
       'login_link' => 'Already have an account? Login',
        'status_empty' => 'Empty',
        'status_weak' => 'Weak (Min 8)',
        'status_med' => 'Medium',
        'status_strong' => 'Strong',
        'msg_success' => 'Registration Successful!'
    ];
}

// 3. Handle Form
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "grant_portal");
    $roll_no  = $conn->real_escape_string($_POST['roll_no']);
    $name     = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $phone    = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password']; 
    $gender   = $conn->real_escape_string($_POST['gender']);
    $address  = $conn->real_escape_string($_POST['address']);

    if (strlen($password) < 8) {
        $message = $is_mm ? "စကားဝှက်သည် အနည်းဆုံး ၈ လုံးရှိရပါမည်။" : "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(09\d{9}|959\d{9})$/', preg_replace('/[\s\-]/', '', $phone))) {
        $message = $is_mm ? "ဖုန်းနံပါတ်သည် 09 ဖြင့် စတင်ပါက ဂဏန်း ၁၁ လုံး ဖြစ်ရပါမည်။ 959 ဖြင့် စတင်ပါက ဂဏန်း ၁၂ လုံး ဖြစ်ရပါမည်။" : "If starting with 09, phone must be 11 digits. If starting with 959, phone must be 12 digits.";
    } else {
        // Check if roll_no already exists
        $checkRoll = $conn->prepare("SELECT id FROM student WHERE roll_no = ? LIMIT 1");
        $checkRoll->bind_param("s", $roll_no);
        $checkRoll->execute();
        $checkRoll->store_result();
        if ($checkRoll->num_rows > 0) {
            $message = $is_mm ? "ဤ Roll No ဖြင့် အကောင့်ရှိပြီးသားဖြစ်သည်။" : "An account with this Roll No already exists.";
        } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM student WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = $is_mm ? "ဤအီးမေးလ်ဖြင့် အကောင့်ရှိပြီးသားဖြစ်သည်။" : "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO student (roll_no, name, email, phone, password, gender, address) 
                    VALUES ('$roll_no', '$name', '$email', '$phone', '$hashed_password', '$gender', '$address')";

            if ($conn->query($sql) === TRUE) {
                header("Location: ../auth/login.php");
                exit();
            } else {
                $message = $is_mm ? "မှတ်ပုံတင်ရာတွင် အမှားရှိခဲ့သည်။" : "Registration failed. Please try again.";
            }
        }
        $check->close();
        }
        $checkRoll->close();
    }
    $conn->close();
}
?>

<div class="w-full max-w-lg mx-auto px-4 sm:px-0 py-8 sm:py-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 sm:px-8 pt-8 pb-2 text-center">
            <div class="w-14 h-14 bg-[#006D69]/10 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h2 class="text-slate-900 text-2xl font-bold"><?php echo $r_lang['title']; ?></h2>
        </div>

        <div class="px-6 sm:px-8 py-6">

            <?php if ($message): ?>
                <div class="flex items-center gap-2.5 <?php echo strpos($message, 'Error') !== false ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'; ?> border text-sm px-4 py-3 rounded-xl mb-5">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?php echo strip_tags($message); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">

                <!-- Row: Roll No + Name -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['roll']; ?></label>
                        <input type="text" name="roll_no" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="e.g. STU-001">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['name']; ?></label>
                        <input type="text" name="name" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="John Doe">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['email']; ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="email" name="email" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="you@example.com">
                    </div>
                </div>

                <!-- Row: Phone + Gender -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['phone']; ?></label>
                        <input type="tel" name="phone" required pattern="(09[0-9]{9}|959[0-9]{9})"
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="09 123456789"
                               oninput="this.value=this.value.replace(/[^0-9]/g,'');if(this.value.startsWith('959')){this.maxLength=12;}else{this.maxLength=11;}if(this.value.length>=2&&!this.value.startsWith('09')&&!this.value.startsWith('959')){this.value='';}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['gender']; ?></label>
                        <select name="gender"
                                class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none">
                            <option value="Male"><?php echo $r_lang['male']; ?></option>
                            <option value="Female"><?php echo $r_lang['female']; ?></option>
                        </select>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['pass']; ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" id="password" name="password" minlength="8" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-10 pr-12 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
                        <button type="button" id="toggleEye"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg id="eyeOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eyeClosed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l6.242-6.242M9.878 9.878l3.536-3.536M21 21l-6.242-6.242"/></svg>
                        </button>
                    </div>
                    <!-- Password Strength -->
                    <div id="strengthMeter" class="mt-2 hidden">
                        <div class="flex gap-1 mb-1">
                            <div id="strBar1" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                            <div id="strBar2" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                            <div id="strBar3" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                        </div>
                        <p id="strText" class="text-xs font-semibold text-slate-400"></p>
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['address']; ?></label>
                    <textarea name="address" rows="3" required
                              class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none resize-none"
                              placeholder="No. 123, Example Street, Township..."></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition transform active:scale-[0.99] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <?php echo $r_lang['btn']; ?>
                </button>
            </form>

            <div class="text-center text-sm text-slate-500 mt-6">
                <a href="login.php" class="text-[#006D69] hover:text-[#005753] font-bold transition">
                    <?php echo $r_lang['login_link']; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const pw = document.getElementById('password');
const eyeOpen = document.getElementById('eyeOpen');
const eyeClosed = document.getElementById('eyeClosed');
const strengthMeter = document.getElementById('strengthMeter');
const strBar1 = document.getElementById('strBar1');
const strBar2 = document.getElementById('strBar2');
const strBar3 = document.getElementById('strBar3');
const strText = document.getElementById('strText');

const lang = {
    empty: "<?php echo $r_lang['status_empty']; ?>",
    weak: "<?php echo $r_lang['status_weak']; ?>",
    med: "<?php echo $r_lang['status_med']; ?>",
    strong: "<?php echo $r_lang['status_strong']; ?>"
};

document.getElementById('toggleEye').addEventListener('click', () => {
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    eyeOpen.classList.toggle('hidden', !hidden);
    eyeClosed.classList.toggle('hidden', hidden);
});

pw.addEventListener('input', () => {
    const val = pw.value;
    if (val.length === 0) {
        strengthMeter.classList.add('hidden');
        strText.textContent = lang.empty;
        return;
    }
    strengthMeter.classList.remove('hidden');

    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    const bars = [strBar1, strBar2, strBar3];
    const colors = ['bg-red-500', 'bg-yellow-500', 'bg-emerald-500'];
    const labels = ['', lang.weak, lang.med, lang.strong];
    const labelColors = ['', 'text-red-500', 'text-yellow-600', 'text-emerald-600'];

    let level = 0;
    if (score <= 1) level = 1;
    else if (score <= 3) level = 2;
    else level = 3;

    bars.forEach((bar, i) => {
        bar.className = `h-1.5 flex-1 rounded-full transition-all duration-300 ${i < level ? colors[level - 1] : 'bg-slate-200'}`;
    });

    strText.className = `text-xs font-semibold ${labelColors[level]}`;
    strText.textContent = labels[level];
});
</script>

<?php include '../includes/footer.php'; ?>