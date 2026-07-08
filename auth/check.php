<?php
// Fix path to point to root directory
include '../includes/header.php';

// 1. Define Language Translation Dictionary
if (isset($is_mm) && $is_mm) {
    $r_lang = [
        'title' => 'ကျောင်းသား အကောင့်ဝင်ရန်',
        'desc' => 'အီးမေးလ်နှင့် စကားဝှက်ကို ထည့်သွင်းပါ။',
        'lbl_email' => 'အီးမေးလ်',
        'lbl_pass' => 'စကားဝှက်',
        'btn_login' => 'အကောင့်ဝင်မည်',
        'no_account' => 'အကောင့် မရှိသေးဘူးလား?',
        'register_link' => 'အကောင့်အသစ် ဖွင့်မည်'
    ];
} else {
    $r_lang = [
        'title' => 'Student Login',
        'desc' => 'Please enter your email and password to log in.',
        'lbl_email' => 'EMAIL ADDRESS',
        'lbl_pass' => 'PASSWORD',
        'btn_login' => 'Login Now',
        'no_account' => "Don't have an account?",
        'register_link' => 'Register Now'
    ];
}
?>

<div style="max-width: 450px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: sans-serif;">
    <h2 style="color:#003D3B; margin-top:0;" class="text-center font-bold text-lg py-3"><?php echo $r_lang['title']; ?></h2>
    <p style="color:#64748b; font-size: 14px; margin-bottom: 20px;"><?php echo $r_lang['desc']; ?></p>

<form action="process_login.php?redirect=<?php echo isset($_GET['redirect']) ? urlencode($_GET['redirect']) : ''; ?>" method="POST">        
    <label style="display:block; font-size:12px; font-weight:bold; color:#64748b; margin-bottom:5px;"><?php echo $r_lang['lbl_email']; ?></label>
        <input type="email" name="email" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">

        <label style="display:block; font-size:12px; font-weight:bold; color:#64748b; margin-bottom:5px;"><?php echo $r_lang['lbl_pass']; ?></label>
        <div style="position:relative; margin-bottom:20px;">
            <input type="password" id="password" name="password" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
            <button type="button" id="toggleEye" style="position:absolute; right:10px; top:10px; background:none; border:none; cursor:pointer; font-size:18px;">👁️</button>
        </div>

        <button type="submit" style="width:100%; padding:12px; background:#0f6d6aff; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">
            <?php echo $r_lang['btn_login']; ?>
        </button>
    </form>

    <div style="text-align: center; font-size: 14px; color: #64748b; margin-top: 15px;">
        <?php echo $r_lang['no_account']; ?> 
        <a href="register.php" style="color: #0f6d6aff; font-weight: bold; text-decoration: none; margin-left: 5px;">
            <?php echo $r_lang['register_link']; ?>
        </a>
    </div>
</div>

<script>
const pw = document.getElementById('password');
const eye = document.getElementById('toggleEye');

eye.addEventListener('click', () => {
    // Toggle input type
    pw.type = (pw.type === 'password') ? 'text' : 'password';
    // Toggle icon
    eye.textContent = (pw.type === 'password') ? '👁️' : '🙈';
});
</script>

<?php include '../includes/footer.php'; ?>