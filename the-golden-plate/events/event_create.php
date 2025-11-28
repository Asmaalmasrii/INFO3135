<?php
session_start();

$activeTab = 'admin_events';
require '../includes/db.php';
include '../includes/header.php';

// Only admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="card" style="border-left:4px solid #c0392b;">
            <p class="card-text">Access denied. Admins only.</p>
          </div>';
    include '../includes/footer.php';
    exit;
}

$title       = '';
$event_date  = '';
$start_time  = '';
$end_time    = '';
$capacity    = '';
$description = '';
$is_public   = 1;
$errors      = [];

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
        $stmt = $pdo->prepare("
            INSERT INTO events (title, event_date, start_time, end_time, capacity, description, is_public)
            VALUES (:title, :event_date, :start_time, :end_time, :capacity, :description, :is_public)
        ");
        $stmt->execute([
            ':title'       => $title,
            ':event_date'  => $event_date,
            ':start_time'  => $start_time !== '' ? $start_time : null,
            ':end_time'    => $end_time !== '' ? $end_time : null,
            ':capacity'    => (int)$capacity,
            ':description' => $description !== '' ? $description : null,
            ':is_public'   => $is_public,
        ]);

        header('Location: admin_events.php?created=1');
        exit;
    }
}
?>

<h2 class="app-section-title">Create Event</h2>
<p class="app-section-subtitle">
    Define an event and limit how many people can reserve.
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

        <button type="submit" class="btn btn-primary">Create Event</button>
        <a href="admin_events.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
