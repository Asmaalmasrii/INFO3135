<?php
session_start();

// Move working directory to project root so that include paths work correctly
chdir(__DIR__ . '/..');

// Include database
require 'includes/db.php';

// Highlight this tab in the nav
$activeTab = 'calendar';

// Include the shared header
include 'includes/header.php';
?>


<!-- Load CSS (URL-relative paths, not affected by chdir) -->
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="schedule.css">

<!-- Hide the original navigation bar from header.php -->
<style>
    nav.tabs-nav { display: none; }
</style>

<!-- Custom navigation bar with correct absolute paths -->
<nav class="tabs-nav">

    <!-- Home -->
    <a href="/the-golden-plate/index.php"
       class="tab-link <?php echo $activeTab === 'home' ? 'active' : ''; ?>">
        <span class="tab-icon">🏠</span> Home
    </a>

    <!-- Admin Dashboard -->
    <a href="/the-golden-plate/admin_dashboard.php"
       class="tab-link <?php echo $activeTab === 'admin' ? 'active' : ''; ?>">
        <span class="tab-icon">🛠</span> Admin Dashboard
    </a>

    <!-- Reservations -->
    <a href="/the-golden-plate/admin_reservations.php"
       class="tab-link <?php echo $activeTab === 'admin_reservations' ? 'active' : ''; ?>">
        <span class="tab-icon">📋</span> Reservations
    </a>

    <!-- Staff Calendar (active tab) -->
    <a href="/the-golden-plate/schedule/calendar.php"
       class="tab-link active">
        <span class="tab-icon">📆</span> Staff Calendar
    </a>

    <!-- Logout -->
    <a href="/the-golden-plate/logout.php" class="tab-link">
        <span class="tab-icon">🚪</span> Logout
    </a>
</nav>


<?php
// =======================================
//  Calendar Logic
// =======================================

// Determine current year and month
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// Validate
if ($year < 1970 || $year > 2100) $year = date('Y');
if ($month < 1 || $month > 12)   $month = date('n');

$firstOfMonth = new DateTime("$year-$month-01");
$daysInMonth  = $firstOfMonth->format('t');
$startWeekday = $firstOfMonth->format('w');
$monthName    = $firstOfMonth->format('F Y');

$prev = (clone $firstOfMonth)->modify('-1 month');
$next = (clone $firstOfMonth)->modify('+1 month');

// Query shifts for this month
$sql = "
    SELECT s.*, e.name AS employee_name
    FROM schedules s
    JOIN employees e ON s.employee_id = e.employee_id
    WHERE s.shift_date BETWEEN :start AND :end
    ORDER BY s.shift_date, s.shift_start
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':start' => $firstOfMonth->format('Y-m-01'),
    ':end'   => $firstOfMonth->format('Y-m-t')
]);
$schedules = $stmt->fetchAll();

// Group shifts by date
$shiftsByDate = [];
foreach ($schedules as $shift) {
    $shiftsByDate[$shift['shift_date']][] = $shift;
}

$today = date('Y-m-d');
?>

<h2 class="app-section-title">Staff Schedule Calendar</h2>

<!-- Month Navigation -->
<div class="calendar-nav">
    <a href="?year=<?= $prev->format('Y'); ?>&month=<?= $prev->format('n'); ?>">&laquo; Previous</a>

    <strong><?= htmlspecialchars($monthName); ?></strong>

    <a href="?year=<?= $next->format('Y'); ?>&month=<?= $next->format('n'); ?>">Next &raquo;</a>

    <a href="calendar.php" class="today-btn">Today</a>
</div>

<!-- Add Schedule Button -->
<?php if($_SESSION['user_role'] === 'admin'): ?>
<div class="add-btn-center">
    <a href="add_schedule.php" class="btn-primary">➕ Add Schedule</a>
</div>
<?php endif; ?>

<!-- Calendar Grid -->
<div class="calendar-container">

    <!-- Weekday Labels -->
    <div class="calendar-grid weekdays">
        <div>Sun</div><div>Mon</div><div>Tue</div>
        <div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>

    <!-- Days -->
    <div class="calendar-grid">
        <?php
        // Empty boxes before the 1st
        for ($i = 0; $i < $startWeekday; $i++) {
            echo '<div class="day-box empty"></div>';
        }

        // Days of the month
        for ($day=1; $day <= $daysInMonth; $day++) {

            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $isToday = ($dateStr === $today);

            echo '<div class="day-box'.($isToday ? ' today' : '').'">';
            echo '<div class="day-number">'.$day.'</div>';

            // Show shifts
            if(isset($shiftsByDate[$dateStr])) {
                foreach(array_slice($shiftsByDate[$dateStr],0,2) as $shift){
                    $start = substr($shift['shift_start'],0,5);
                    $end = substr($shift['shift_end'],0,5);
                    $color = "shift-color-".($shift['employee_id'] % 5);

                    echo "<div class='shift-box $color'>".
                            htmlspecialchars($shift['employee_name']).
                            "<br>$start - $end
                          </div>";
                }
                if(count($shiftsByDate[$dateStr]) > 2){
                    echo '<div class="shift-more">+'.(count($shiftsByDate[$dateStr]) - 2).' more</div>';
                }
            }

            // View link
            echo '<div class="view-day-left">
                    <a href="view_schedule.php?date='.$dateStr.'">View 🔍</a>
                  </div>';

            echo '</div>'; // day-box
        }
        ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>