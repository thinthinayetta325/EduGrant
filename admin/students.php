<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin Clerk";
$is_mm = (isset($_GET['lang']) && $_GET['lang'] === 'mm');
$lang_param = $is_mm ? 'mm' : 'en';
$sidebar_lang = $is_mm ? [
    'dashboard' => 'ဒက်ရှ်ဘုတ်',
    'schemes' => 'ပညာသင်ဆုအစီအစဉ်များ',
    'reviewers' => 'စိစစ်ရေးမှူးများ',
    'students' => 'ကျောင်းသားများ',
    'applications' => 'လျှောက်လွှာများ',
    'bank_verify' => 'ဘဏ်စစ်ဆေးခြင်းများ',
    'recipients' => 'ဆုရရှိသူများ',
    'disbursements' => 'ငွေပေးချေမှုများ',
    'reports' => 'အစီရင်ခံစာများ',
    'messages' => 'စာတိုပေးစာများ',
    'logout' => 'ထွက်မည်',
    'page_title' => 'ကျောင်းသားများစီမံရန်',
] : [
    'dashboard' => 'Dashboard',
    'schemes' => 'Schemes',
    'reviewers' => 'Reviewers',
    'students' => 'Students',
    'applications' => 'Applications',
    'bank_verify' => 'Bank Verifications',
    'recipients' => 'Recipients',
    'disbursements' => 'Disbursements',
    'reports' => 'Reports',
    'messages' => 'Messages',
    'logout' => 'Logout',
    'page_title' => 'Students',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM student WHERE id=$id");
    }
    header("Location: students.php?lang=" . $lang_param);
    exit();
}

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total_result = $conn->query("SELECT COUNT(*) AS cnt FROM student");
$total_students = $total_result ? $total_result->fetch_assoc()['cnt'] : 0;
$total_pages = max(1, ceil($total_students / $per_page));
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

$students = $conn->query("SELECT s.*, COUNT(a.id) AS total_apps FROM student s LEFT JOIN applications a ON s.id = a.student_id GROUP BY s.id ORDER BY s.id DESC LIMIT $per_page OFFSET $offset");
$current_page = 'students';

