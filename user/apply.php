
<?php
ob_start(); // Buffer the output
session_start();
// ... rest of your code ...
// session_start();
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
    $scheme_stmt = $conn->prepare("SELECT scheme_name FROM schemes WHERE id = ? AND status='Active'");
    $scheme_stmt->bind_param("i", $selected_scheme_id);
    $scheme_stmt->execute();
    $scheme_result = $scheme_stmt->get_result()->fetch_assoc();
    if ($scheme_result) {
        $selected_scheme_name = $scheme_result['scheme_name'];
    }
    $scheme_stmt->close();
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

/* Apply Form Submit */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $scheme_id = (int)$_POST['scheme_id'];
    $family_income = trim($_POST['family_income']);

    if (empty($scheme_id) || empty($family_income)) {

        $error = "Please fill all fields.";

    } elseif (!ctype_digit($family_income) || $family_income[0] === '0') {

        $error = "Income must be a valid number (no leading zeros).";

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

            $error = "You have already applied for this scholarship.";

        } else {

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

                $_SESSION['success_message'] =
                    "Application submitted successfully.";

                header("Location: profile.php");
                exit();

            } else {

                $error = "Failed to submit application.";
            }
        }
    }
}
?>

<main class="max-w-4xl mx-auto px-4 py-10">

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8">

        <div class="mb-8">

            <h1 class="text-3xl font-bold text-slate-900">
                Scholarship Application
            </h1>

            <?php if ($selected_scheme_name): ?>
                <p class="text-teal-700 font-semibold mt-2 text-lg">
                    Applying for: <?= htmlspecialchars($selected_scheme_name); ?>
                </p>
            <?php endif; ?>

            <p class="text-slate-500 mt-2">
                Complete the form below to apply.
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
                    Name
                </label>

                <input
                    type="text"
                    readonly
                    value="<?= htmlspecialchars($student['name']) ?>"
                    class="w-full bg-slate-100 border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    Roll Number
                </label>

                <input
                    type="text"
                    readonly
                    value="<?= htmlspecialchars($student['roll_no']) ?>"
                    class="w-full bg-slate-100 border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    Email
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
                    Scholarship Scheme
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
                            Select Scholarship Scheme
                        </option>

                        <?php
                        $schemes = $conn->query("
                            SELECT id, scheme_name
                            FROM schemes
                            WHERE status='Active'
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
                    Family Monthly Income (MMK)
                </label>

                <input
                    type="text"
                    name="family_income"
                    inputmode="numeric"
                    pattern="[1-9][0-9]*"
                    value=""
                    required
                    oninput="this.value = this.value.replace(/^0+|[^0-9]/g, '')"
                    placeholder="e.g. 3000000"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3">

            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="w-full bg-[#004D4A] hover:bg-[#003D3B] text-white py-3 rounded-xl font-bold transition">

                Submit Application

            </button>

        </form>

    </div>

</main>

<?php include '../includes/footer.php'; ?>

