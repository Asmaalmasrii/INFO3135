<?php
session_start();

// set working directory to project root
chdir(__DIR__ . '/..');

// include DB + header
require 'includes/db.php';
$activeTab = 'calendar'; // highlight Staff Calendar tab
include 'includes/header.php';
?>

<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="schedule.css">

<!-- Hide original header nav and load custom navigation -->
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

<?php
// fetch employees
$empStmt = $pdo->query("SELECT employee_id, name, role FROM employees ORDER BY name");
$employees = $empStmt->fetchAll();

$defaultDate = $_GET['date'] ?? date('Y-m-d');
?>

<div class="app-wrapper">

    <!-- page header card -->
    <div class="card card-page-header">
        <h2 class="page-title">Add Staff Schedule</h2>
        <a href="calendar.php" class="btn btn-outline back-btn">
            ← Back to Calendar
        </a>
    </div>

    <!-- form card -->
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