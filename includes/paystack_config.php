<?php
/**
 * Payment Gateway Configuration
 * - Paystack and other payment gateway settings
 */

// Paystack Configuration - Using Environment Variables
define('PAYSTACK_PUBLIC_KEY', $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '');
define('PAYSTACK_SECRET_KEY', $_ENV['PAYSTACK_SECRET_KEY'] ?? '');
define('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co/transaction/initialize');
define('PAYSTACK_VERIFY_URL', 'https://api.paystack.co/transaction/verify/');

// PayPal Configuration (for future implementation)
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_CLIENT_SECRET', '');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'

// Payment settings - Correctly encoded callback URL
define('CURRENCY', 'GHS'); // Ghana Cedi for Paystack
define('PAYMENT_CALLBACK_URL', 'http://127.0.0.1/My%20Shop%202/verify_payment.php'); // Correctly encoded space
define('PAYMENT_RETURN_URL', 'https://yourdomain.com/verify_payment.php'); // Replace with your actual domain

/**
 * Initialize Paystack payment
 *
 * @param array $payment_data Payment data including amount, email, order_id, etc.
 * @return array|bool Returns payment initialization response or false on error
 */
function initializePaystackPayment($payment_data) {
    $secret = PAYSTACK_SECRET_KEY;
    $url = "https://api.paystack.co/transaction/initialize";

    $payload = json_encode($payment_data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secret",
        "Content-Type: application/json",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Paystack init curl error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $json = json_decode($resp, true);
    error_log('Paystack init response: ' . print_r($json, true));
    return $json;
}

/**
 * Verify Paystack payment
 *
 * @param string $reference Payment reference
 * @return array|bool Returns payment verification data or false on error
 */
function verifyPaystackPayment($reference) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYSTACK_VERIFY_URL . $reference);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ]);

    // Disable SSL verification for development
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        return json_decode($response, true);
    }

    error_log('Paystack payment verification failed: ' . $response);
    return false;
}

/**
 * Format amount for Paystack (convert to kobo)
 *
 * @param float $amount Amount in naira
 * @return int Amount in kobo
 */
function formatAmountForPaystack($amount) {
    return (int)($amount * 100); // Convert to kobo
}

/**
 * Format amount from Paystack (convert from kobo to naira)
 *
 * @param int $amount Amount in kobo
 * @return float Amount in naira
 */
function formatAmountFromPaystack($amount) {
    return $amount / 100; // Convert from kobo to naira
}
?>
