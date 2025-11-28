<?php
// admin_reservations.php
session_start();

$activeTab = 'admin_reservations';

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

// =====================================
// TABLE RESERVATIONS (existing logic)
// =====================================
$stmt = $pdo->query('
    SELECT r.*, u.full_name, u.email, t.table_number
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN tables t ON r.table_id = t.id
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
');
$allReservations = $stmt->fetchAll();

// Group into upcoming and past using PHP
$today = date('Y-m-d');
$upcomingReservations = [];
$pastReservations     = [];

foreach ($allReservations as $res) {
    if ($res['reservation_date'] >= $today && $res['status'] !== 'completed') {
        $upcomingReservations[] = $res;
    } else {
        $pastReservations[] = $res;
    }
}

// =====================================
// EVENT RESERVATIONS (NEW)
// =====================================

// Load all event reservations with event + user info
$eventStmt = $pdo->query("
    SELECT er.*, e.title, e.event_date, e.start_time, e.end_time,
           u.full_name, u.email
    FROM event_reservations er
    JOIN events e ON er.event_id = e.event_id
    LEFT JOIN users u ON er.user_id = u.id
    ORDER BY e.event_date DESC, e.start_time DESC, er.created_at DESC
");
$allEventReservations = $eventStmt->fetchAll();

$upcomingEventReservations = [];
$pastEventReservations     = [];

foreach ($allEventReservations as $er) {
    if ($er['event_date'] >= $today) {
        $upcomingEventReservations[] = $er;
    } else {
        $pastEventReservations[] = $er;
    }
}
?>

<h2 class="app-section-title">Reservations Overview</h2>
<p class="app-section-subtitle">
    View all <strong>table reservations</strong> and <strong>event reservations</strong> for Golden Plate.
</p>

<!-- TABLE: Upcoming Reservations -->
<div class="card">
    <h3 class="card-title">📅 Upcoming Table Reservations (All Customers)</h3>

    <?php if (empty($upcomingReservations)): ?>
        <p class="card-text">
            No upcoming table reservations found.
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Table</th>
                <th>Date</th>
                <th>Time</th>
                <th>Party Size</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($upcomingReservations as $res): ?>
                <tr>
                    <td><?php echo (int)$res['id']; ?></td>
                    <td><?php echo htmlspecialchars($res['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($res['email']); ?></td>
                    <td>Table <?php echo htmlspecialchars($res['table_number']); ?></td>
                    <td><?php echo htmlspecialchars($res['reservation_date']); ?></td>
                    <td><?php echo htmlspecialchars($res['reservation_time']); ?></td>
                    <td><?php echo (int)$res['party_size']; ?></td>
                    <td><?php echo htmlspecialchars($res['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- TABLE: Past Reservations -->
<div class="card">
    <h3 class="card-title">📜 Past Table Reservations</h3>

    <?php if (empty($pastReservations)): ?>
        <p class="card-text">
            No past table reservations found.
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Table</th>
                <th>Date</th>
                <th>Time</th>
                <th>Party Size</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pastReservations as $res): ?>
                <tr>
                    <td><?php echo (int)$res['id']; ?></td>
                    <td><?php echo htmlspecialchars($res['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($res['email']); ?></td>
                    <td>Table <?php echo htmlspecialchars($res['table_number']); ?></td>
                    <td><?php echo htmlspecialchars($res['reservation_date']); ?></td>
                    <td><?php echo htmlspecialchars($res['reservation_time']); ?></td>
                    <td><?php echo (int)$res['party_size']; ?></td>
                    <td><?php echo htmlspecialchars($res['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- EVENTS: Upcoming Event Reservations -->
<div class="card">
    <h3 class="card-title">🎉 Upcoming Event Reservations</h3>

    <?php if (empty($upcomingEventReservations)): ?>
        <p class="card-text">
            No upcoming event reservations found.
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Date</th>
                <th>Time</th>
                <th>Guests</th>
                <th>Booked At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($upcomingEventReservations as $er): ?>
                <tr>
                    <td><?php echo (int)$er['id']; ?></td>
                    <td><?php echo htmlspecialchars($er['title']); ?></td>
                    <td><?php echo htmlspecialchars($er['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($er['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($er['event_date']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($er['start_time']); ?>
                        <?php if (!empty($er['end_time'])): ?>
                            – <?php echo htmlspecialchars($er['end_time']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$er['num_guests']; ?></td>
                    <td><?php echo htmlspecialchars($er['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- EVENTS: Past Event Reservations -->
<div class="card">
    <h3 class="card-title">📜 Past Event Reservations</h3>

    <?php if (empty($pastEventReservations)): ?>
        <p class="card-text">
            No past event reservations found.
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Date</th>
                <th>Time</th>
                <th>Guests</th>
                <th>Booked At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pastEventReservations as $er): ?>
                <tr>
                    <td><?php echo (int)$er['id']; ?></td>
                    <td><?php echo htmlspecialchars($er['title']); ?></td>
                    <td><?php echo htmlspecialchars($er['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($er['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($er['event_date']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($er['start_time']); ?>
                        <?php if (!empty($er['end_time'])): ?>
                            – <?php echo htmlspecialchars($er['end_time']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$er['num_guests']; ?></td>
                    <td><?php echo htmlspecialchars($er['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
