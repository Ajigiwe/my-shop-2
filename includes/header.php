<?php
/**
 * Header include
 * - Outputs <head> and opens <body>
 * - computes $base to make asset and link paths work from root, /admin, /user, and /legal
 * - Exposes $site_name used across the UI (defaults to 'ASO Online Market')
 */

// Start session for header/navbar functionality
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for navbar display)
$user_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Commerce Shop - Your one-stop destination for quality products">
    <meta name="keywords" content="ecommerce, shop, products, online shopping">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Home'; ?> - <?php echo htmlspecialchars($site_name); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap JS (with defer to prevent render blocking) -->
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    
    <!-- Mobile Scaling JavaScript -->
    <script>
    // Handle mobile scaling issues
    document.addEventListener('DOMContentLoaded', function() {
        // Fix any scaling issues with interactive elements
        if (window.innerWidth <= 768) {
            // Ensure all form elements work properly
            const formElements = document.querySelectorAll('input, textarea, select, button');
            formElements.forEach(element => {
                element.style.transform = 'scale(1)';
            });
            
            // Fix any Bootstrap components that might have scaling issues
            const bootstrapComponents = document.querySelectorAll('.modal, .dropdown-menu, .tooltip, .popover, .toast');
            bootstrapComponents.forEach(component => {
                component.style.transform = 'scale(1)';
            });
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                // Re-apply fixes on resize
                const formElements = document.querySelectorAll('input, textarea, select, button');
                formElements.forEach(element => {
                    element.style.transform = 'scale(1)';
                });
            }
        });
    });
    </script>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/search.css">
    
    <!-- Simple modal initialization -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Log to verify script is running
        console.log('Modal initialization script loaded');
        
        // Initialize tooltips and popovers
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Log modal elements for debugging
        console.log('Modal elements:', {
            modal: document.getElementById('imageModal'),
            buttons: document.querySelectorAll('[data-bs-toggle="modal"]')
        });
    });
    </script>


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
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
