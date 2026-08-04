<?php
/**
 * Paystack Configuration
 * Handles Paystack API integration for payment processing
 */

// Include environment loader and Composer autoloader
require_once __DIR__ . '/env_loader.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php') && file_exists(__DIR__ . '/../vendor/composer/autoload_real.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Initialize Paystack with environment variables
$paystack_public_key = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';
$paystack_secret_key = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';

// Validate that keys are set
if (empty($paystack_public_key) || empty($paystack_secret_key)) {
    error_log('Paystack keys not configured in .env file');
}

// Initialize Paystack if library class exists and keys are present
$paystack = null;
if (!empty($paystack_secret_key) && class_exists('\Yabacon\Paystack')) {
    try {
        $paystack = new \Yabacon\Paystack($paystack_secret_key);
    } catch (Throwable $e) {
        error_log('Paystack init error: ' . $e->getMessage());
    }
}

/**
 * Initialize Paystack payment using direct cURL
 * @param array $data Payment data
 * @return object Response from Paystack
 */
function initializePaystackPayment($data) {
    global $paystack_secret_key;
    
    $url = 'https://api.paystack.co/transaction/initialize';
    
    $fields = [
        'amount' => $data['amount'],
        'email' => $data['email'],
        'reference' => $data['reference'],
        'callback_url' => $data['callback_url'] ?? '',
        'metadata' => $data['metadata'] ?? []
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystack_secret_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('cURL error: ' . $error);
        throw new Exception('Network error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        error_log('HTTP error: ' . $httpCode . ' - ' . $response);
        throw new Exception('API error: HTTP ' . $httpCode);
    }
    
    $result = json_decode($response);
    
    if (!$result) {
        error_log('Invalid JSON response: ' . $response);
        throw new Exception('Invalid response from Paystack API');
    }
    
    return $result;
}

/**
 * Verify Paystack payment using direct cURL
 * @param string $reference Payment reference
 * @return object Payment verification response
 */
function verifyPaystackPayment($reference) {
    global $paystack_secret_key;
    
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystack_secret_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('cURL error: ' . $error);
        throw new Exception('Network error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        error_log('HTTP error: ' . $httpCode . ' - ' . $response);
        throw new Exception('API error: HTTP ' . $httpCode);
    }
    
    $result = json_decode($response);
    
    if (!$result) {
        error_log('Invalid JSON response: ' . $response);
        throw new Exception('Invalid response from Paystack API');
    }
    
    return $result;
}

/**
 * Get Paystack public key for frontend
 * @return string Public key
 */
function getPaystackPublicKey() {
    global $paystack_public_key;
    return $paystack_public_key;
}

/**
 * Generate unique transaction reference
 * @return string Unique reference
 */
function generateTransactionReference() {
    return 'TXN_' . time() . '_' . uniqid();
}

/**
 * Format amount for Paystack (convert to kobo)
 * @param float $amount Amount in main currency
 * @return int Amount in kobo
 */
function formatAmountForPaystack($amount) {
    // Convert to kobo (multiply by 100)
    return (int)($amount * 100);
}

/**
 * Format amount from Paystack (convert from kobo)
 * @param int $amount Amount in kobo
 * @return float Amount in main currency
 */
function formatAmountFromPaystack($amount) {
    // Convert from kobo (divide by 100)
    return $amount / 100;
}
?>
