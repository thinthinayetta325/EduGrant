<?php
if ($is_mm) {
    $f_lang = [
        'desc' => 'မြန်မာနိုင်ငံတစ်ဝှမ်းရှိ ပညာသင်ဆုများ၊ ထောက်ပံ့ကြေးများနှင့် ပညာရေးအခွင့်အလမ်းများမှတစ်ဆင့် ကျောင်းသားကျောင်းသူများကို စွမ်းရည်မြှင့်တင်ပေးခြင်း။',
        'quick' => 'အမြန်လင့်ခ်များ',
        'portal' => 'ပညာသင်ဆု ပေါ်တယ်',
        'nav_status' => 'လျှောက်လွှာအခြေအနေ',
        'nav_contact' => 'ဆက်သွယ်ရန်',
        'support' => 'ပံ့ပိုးမှု',
        'help' => 'ကူညီရေးစင်တာ',
        'rights' => 'မူပိုင်ခွင့်အားလုံး လုံခြုံပြီးဖြစ်သည်။'
    ];
} else {
    $f_lang = [
        'desc' => 'Empowering students through scholarships, grants, and educational opportunities across Myanmar.',
        'quick' => 'QUICK LINKS',
        'portal' => 'Scholarship Portal',
        'nav_status' => 'Application Status',
        'nav_contact' => 'Contact Us',
        'support' => 'SUPPORT',
        'help' => 'Help Center',
        'rights' => 'All Rights Reserved.'
    ];
}
?>
    <footer class="bg-[#006D69] text-slate-300 px-4 sm:px-6 py-12 mt-20 border-t border-teal-950" style="<?php echo $is_mm ? "font-family: 'Padauk', 'Pyidaungsu', sans-serif;" : ''; ?>">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 text-sm ">
            <div>
                <h3 class="text-white text-lg font-bold mb-3">EduGrant Myanmar</h3>
                <p class=" hover:text-white transition"><?php echo $f_lang['desc']; ?></p>
            </div>
            <div>
                <h4 class="text-white font-bold tracking-wider uppercase text-xs mb-4"><?php echo $f_lang['quick']; ?></h4>
                <ul class="space-y-2">
                    <li><a href="scholarships.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white transition"><?php echo $f_lang['portal']; ?></a></li>
                    <li><a href="../common/status.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white transition"><?php echo $f_lang['nav_status']; ?></a></li>
                    <li><a href="../common/contact.php?lang=<?php echo $is_mm ? 'mm' : 'en'; ?>" class="hover:text-white transition"><?php echo $f_lang['nav_contact']; ?></a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold tracking-wider uppercase text-xs mb-4"><?php echo $f_lang['support']; ?></h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-white transition"><?php echo $f_lang['help']; ?></a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold tracking-wider uppercase text-xs mb-4">FOLLOW US</h4>
                <div class="flex gap-3 text-lg mt-2">
                    <a href="#" class="bg-white/10 w-8 h-8 flex items-center justify-center rounded-full text-white">📘</a>
                    <a href="#" class="bg-white/10 w-8 h-8 flex items-center justify-center rounded-full text-white">📷</a>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto border-t border-teal-900 mt-10 pt-6 text-center text-xs text-slate-100">
            © 2026 EduGrant Myanmar. <?php echo $f_lang['rights']; ?>
        </div>
    </footer>
</div> </body>
</html>