<?php
session_start();

chdir(__DIR__ . '/..');

require 'includes/db.php';

// Highlight current tab
$activeTab = 'calendar';

// Include header
include 'includes/header.php';
?>

<!-- Load shared CSS -->
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="schedule.css">

<!-- Hide header.php tabs -->
<style>
    nav.tabs-nav { display: none; }
</style>

<!-- Replace with same nav structure as calendar.php -->
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


<?php
// ================== LOAD DATA ==================
$date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$sql = "
    SELECT s.*, e.name AS employee_name, e.role AS employee_role
    FROM schedules s
    JOIN employees e ON s.employee_id = e.employee_id
    WHERE s.shift_date = :date
    ORDER BY s.shift_start
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':date' => $date]);
$shifts = $stmt->fetchAll();

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>


<div class="app-wrapper">

    <!-- ===== Header Card ===== -->
    <div class="card card-page-header">

        <div class="page-title">
            Schedule for <?= htmlspecialchars($date) ?>
        </div>

        <div class="action-row view-header">

            <a href="calendar.php" class="btn-white">&larr; Back to Calendar</a>

            <?php if ($isAdmin): ?>
                <a href="add_schedule.php?date=<?= htmlspecialchars($date) ?>" 
                    class="btn-add-schedule">
                    <span style="color:#8f7aec;">➕</span> Add Schedule
                </a>
            <?php endif; ?>

    </div>

</div>
    </div>

    <!-- ===== Main Table Card ===== -->
    <div class="card">

        <?php if (empty($shifts)): ?>
            <p class="empty-text">No shifts for this day.</p>
        <?php else: ?>

        <table class="table schedule-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Role</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Notes</th>
                    <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($shifts as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['employee_name']) ?></td>
                    <td><?= htmlspecialchars($s['employee_role']) ?></td>
                    <td><?= substr($s['shift_start'],0,5) ?></td>
                    <td><?= substr($s['shift_end'],0,5) ?></td>
                    <td><?= htmlspecialchars($s['notes']) ?></td>

                    <?php if ($isAdmin): ?>
                    <td class="action-buttons">
                        <a class="btn-small btn-white"
                           href="edit_schedule.php?id=<?= (int)$s['schedule_id'] ?>">Edit</a>

                        <a class="btn-small btn-danger"
                           href="delete_schedule.php?id=<?= (int)$s['schedule_id'] ?>"
                           onclick="return confirm('Delete this shift?');">
                            Delete
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>

    </div>

</div>

<?php include 'includes/footer.php'; ?>