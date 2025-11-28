<?php
// admin_reservations.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$activeTab = 'admin_reservations';

require 'includes/db.php';
include 'includes/header.php';

$successMessage = '';
$errors         = [];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reservationId = $_POST['reservation_id'] ?? null;
    $newStatus     = $_POST['status']         ?? null;

    if ($reservationId && $newStatus) {
        $updateStmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        if ($updateStmt->execute([$newStatus, $reservationId])) {
            $successMessage = 'Reservation status updated successfully!';
        } else {
            $errors[] = 'Failed to update reservation status.';
        }
    }
}

// Handle reservation deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservationId = $_GET['delete'];
    $deleteStmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
    if ($deleteStmt->execute([$reservationId])) {
        $successMessage = 'Reservation deleted successfully!';
    } else {
        $errors[] = 'Failed to delete reservation.';
    }
}

// Fetch all reservations
$reservationsStmt = $pdo->query('
    SELECT r.*, t.table_number, t.capacity, u.full_name, u.email 
    FROM reservations r
    JOIN tables t ON r.table_id = t.id
    JOIN users  u ON r.user_id = u.id
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
');
$allReservations = $reservationsStmt->fetchAll();

// Separate by status / date
$upcoming  = [];
$past      = [];
$cancelled = [];

$today = date('Y-m-d');

foreach ($allReservations as $res) {
    if (strtolower($res['status']) === 'cancelled') {
        $cancelled[] = $res;
    } elseif ($res['reservation_date'] >= $today) {
        $upcoming[] = $res;
    } else {
        $past[] = $res;
    }
}
?>

<h2 class="app-section-title">📋 Manage All Reservations</h2>
<p class="app-section-subtitle">
    View and manage all customer table reservations.
</p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-left: 4px solid #c0392b;">
        <h3 class="card-title">Errors:</h3>
        <ul class="card-text" style="margin-left: 18px; list-style: disc;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div class="card" style="border-left: 4px solid #27ae60;">
        <p class="card-text"><?php echo htmlspecialchars($successMessage); ?></p>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="card">
    <h3 class="card-title">📊 Reservations Summary</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #e8f5e9; text-align: center;">
            <strong>Upcoming</strong>
            <p style="font-size: 24px; margin: 5px 0;"><?php echo count($upcoming); ?></p>
        </div>
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fffde7; text-align: center;">
            <strong>Past</strong>
            <p style="font-size: 24px; margin: 5px 0;"><?php echo count($past); ?></p>
        </div>
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #ffebee; text-align: center;">
            <strong>Cancelled</strong>
            <p style="font-size: 24px; margin: 5px 0;"><?php echo count($cancelled); ?></p>
        </div>
    </div>
</div>

<!-- Upcoming Reservations -->
<div class="card">
    <h3 class="card-title">📅 Upcoming Reservations</h3>

    <?php if (empty($upcoming)): ?>
        <p class="card-text">No upcoming reservations.</p>
    <?php else: ?>
        <?php foreach ($upcoming as $res): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h4 style="margin-top: 0; color: #2c3e50;">
                            Reservation #<?php echo $res['id']; ?>
                        </h4>
                        <p style="margin: 5px 0; font-size: 14px;">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($res['full_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($res['email']); ?><br>
                            <strong>Table:</strong> Table <?php echo htmlspecialchars($res['table_number']); ?>
                            (Capacity: <?php echo htmlspecialchars($res['capacity']); ?>)<br>
                            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($res['reservation_date'])); ?><br>
                            <strong>Time:</strong> <?php echo htmlspecialchars($res['reservation_time']); ?><br>
                            <strong>Party Size:</strong> <?php echo htmlspecialchars($res['party_size']); ?> guests<br>
                            <strong>Status:</strong>
                            <span style="background: #27ae60; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px;">
                                <?php echo strtoupper($res['status']); ?>
                            </span>
                        </p>

                        <?php if (!empty($res['special_requests'])): ?>
                            <p style="margin: 8px 0; font-size: 13px; color: #666;">
                                <strong>Special Requests:</strong><br>
                                <?php echo nl2br(htmlspecialchars($res['special_requests'])); ?>
                            </p>
                        <?php endif; ?>

                        <p style="margin: 5px 0; font-size: 12px; color: #999;">
                            Booked on: <?php echo date('M j, Y', strtotime($res['created_at'])); ?>
                        </p>
                    </div>

                    <div style="margin-top: 10px;">
                        <form method="post" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                            <select name="status" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="confirmed" <?php echo strtolower($res['status']) === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo strtolower($res['status']) === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="completed" <?php echo strtolower($res['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_status" class="btn" style="padding: 5px 12px; font-size: 13px;">
                                Update
                            </button>
                        </form>

                        <a href="admin_reservations.php?delete=<?php echo $res['id']; ?>"
                           class="btn"
                           style="background: #c0392b; color: white; padding: 5px 12px; font-size: 13px; text-decoration: none;"
                           onclick="return confirm('Are you sure you want to delete this reservation?');">
                            Delete
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Past Reservations -->
<div class="card">
    <h3 class="card-title">📜 Past Reservations</h3>
    <?php if (empty($past)): ?>
        <p class="card-text">No past reservations.</p>
    <?php else: ?>
        <?php foreach (array_slice($past, 0, 10) as $res): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f9f9f9; opacity: 0.7;">
                <h4 style="margin-top: 0; color: #7f8c8d; font-size: 16px;">
                    Reservation #<?php echo $res['id']; ?>
                </h4>
                <p style="margin: 5px 0; font-size: 13px; color: #666;">
                    <strong>Customer:</strong> <?php echo htmlspecialchars($res['full_name']); ?> |
                    <strong>Table:</strong> <?php echo htmlspecialchars($res['table_number']); ?> |
                    <strong>Date:</strong> <?php echo date('M j, Y', strtotime($res['reservation_date'])); ?> |
                    <strong>Time:</strong> <?php echo htmlspecialchars($res['reservation_time']); ?>
                </p>
            </div>
        <?php endforeach; ?>

        <?php if (count($past) > 10): ?>
            <p class="card-text" style="font-size: 13px; color: #666;">
                Showing 10 of <?php echo count($past); ?> past reservations
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Cancelled Reservations -->
<div class="card">
    <h3 class="card-title">❌ Cancelled Reservations</h3>
    <?php if (empty($cancelled)): ?>
        <p class="card-text">No cancelled reservations.</p>
    <?php else: ?>
        <?php foreach ($cancelled as $res): ?>
            <div style="border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 8px; background: #fbeaea;">
                <h4 style="margin-top: 0; color: #c0392b; font-size: 16px;">
                    Reservation #<?php echo $res['id']; ?>
                </h4>
                <p style="margin: 5px 0; font-size: 13px; color: #666;">
                    <strong>Customer:</strong> <?php echo htmlspecialchars($res['full_name']); ?> |
                    <strong>Table:</strong> <?php echo htmlspecialchars($res['table_number']); ?> |
                    <strong>Date:</strong> <?php echo date('M j, Y', strtotime($res['reservation_date'])); ?> |
                    <strong>Time:</strong> <?php echo htmlspecialchars($res['reservation_time']); ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
