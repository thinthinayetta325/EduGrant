<?php
// Fix path to point to root directory
include '../includes/header.php';

// 1. Define Language Translation Dictionary (Updated with role labels)
if (isset($is_mm) && $is_mm) {
    $r_lang = [
        'title' => 'အကောင့်ဝင်ရန်',
        'desc' => 'သင့်အီးမေးလ်နှင့် စကားဝှက်ကို ထည့်သွင်းပါ။',
        'lbl_email' => 'အီးမေးလ်',
        'lbl_pass' => 'စကားဝှက်',
        'btn_login' => 'အကောင့်ဝင်မည်',
        'no_account' => 'အကောင့် မရှိသေးဘူးလား?',
        'register_link' => 'အကောင့်အသစ် ဖွင့်မည်'
    ];
} else {
    $r_lang = [
        'title' => 'Portal Login',
        'desc' => 'Enter your email and password to log in.',
        'lbl_email' => 'EMAIL ADDRESS',
        'lbl_pass' => 'PASSWORD',
        'btn_login' => 'Login Now',
        'no_account' => "Don't have an account?",
        'register_link' => 'Register Now'
    ];
}
?>

<div class="w-full max-w-md mx-auto px-4 sm:px-0 py-8 sm:py-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 sm:px-8 pt-8 pb-2 text-center">
            <div class="w-14 h-14 bg-[#006D69]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-[#006D69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5m-7 5h12"/></svg>
            </div>
            <h2 class="text-slate-900 text-2xl font-bold"><?php echo $r_lang['title']; ?></h2>
            <p class="text-slate-500 text-sm mt-1.5"><?php echo $r_lang['desc']; ?></p>
        </div>

        <div class="px-6 sm:px-8 py-6">

            <!-- Error Alert -->
            <?php if (isset($_GET['error'])): ?>
                <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-5">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>

            <form action="process_login.php?redirect=<?php echo isset($_GET['redirect']) ? urlencode($_GET['redirect']) : ''; ?>" method="POST" class="space-y-5">

                <!-- Email -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['lbl_email']; ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="email" name="email" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="you@example.com">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"><?php echo $r_lang['lbl_pass']; ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-10 pr-12 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-[#006D69] transition outline-none"
                               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
                        <button type="button" id="toggleEye"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <!-- Eye open -->
                            <svg id="eyeOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <!-- Eye closed -->
                            <svg id="eyeClosed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l6.242-6.242M9.878 9.878l3.536-3.536M21 21l-6.242-6.242"/></svg>
                        </button>
                    </div>
                    <!-- Password Strength Meter -->
                    <div id="strengthMeter" class="mt-2 hidden">
                        <div class="flex gap-1 mb-1">
                            <div id="strBar1" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                            <div id="strBar2" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                            <div id="strBar3" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                        </div>
                        <p id="strText" class="text-xs font-semibold text-slate-400"></p>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-[#006D69] hover:bg-[#005753] text-white font-bold text-sm px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition transform active:scale-[0.99] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <?php echo $r_lang['btn_login']; ?>
                </button>
            </form>

            <div class="text-center text-sm text-slate-500 mt-6">
                <?php echo $r_lang['no_account']; ?>
                <a href="../auth/register.php" class="text-[#006D69] hover:text-[#005753] font-bold transition ml-1">
                    <?php echo $r_lang['register_link']; ?>
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

// Toggle password visibility
document.getElementById('toggleEye').addEventListener('click', () => {
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    eyeOpen.classList.toggle('hidden', !hidden);
    eyeClosed.classList.toggle('hidden', hidden);
});

// Password strength
pw.addEventListener('input', () => {
    const val = pw.value;
    if (val.length === 0) {
        strengthMeter.classList.add('hidden');
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
    const labels = ['', 'Weak', 'Medium', 'Strong'];
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