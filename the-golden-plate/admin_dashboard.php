<?php
// admin_dashboard.php
session_start();

$activeTab = 'admin';
require 'includes/db.php';
include 'includes/header.php';

// Only admin can see this page
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="card" style="border-left:4px solid #c0392b;">
            <p class="card-text">Access denied. Admins only.</p>
          </div>';
    include 'includes/footer.php';
    exit;
}

// Fetch all users
$stmt  = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

// Fetch employees
$empStmt = $pdo->query('SELECT employee_id, name, department, role, created_at FROM employees ORDER BY name');
$employees = $empStmt->fetchAll();

// Fetch a few upcoming shifts
$today = date('Y-m-d');
$shiftStmt = $pdo->prepare("
    SELECT s.*, e.name AS employee_name, e.role AS employee_role
    FROM schedules s
    JOIN employees e ON s.employee_id = e.employee_id
    WHERE s.shift_date >= :today
    ORDER BY s.shift_date, s.shift_start
    LIMIT 10
");
$shiftStmt->execute([':today' => $today]);
$shifts = $shiftStmt->fetchAll();
?>

<h2 class="app-section-title">Admin Dashboard</h2>
<p class="app-section-subtitle">
    Manage users, employees, staff schedules, and restaurant events for Golden Plate.
</p>

<!-- Quick actions -->
<div class="card">
    <h3 class="card-title">Quick Actions</h3>
    <p class="card-text">
        <a href="schedule/add_schedule.php" class="btn btn-primary" style="margin-right:8px;">
            ➕ Add Staff Schedule
        </a>
        <a href="schedule/calendar.php" class="btn btn-outline" style="margin-right:8px;">
            📅 View Schedule Calendar
        </a>
        <!-- ✅ New: manage events -->
        <a href="events/admin_events.php" class="btn btn-outline">
            🎉 Manage Events
        </a>
    </p>
</div>



<!-- Users -->
<div class="card">
    <h3 class="card-title">Registered Users</h3>

    <?php if (count($users) === 0): ?>
        <p class="card-text muted">No users found yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Registered At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo (int)$u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-warning' : 'badge-success'; ?>">
                            <?php echo htmlspecialchars($u['role']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Employees -->
<div class="card">
    <h3 class="card-title">Employees</h3>

    <?php if (count($employees) === 0): ?>
        <p class="card-text muted">No employees found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Role</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $e): ?>
                <tr>
                    <td><?php echo (int)$e['employee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['name']); ?></td>
                    <td><?php echo htmlspecialchars($e['department']); ?></td>
                    <td><?php echo htmlspecialchars($e['role']); ?></td>
                    <td><?php echo htmlspecialchars($e['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Upcoming shifts -->
<div class="card">
    <h3 class="card-title">Upcoming Shifts</h3>

    <?php if (count($shifts) === 0): ?>
        <p class="card-text muted">No upcoming shifts found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Role</th>
                <th>Start</th>
                <th>End</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shifts as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['shift_date']); ?></td>
                    <td><?php echo htmlspecialchars($s['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['employee_role']); ?></td>
                    <td><?php echo htmlspecialchars(substr($s['shift_start'], 0, 5)); ?></td>
                    <td><?php echo htmlspecialchars(substr($s['shift_end'], 0, 5)); ?></td>
                    <td><?php echo htmlspecialchars($s['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>