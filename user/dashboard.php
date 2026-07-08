<?php
// Start session to track logged-in student
session_start();

// 1. Pull in your central database connection configuration
require_once '../config/db.php'; 

// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : "Student";

// 2. Fetch the latest application state for this student to drive the dynamic cards
$app_query = "SELECT a.id AS app_id, a.application_no, a.status, a.family_income, a.apply_date,
                     sc.id AS scheme_id, sc.scheme_name, sc.amount
              FROM applications a
              JOIN schemes sc ON a.scheme_id = sc.id
              WHERE a.student_id = ? 
              ORDER BY a.id DESC LIMIT 1";

$stmt = $conn->prepare($app_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($app_id, $app_no, $app_status, $fam_income, $apply_date, $scheme_id, $scheme_name, $amount);
$has_application = $stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Grant Portal</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8fafc; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Navigation UI from image_98f107.jpg */
        .sidebar { width: 260px; background-color: #0c1e36; color: #fff; display: flex; flex-direction: column; padding: 20px 0; }
        .profile-section { padding: 0 20px 20px 20px; border-bottom: 1px solid #1e293b; display: flex; align-items: center; gap: 12px; }
        .avatar { width: 45px; height: 45px; background: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; color: #fff; }
        .profile-info h4 { margin: 0; font-size: 14px; }
        .profile-info p { margin: 2px 0 0 0; font-size: 11px; color: #94a3b8; text-transform: uppercase; }
        .nav-list { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; }
        .nav-item a { display: block; padding: 12px 24px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .nav-item.active a, .nav-item a:hover { background-color: #1e3a8a; color: #fff; border-left: 4px solid #38bdf8; }
        
        /* Main Application Workspace */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-navbar { background: #fff; padding: 15px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .top-navbar h2 { margin: 0; font-size: 18px; color: #0f172a; }
        
        .dashboard-grid { padding: 30px; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; }
        
        /* Card Layout Elements from image_98f107.jpg */
        .card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 24px; width: 320px; box-sizing: border-box; position: relative; }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
        .card-icon { width: 35px; height: 35px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .card-title { margin: 0; font-size: 15px; color: #1e3a8a; font-weight: bold; }
        .card-subtitle { margin: 2px 0 0 0; font-size: 11px; color: #64748b; }
        
        /* Arrow connectors */
        .step-arrow { display: flex; align-items: center; justify-content: center; font-size: 20px; color: #cbd5e1; height: 100px; }

        /* Badge status colors */
        .status-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; border-radius: 6px; text-transform: uppercase; }
        .status-review { background-color: #fef3c7; color: #d97706; }
        .status-approved { background-color: #dcfce7; color: #15803d; }
        .status-rejected { background-color: #fee2e2; color: #b91c1c; }

        /* Form elements */
        .form-label { display: block; font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
        .form-value { font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
        .input-box { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; margin-bottom: 12px; font-size: 13px; }
        .btn-blue { width: 100%; padding: 10px; background: #1d4ed8; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; text-align: center; text-decoration: none; display: block; box-sizing: border-box; }
        .btn-blue:hover { background: #1e40af; }
    </style>
</head>
<body>

    <!-- SIDEBAR NAVIGATION PANEL -->
    <div class="sidebar">
        <div class="profile-section">
            <div class="avatar"><?php echo strtoupper(substr($student_name, 0, 1)); ?></div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($student_name); ?></h4>
                <p>Student Account</p>
            </div>
        </div>
        <ul class="nav-list">
            <li class="nav-item active"><a href="dashboard.php">📊 Dashboard Workspace</a></li>
            <li class="nav-item"><a href="scholarships.php">📜 Available Schemes</a></li>
            <li class="nav-item"><a href="my_applications.php">📁 My Applications</a></li>
            <li class="nav-item"><a href="bank_details.php">🏦 Bank Setup</a></li>
            <li class="nav-item"><a href="../auth/logout.php" style="color: #f87171;">🚪 System Logout</a></li>
        </ul>
    </div>

    <!-- MAIN WORKSPACE CONTENT -->
    <div class="main-content">
        <div class="top-navbar">
            <h2>Scholarship Lifecycle Matrix Tracking</h2>
            <span style="font-size: 13px; color: #64748b;">System Date: <?php echo date('d M Y'); ?></span>
        </div>

        <div class="dashboard-grid">

            <!-- STEP 1: VIEW SCHEMES CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🎓</div>
                    <div>
                        <h4 class="card-title">View Schemes</h4>
                        <p class="card-subtitle">စီမံကိန်းများကြည့်ရှုရန်</p>
                    </div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <p style="margin: 0; font-size: 13px; font-weight: bold; color: #1e3a8a;">Need-Based Scholarship</p>
                    <p style="margin: 4px 0 0 0; font-size: 11px; color: #16a34a; font-weight: bold;">500,000 MMK</p>
                </div>
                <a href="apply.php?scheme_id=1" class="btn-blue">View Details</a>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 2: APPLY SCHOLARSHIP CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📝</div>
                    <div>
                        <h4 class="card-title">Apply Scholarship</h4>
                        <p class="card-subtitle">ပညာသင်ဆုလျှောက်ထားရန်</p>
                    </div>
                </div>
                <form action="process_apply.php" method="POST">
                    <label class="form-label">Selected Scheme</label>
                    <input type="text" class="input-box" value="Need-Based Scholarship" readonly>
                    
                    <label class="form-label">Family Income (MMK)</label>
                    <input type="number" name="family_income" class="input-box" value="<?php echo $has_application ? htmlspecialchars($fam_income) : '300000'; ?>" required>
                    
                    <button type="submit" class="btn-blue" <?php if($has_application) echo 'disabled style="background:#cbd5e1; cursor:not-allowed;"'; ?>>
                        <?php echo $has_application ? 'Already Applied' : 'Submit Application'; ?>
                    </button>
                </form>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 3: WAIT FOR REVIEW CARD -->
            <div class="card" style="text-align: center;">
                <div class="card-header" style="text-align: left;">
                    <div class="card-icon">⏳</div>
                    <div>
                        <h4 class="card-title">Wait For Review</h4>
                        <p class="card-subtitle">စစ်ဆေးမှုကိုစောင့်ပါ</p>
                    </div>
                </div>
                <div style="font-size: 40px; margin: 10px 0;">📋</div>
                <p style="font-size: 13px; font-weight: bold; color: #0f172a; margin: 5px 0;">Your application is under review.</p>
                <p style="font-size: 11px; color: #64748b; margin: 0 0 15px 0;">သတ်မှတ်လျှောက်လွှာကို စစ်ဆေးနေပါသည်。</p>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 4: VIEW STATUS CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🔍</div>
                    <div>
                        <h4 class="card-title">View Status</h4>
                        <p class="card-subtitle">အခြေအနေကြည့်ရှုရန်</p>
                    </div>
                </div>
                <label class="form-label">Application ID</label>
                <div class="form-value"><?php echo $has_application ? htmlspecialchars($app_no) : 'APP-2026-XXXX'; ?></div>
                
                <label class="form-label">Scheme</label>
                <div class="form-value"><?php echo $has_application ? htmlspecialchars($scheme_name) : 'None'; ?></div>
                
                <label class="form-label">Status</label>
                <div>
                    <?php if($has_application): ?>
                        <span class="status-badge <?php echo ($app_status === 'Recommended') ? 'status-approved' : (($app_status === 'Rejected') ? 'status-rejected' : 'status-review'); ?>">
                            <?php echo htmlspecialchars($app_status); ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-review">No Active Records</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 5: APPROVAL / ADD BANK ROUTE -->
            <div class="card" style="text-align: center;">
                <div class="card-header" style="text-align: left;">
                    <div class="card-icon">❓</div>
                    <div>
                        <h4 class="card-title">Approved?</h4>
                        <p class="card-subtitle">အတည်ပြုပြီးလား?</p>
                    </div>
                </div>
                <?php if($has_application && $app_status === 'Recommended'): ?>
                    <div style="font-size: 45px; color: #16a34a; margin: 10px 0;">✅</div>
                    <p style="font-size: 14px; font-weight: bold; color: #16a34a; margin: 0 0 15px 0;">Congratulations!</p>
                    <a href="#bank-section" class="btn-blue">Add Bank Details</a>
                <?php else: ?>
                    <div style="font-size: 45px; color: #cbd5e1; margin: 10px 0;">🛡️</div>
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 15px 0;">Awaiting processing confirmation.</p>
                <?php endif; ?>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 6: ADD BANK DETAILS CARD -->
            <div class="card" id="bank-section">
                <div class="card-header">
                    <div class="card-icon">🏛️</div>
                    <div>
                        <h4 class="card-title">Add Bank Details</h4>
                        <p class="card-subtitle">ဘဏ်အချက်အလက်ထည့်ရန်</p>
                    </div>
                </div>
                <form action="save_bank.php" method="POST">
                    <label class="form-label">Bank Name</label>
                    <select class="input-box" name="bank_name">
                        <option>KBZ Bank</option>
                        <option>CB Bank</option>
                        <option>AYA Bank</option>
                    </select>
                    
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" name="holder_name" class="input-box" value="<?php echo htmlspecialchars($student_name); ?>">
                    
                    <label class="input-box-label form-label">Account Number</label>
                    <input type="text" name="account_no" class="input-box" placeholder="1234567890123456">
                    
                    <button type="submit" class="btn-blue">Save Bank Details</button>
                </form>
            </div>

            <div class="step-arrow">➔</div>

            <!-- STEP 7: PAYMENT HISTORY CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">💰</div>
                    <div>
                        <h4 class="card-title">Payment History</h4>
                        <p class="card-subtitle">ငွေပေးချေမှုမှတ်တမ်း</p>
                    </div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; font-size: 12px; font-weight: bold;">500,000 MMK</p>
                        <p style="margin: 2px 0 0 0; font-size: 10px; color: #64748b;">Semester 1</p>
                    </div>
                    <span style="font-size: 10px; font-weight: bold; background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px;">Paid</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>