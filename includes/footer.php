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
        'faq' => 'အမေးများသောမေးခွန်းများ',
        'guide' => 'လျှောက်ထားနည်းလမ်းညွှန်',

        'contact' => 'ဆက်သွယ်ရန်',
        'address' => 'ရန်ကုန်၊ မြန်မာ',
        'phone' => '+95 9 123456789',
        'email' => 'support@edugrantmyanmar.com',

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
        'faq' => 'Frequently Asked Questions',
        'guide' => 'Application Guide',

        'contact' => 'CONTACT US',
        'address' => 'Yangon, Myanmar',
        'phone' => '+95 9 123456789',
        'email' => 'support@edugrantmyanmar.com',

        'rights' => 'All Rights Reserved.'
    ];
}
?>


<footer
class="bg-[#006D69] text-slate-300 mt-24 border-t border-teal-900"
style="<?php echo $is_mm ? "font-family:'Padauk','Pyidaungsu',sans-serif;" : ''; ?>">


<div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">


<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">


<!-- EduGrant Myanmar -->
<div>

<h3 class="text-white text-2xl font-bold mb-4">
EduGrant Myanmar
</h3>

<p class="leading-7 text-slate-200">
<?php echo $f_lang['desc']; ?>
</p>

</div>



<!-- Quick Links -->
<div>

<h4 class="text-white font-bold uppercase tracking-wider text-sm mb-4">
<?php echo $f_lang['quick']; ?>
</h4>


<ul class="space-y-3">

<li>
<a href="scholarships.php?lang=<?php echo $is_mm?'mm':'en'; ?>"
class="hover:text-white transition">
<?php echo $f_lang['portal']; ?>
</a>
</li>


<li>
<a href="../common/status.php?lang=<?php echo $is_mm?'mm':'en'; ?>"
class="hover:text-white transition">
<?php echo $f_lang['nav_status']; ?>
</a>
</li>


<li>
<a href="../common/contact.php?lang=<?php echo $is_mm?'mm':'en'; ?>"
class="hover:text-white transition">
<?php echo $f_lang['nav_contact']; ?>
</a>
</li>


</ul>

</div>




<!-- Support -->
<div>

<h4 class="text-white font-bold uppercase tracking-wider text-sm mb-4">
<?php echo $f_lang['support']; ?>
</h4>


<ul class="space-y-3">


<li>
<a href="#" class="hover:text-white transition">
<?php echo $f_lang['help']; ?>
</a>
</li>


<li>
<a href="#" class="hover:text-white transition">
<?php echo $f_lang['faq']; ?>
</a>
</li>


<li>
<a href="#" class="hover:text-white transition">
<?php echo $f_lang['guide']; ?>
</a>
</li>


</ul>

</div>





<!-- Contact Us -->
<div>

<h4 class="text-white font-bold uppercase tracking-wider text-sm mb-4">
<?php echo $f_lang['contact']; ?>
</h4>


<ul class="space-y-3">


<li class="flex gap-2">
<span>📍</span>
<span>
<?php echo $f_lang['address']; ?>
</span>
</li>


<li class="flex gap-2">
<span>📞</span>
<span>
<?php echo $f_lang['phone']; ?>
</span>
</li>


<li class="flex gap-2">
<span>✉️</span>
<span>
<?php echo $f_lang['email']; ?>
</span>
</li>


</ul>

</div>



</div>




<!-- Bottom Footer -->

<div class="border-t border-teal-800 mt-12 pt-6">

<p class="text-center text-sm text-slate-100">

© 2026 EduGrant Myanmar.
<?php echo $f_lang['rights']; ?>

</p>

</div>



</div>


</footer>


</div>
</body>
</html>