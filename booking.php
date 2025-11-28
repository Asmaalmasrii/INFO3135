<?php 
// booking.php - make reservation page
session_start();

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$activeTab = 'reservations';

require 'includes/db.php';
include 'includes/header.php';

$errors = [];
$successMessage = '';

// fetch available tables
$tablesStmt = $pdo->query("SELECT * FROM tables WHERE status = 'available' ORDER BY capacity");
$tables = $tablesStmt->fetchAll();

// build [table_id => capacity] map for JS
$tableCapacities = [];
foreach ($tables as $t) {
    $tableCapacities[$t['id']] = (int)$t['capacity'];
}

// available time slots 
$timeSlots = [
    "12:00 PM","12:30 PM","1:00 PM","1:30 PM","2:00 PM",
    "5:00 PM","5:30 PM","6:00 PM","6:30 PM","7:00 PM",
    "7:30 PM","8:00 PM","8:30 PM","9:00 PM","9:30 PM"
];

$date = '';
$time = '';
$partySize = '';
$specialReq = '';
$tableId = 0;

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableId    = isset($_POST['table_id']) ? (int)$_POST['table_id'] : 0;
    $date       = $_POST['reservation_date']  ?? '';
    $time       = $_POST['reservation_time']  ?? '';
    $partySize  = isset($_POST['party_size']) ? (int)$_POST['party_size'] : 0;
    $specialReq = trim($_POST['special_requests'] ?? '');

    // validation
    if ($tableId <= 0) {
        $errors[] = 'Please select a table.';
    }
    if ($date === '') {
        $errors[] = 'Reservation date is required.';
    }
    if ($time === '') {
        $errors[] = 'Reservation time is required.';
    }
    if ($partySize <= 0) {
        $errors[] = 'Number of guests is required.';
    }

    // validate date (cannot book past dates)
    $today = date('Y-m-d');
    if ($date !== '' && $date < $today) {
        $errors[] = "Cannot book for past dates!";
    }

    // SERVER-SIDE: Check party size does not exceed table capacity
    if ($tableId > 0) {
        $capacityStmt = $pdo->prepare('SELECT capacity FROM tables WHERE id = ? AND status = "available"');
        $capacityStmt->execute([$tableId]);
        $tableRow = $capacityStmt->fetch();

        if (!$tableRow) {
            $errors[] = 'The selected table is no longer available. Please choose another table.';
        } else {
            $capacity = (int)$tableRow['capacity'];
            if ($capacity <= 0) {
                $capacity = 1; // safety
            }
            if ($partySize > $capacity) {
                $errors[] = "This table can seat up to {$capacity} people. Please reduce your party size or choose a larger table.";
            }
        }
    }

    if (empty($errors)) {
        // check if table is already booked at that time and date
        $checkStmt = $pdo->prepare('
            SELECT * FROM reservations
            WHERE table_id = ?
              AND reservation_date = ?
              AND reservation_time = ?
              AND status != "cancelled"
        ');
        $checkStmt->execute([$tableId, $date, $time]);

        if ($checkStmt->fetch()) {
            $errors[] = 'This table is already booked at this time. Please select another table or time.';
        } else {
            // insert reservation (same logic as before)
            $insertStmt = $pdo->prepare('
                INSERT INTO reservations
                (user_id, table_id, reservation_date, reservation_time, party_size, special_requests, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, "confirmed", NOW())
            ');
            $insertStmt->execute([
                $_SESSION['user_id'],
                $tableId,
                $date,
                $time,
                $partySize,
                $specialReq
            ]);
            $successMessage = 'Reservation created successfully!';
            // reset form values
            $date = '';
            $time = '';
            $partySize = '';
            $specialReq = '';
            $tableId = 0;
        }
    }
}
?>

<h2 class="app-section-title">📅 Make a Table Reservation</h2>
<p class="app-section-subtitle">
    Reserve your table at Golden Plate Restaurant.
</p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-left: 4px solid #c0392b;">
        <h3 class="card-title">There were some problems:</h3>
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
            <a href="my_reservations.php" class="btn btn-primary" style="margin-left:10px;">View My Reservations</a>
        </p>
    </div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Available Tables</h3>
    <?php if (empty($tables)): ?>
        <p class="card-text">No tables available at the moment.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <?php foreach ($tables as $table): ?>
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9; text-align: center;">
                    <h4 style="margin-top: 0; color: #d4a024;">
                        Table <?php echo htmlspecialchars($table['table_number']); ?>
                    </h4>
                    <p style="margin: 5px 0; font-size: 14px;">
                        <strong>Capacity:</strong> <?php echo htmlspecialchars($table['capacity']); ?> people
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 class="card-title">Book Your Table</h3>
    <form action="booking.php" method="post" style="margin-top: 12px;">
        <div style="margin-bottom: 10px;">
            <label>Select Table</label><br>
            <select name="table_id" required
                    style="padding: 6px 10px; width: 100%; max-width: 350px; border-radius: 6px; border: 1px solid #ccc;">
                <option value="">-- Choose a table --</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?php echo $table['id']; ?>"
                        <?php echo ($tableId == $table['id']) ? 'selected' : ''; ?>>
                        Table <?php echo htmlspecialchars($table['table_number']) . ' (Capacity: ' . htmlspecialchars($table['capacity']) . ' people)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 10px;">
            <label>Reservation Date</label><br>
            <input type="date" name="reservation_date"
                   min="<?php echo date('Y-m-d'); ?>"
                   value="<?php echo $date ? htmlspecialchars($date) : ''; ?>"
                   required
                   style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Reservation Time</label><br>
            <select name="reservation_time" required
                    style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
                <option value="">-- Choose a time --</option>
                <?php foreach ($timeSlots as $slot): ?>
                    <option value="<?php echo $slot; ?>"
                        <?php echo ($time === $slot) ? 'selected' : ''; ?>>
                        <?php echo $slot; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 10px;">
            <label>Party Size (Number of Guests)</label><br>
            <input type="number" name="party_size" min="1" max="20"
                   value="<?php echo $partySize !== '' ? htmlspecialchars($partySize) : '2'; ?>"
                   required
                   style="padding: 6px 10px; width: 100%; max-width: 150px; border-radius: 6px; border: 1px solid #ccc;">
            <p style="font-size: 12px; color:#7f8c8d; margin-top:4px;">
                * Party size cannot be more than the selected table capacity.
            </p>
        </div>

        <div style="margin-bottom: 10px;">
            <label>Special Requests (Optional)</label><br>
            <textarea name="special_requests" rows="3"
                      style="padding: 6px 10px; width: 100%; max-width: 450px; border-radius: 6px; border: 1px solid #ccc;"
                      placeholder="Any dietary restrictions, celebrations, or special requirements..."><?php echo htmlspecialchars($specialReq); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Book Table</button>
    </form>
</div>

<!-- Small JS helper to sync party size max with table capacity -->
<script>
    const tableCapacities = <?php echo json_encode($tableCapacities); ?>;
    const tableSelect = document.querySelector('select[name="table_id"]');
    const partyInput  = document.querySelector('input[name="party_size"]');

    function updatePartyMax() {
        if (!tableSelect || !partyInput) return;
        const tableId = tableSelect.value;
        const cap = tableCapacities[tableId] ? parseInt(tableCapacities[tableId]) : 20;
        partyInput.max = cap;
        if (parseInt(partyInput.value || '1') > cap) {
            partyInput.value = cap;
        }
    }

    if (tableSelect && partyInput) {
        tableSelect.addEventListener('change', updatePartyMax);
        // run once on page load (for when a table is already selected)
        updatePartyMax();
    }
</script>

<?php
include 'includes/footer.php';
?>
