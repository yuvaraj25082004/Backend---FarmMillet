<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;

class RazorpayController
{
    /**
     * Create Razorpay order
     */
    public static function createOrder(): void
    {
        $user = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        $requiredFields = ['amount', 'order_id'];
        $errors = Validator::required($data, $requiredFields);

        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'Amount must be a positive number';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            // Verify order exists and belongs to user
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND consumer_id = :consumer_id");
            $stmt->execute([
                'id' => $data['order_id'],
                'consumer_id' => $user->user_id
            ]);
            $order = $stmt->fetch();

            if (!$order) {
                Response::notFound('Order not found');
            }

            // Razorpay API credentials
            $keyId = Database::getEnv('RAZORPAY_KEY_ID');
            $keySecret = Database::getEnv('RAZORPAY_KEY_SECRET');

            // Demo Mode Check
            $isDemoMode = (strpos($keyId, 'rzp_test_your_key_id') !== false || empty($keyId));

            if ($isDemoMode) {
                $razorpayOrder = [
                    'id' => 'order_demo_' . bin2hex(random_bytes(4)),
                    'amount' => $data['amount'] * 100,
                    'currency' => 'INR'
                ];
                $keyId = 'rzp_test_demo_key';
            } else {
                // Create Razorpay order
                $razorpayOrderData = [
                    'amount' => $data['amount'] * 100, // Amount in paise
                    'currency' => 'INR',
                    'receipt' => $order['order_number'],
                    'notes' => [
                        'order_id' => $order['id'],
                        'consumer_id' => $user->user_id
                    ]
                ];

                $ch = curl_init('https://api.razorpay.com/v1/orders');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($razorpayOrderData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($keyId . ':' . $keySecret)
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    error_log("Razorpay order creation failed: " . $response);
                    Response::serverError('Failed to create payment order');
                }

                $razorpayOrder = json_decode($response, true);
            }

            // Store payment record
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, payment_type, razorpay_order_id, amount, status
                ) VALUES (
                    :order_id, 'consumer_order', :razorpay_order_id, :amount, 'pending'
                )
            ");

            $stmt->execute([
                'order_id' => $order['id'],
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $data['amount']
            ]);

            Response::success('Razorpay order created successfully', [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $data['amount'],
                'currency' => 'INR',
                'key_id' => $keyId
            ]);

        } catch (\Exception $e) {
            error_log("Create Razorpay order failed: " . $e->getMessage());
            Response::serverError('Failed to create payment order.');
        }
    }

    /**
     * Verify Razorpay payment signature
     */
    public static function verifyPayment(): void
    {
        $user = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        $requiredFields = ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'];
        $errors = Validator::required($data, $requiredFields);

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            // Get payment record
            $stmt = $db->prepare("
                SELECT * FROM payments 
                WHERE razorpay_order_id = :razorpay_order_id
            ");
            $stmt->execute(['razorpay_order_id' => $data['razorpay_order_id']]);
            $payment = $stmt->fetch();

            if (!$payment) {
                Response::notFound('Payment record not found');
            }

            // Verify signature
            $keySecret = Database::getEnv('RAZORPAY_KEY_SECRET');
            $isDemoMode = (strpos($keySecret, 'your_key_secret') !== false || empty($keySecret));

            $isSignatureValid = false;
            if ($isDemoMode && $data['razorpay_signature'] === 'mock_signature') {
                $isSignatureValid = true;
            } else {
                $generatedSignature = hash_hmac(
                    'sha256',
                    $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
                    $keySecret
                );
                $isSignatureValid = ($generatedSignature === $data['razorpay_signature']);
            }

            if (!$isSignatureValid) {
                // Update payment status to failed
                $stmt = $db->prepare("
                    UPDATE payments 
                    SET status = 'failed', updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $payment['id']]);

                Response::error('Payment verification failed. Invalid signature.', null, 400);
            }

            // Begin transaction
            $db->beginTransaction();

            // Update payment record
            $stmt = $db->prepare("
                UPDATE payments 
                SET razorpay_payment_id = :razorpay_payment_id,
                    razorpay_signature = :razorpay_signature,
                    status = 'success',
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature'],
                'id' => $payment['id']
            ]);

            // Update order status to confirmed
            if ($payment['order_id']) {
                $stmt = $db->prepare("
                    UPDATE orders 
                    SET status = 'confirmed', updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $payment['order_id']]);

                // Add to status history
                $stmt = $db->prepare("
                    INSERT INTO order_status_history (order_id, status, notes)
                    VALUES (:order_id, 'confirmed', 'Payment received and verified')
                ");
                $stmt->execute(['order_id' => $payment['order_id']]);
            }

            $db->commit();

            Response::success('Payment verified successfully', [
                'payment_id' => $payment['id'],
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Verify payment failed: " . $e->getMessage());
            Response::serverError('Payment verification failed.');
        }
    }

    /**
     * Get payment details
     */
    public static function getPaymentDetails(int $paymentId): void
    {
        $user = AuthMiddleware::authenticate();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    o.order_number,
                    o.total_amount as order_amount
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                WHERE p.id = :id
            ");

            $stmt->execute(['id' => $paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                Response::notFound('Payment not found');
            }

            Response::success('Payment details retrieved', [
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            error_log("Get payment details failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve payment details.');
        }
    }
}
