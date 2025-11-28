<?php
session_start();

chdir(__DIR__ . '/..');

require 'includes/db.php';

// Must be admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Access denied. Admins only.";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid schedule ID.";
    exit;
}

// Fetch schedule
$stmt = $pdo->prepare("
    SELECT * FROM schedules
    WHERE schedule_id = :id
");
$stmt->execute([':id' => $id]);
$schedule = $stmt->fetch();

if (!$schedule) {
    echo "Schedule not found.";
    exit;
}

// Fetch employees
$empStmt = $pdo->query("SELECT employee_id, name, role FROM employees ORDER BY name");
$employees = $empStmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $shift_date  = $_POST['shift_date'] ?? '';
    $shift_start = $_POST['shift_start'] ?? '';
    $shift_end   = $_POST['shift_end'] ?? '';
    $notes       = $_POST['notes'] ?? '';

    if ($employee_id === '' || $shift_date === '' || $shift_start === '' || $shift_end === '') {
        $errors[] = "All required fields must be filled.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE schedules
            SET employee_id = :employee_id,
                shift_date  = :shift_date,
                shift_start = :shift_start,
                shift_end   = :shift_end,
                notes       = :notes
            WHERE schedule_id = :id
        ");
        $update->execute([
            ':employee_id' => $employee_id,
            ':shift_date'  => $shift_date,
            ':shift_start' => $shift_start,
            ':shift_end'   => $shift_end,
            ':notes'       => $notes,
            ':id'          => $id
        ]);

        header("Location: schedule/view_schedule.php?date=" . urlencode($shift_date));
        exit;
    }
}

// Highlight tab
$activeTab = 'calendar';

// include header
include 'includes/header.php';
?>

<!-- Load shared CSS -->
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="schedule.css">

<!-- Hide original nav in header.php -->
<style> nav.tabs-nav { display: none; } </style>

<!-- Rebuild navigation bar -->
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
    <a href="/the-golden-plate/schedule/calendar.php"
       class="tab-link active">
        <span class="tab-icon">📆</span> Staff Calendar
    </a>
    <a href="/the-golden-plate/logout.php" class="tab-link">
        <span class="tab-icon">🚪</span> Logout
    </a>
</nav>

<div class="app-wrapper">

    <!-- ===== HEADER CARD (same style as add/view) ===== -->
    <div class="card card-page-header">

        <div class="page-title">Edit Staff Schedule</div>

        <div class="action-row view-header">
            <a href="view_schedule.php?date=<?= htmlspecialchars($schedule['shift_date']); ?>"
               class="btn-white">&larr; Back to Day Schedule</a>
        </div>

    </div>


    <!-- ===== FORM CARD ===== -->
    <div class="card form-card">

        <?php if (!empty($errors)): ?>
            <div style="color:#c0392b; margin-bottom: 12px;">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="form-group">
                <label>Employee</label>
                <select name="employee_id" required>
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['employee_id']; ?>"
                            <?= $emp['employee_id'] == $schedule['employee_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($emp['name'] . " ({$emp['role']})"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Shift Date</label>
                <input type="date" name="shift_date"
                       value="<?= htmlspecialchars($schedule['shift_date']); ?>" required>
            </div>

            <div class="form-group">
                <label>Shift Start Time</label>
                <input type="time" name="shift_start"
                       value="<?= substr($schedule['shift_start'], 0, 5); ?>" required>
            </div>

            <div class="form-group">
                <label>Shift End Time</label>
                <input type="time" name="shift_end"
                       value="<?= substr($schedule['shift_end'], 0, 5); ?>" required>
            </div>

            <div class="form-group">
                <label>Notes (optional)</label>
                <textarea name="notes" rows="3"><?= htmlspecialchars($schedule['notes']); ?></textarea>
            </div>

            <div class="button-row" style="margin-top: 20px;">
                <button type="submit" class="btn-primary" style="padding: 12px 26px;">
                    💾 Save Changes
                </button>
            </div>

        </form>

    </div>

</div>

<?php include 'includes/footer.php'; ?>