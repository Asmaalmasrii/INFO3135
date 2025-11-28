<?php
session_start();

$activeTab = 'events';
require '../includes/db.php';
include '../includes/header.php';

// Customers can see events; if not logged in we still show them but ask to log in to book
$userId   = $_SESSION['user_id'] ?? null;

// Load upcoming public events with remaining capacity
$sql = "
    SELECT e.*,
           COALESCE(SUM(r.num_guests), 0) AS reserved_seats
    FROM events e
    LEFT JOIN event_reservations r ON e.event_id = r.event_id
    WHERE e.is_public = 1
    ORDER BY e.event_date, e.start_time
";
$stmt   = $pdo->query($sql);
$events = $stmt->fetchAll();
?>

<h2 class="app-section-title">Restaurant Events</h2>
<p class="app-section-subtitle">
    Explore upcoming events and reserve your spot.
</p>

<div class="card">
    <?php if (empty($events)): ?>
        <p class="card-text muted">No upcoming events right now.</p>
    <?php else: ?>
        <?php foreach ($events as $e):
            $capacity  = (int)$e['capacity'];
            $reserved  = (int)$e['reserved_seats'];
            $remaining = max($capacity - $reserved, 0);
        ?>
            <div style="border-bottom:1px solid #f0e6d8; padding:12px 0;">
                <h3 class="card-title" style="margin-bottom:4px;">
                    <?php echo htmlspecialchars($e['title']); ?>
                </h3>
                <p class="card-text" style="margin-bottom:4px;">
                    <strong>Date:</strong> <?php echo htmlspecialchars($e['event_date']); ?>
                    <?php
                    $start = $e['start_time'] ? substr($e['start_time'], 0, 5) : '';
                    $end   = $e['end_time'] ? substr($e['end_time'], 0, 5) : '';
                    if ($start || $end) {
                        echo '&nbsp;|&nbsp;<strong>Time:</strong> ' .
                             htmlspecialchars(trim($start . ($end ? ' - ' . $end : '')));
                    }
                    ?>
                </p>
                <?php if (!empty($e['description'])): ?>
                    <p class="card-text" style="margin-bottom:4px;">
                        <?php echo nl2br(htmlspecialchars($e['description'])); ?>
                    </p>
                <?php endif; ?>

                <p class="card-text">
                    <strong>Capacity:</strong> <?php echo $capacity; ?> |
                    <strong>Reserved:</strong> <?php echo $reserved; ?> |
                    <strong>Remaining:</strong> <?php echo $remaining; ?>
                </p>

                <?php if ($remaining <= 0): ?>
                    <p class="card-text" style="color:#c0392b; font-weight:bold; margin-top:4px;">
                        This event is fully booked.
                    </p>
                <?php else: ?>
                    <p style="margin-top:6px;">
                        <a href="event_book.php?id=<?php echo (int)$e['event_id']; ?>"
                           class="btn btn-primary">
                            Reserve Spot
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
