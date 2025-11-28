<?php
// login.php
session_start();

$activeTab = 'login';
require 'includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success: store session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: customer_dashboard.php");
            }
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<h2 class="app-section-title">Login</h2>
<p class="app-section-subtitle">
    Log in with your Golden Plate account.
</p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-left: 4px solid #c0392b;">
        <h3 class="card-title">Login failed</h3>
        <ul class="card-text" style="margin-left: 18px; list-style: disc;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Enter your credentials</h3>

    <form action="login.php" method="post" style="margin-top: 12px;">
        <div style="margin-bottom: 10px;">
            <label>Email</label><br>
            <input type="email" name="email"
                   required
                   style="padding: 6px 10px; width: 100%; max-width: 320px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Password</label><br>
            <input type="password" name="password" required
                   style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php
include 'includes/footer.php';
?>
