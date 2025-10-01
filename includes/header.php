<?php
/**
 * Header include
 * - Outputs <head> and opens <body>
 * - Computes $base to make asset and link paths work from root, /admin, /user, and /legal
 * - Exposes $site_name used across the UI (defaults to 'ASO Online Market')
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Commerce Shop - Your one-stop destination for quality products">
    <meta name="keywords" content="ecommerce, shop, products, online shopping">
    <?php
    // Define graceful degradation for navbar and other optional components
    define('ALLOW_DB_GRACEFUL_DEGRADATION', true);

    // Compute base path for assets (works from root, /admin, /user, /legal, etc.)
    $base = '';
    $current_path = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('/\/(admin|user|legal)\//', $current_path)) {
        $base = '../';
    }

    // Set site name
    $site_name = 'ASO Online Market';
    ?>
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Home'; ?> - <?php echo htmlspecialchars($site_name); ?></title>

    <!-- Bootstrap CSS -->
    <link href="<?php echo $base; ?>https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap JS (required for dropdowns and other components) -->
    <script src="<?php echo $base; ?>https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?php echo $base; ?>https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">

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
            background-color: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            z-index: 1000;
            padding: 8px 0;
        }

        .dropdown-menu-custom.show {
            display: block;
        }

        .dropdown-item-custom {
            display: block;
            padding: 10px 16px;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-item-custom:hover {
            background-color: var(--gray-100);
            color: var(--primary-color);
            text-decoration: none;
        }

        .dropdown-divider-custom {
            height: 1px;
            background-color: var(--gray-200);
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>


</body>
</html>
