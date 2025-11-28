<?php
session_start();

$activeTab = 'events';
require '../includes/db.php';

// Must be logged-in customer to book
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    include '../includes/header.php';
    echo '<div class="card" style="border-left:4px solid #c0392b;">
            <p class="card-text">You must be logged in as a customer to reserve an event.</p>
            <p class="card-text">
                <a href="/the-golden-plate/login.php" class="btn btn-primary">Login</a>
            </p>
          </div>';
    include '../includes/footer.php';
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Helper to load event + reserved seats
function loadEventWithReserved(PDO $pdo, int $id)
{
    $sql = "
        SELECT e.*,
               COALESCE(SUM(r.num_guests), 0) AS reserved_seats
        FROM events e
        LEFT JOIN event_reservations r ON e.event_id = r.event_id
        WHERE e.event_id = :id
        GROUP BY e.event_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($eventId <= 0) {
    include '../includes/header.php';
    echo '<div class="card"><p class="card-text">Invalid event.</p></div>';
    include '../includes/footer.php';
    exit;
}

$event = loadEventWithReserved($pdo, $eventId);
if (!$event || (int)$event['is_public'] !== 1) {
    include '../includes/header.php';
    echo '<div class="card"><p class="card-text">Event not found.</p></div>';
    include '../includes/footer.php';
    exit;
}

$capacity  = (int)$event['capacity'];
$reserved  = (int)$event['reserved_seats'];
$remaining = max($capacity - $reserved, 0);

$errors     = [];
$successMsg = '';
$num_guests = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_guests = $_POST['num_guests'] ?? '1';

    if (!ctype_digit($num_guests) || (int)$num_guests <= 0) {
        $errors[] = 'Number of guests must be a positive whole number.';
    } else {
        // Reload event to get up-to-date reserved count
        $event = loadEventWithReserved($pdo, $eventId);

        $capacity  = (int)$event['capacity'];
        $reserved  = (int)$event['reserved_seats'];
        $remaining = max($capacity - $reserved, 0);

        if ((int)$num_guests > $remaining) {
            $errors[] = "Only $remaining seats remaining for this event.";
        } else {
            $insert = $pdo->prepare("
                INSERT INTO event_reservations (event_id, user_id, guest_name, num_guests)
                VALUES (:event_id, :user_id, NULL, :num_guests)
            ");
            $insert->execute([
                ':event_id'   => $eventId,
                ':user_id'    => $userId,
                ':num_guests' => (int)$num_guests,
            ]);

            $successMsg = 'Your reservation for this event has been confirmed.';
            // Reset form value
            $num_guests = '1';

            // Refresh remaining seats
            $event     = loadEventWithReserved($pdo, $eventId);
            $capacity  = (int)$event['capacity'];
            $reserved  = (int)$event['reserved_seats'];
            $remaining = max($capacity - $reserved, 0);
        }
    }
}

include '../includes/header.php';
?>

<h2 class="app-section-title">Reserve Event Spot</h2>
<p class="app-section-subtitle">
    Confirm how many seats you want to reserve for this event.
</p>

<div class="card">
    <h3 class="card-title" style="margin-bottom:4px;">
        <?php echo htmlspecialchars($event['title']); ?>
    </h3>
    <p class="card-text" style="margin-bottom:4px;">
        <strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?>
        <?php
        $start = $event['start_time'] ? substr($event['start_time'], 0, 5) : '';
        $end   = $event['end_time'] ? substr($event['end_time'], 0, 5) : '';
        if ($start || $end) {
            echo '&nbsp;|&nbsp;<strong>Time:</strong> ' .
                 htmlspecialchars(trim($start . ($end ? ' - ' . $end : '')));
        }
        ?>
    </p>

    <p class="card-text" style="margin-bottom:4px;">
        <strong>Capacity:</strong> <?php echo $capacity; ?> |
        <strong>Reserved:</strong> <?php echo $reserved; ?> |
        <strong>Remaining:</strong> <?php echo $remaining; ?>
    </p>

    <?php if (!empty($event['description'])): ?>
        <p class="card-text" style="margin-bottom:8px;">
            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
        </p>
    <?php endif; ?>

    <?php if ($remaining <= 0): ?>
        <p class="card-text" style="color:#c0392b; font-weight:bold;">
            This event is fully booked.
        </p>
        <p style="margin-top:8px;">
            <a href="events.php" class="btn btn-outline">Back to Events</a>
        </p>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div style="color:#c0392b; margin-bottom: 8px;">
                <?php foreach ($errors as $e): ?>
                    <p><?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div style="color:#27ae60; margin-bottom: 8px;">
                <p><?php echo htmlspecialchars($successMsg); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" style="margin-top:8px;">
            <div style="margin-bottom:10px;">
                <label>Number of guests to reserve (max <?php echo $remaining; ?>)</label><br>
                <input type="number" name="num_guests" min="1"
                       max="<?php echo $remaining; ?>"
                       value="<?php echo htmlspecialchars($num_guests); ?>"
                       style="padding:8px; max-width:120px;">
            </div>

            <button type="submit" class="btn btn-primary">
                Confirm Reservation
            </button>
            <a href="events.php" class="btn btn-outline" style="margin-left:8px;">Back to Events</a>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
