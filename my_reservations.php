<?php
// my_reservations.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$activeTab = 'my_reservations';

require 'includes/db.php';
include 'includes/header.php';

$userId         = $_SESSION['user_id'];
$successMessage = '';
$errors         = [];

// Handle cancellation for TABLE reservations only
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservationId = (int)$_GET['cancel'];

    // Verify this reservation belongs to the user
    $verifyStmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ? AND user_id = ?');
    $verifyStmt->execute([$reservationId, $userId]);
    $reservation = $verifyStmt->fetch();

    if ($reservation) {
        // Update status to cancelled
        $cancelStmt = $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?');
        $cancelStmt->execute([$reservationId]);
        $successMessage = 'Reservation cancelled successfully.';
    } else {
        $errors[] = 'Invalid reservation.';
    }
}

// ==========================
// TABLE RESERVATIONS
// ==========================

// Fetch upcoming table reservations
$upcomingStmt = $pdo->prepare('
    SELECT r.*, t.table_number, t.capacity 
    FROM reservations r
    JOIN tables t ON r.table_id = t.id
    WHERE r.user_id = ? 
      AND r.reservation_date >= CURDATE()
      AND r.status != "completed"
    ORDER BY r.reservation_date ASC, r.reservation_time ASC
');
$upcomingStmt->execute([$userId]);
$upcomingReservations = $upcomingStmt->fetchAll();

// Fetch past table reservations
$pastStmt = $pdo->prepare('
    SELECT r.*, t.table_number, t.capacity 
    FROM reservations r
    JOIN tables t ON r.table_id = t.id
    WHERE r.user_id = ? 
      AND (r.reservation_date < CURDATE() OR r.status = "completed")
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
    LIMIT 10
');
$pastStmt->execute([$userId]);
$pastReservations = $pastStmt->fetchAll();

// ==========================
// EVENT RESERVATIONS
// ==========================

// Upcoming event reservations
$eventUpcomingStmt = $pdo->prepare('
    SELECT er.*, e.title, e.event_date, e.start_time, e.end_time
    FROM event_reservations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.user_id = ?
      AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC, e.start_time ASC
');
$eventUpcomingStmt->execute([$userId]);
$upcomingEventReservations = $eventUpcomingStmt->fetchAll();

// Past event reservations
$eventPastStmt = $pdo->prepare('
    SELECT er.*, e.title, e.event_date, e.start_time, e.end_time
    FROM event_reservations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.user_id = ?
      AND e.event_date < CURDATE()
    ORDER BY e.event_date DESC, e.start_time DESC
    LIMIT 10
');
$eventPastStmt->execute([$userId]);
$pastEventReservations = $eventPastStmt->fetchAll();
?>

<h2 class="app-section-title">🧾 My Reservations</h2>
<p class="app-section-subtitle">
    View and manage your <strong>table</strong> and <strong>event</strong> reservations at Golden Plate.
</p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-left: 4px solid #c0392b;">
        <h3 class="card-title">Error</h3>
        <ul class="card-text" style="margin-left: 18px; list-style: disc;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div class="card" style="border-left: 4px solid #27ae60;">
        <p class="card-text">
            <?php echo htmlspecialchars($successMessage); ?>
        </p>
    </div>
<?php endif; ?>

<!-- ==========================
     TABLE - UPCOMING
     ========================== -->
<div class="card">
    <h3 class="card-title">📅 Upcoming Table Reservations</h3>

    <?php if (empty($upcomingReservations)): ?>
        <p class="card-text">
            You have no upcoming table reservations.<br><br>
            <a href="booking.php" class="btn btn-primary">Make a Table Reservation</a>
        </p>
    <?php else: ?>
        <?php foreach ($upcomingReservations as $res): ?>
            <?php
            // Status badge color
            $statusColor = '#7f8c8d';
            switch (strtolower($res['status'])) {
                case 'pending':
                    $statusColor = '#f39c12'; // orange
                    break;
                case 'confirmed':
                    $statusColor = '#27ae60'; // green
                    break;
                case 'cancelled':
                    $statusColor = '#c0392b'; // red
                    break;
            }
            ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 8px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 style="margin-top: 0; color: #2c3e50;">
                            Reservation #<?php echo $res['id']; ?>
                        </h4>
                    </div>
                    <div>
                        <span style="background: <?php echo $statusColor; ?>; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase;">
                            <?php echo htmlspecialchars($res['status']); ?>
                        </span>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <p style="margin: 5px 0; font-size: 14px;">
                        <strong>Table:</strong> Table <?php echo htmlspecialchars($res['table_number']); ?><br>
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($res['reservation_date'])); ?><br>
                        <strong>Time:</strong> <?php echo htmlspecialchars($res['reservation_time']); ?><br>
                        <strong>Party Size:</strong> <?php echo htmlspecialchars($res['party_size']); ?> guests<br>
                        <strong>Table Capacity:</strong> <?php echo htmlspecialchars($res['capacity']); ?> people
                    </p>

                    <?php if (!empty($res['special_requests'])): ?>
                        <p style="margin: 8px 0; font-size: 13px; color: #666;">
                            <strong>Special Requests:</strong><br>
                            <?php echo nl2br(htmlspecialchars($res['special_requests'])); ?>
                        </p>
                    <?php endif; ?>

                    <p style="margin: 5px 0; font-size: 12px; color: #7f8c8d;">
                        Booked on: <?php echo date('F j, Y', strtotime($res['created_at'])); ?>
                    </p>
                </div>

                <?php if (in_array(strtolower($res['status']), ['pending', 'confirmed'])): ?>
                    <div style="margin-top: 12px;">
                        <a href="my_reservations.php?cancel=<?php echo $res['id']; ?>"
                           class="btn"
                           style="background: #c0392b; color: white;"
                           onclick="return confirm('Are you sure you want to cancel this reservation?');">
                            Cancel Reservation
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ==========================
     TABLE - PAST
     ========================== -->
<div class="card">
    <h3 class="card-title">📜 Past Table Reservations</h3>

    <?php if (empty($pastReservations)): ?>
        <p class="card-text">
            No past table reservations found.
        </p>
    <?php else: ?>
        <?php foreach ($pastReservations as $res): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 8px; background: #f9f9f9; opacity: 0.8;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 style="margin-top: 0; color: #7f8c8d;">
                            Reservation #<?php echo $res['id']; ?>
                        </h4>
                    </div>
                    <div>
                        <?php if (strtolower($res['status']) === 'cancelled'): ?>
                            <span style="color: #c0392b; font-weight: bold;">✗ CANCELLED</span>
                        <?php else: ?>
                            <span style="color: #7f8c8d; font-weight: bold;">✓ COMPLETED</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <p style="margin: 5px 0; font-size: 14px; color: #666;">
                        <strong>Table:</strong> Table <?php echo htmlspecialchars($res['table_number']); ?><br>
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($res['reservation_date'])); ?><br>
                        <strong>Time:</strong> <?php echo htmlspecialchars($res['reservation_time']); ?><br>
                        <strong>Party Size:</strong> <?php echo htmlspecialchars($res['party_size']); ?> guests
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ==========================
     EVENTS - UPCOMING
     ========================== -->
<div class="card">
    <h3 class="card-title">🎉 Upcoming Event Reservations</h3>

    <?php if (empty($upcomingEventReservations)): ?>
        <p class="card-text">
            You have no upcoming event reservations.<br><br>
            <a href="events/events.php" class="btn btn-outline">Browse Events</a>
        </p>
    <?php else: ?>
        <?php foreach ($upcomingEventReservations as $ev): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 8px; background: #f9f9f9;">
                <h4 style="margin-top: 0; color: #2c3e50;">
                    <?php echo htmlspecialchars($ev['title']); ?>
                </h4>
                <p style="margin: 5px 0; font-size: 14px;">
                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($ev['event_date'])); ?><br>
                    <strong>Time:</strong>
                    <?php echo htmlspecialchars($ev['start_time']); ?>
                    <?php if (!empty($ev['end_time'])): ?>
                        – <?php echo htmlspecialchars($ev['end_time']); ?>
                    <?php endif; ?><br>
                    <strong>Guests:</strong> <?php echo (int)$ev['num_guests']; ?>
                </p>
                <p style="margin: 5px 0; font-size: 12px; color: #7f8c8d;">
                    Booked on: <?php echo date('F j, Y', strtotime($ev['created_at'])); ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ==========================
     EVENTS - PAST
     ========================== -->
<div class="card">
    <h3 class="card-title">📜 Past Event Reservations</h3>

    <?php if (empty($pastEventReservations)): ?>
        <p class="card-text">
            No past event reservations found.
        </p>
    <?php else: ?>
        <?php foreach ($pastEventReservations as $ev): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 8px; background: #f9f9f9; opacity: 0.85;">
                <h4 style="margin-top: 0; color: #7f8c8d;">
                    <?php echo htmlspecialchars($ev['title']); ?>
                </h4>
                <p style="margin: 5px 0; font-size: 14px; color: #666;">
                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($ev['event_date'])); ?><br>
                    <strong>Time:</strong>
                    <?php echo htmlspecialchars($ev['start_time']); ?>
                    <?php if (!empty($ev['end_time'])): ?>
                        – <?php echo htmlspecialchars($ev['end_time']); ?>
                    <?php endif; ?><br>
                    <strong>Guests:</strong> <?php echo (int)$ev['num_guests']; ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
