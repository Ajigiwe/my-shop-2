<?php
// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Set page title
$page_title = 'Profile Settings';

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header('Location: ../index.php');
    exit();
}

// Handle form submission
$errors = [];
$success = '';
$name = $user['name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$old_password = '';
$new_password = '';
$confirm_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = 'Please enter a valid phone number';
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already in use';
            }
        } catch(PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }

    // Validate password change
    if (!empty($new_password)) {
        if (empty($old_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!verifyPassword($old_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }

    // If no errors, update profile
    if (empty($errors)) {
        try {
            $update_fields = ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address];
            $update_values = [$name, $email, $phone, $address, $_SESSION['user_id']];

            // Add password update if provided
            if (!empty($new_password)) {
                $update_fields['password'] = hashPassword($new_password);
                $update_values = [$name, $email, $phone, $address, $update_fields['password'], $_SESSION['user_id']];
            }

            $sql = "UPDATE users SET " . implode(' = ?, ', array_keys($update_fields)) . " = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_values);

            // Update session variables
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            $success = 'Profile updated successfully!';

            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;

        } catch(PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $errors[] = 'An error occurred while updating your profile. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Custom Navbar Styles -->
    <style>
        .dropdown-container {
            position: relative;
        }

        .dropdown-menu-custom {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            padding: 8px 0;
        }

        .dropdown-menu-custom.show {
            display: block;
        }

        .dropdown-item-custom {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .dropdown-item-custom:hover {
            background-color: #f8f9fa;
            color: #007bff;
            text-decoration: none;
        }

        .dropdown-divider-custom {
            height: 1px;
            margin: 8px 0;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Profile Settings</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Basic Information -->
                        <h5 class="mb-3">Basic Information</h5>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($phone); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>

                        <!-- Password Change -->
                        <h5 class="mb-3 mt-4">Change Password</h5>
                        <p class="text-muted small">Leave blank to keep current password</p>

                        <div class="mb-3">
                            <label for="old_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="old_password" name="old_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-user fa-3x text-primary mb-3"></i>
                    <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6>Quick Links</h6>
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>User Dashboard
                        </a>
                        <a href="orders.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a href="../cart.php" class="btn btn-outline-info">
                            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
