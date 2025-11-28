<?php
session_start();
$activeTab = 'home';
$userRole  = $_SESSION['user_role'] ?? null;

include 'includes/header.php';
?>

<h2 class="app-section-title">Welcome to Golden Plate</h2>
<p class="app-section-subtitle">
    Use the navigation above to access your dashboard and features.
</p>

<?php if ($userRole === 'admin'): ?>
    <!-- Admin sees staff schedule calendar -->
    <div class="card">
        <h3 class="card-title">Staff Schedule Calendar</h3>
        <p class="card-text">
            View staff shifts and manage staff availability.
        </p>
        <a href="schedule/calendar.php" class="btn btn-outline">
            📆 View Staff Calendar
        </a>
    </div>
<?php else: ?>
    <!-- Customers/guests see events, not staff schedule -->
    <div class="card">
        <h3 class="card-title">Restaurant Events</h3>
        <p class="card-text">
            See upcoming events and special days at Golden Plate.
        </p>
        <a href="events/events.php" class="btn btn-outline">
            🎉 View Events
        </a>
    </div>
<?php endif; ?>

<?php if ($userRole === null): ?>
    <div class="card">
        <h3 class="card-title">Get Started</h3>
        <p class="card-text">
            Create an account or log in to make reservations and manage schedules.
        </p>
        <a href="register.php" class="btn btn-primary" style="margin-right: 8px;">📝 Register</a>
        <a href="login.php" class="btn btn-outline">🔐 Login</a>
    </div>
<?php endif; ?>

<?php
include 'includes/footer.php';
?>