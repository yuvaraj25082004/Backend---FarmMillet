<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Validator;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

class SHGController
{
    /**
     * View all farmer supplies
     */
    public static function viewSupplies(): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    fs.*,
                    fp.name as farmer_name,
                    fp.farm_location,
                    u.email as farmer_email,
                    u.mobile as farmer_mobile
                FROM farmer_supplies fs
                INNER JOIN farmer_profiles fp ON fs.farmer_id = fp.user_id
                INNER JOIN users u ON fs.farmer_id = u.id
                ORDER BY fs.created_at DESC
            ");

            $stmt->execute();
            $supplies = $stmt->fetchAll();

            Response::success('Farmer supplies retrieved successfully', $supplies);

        } catch (\Exception $e) {
            error_log("View supplies failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve supplies.');
        }
    }

    /**
     * Accept farmer supply
     */
    public static function acceptSupply(int $supplyId): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            // Check if supply exists and is pending
            $stmt = $db->prepare("SELECT * FROM farmer_supplies WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $supplyId]);
            $supply = $stmt->fetch();

            if (!$supply) {
                Response::notFound('Supply not found or already processed');
            }

            // Begin transaction
            $db->beginTransaction();

            // Update supply status and assign logistics
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $collectionBy = $data['collection_by'] ?? 'Logistics Partner';
            $collectionDate = $data['collection_date'] ?? date('Y-m-d', strtotime('+2 days'));
            $qualityGrade = isset($data['quality_grade']) ? strtoupper($data['quality_grade']) : 'A';

            $stmt = $db->prepare("
                UPDATE farmer_supplies 
                SET status = 'accepted', 
                    shg_id = :shg_id, 
                    collection_by = :collection_by,
                    collection_date = :collection_date,
                    quality_grade = :quality_grade,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'shg_id' => $user->user_id,
                'id' => $supplyId,
                'collection_by' => $collectionBy,
                'collection_date' => $collectionDate,
                'quality_grade' => $qualityGrade
            ]);

            // Generate traceability record
            $traceabilityId = self::generateTraceabilityId();
            
            // Get farmer name
            $stmt = $db->prepare("SELECT name FROM farmer_profiles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $supply['farmer_id']]);
            $farmer = $stmt->fetch();

            $stmt = $db->prepare("
                INSERT INTO traceability_records (
                    traceability_id, supply_id, farmer_id, millet_type,
                    farmer_name, harvest_date, packaging_date, quality_grade
                ) VALUES (
                    :traceability_id, :supply_id, :farmer_id, :millet_type,
                    :farmer_name, :harvest_date, :packaging_date, :quality_grade
                )
            ");

            $stmt->execute([
                'traceability_id' => $traceabilityId,
                'supply_id' => $supplyId,
                'farmer_id' => $supply['farmer_id'],
                'millet_type' => $supply['millet_type'],
                'farmer_name' => $farmer['name'],
                'harvest_date' => $supply['harvest_date'],
                'packaging_date' => $supply['packaging_date'],
                'quality_grade' => $qualityGrade
            ]);

            $db->commit();

            Response::success('Supply accepted and traceability record created', [
                'supply_id' => $supplyId,
                'traceability_id' => $traceabilityId
            ]);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Accept supply failed: " . $e->getMessage());
            Response::serverError('Failed to accept supply.');
        }
    }

    /**
     * Get SHG dashboard stats
     */
    public static function getDashboardStats(): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            // Total Revenue
            $stmt = $db->prepare("SELECT SUM(total_amount) as total_revenue FROM orders WHERE shg_id = :shg_id AND status = 'delivered'");
            $stmt->execute(['shg_id' => $user->user_id]);
            $revenue = $stmt->fetch();

            // Total Orders count
            $stmt = $db->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE shg_id = :shg_id");
            $stmt->execute(['shg_id' => $user->user_id]);
            $ordersCount = $stmt->fetch();

            // Pending Orders count
            $stmt = $db->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE shg_id = :shg_id AND status IN ('order_placed', 'confirmed')");
            $stmt->execute(['shg_id' => $user->user_id]);
            $pendingOrders = $stmt->fetch();

            // Available Products count
            $stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products WHERE shg_id = :shg_id AND is_active = 1");
            $stmt->execute(['shg_id' => $user->user_id]);
            $productsCount = $stmt->fetch();

            // Recent Orders
            $stmt = $db->prepare("
                SELECT o.*, c.name as consumer_name 
                FROM orders o 
                JOIN consumer_profiles c ON o.consumer_id = c.user_id 
                WHERE o.shg_id = :shg_id 
                ORDER BY o.created_at DESC LIMIT 5
            ");
            $stmt->execute(['shg_id' => $user->user_id]);
            $recentOrders = $stmt->fetchAll();

            Response::success('Dashboard stats retrieved successfully', [
                'total_revenue' => (float)($revenue['total_revenue'] ?? 0),
                'total_orders' => (int)$ordersCount['total_orders'],
                'pending_orders' => (int)$pendingOrders['pending_orders'],
                'total_products' => (int)$productsCount['total_products'],
                'recent_orders' => $recentOrders
            ]);

        } catch (\Exception $e) {
            error_log("SHG dashboard stats failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve dashboard stats.');
        }
    }

    /**
     * Complete farmer supply
     */
    public static function completeSupply(int $supplyId): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            // Check if supply exists and is accepted
            $stmt = $db->prepare("
                SELECT * FROM farmer_supplies 
                WHERE id = :id AND status = 'accepted' AND shg_id = :shg_id
            ");
            $stmt->execute([
                'id' => $supplyId,
                'shg_id' => $user->user_id
            ]);
            $supply = $stmt->fetch();

            if (!$supply) {
                Response::notFound('Supply not found or not accepted by you');
            }

            // Update supply status
            $stmt = $db->prepare("
                UPDATE farmer_supplies 
                SET status = 'completed', updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $supplyId]);

            Response::success('Supply marked as completed');

        } catch (\Exception $e) {
            error_log("Complete supply failed: " . $e->getMessage());
            Response::serverError('Failed to complete supply.');
        }
    }

    /**
     * Record payment to farmer
     */
    public static function recordPayment(): void
    {
        $user = AuthMiddleware::shg();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requiredFields = ['farmer_id', 'amount', 'payment_method','supply_id'];
        $errors = Validator::required($data, $requiredFields);

        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'Amount must be a positive number';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO payments (
                    farmer_id, payment_type, amount, status, payment_method,supply_id
                ) VALUES (
                    :farmer_id, 'farmer_payment', :amount, 'success', :payment_method,:supply_id
                )
            ");

            $stmt->execute([
                'farmer_id' => $data['farmer_id'],
                'amount' => $data['amount'],
                'payment_method' => Validator::sanitize($data['payment_method']),
                'supply_id' => $data['supply_id'] ?? null
            ]);
            
            $paymentId = $db->lastInsertId();

            // If supply_id is provided, mark supply as paid
            if (isset($data['supply_id'])) {
                $stmt = $db->prepare("
                    UPDATE farmer_supplies 
                    SET payment_status = 'paid', updated_at = NOW()
                    WHERE id = :id AND farmer_id = :farmer_id
                ");
                $stmt->execute([
                    'id' => $data['supply_id'],
                    'farmer_id' => $data['farmer_id'],
                    
                ]);
            }

            $db->commit();

            Response::success('Payment recorded successfully', [
                'payment_id' => $paymentId
            ], 201);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Record payment failed: " . $e->getMessage());
            Response::serverError('Failed to record payment: ' . $e->getMessage());
        }
    }


    /**
     * Create product listing
     */
    public static function createProduct(): void
    {
        $user = AuthMiddleware::shg();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requiredFields = ['millet_type', 'quantity_kg', 'price_per_kg', 'quality_grade', 'packaging_date'];
        $errors = Validator::required($data, $requiredFields);

        if (isset($data['quality_grade']) && !Validator::qualityGrade($data['quality_grade'])) {
            $errors['quality_grade'] = 'Quality grade must be A, B, or C';
        }

        if (isset($data['quantity_kg']) && (!is_numeric($data['quantity_kg']) || $data['quantity_kg'] <= 0)) {
            $errors['quantity_kg'] = 'Quantity must be a positive number';
        }

        if (isset($data['price_per_kg']) && (!is_numeric($data['price_per_kg']) || $data['price_per_kg'] <= 0)) {
            $errors['price_per_kg'] = 'Price must be a positive number';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                INSERT INTO products (
                    shg_id, supply_id, millet_type, quantity_kg, price_per_kg,
                    quality_grade, packaging_date, source_farmer_name, description
                ) VALUES (
                    :shg_id, :supply_id, :millet_type, :quantity_kg, :price_per_kg,
                    :quality_grade, :packaging_date, :source_farmer_name, :description
                )
            ");

            $stmt->execute([
                'shg_id' => $user->user_id,
                'supply_id' => $data['supply_id'] ?? null,
                'millet_type' => Validator::sanitize($data['millet_type']),
                'quantity_kg' => $data['quantity_kg'],
                'price_per_kg' => $data['price_per_kg'],
                'quality_grade' => strtoupper($data['quality_grade']),
                'packaging_date' => $data['packaging_date'],
                'source_farmer_name' => isset($data['source_farmer_name']) ? Validator::sanitize($data['source_farmer_name']) : null,
                'description' => isset($data['description']) ? Validator::sanitize($data['description']) : null
            ]);

            $productId = $db->lastInsertId();

            // Update supply status if applicable
            if (isset($data['supply_id'])) {
                $stmt = $db->prepare("UPDATE farmer_supplies SET status = 'listed', updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $data['supply_id']]);
            }

            Response::success('Product created successfully', [
                'product_id' => $productId
            ], 201);

        } catch (\Exception $e) {
            error_log("Create product failed: " . $e->getMessage());
            Response::serverError('Failed to create product.');
        }
    }

    /**
     * Get SHG products
     */
    public static function getProducts(): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    t.traceability_id
                FROM products p
                LEFT JOIN traceability_records t ON p.supply_id = t.supply_id
                WHERE p.shg_id = :shg_id
                ORDER BY p.created_at DESC
            ");

            $stmt->execute(['shg_id' => $user->user_id]);
            $products = $stmt->fetchAll();

            Response::success('Products retrieved successfully', $products);

        } catch (\Exception $e) {
            error_log("Get products failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve products.');
        }
    }

    /**
     * Update order status
     */
    public static function updateOrderStatus(int $orderId): void
    {
        $user = AuthMiddleware::shg();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = Validator::required($data, ['status']);

        if (isset($data['status']) && !Validator::orderStatus($data['status'])) {
            $errors['status'] = 'Invalid order status';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            // Check if order belongs to this SHG
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND shg_id = :shg_id");
            $stmt->execute([
                'id' => $orderId,
                'shg_id' => $user->user_id
            ]);
            $order = $stmt->fetch();

            if (!$order) {
                Response::notFound('Order not found');
            }

            // Begin transaction
            $db->beginTransaction();

            // Update order status
            $stmt = $db->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                'status' => $data['status'],
                'id' => $orderId
            ]);

            // Add to status history
            $stmt = $db->prepare("
                INSERT INTO order_status_history (order_id, status, notes)
                VALUES (:order_id, :status, :notes)
            ");
            $stmt->execute([
                'order_id' => $orderId,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null
            ]);

            $db->commit();

            Response::success('Order status updated successfully');
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Update order status failed: " . $e->getMessage());
            Response::serverError('Failed to update order status.');
        }
    }

    /**
     * Get SHG orders
     */
    public static function getOrders(): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    o.*,
                    c.name as consumer_name,
                    c.city as consumer_city,
                    c.street as consumer_address,
                    u.mobile as consumer_mobile
                FROM orders o
                INNER JOIN consumer_profiles c ON o.consumer_id = c.user_id
                INNER JOIN users u ON o.consumer_id = u.id
                WHERE o.shg_id = :shg_id
                ORDER BY o.created_at DESC
            ");

            $stmt->execute(['shg_id' => $user->user_id]);
            $orders = $stmt->fetchAll();

            // Get items for each order
            foreach ($orders as &$order) {
                $stmt = $db->prepare("
                    SELECT 
                        oi.id,
                        oi.order_id,
                        oi.product_id,
                        oi.quantity_kg,
                        oi.price_per_kg,
                        oi.total_price,
                        p.millet_type,
                        p.millet_type as product_name,
                        p.quality_grade
                    FROM order_items oi
                    INNER JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = :order_id
                ");
                $stmt->execute(['order_id' => $order['id']]);
                $order['items'] = $stmt->fetchAll();
            }

            Response::success('Orders retrieved successfully', $orders);

        } catch (\Exception $e) {
            error_log("Get orders failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve orders.');
        }
    }

    /**
     * Generate unique traceability ID
     */
    private static function generateTraceabilityId(): string
    {
        $year = date('Y');
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM traceability_records 
            WHERE traceability_id LIKE :pattern
        ");
        $stmt->execute(['pattern' => "TR-{$year}-%"]);
        $result = $stmt->fetch();
        
        $sequence = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "TR-{$year}-{$sequence}";
    }

    /**
     * Get SHG payment history (from consumers)
     */
    public static function getPaymentHistory(): void
    {
        $user = AuthMiddleware::shg();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    o.order_number,
                    c.name as consumer_name
                FROM payments p
                JOIN orders o ON p.order_id = o.id
                JOIN consumer_profiles c ON o.consumer_id = c.user_id
                WHERE o.shg_id = :shg_id AND p.payment_type = 'consumer_order'
                ORDER BY p.created_at DESC
            ");

            $stmt->execute(['shg_id' => $user->user_id]);
            $payments = $stmt->fetchAll();

            Response::success('Payment history retrieved successfully', $payments);

        } catch (\Exception $e) {
            error_log("SHG payment history failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve payment history.');
        }
    }
}
