<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AbaPayWayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $abaService;

    public function __construct(AbaPayWayService $abaService)
    {
        $this->abaService = $abaService;
    }

    /**
     * Create a new payment request
     * POST /api/payment/create
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'string|in:USD,KHR',
            'payment_option' => 'string|in:abapay_khqr,abapay_khqr_deeplink,abapay_deeplink',
            'user_id' => 'nullable|exists:users,id',
            'booking_id' => 'nullable|integer',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Generate unique transaction ID
            $tranId = 'TXN' . strtoupper(uniqid());

            // Create payment record in database
            $payment = Payment::create([
                'tran_id' => $tranId,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'USD',
                'payment_status' => Payment::STATUS_PENDING,
                'user_id' => $request->user_id,
                'booking_id' => $request->booking_id,
                'payment_option' => $request->payment_option ?? 'abapay_khqr_deeplink',
            ]);

            // Prepare data for ABA API
            $abaData = [
                'tran_id' => $tranId,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'USD',
                'payment_option' => $request->payment_option ?? 'abapay_khqr_deeplink',
                'firstname' => $request->firstname ?? 'Customer',
                'lastname' => $request->lastname ?? 'User',
                'email' => $request->email ?? 'customer@example.com',
                'phone' => $request->phone ?? '012345678',
                'return_deeplink' => $request->return_deeplink ?? 'myapp://payment',
                'lifetime' => $request->lifetime ?? 10,
            ];

            // Optional items array
            if ($request->has('items')) {
                $abaData['items'] = $request->items;
            }

            // Send purchase request to ABA PayWay
            $abaResponse = $this->abaService->createPurchase($abaData);

            if (!$abaResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment with ABA PayWay',
                    'error' => $abaResponse['error'] ?? 'Unknown error',
                ], 500);
            }

            // Extract payment details from ABA response
            $responseData = $abaResponse['data'];
            
            // Update payment record with ABA response data
            $payment->update([
                'qr_string' => $responseData['qr_string'] ?? null,
                'deeplink' => $responseData['abapay_deeplink'] ?? null,
            ]);

            // Return response to Flutter app
            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => [
                    'tran_id' => $tranId,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_status' => $payment->payment_status,
                    'qr_string' => $responseData['qr_string'] ?? null,
                    'abapay_deeplink' => $responseData['abapay_deeplink'] ?? null,
                    'payment_option' => $payment->payment_option,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status
     * GET /api/payment/status?tran_id=xxx
     */
    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tran_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = Payment::where('tran_id', $request->tran_id)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tran_id' => $payment->tran_id,
                    'payment_status' => $payment->payment_status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'paid_at' => $payment->paid_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'tran_id' => $request->tran_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status',
            ], 500);
        }
    }

    /**
     * Handle ABA PayWay callback (return URL)
     * POST /api/payway/return
     * This endpoint MUST be publicly accessible
     */
    public function paywayReturn(Request $request)
    {
        // Log the full callback payload
        Log::info('ABA PayWay Callback Received', [
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $tranId = $request->input('tran_id');
            $status = $request->input('status'); // 0 = success, other = failed
            $amount = $request->input('amount');
            $hash = $request->input('hash');

            // Find payment record
            $payment = Payment::where('tran_id', $tranId)->first();

            if (!$payment) {
                Log::error('Payment not found in callback', ['tran_id' => $tranId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // Verify hash (optional but recommended)
            // Uncomment if you want to verify the callback hash
            // if (!$this->abaService->verifyCallbackHash($request->all())) {
            //     Log::error('Invalid callback hash', ['tran_id' => $tranId]);
            //     return response()->json(['success' => false, 'message' => 'Invalid hash'], 400);
            // }

            // Update payment status based on callback
            if ($status == '0') {
                // Payment successful
                $payment->markAsPaid($request->all());
                
                Log::info('Payment marked as paid', [
                    'tran_id' => $tranId,
                    'amount' => $amount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed successfully',
                ]);
            } else {
                // Payment failed
                $payment->markAsFailed($request->all());
                
                Log::warning('Payment failed', [
                    'tran_id' => $tranId,
                    'status' => $status,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PayWay callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Callback processing failed',
            ], 500);
        }
    }

    /**
     * Handle ABA PayWay cancel callback
     * POST /api/payway/cancel
     */
    public function paywayCancel(Request $request)
    {
        Log::info('ABA PayWay Cancel Callback', ['data' => $request->all()]);

        $tranId = $request->input('tran_id');
        
        if ($tranId) {
            $payment = Payment::where('tran_id', $tranId)->first();
            if ($payment && $payment->isPending()) {
                $payment->update(['payment_status' => Payment::STATUS_EXPIRED]);
            }
        }

        return response()->json(['message' => 'Payment cancelled']);
    }
}
