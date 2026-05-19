<?php
/**
 * User: Profile Settings
 * - Rebuilt to be significantly less bulky.
 * - Minimalist, information-dense, and refined.
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Settings';

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Simple validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
            $params = [$name, $email, $phone, $address, $user_id];
            
            // Optional password update
            if (!empty($new_password)) {
                if (password_verify($old_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE user_id = ?";
                        $params = [$name, $email, $phone, $address, password_hash($new_password, PASSWORD_DEFAULT), $user_id];
                    } else {
                        $errors[] = "New passwords don't match";
                    }
                } else {
                    $errors[] = "Current password is incorrect";
                }
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success = "Settings updated successfully!";
                
                // Refresh data
                $_SESSION['user_name'] = $name;
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['address'] = $address;
            }
        } catch(PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="flex-1 bg-[#F9F9F9] min-h-screen">
    <div class="max-w-[1200px] mx-auto px-6 py-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 bg-white border border-[#EEEEEE] p-6 rounded-xl shadow-sm">
            <div>
                <nav class="flex items-center gap-1.5 text-[9px] font-black text-[#888888] uppercase tracking-widest mb-3">
                    <a href="dashboard.php" class="hover:text-[#1A1A1A]">Dashboard</a>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-[#1A1A1A]">Account Settings</span>
                </nav>
                <h1 class="text-[24px] font-black text-[#1A1A1A] tracking-tighter mb-1">Settings</h1>
                <p class="text-[12px] text-[#666666] font-medium">Manage your personal information and security.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="../logout.php" class="h-10 px-6 rounded-lg bg-red-50 border border-red-100 text-red-600 font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:bg-red-100 transition-all">
                    <span class="material-symbols-outlined text-[16px]">logout</span> Sign Out
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Sidebar Nav -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-5 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-white text-[18px] font-black">
                            <?php echo substr($user['name'], 0, 1); ?>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-black text-[#1A1A1A] tracking-tight"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-[10px] font-bold text-[#888888]"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">dashboard</span> Dashboard
                        </a>
                        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span> My Orders
                        </a>
                        <a href="wishlist.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">favorite</span> My Wishlist
                        </a>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-primary text-white font-black text-[11px] uppercase tracking-widest shadow-md">
                            <span class="material-symbols-outlined text-[18px]">settings</span> Settings
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="lg:col-span-8">
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 md:p-8 shadow-sm">
                    <?php if ($success): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-100 rounded-lg flex items-center gap-3 text-green-700">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            <p class="text-[12px] font-black tracking-tight"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-lg flex items-start gap-3 text-red-700">
                            <span class="material-symbols-outlined text-[18px]">error</span>
                            <div class="flex flex-col gap-1">
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-[11px] font-black tracking-tight"><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-8">
                        <div>
                            <h3 class="text-[10px] font-black text-[#888888] uppercase tracking-widest mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> Basic Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                                           class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]" required>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]" required>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]">
                                </div>
                            </div>
                            <div class="mt-5 space-y-1.5">
                                <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Default Address</label>
                                <textarea name="address" rows="2" class="w-full p-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px] resize-none"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-[#EEEEEE]">
                            <h3 class="text-[10px] font-black text-[#888888] uppercase tracking-widest mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Security
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Current Password</label>
                                    <input type="password" name="old_password" placeholder="••••••••" class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">New Password</label>
                                    <input type="password" name="new_password" placeholder="••••••••" class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-[#1A1A1A] uppercase tracking-widest ml-1">Confirm</label>
                                    <input type="password" name="confirm_password" placeholder="••••••••" class="w-full h-11 px-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] focus:border-primary transition-all outline-none font-bold text-[13px]">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t border-[#EEEEEE]">
                            <button type="submit" class="h-11 px-8 rounded-lg bg-primary text-white font-black text-[11px] uppercase tracking-widest shadow-lg hover:scale-105 transition-transform">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
