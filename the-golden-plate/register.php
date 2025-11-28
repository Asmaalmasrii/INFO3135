<?php
// register.php
session_start();

$activeTab = 'register';
require 'includes/db.php';

$errors = [];
$successMessage = '';

// Admin registration secret code (you can change it)
$ADMIN_REG_CODE = 'GPADMIN2025';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'customer';  // 'customer' or 'admin'
    $adminCode = trim($_POST['admin_code'] ?? '');

    // Validation
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Password and confirmation do not match.';
    }

    if (!in_array($role, ['customer', 'admin'], true)) {
        $role = 'customer';
    }

    // If admin chosen, verify admin code
    if ($role === 'admin') {
        if ($adminCode === '') {
            $errors[] = 'Admin registration code is required to create an admin account.';
        } elseif ($adminCode !== $ADMIN_REG_CODE) {
            $errors[] = 'Invalid admin registration code.';
        }
    }

    // Check email uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    // Insert user
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $email, $hash, $role]);

        $successMessage = 'Registration successful! You can now log in.';
        $fullName = $email = '';
        $role = 'customer';
        $adminCode = '';
    }
}

include 'includes/header.php';
?>

<h2 class="app-section-title">Create an Account</h2>
<p class="app-section-subtitle">
    Register as a <strong>Customer</strong> to make reservations, or as an <strong>Admin</strong> to manage users and staff schedules.
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
            <a href="login.php" class="btn btn-primary" style="margin-left:10px;">Go to Login</a>
        </p>
    </div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Register</h3>

    <form action="register.php" method="post" style="margin-top: 12px;">
        <div style="margin-bottom: 10px;">
            <label>Full Name</label><br>
            <input type="text" name="full_name"
                   value="<?php echo isset($fullName) ? htmlspecialchars($fullName) : ''; ?>"
                   required
                   style="padding: 6px 10px; width: 100%; max-width: 350px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Email</label><br>
            <input type="email" name="email"
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                   required
                   style="padding: 6px 10px; width: 100%; max-width: 350px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Password</label><br>
            <input type="password" name="password" required
                   style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Confirm Password</label><br>
            <input type="password" name="confirm_password" required
                   style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 10px;">
            <label>Register As</label><br>
            <label style="margin-right: 12px;">
                <input type="radio" name="role" value="customer"
                    <?php echo (!isset($role) || $role === 'customer') ? 'checked' : ''; ?>>
                Customer
            </label>
            <label>
                <input type="radio" name="role" value="admin"
                    <?php echo (isset($role) && $role === 'admin') ? 'checked' : ''; ?>>
                Admin
            </label>
        </div>

        <div style="margin-bottom: 10px;">
            <label>Admin Registration Code (only if registering as Admin)</label><br>
            <input type="text" name="admin_code"
                   value="<?php echo isset($adminCode) ? htmlspecialchars($adminCode) : ''; ?>"
                   placeholder="Enter admin code if Admin"
                   style="padding: 6px 10px; width: 100%; max-width: 250px; border-radius: 6px; border: 1px solid #ccc;">
            <p class="muted" style="font-size: 0.8rem; margin-top: 4px;">
                For testing, you can use: <strong><?php echo htmlspecialchars($ADMIN_REG_CODE); ?></strong>
            </p>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<?php
include 'includes/footer.php';
?>