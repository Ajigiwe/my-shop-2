<?php
/**
 * Alternative Database Connection
 * Using mysqli for compatibility (since queries are written for mysqli)
 */

function getDbConnection() {
    // Use mysqli (consistent with query code)
    $conn = mysqli_connect('localhost', 'root', '', 'ecommerce_db');

    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        return null;
    }

    // Set charset
    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}
?>
