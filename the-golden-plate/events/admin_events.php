<?php
session_start();

// This tells header.php which tab to highlight
$activeTab = 'admin_events';

// Because this file is in /events, go one level up to reach includes/
require '../includes/db.php';

// --- Admin check BEFORE doing anything else ---
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    include '../includes/header.php';
    echo '<div class="card" style="border-left:4px solid #c0392b; margin-top:16px;">
            <p class="card-text">Access denied. Admins only.</p>
          </div>';
    include '../includes/footer.php';
    exit;
}

include '../includes/header.php';

// Optional: show success message after delete
$deleted = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;

// Try to load events; if tables are missing, show a friendly message instead of a blank page
try {
    $sql = "
        SELECT e.*,
               COALESCE(SUM(r.num_guests), 0) AS reserved_seats
        FROM events e
        LEFT JOIN event_reservations r ON e.event_id = r.event_id
        GROUP BY e.event_id
        ORDER BY e.event_date, e.start_time
    ";
    $stmt   = $pdo->query($sql);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<div class="card" style="margin-top:16px; border-left:4px solid #c0392b;">
            <p class="card-text">
                There was a problem loading events.<br>
                <strong>Tip:</strong> Make sure you imported <code>golden_plate_db.sql</code>
                so the <code>events</code> and <code>event_reservations</code> tables exist.
            </p>
          </div>';
    include '../includes/footer.php';
    exit;
}
?>

<h2 class="app-section-title">Events Management</h2>
<p class="app-section-subtitle">
    Create restaurant events and set how many people can reserve each event.
</p>

<?php if ($deleted): ?>
    <div class="card" style="border-left:4px solid #27ae60; margin-bottom:12px;">
        <p class="card-text">Event deleted successfully.</p>
    </div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Create New Event</h3>
    <p class="card-text">Add a new event with date and capacity.</p>
    <!-- file is in /events, so use relative link -->
    <a href="event_create.php" class="btn btn-primary">➕ Add Event</a>
</div>

<div class="card">
    <h3 class="card-title">Existing Events</h3>

    <?php if (empty($events)): ?>
        <p class="card-text muted">No events created yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Title</th>
                <th>Date</th>
                <th>Time</th>
                <th>Capacity</th>
                <th>Reserved</th>
                <th>Remaining</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $e):
                $capacity  = (int)$e['capacity'];
                $reserved  = (int)$e['reserved_seats'];
                $remaining = max($capacity - $reserved, 0);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['title']); ?></td>
                    <td><?php echo htmlspecialchars($e['event_date']); ?></td>
                    <td>
                        <?php
                        $start = $e['start_time'] ? substr($e['start_time'], 0, 5) : '';
                        $end   = $e['end_time'] ? substr($e['end_time'], 0, 5) : '';
                        echo htmlspecialchars(trim($start . ($end ? ' - ' . $end : '')));
                        ?>
                    </td>
                    <td><?php echo $capacity; ?></td>
                    <td><?php echo $reserved; ?></td>
                    <td><?php echo $remaining; ?></td>
                    <td>
                        <a href="event_edit.php?id=<?php echo (int)$e['event_id']; ?>" class="btn btn-outline">
                            ✏️ Edit
                        </a>
                        <a href="event_delete.php?id=<?php echo (int)$e['event_id']; ?>"
                           class="btn btn-outline"
                           onclick="return confirm('Are you sure you want to delete this event? All reservations for it will also be removed.');">
                            🗑 Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
