<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbaPayWayService
{
    private $merchantId;
    private $apiKey;
    private $purchaseEndpoint;
    private $returnUrl;
    private $cancelUrl;
    private $continueSuccessUrl;

    public function __construct()
    {
        $this->merchantId = config('services.aba.merchant_id');
        $this->apiKey = config('services.aba.api_key');
        $this->purchaseEndpoint = config('services.aba.purchase_endpoint');
        $this->returnUrl = config('services.aba.return_url');
        $this->cancelUrl = config('services.aba.cancel_url');
        $this->continueSuccessUrl = config('services.aba.continue_success_url');
    }

    /**
     * Create a purchase request to ABA PayWay
     * 
     * @param array $data Contains: amount, tran_id, payment_option, items (optional), user info
     * @return array Response from ABA API
     */
    public function createPurchase(array $data)
    {
        $reqTime = gmdate('YmdHis');
        $tranId = $data['tran_id'];
        $amount = number_format($data['amount'], 2, '.', '');
        $currency = $data['currency'] ?? 'USD';
        $paymentOption = $data['payment_option'] ?? 'abapay_khqr_deeplink';

        // Prepare items array
        $itemsArray = $data['items'] ?? [
            ['name' => 'Payment', 'quantity' => 1, 'price' => (float)$amount]
        ];
        $items = base64_encode(json_encode($itemsArray));

        // Customer info
        $firstName = $data['firstname'] ?? 'Customer';
        $lastName = $data['lastname'] ?? 'User';
        $email = $data['email'] ?? 'customer@example.com';
        $phone = $data['phone'] ?? '012345678';
        $shipping = '0.00';

        // Payment settings
        $purchaseType = 'purchase';
        $returnDeeplink = $data['return_deeplink'] ?? 'myapp://payment';
        $lifetime = $data['lifetime'] ?? 10; // QR code lifetime in minutes

        // Optional parameters
        $customFields = $data['custom_fields'] ?? '';
        $returnParams = $data['return_params'] ?? '';
        $payout = '';
        $additionalParams = '';
        $googlePayToken = '';
        $skipSuccessPage = '';

        // Build hash string (EXACT ORDER as per ABA documentation)
        $b4hash = $reqTime
            . $this->merchantId
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
            . $this->returnUrl
            . $this->cancelUrl
            . $this->continueSuccessUrl
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
        $hash = base64_encode(hash_hmac('sha512', $b4hash, $this->apiKey, true));

        // Prepare POST data
        $postData = [
            'req_time' => $reqTime,
            'merchant_id' => $this->merchantId,
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
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'continue_success_url' => $this->continueSuccessUrl,
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

        // Log the request
        Log::info('ABA PayWay Purchase Request', [
            'tran_id' => $tranId,
            'amount' => $amount,
            'payment_option' => $paymentOption,
        ]);

        try {
            // Send request using Laravel HTTP client
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->purchaseEndpoint, $postData);

            $responseData = $response->json();

            // Log the response
            Log::info('ABA PayWay Purchase Response', [
                'tran_id' => $tranId,
                'status_code' => $response->status(),
                'response' => $responseData,
            ]);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('ABA PayWay Purchase Error', [
                'tran_id' => $tranId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify callback hash from ABA
     * 
     * @param array $data Callback data from ABA
     * @return bool
     */
    public function verifyCallbackHash(array $data)
    {
        // Extract the hash sent by ABA
        $receivedHash = $data['hash'] ?? '';

        // Build the hash string from callback data
        // The order should match ABA's documentation for callback
        $b4hash = ($data['tran_id'] ?? '')
            . ($data['req_time'] ?? '')
            . ($data['amount'] ?? '')
            . ($data['status'] ?? '');

        // Generate expected hash
        $expectedHash = base64_encode(hash_hmac('sha512', $b4hash, $this->apiKey, true));

        return hash_equals($expectedHash, $receivedHash);
    }
}
