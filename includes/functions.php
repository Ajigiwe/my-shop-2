<?php
/**
 * Helper functions
 */

/**
 * Format price with currency symbol
 */
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
