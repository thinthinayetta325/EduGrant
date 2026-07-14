<?php
session_start();
include '../config/db.php';
include '../includes/header.php';

/* ---------------------------
   LOGIN CHECK
----------------------------*/
if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

/* ---------------------------
   GET SCHEME ID
----------------------------*/
$scheme_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scheme_id <= 0) {
    header("Location: ../user/scholarships.php");
    exit();
}

/* ---------------------------
   FETCH SCHEME
----------------------------*/
$stmt = $conn->prepare("SELECT * FROM schemes WHERE id = ?");
$stmt->bind_param("i", $scheme_id);
$stmt->execute();
$result = $stmt->get_result();
$scheme = $result->fetch_assoc();

/* NOT FOUND */
if (!$scheme) {
    header("Location: ../user/scholarships.php");
    exit();
}

/* LANGUAGE */
$lang = $_GET['lang'] ?? 'en';
?>
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
</style>
<main class="max-w-6xl mx-auto px-4 py-10">

    <!-- HERO -->
    <div class="bg-gradient-to-r from-[#004D4A] to-[#003D3B] text-white p-10 rounded-3xl shadow-lg">

        <h1 class="text-3xl font-bold">

            <?php if ($lang == 'mm'): ?>
                <?= htmlspecialchars($scheme['scheme_name_mm']) ?>
            <?php else: ?>
                <?= htmlspecialchars($scheme['scheme_name']) ?>
            <?php endif; ?>

        </h1>

        <p class="mt-3 text-white/80">

            <?php if ($lang == 'mm'): ?>
                <?= htmlspecialchars($scheme['description_mm']) ?>
            <?php else: ?>
                <?= htmlspecialchars($scheme['description']) ?>
            <?php endif; ?>

        </p>

        <!-- APPLY BUTTON -->
        <a href="apply.php?id=<?= $scheme['id'] ?>"
           class="inline-block mt-6 bg-white text-[#003D3B] px-6 py-3 rounded-xl font-bold hover:bg-gray-100">
            Apply Now
        </a>

    </div>

    <!-- DETAILS -->
    <div class="grid md:grid-cols-3 gap-6 mt-10">

        <!-- LEFT -->
        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow border">

            <h2 class="text-xl font-bold mb-4">Eligibility</h2>

            <p class="text-gray-600 leading-relaxed">
                <?= nl2br(htmlspecialchars($scheme['eligibility'])) ?>
            </p>

        </div>

        <!-- RIGHT -->
        <div class="bg-white p-6 rounded-2xl shadow border">

            <h2 class="text-lg font-bold mb-4">Information</h2>

            <div class="space-y-3 text-sm">

                <!-- AMOUNT -->
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-semibold">
                        <?= ($lang == 'mm') ? $scheme['amount_mm'] : $scheme['amount'] ?>
                    </span>
                </div>

                <!-- DEADLINE -->
                <div class="flex justify-between">
                    <span class="text-gray-500">Deadline</span>
                    <span class="font-semibold">
                        <?= $scheme['deadline'] ?>
                    </span>
                </div>

                <!-- STATUS -->
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-700">
                        <?= $scheme['status'] ?>
                    </span>
                </div>

            </div>

        </div>

    </div>

</main>

<?php include '../includes/footer.php'; ?>