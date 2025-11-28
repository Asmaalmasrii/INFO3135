<?php 
session_start();

// Set working directory to project root
chdir(__DIR__ . '/..');

// Include DB + header
require 'includes/db.php';
$activeTab = 'calendar';
include 'includes/header.php';

// =========================
// Handle Form Submission
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id = $_POST['employee_id'];
    $shift_date  = $_POST['shift_date'];       // YYYY-MM-DD
    $shift_start = $_POST['shift_start'];      // HH:MM
    $shift_end   = $_POST['shift_end'];        // HH:MM
    $notes       = $_POST['notes'] ?? '';

    // Insert schedule
    $sql = "INSERT INTO schedules (employee_id, shift_date, shift_start, shift_end, notes)
            VALUES (:employee_id, :shift_date, :shift_start, :shift_end, :notes)";
    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        ':employee_id' => $employee_id,
        ':shift_date'  => $shift_date,
        ':shift_start' => $shift_start,
        ':shift_end'   => $shift_end,
        ':notes'       => $notes
    ]);

    if ($success) {
        // Redirect back to calendar month
        header("Location: calendar.php?year=" . date('Y', strtotime($shift_date)) .
                            "&month=" . date('n', strtotime($shift_date)));
        exit;
    } else {
        echo "<p style='color:red; text-align:center;'>❌ Failed to save schedule.</p>";
    }
}

// =========================
// Fetch employee list
// =========================
$empStmt = $pdo->query("SELECT employee_id, name, role FROM employees ORDER BY name");
$employees = $empStmt->fetchAll();

// Default date from GET
$defaultDate = $_GET['date'] ?? date('Y-m-d');
?>

<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="schedule.css">

<!-- Hide original header nav -->
<style>
    nav.tabs-nav { display: none; }
</style>

<nav class="tabs-nav">
    <a href="/the-golden-plate/index.php" class="tab-link">
        <span class="tab-icon">🏠</span> Home
    </a>

    <a href="/the-golden-plate/admin_dashboard.php" class="tab-link">
        <span class="tab-icon">🛠</span> Admin Dashboard
    </a>

    <a href="/the-golden-plate/admin_reservations.php" class="tab-link">
        <span class="tab-icon">📋</span> Reservations
    </a>

    <a href="/the-golden-plate/schedule/calendar.php" class="tab-link active">
        <span class="tab-icon">📆</span> Staff Calendar
    </a>

    <a href="/the-golden-plate/logout.php" class="tab-link">
        <span class="tab-icon">🚪</span> Logout
    </a>
</nav>

<div class="app-wrapper">

    <!-- ⭐ UPDATED HEADER WITH BACK TO HOME BUTTON -->
    <div class="card card-page-header">
        <h2 class="page-title">Add Staff Schedule</h2>
        
        <div style="display:flex; gap:10px;">
            <a href="calendar.php" class="btn btn-outline back-btn">← Back to Calendar</a>

            <!-- ⭐ NEW BUTTON (SAFE, SIMPLE, NO CODE BREAKING) -->
            <a href="/the-golden-plate/index.php" class="btn btn-primary">🏠 Back to Home</a>
        </div>
    </div>

    <!-- Form -->
    <div class="card form-card">
        <form method="post">

            <div class="form-group">
                <label>Employee</label>
                <select name="employee_id" required>
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['employee_id'] ?>">
                            <?= htmlspecialchars($emp['name'] . " ({$emp['role']})") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Shift Date</label>
                <input type="date" name="shift_date" value="<?= $defaultDate ?>" required>
            </div>

            <div class="form-group">
                <label>Shift Start Time</label>
                <input type="time" name="shift_start" required>
            </div>

            <div class="form-group">
                <label>Shift End Time</label>
                <input type="time" name="shift_end" required>
            </div>

            <div class="form-group">
                <label>Notes (optional)</label>
                <textarea name="notes" rows="3"></textarea>
            </div>

            <button class="btn btn-primary">➕ Save Schedule</button>

        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
