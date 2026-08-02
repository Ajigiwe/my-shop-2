<?php
/**
 * Account Sidebar (Avazonia) — shared by user/*.php pages
 * Expected: $user (array), $base = '../'
 */
$nav_items = [
    'dashboard.php' => ['icon' => '📊', 'label' => 'Dashboard'],
    'orders.php'    => ['icon' => '📦', 'label' => 'My Orders'],
    'wishlist.php'  => ['icon' => '💖', 'label' => 'Wishlist'],
    'profile.php'   => ['icon' => '⚙️', 'label' => 'Profile Settings'],
];
$current = basename($_SERVER['PHP_SELF']);
$user_name = $user['name'] ?? 'Member';
$user_email = $user['email'] ?? '';
?>
<aside class="account-sidebar">
    <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 20px; margin-bottom: 16px; border-bottom: 1px solid #f0f0f0;">
        <div style="width: 44px; height: 44px; background: var(--ink); color: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: var(--f-display); font-weight: 900; font-size: 16px; text-transform: uppercase; flex-shrink: 0;">
            <?php echo substr(htmlspecialchars($user_name), 0, 1); ?>
        </div>
        <div style="min-width: 0;">
            <p style="font-family: var(--f-display); font-weight: 800; font-size: 13px; color: var(--ink); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($user_name); ?></p>
            <p style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--mid-gray); margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($user_email); ?></p>
        </div>
    </div>

    <nav style="display: flex; flex-direction: column; gap: 4px;">
        <?php foreach ($nav_items as $file => $item): ?>
            <?php $active = ($current === $file); ?>
            <a href="<?php echo $file; ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; transition: 0.2s; <?php echo $active ? 'background: var(--off); color: var(--red); font-weight: 700;' : 'color: var(--mid-gray);'; ?>">
                <span style="font-size: 16px;"><?php echo $item['icon']; ?></span> <?php echo $item['label']; ?>
            </a>
        <?php endforeach; ?>

        <div style="margin: 12px 0; border-top: 1px solid #eee;"></div>

        <a href="<?php echo $base; ?>contact.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
            <span style="font-size: 16px;">🛟</span> Support
        </a>
        <a href="<?php echo $base; ?>track-order.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
            <span style="font-size: 16px;">🚚</span> Track order
        </a>
        <a href="<?php echo $base; ?>logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #f5222d; font-size: 13px;">
            <span style="font-size: 16px;">👋</span> Logout
        </a>
    </nav>
</aside>