$gender_m = $conn->query("SELECT COUNT(*) AS cnt FROM student WHERE gender='Male'");
$male_count = $gender_m ? $gender_m->fetch_assoc()['cnt'] : 0;
$gender_f = $conn->query("SELECT COUNT(*) AS cnt FROM student WHERE gender='Female'");
$female_count = $gender_f ? $gender_f->fetch_assoc()['cnt'] : 0;
$apps_count = $conn->query("SELECT COUNT(DISTINCT student_id) AS cnt FROM applications");
$applied_count = $apps_count ? $apps_count->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<script>if(localStorage.getItem('admin_theme')==='dark')document.documentElement.classList.add('dark-mode')</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#006D69', dark: '#004D4A', light: '#005a56' },
                        accent: '#FFD700',
                    }
                }
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; color: #1e293b; }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }

        .pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 15px; flex-wrap: wrap; }
        .page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; color: #475569; font-size: 12px; font-weight: 500; text-decoration: none; transition: 0.2s ease; }
        .page-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
        .page-btn.active { background: #006D69; border-color: #006D69; color: #fff; font-weight: 700; }
        .page-btn.disabled { opacity: 0.4; pointer-events: none; }
        .page-dots { color: #94a3b8; font-size: 12px; padding: 0 4px; }
        .page-info { font-size: 11px; color: #94a3b8; margin-left: 10px; }

        html.dark-mode .page-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
        html.dark-mode .page-btn:hover { background: #334155; border-color: #475569; color: #f1f5f9; }
        html.dark-mode .page-btn.active { background: rgba(255,215,0,0.15); border-color: #FFD700; color: #FFD700; }
        html.dark-mode .page-dots { color: #64748b; }
    </style>
    <?php include_once 'admin-style.php'; ?>
</head>
<body class="<?php echo $is_mm ? 'myanmar-font' : ''; ?>">

<?php include 'sidebar.php'; ?>

<div class="workspace">
    <?php $page_title = $sidebar_lang['page_title'] ?? 'Students'; include 'header.php'; ?>
    <div class="dashboard-body">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-5 text-center border-l-[3px] border-l-[#2563eb] border-r-[3px] border-r-[#2563eb]">
                <div class="text-[26px] font-extrabold text-[#0f172a]"><?php echo $total_students; ?></div>
                <div class="text-[10px] text-[#64748b] font-bold uppercase mt-1"><?php echo $is_mm ? 'စုစုပေါင်း ကျောင်းသား' : 'Total Students'; ?></div>
            </div>
            <div class="bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-5 text-center border-l-[3px] border-l-[#10b981] border-r-[3px] border-r-[#10b981]">
                <div class="text-[26px] font-extrabold text-[#0f172a]"><?php echo $male_count; ?></div>
                <div class="text-[10px] text-[#64748b] font-bold uppercase mt-1"><?php echo $is_mm ? ' erdote' : 'Male'; ?></div>
            </div>
            <div class="bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-5 text-center border-l-[3px] border-l-[#f59e0b] border-r-[3px] border-r-[#f59e0b]">
                <div class="text-[26px] font-extrabold text-[#0f172a]"><?php echo $female_count; ?></div>
                <div class="text-[10px] text-[#64748b] font-bold uppercase mt-1"><?php echo $is_mm ? 'မိန်းကလေး' : 'Female'; ?></div>
            </div>
            <div class="bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-5 text-center border-l-[3px] border-l-[#8b5cf6] border-r-[3px] border-r-[#8b5cf6]">
                <div class="text-[26px] font-extrabold text-[#0f172a]"><?php echo $applied_count; ?></div>
                <div class="text-[10px] text-[#64748b] font-bold uppercase mt-1"><?php echo $is_mm ? 'လျှောက်ထားသူ' : 'Applied'; ?></div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-[#e2e8f0] p-5 mb-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold text-[#0f172a]">🎓 <?php echo $sidebar_lang['page_title']; ?></h2>
                <input type="text" class="px-3 py-2 border border-[#cbd5e1] rounded-md text-xs w-[220px] search-box" placeholder="<?php echo $is_mm ? ' ရှာဖွေရန်...' : 'Search students...'; ?>" id="searchInput" onkeyup="filterTable()">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-xs" id="studentsTable">
                    <thead>
                        <tr>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">No.</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Photo</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Roll No</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Name</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Email</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Phone</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Gender</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Applications</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Joined</th>
                            <th class="bg-[#f8fafc] px-2 py-2.5 font-semibold text-[#64748b] border-b-2 border-[#e2e8f0] text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students && $students->num_rows > 0): ?>
                            <?php $no = $offset + 1; while ($row = $students->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo $no++; ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle">
                                        <?php if (!empty($row['profile_image']) && file_exists("../uploads/profile_pics/" . $row['profile_image'])): ?>
                                            <img src="../uploads/profile_pics/<?php echo htmlspecialchars($row['profile_image']); ?>" alt="Photo" class="w-8 h-8 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs text-[#004D4A]" style="background:linear-gradient(135deg,#FFD700,#f59e0b);"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle font-bold"><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#dcfce7] text-[#15803d]"><?php echo $row['total_apps']; ?></span>
                                    </td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td class="px-2 py-2.5 border-b border-[#f1f5f9] align-middle">
                                        <form method="POST" class="inline" onsubmit="return confirm('<?php echo $is_mm ? 'ဤကျောင်းသားကို ဖျက်မည်ဖြစ်သည်။ ဆက်လက်ဆောင်ရွက်မည်လား' : 'Delete this student? This action cannot be undone.'; ?>')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="bg-[#dc2626] text-white border-none px-2.5 py-1 rounded text-[10px] font-bold cursor-pointer">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center py-5 text-[#94a3b8]"><?php echo $is_mm ? 'ကျောင်းသားများ မတွေ့ပါ' : 'No students found.'; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>&lang=<?php echo $lang_param; ?>" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">&laquo;</a>
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                if ($start > 1): ?>
                    <a href="?page=1&lang=<?php echo $lang_param; ?>" class="page-btn">1</a>
                    <?php if ($start > 2): ?><span class="page-dots">...</span><?php endif; ?>
                <?php endif;
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&lang=<?php echo $lang_param; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor;
                if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?>&lang=<?php echo $lang_param; ?>" class="page-btn"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                <a href="?page=<?php echo min($total_pages, $page + 1); ?>&lang=<?php echo $lang_param; ?>" class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">&raquo;</a>
                <span class="page-info"><?php echo $is_mm ? 'စုစုပေါင်း' : 'Page'; ?> <?php echo $page; ?> / <?php echo $total_pages; ?> (<?php echo $total_students; ?> <?php echo $is_mm ? 'ယောက်ျား' : 'records'; ?>)</span>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function filterTable() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('studentsTable');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var match = false;
        for (var j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}
</script>
</body>
</html>
<?php $conn->close(); ?>
