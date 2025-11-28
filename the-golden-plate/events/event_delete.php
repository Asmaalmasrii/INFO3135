<?php
// events/event_delete.php
session_start();

require '../includes/db.php';

// Only admin can delete events
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: admin_events.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // First delete reservations for this event (but FK already cascades; this is just explicit)
    $delRes = $pdo->prepare("DELETE FROM event_reservations WHERE event_id = :id");
    $delRes->execute([':id' => $id]);

    // Then delete the event itself
    $delEvent = $pdo->prepare("DELETE FROM events WHERE event_id = :id");
    $delEvent->execute([':id' => $id]);
}

header('Location: admin_events.php?deleted=1');
exit;
