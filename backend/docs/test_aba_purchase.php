<?php

/**
 * Simple ABA PayWay Purchase API Test Script
 * 
 * This script tests the purchase endpoint with your new credentials.
 * Run: php test_aba_purchase.php
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found at $path\n");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        if (strpos($line, '=') === false) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Remove quotes if present
        $value = trim($value, '"\'');
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Configuration from .env
$merchantId = $_ENV['ABA_MERCHANT_ID'] ?? '';
$apiKey = $_ENV['ABA_API_KEY'] ?? '';
$purchaseEndpoint = $_ENV['ABA_PURCHASE_ENDPOINT'] ?? '';

if (empty($merchantId) || empty($apiKey) || empty($purchaseEndpoint)) {
    die("Error: Missing ABA credentials in .env file\n");
}

echo "=== ABA PayWay Purchase API Test ===\n";
echo "Merchant ID: $merchantId\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Endpoint: $purchaseEndpoint\n\n";

// Payment parameters
$reqTime = gmdate('YmdHis');
$tranId = strtoupper(uniqid('TXN')); // Generate unique transaction ID without underscore
$amount = '5.00'; // Test amount in USD
$currency = 'USD';

// Items - must be base64-encoded JSON array according to ABA specs
$itemsArray = [
    ["name" => "Test Product 1", "quantity" => 1, "price" => 3.00],
    ["name" => "Test Product 2", "quantity" => 1, "price" => 2.00]
];
$items = base64_encode(json_encode($itemsArray));

$shipping = '0.00';
$firstName = 'John';
$lastName = 'Doe';
$email = 'test@example.com';
$phone = '012345678';
$purchaseType = 'purchase';
$paymentOption = 'abapay_khqr_deeplink'; // For deeplink
$returnUrl = 'https://elegant-many-oyster.ngrok-free.app/api/payments/aba/return';
$cancelUrl = 'https://elegant-many-oyster.ngrok-free.app/api/payments/aba/cancel';
$continueSuccessUrl = 'https://elegant-many-oyster.ngrok-free.app/api/payments/aba/success';
$returnDeeplink = 'myapp://payment';
$customFields = '';
$returnParams = '';
$payout = '';
$lifetime = 10; // QR code lifetime in minutes
$additionalParams = '';
$googlePayToken = '';
$skipSuccessPage = '';

// Build hash string (EXACT ORDER as per ABA documentation)
// Note: Hash string should be concatenated directly WITHOUT removing whitespace
$b4hash = $reqTime
    . $merchantId
    . $tranId
    . $amount
    . $items
    . $shipping
    . $firstName
    . $lastName
    . $email
    . $phone
    . $purchaseType
    . $paymentOption
    . $returnUrl
    . $cancelUrl
    . $continueSuccessUrl
    . $returnDeeplink
    . $currency
    . $customFields
    . $returnParams
    . $payout
    . $lifetime
    . $additionalParams
    . $googlePayToken
    . $skipSuccessPage;

// Generate hash - Base64 HMAC SHA512
$hash = base64_encode(hash_hmac('sha512', $b4hash, $apiKey, true));

echo "Transaction Details:\n";
echo "  Transaction ID: $tranId\n";
echo "  Amount: $amount $currency\n";
echo "  Payment Option: $paymentOption\n";
echo "  Hash Input Length: " . strlen($b4hash) . " chars\n";
echo "  Hash: " . substr($hash, 0, 20) . "...\n\n";

// Debug: Show hash components
echo "Hash Components (for verification):\n";
echo "  req_time: '$reqTime'\n";
echo "  merchant_id: '$merchantId'\n";
echo "  tran_id: '$tranId'\n";
echo "  amount: '$amount'\n";
echo "  items: '$items'\n";
echo "  shipping: '$shipping'\n";
echo "  firstname: '$firstName'\n";
echo "  lastname: '$lastName'\n";
echo "  email: '$email'\n";
echo "  phone: '$phone'\n";
echo "  type: '$purchaseType'\n";
echo "  payment_option: '$paymentOption'\n";
echo "  return_url: '$returnUrl'\n";
echo "  cancel_url: '$cancelUrl'\n";
echo "  continue_success_url: '$continueSuccessUrl'\n";
echo "  return_deeplink: '$returnDeeplink'\n";
echo "  currency: '$currency'\n";
echo "  custom_fields: '$customFields'\n";
echo "  return_params: '$returnParams'\n";
echo "  payout: '$payout'\n";
echo "  lifetime: '$lifetime'\n";
echo "  additional_params: '$additionalParams'\n";
echo "  google_pay_token: '$googlePayToken'\n";
echo "  skip_success_page: '$skipSuccessPage'\n\n";

// Prepare POST data
$postData = [
    'req_time' => $reqTime,
    'merchant_id' => $merchantId,
    'tran_id' => $tranId,
    'amount' => $amount,
    'items' => $items,
    'shipping' => $shipping,
    'firstname' => $firstName,
    'lastname' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'type' => $purchaseType,
    'payment_option' => $paymentOption,
    'return_url' => $returnUrl,
    'cancel_url' => $cancelUrl,
    'continue_success_url' => $continueSuccessUrl,
    'return_deeplink' => $returnDeeplink,
    'currency' => $currency,
    'custom_fields' => $customFields,
    'return_params' => $returnParams,
    'payout' => $payout,
    'lifetime' => $lifetime,
    'additional_params' => $additionalParams,
    'google_pay_token' => $googlePayToken,
    'skip_success_page' => $skipSuccessPage,
    'hash' => $hash,
];

// Initialize cURL
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $purchaseEndpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
    ],
]);

echo "Sending request to ABA PayWay...\n\n";

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

// Display results
echo "=== Response ===\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response received successfully!\n\n";
    
    // Try to parse as JSON
    $json = json_decode($response, true);
    
    if ($json) {
        echo "JSON Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        
        // Extract useful information
        if (isset($json['abapay_deeplink'])) {
            echo "✓ ABA Pay Deeplink: " . $json['abapay_deeplink'] . "\n";
        }
        if (isset($json['qr_string'])) {
            echo "✓ QR Code: " . substr($json['qr_string'], 0, 50) . "...\n";
        }
        if (isset($json['status'])) {
            $statusCode = $json['status']['code'] ?? 'N/A';
            $statusMsg = $json['status']['message'] ?? 'N/A';
            echo "✓ Status: [$statusCode] $statusMsg\n";
        }
    } else {
        // HTML or other response
        echo "Raw Response (first 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
        
        if (strlen($response) > 500) {
            echo "\n... (truncated, total length: " . strlen($response) . " bytes)\n";
        }
    }
}

echo "\n=== Test Complete ===\n";