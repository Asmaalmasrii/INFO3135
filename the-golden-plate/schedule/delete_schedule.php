<?php
// schedule/delete_schedule.php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Access denied. Admins only.";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid schedule ID.";
    exit;
}

// Get the schedule first so we know which date to redirect back to
$stmt = $pdo->prepare("SELECT shift_date FROM schedules WHERE schedule_id = :id");
$stmt->execute([':id' => $id]);
$schedule = $stmt->fetch();

if (!$schedule) {
    echo "Schedule not found.";
    exit;
}

$shiftDate = $schedule['shift_date'];

// Delete and redirect back to the daily view
$del = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = :id");
$del->execute([':id' => $id]);

header('Location: view_schedule.php?date=' . urlencode($shiftDate));
exit;