<?php
session_start();

$activeTab = 'admin_events';
require '../includes/db.php';

// Only admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    include '../includes/header.php';
    echo '<div class="card" style="border-left:4px solid #c0392b;">
            <p class="card-text">Access denied. Admins only.</p>
          </div>';
    include '../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    include '../includes/header.php';
    echo '<div class="card"><p class="card-text">Invalid event ID.</p></div>';
    include '../includes/footer.php';
    exit;
}

// Load existing event
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = :id");
$stmt->execute([':id' => $id]);
$event = $stmt->fetch();

if (!$event) {
    include '../includes/header.php';
    echo '<div class="card"><p class="card-text">Event not found.</p></div>';
    include '../includes/footer.php';
    exit;
}

$title       = $event['title'];
$event_date  = $event['event_date'];
$start_time  = $event['start_time'];
$end_time    = $event['end_time'];
$capacity    = (string)$event['capacity'];
$description = $event['description'];
$is_public   = (int)$event['is_public'];
$errors      = [];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $start_time  = trim($_POST['start_time'] ?? '');
    $end_time    = trim($_POST['end_time'] ?? '');
    $capacity    = trim($_POST['capacity'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_public   = isset($_POST['is_public']) ? 1 : 0;

    if ($title === '' || $event_date === '' || $capacity === '') {
        $errors[] = 'Title, date, and capacity are required.';
    } elseif (!ctype_digit($capacity) || (int)$capacity <= 0) {
        $errors[] = 'Capacity must be a positive whole number.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE events
            SET title = :title,
                event_date = :event_date,
                start_time = :start_time,
                end_time   = :end_time,
                capacity   = :capacity,
                description = :description,
                is_public   = :is_public
            WHERE event_id = :id
        ");
        $update->execute([
            ':title'       => $title,
            ':event_date'  => $event_date,
            ':start_time'  => $start_time !== '' ? $start_time : null,
            ':end_time'    => $end_time !== '' ? $end_time : null,
            ':capacity'    => (int)$capacity,
            ':description' => $description !== '' ? $description : null,
            ':is_public'   => $is_public,
            ':id'          => $id,
        ]);

        header('Location: admin_events.php?updated=1');
        exit;
    }
}

include '../includes/header.php';
?>

<h2 class="app-section-title">Edit Event</h2>
<p class="app-section-subtitle">
    Update event details and capacity.
</p>

<div class="card">
    <?php if (!empty($errors)): ?>
        <div style="color:#c0392b; margin-bottom: 12px;">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div style="margin-bottom:10px;">
            <label>Title</label><br>
            <input type="text" name="title"
                   value="<?php echo htmlspecialchars($title); ?>"
                   style="width:100%; padding:8px;">
        </div>

        <div style="margin-bottom:10px;">
            <label>Date</label><br>
            <input type="date" name="event_date"
                   value="<?php echo htmlspecialchars($event_date); ?>"
                   style="padding:8px;">
        </div>

        <div style="margin-bottom:10px;">
            <label>Start Time (optional)</label><br>
            <input type="time" name="start_time"
                   value="<?php echo htmlspecialchars($start_time); ?>"
                   style="padding:8px;">
        </div>

        <div style="margin-bottom:10px;">
            <label>End Time (optional)</label><br>
            <input type="time" name="end_time"
                   value="<?php echo htmlspecialchars($end_time); ?>"
                   style="padding:8px;">
        </div>

        <div style="margin-bottom:10px;">
            <label>Capacity (number of people)</label><br>
            <input type="number" name="capacity" min="1"
                   value="<?php echo htmlspecialchars($capacity); ?>"
                   style="padding:8px;">
        </div>

        <div style="margin-bottom:10px;">
            <label>Description (optional)</label><br>
            <textarea name="description" rows="4" style="width:100%; padding:8px;"><?php
                echo htmlspecialchars($description);
            ?></textarea>
        </div>

        <div style="margin-bottom:10px;">
            <label>
                <input type="checkbox" name="is_public" <?php echo $is_public ? 'checked' : ''; ?>>
                Public event (visible to customers)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="admin_events.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
