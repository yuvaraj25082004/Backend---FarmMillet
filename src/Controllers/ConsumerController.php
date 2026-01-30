<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Validator;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

class ConsumerController
{
    /**
     * View all available products
     */
    public static function viewProducts(): void
    {
        $user = AuthMiddleware::consumer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    s.organization_name as shg_name,
                    s.city as shg_city,
                    t.traceability_id
                FROM products p
                INNER JOIN shg_profiles s ON p.shg_id = s.user_id
                LEFT JOIN traceability_records t ON p.supply_id = t.supply_id
                WHERE p.is_active = TRUE AND p.quantity_kg > 0
                ORDER BY p.created_at DESC
            ");

            $stmt->execute();
            $products = $stmt->fetchAll();

            Response::success('Products retrieved successfully', $products);

        } catch (\Exception $e) {
            error_log("View products failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve products.');
        }
    }

    /**
     * View a single product
     */
    public static function getProduct(int $id): void
    {
        $user = AuthMiddleware::consumer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    s.organization_name as shg_name,
                    s.city as shg_city,
                    t.traceability_id
                FROM products p
                INNER JOIN shg_profiles s ON p.shg_id = s.user_id
                LEFT JOIN traceability_records t ON p.supply_id = t.supply_id
                WHERE p.id = :id AND p.is_active = TRUE
            ");

            $stmt->execute(['id' => $id]);
            $product = $stmt->fetch();

            if (!$product) {
                Response::notFound('Product not found');
            }

            Response::success('Product retrieved successfully', [
                'product' => $product
            ]);

        } catch (\Exception $e) {
            error_log("Get product failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve product.');
        }
    }

    /**
     * Place an order
     */
    public static function placeOrder(): void
    {
        $user = AuthMiddleware::consumer();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requiredFields = ['items', 'dropoff_location'];
        $errors = Validator::required($data, $requiredFields);

        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            $errors['items'] = 'Order must contain at least one item';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Validate all items and calculate total
            $totalAmount = 0;
            $shgId = null;
            $validatedItems = [];

            foreach ($data['items'] as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity_kg'])) {
                    Response::validationError(['items' => 'Each item must have product_id and quantity_kg']);
                }

                // Get product details
                $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND is_active = TRUE");
                $stmt->execute(['id' => $item['product_id']]);
                $product = $stmt->fetch();

                if (!$product) {
                    Response::error("Product {$item['product_id']} not found or inactive");
                }

                if ($product['quantity_kg'] < $item['quantity_kg']) {
                    Response::error("Insufficient quantity for product {$product['millet_type']}");
                }

                // All items must be from same SHG
                if ($shgId === null) {
                    $shgId = $product['shg_id'];
                } elseif ($shgId !== $product['shg_id']) {
                    Response::error('All items must be from the same seller');
                }

                $itemTotal = $product['price_per_kg'] * $item['quantity_kg'];
                $totalAmount += $itemTotal;

                $validatedItems[] = [
                    'product_id' => $product['id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'price_per_kg' => $product['price_per_kg'],
                    'total_price' => $itemTotal
                ];
            }

            // Get SHG warehouse location for pickup
            $stmt = $db->prepare("SELECT warehouse_location FROM shg_profiles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $shgId]);
            $shg = $stmt->fetch();

            // Generate order number
            $orderNumber = self::generateOrderNumber();

            // Create order
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, consumer_id, shg_id, total_amount,
                    pickup_location, dropoff_location, status
                ) VALUES (
                    :order_number, :consumer_id, :shg_id, :total_amount,
                    :pickup_location, :dropoff_location, 'order_placed'
                )
            ");

            $stmt->execute([
                'order_number' => $orderNumber,
                'consumer_id' => $user->user_id,
                'shg_id' => $shgId,
                'total_amount' => $totalAmount,
                'pickup_location' => $shg['warehouse_location'],
                'dropoff_location' => Validator::sanitize($data['dropoff_location'])
            ]);

            $orderId = $db->lastInsertId();

            // Create order items
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity_kg, price_per_kg, total_price)
                VALUES (:order_id, :product_id, :quantity_kg, :price_per_kg, :total_price)
            ");

            foreach ($validatedItems as $item) {
                $stmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'price_per_kg' => $item['price_per_kg'],
                    'total_price' => $item['total_price']
                ]);

                // Update product quantity
                $updateStmt = $db->prepare("
                    UPDATE products 
                    SET quantity_kg = quantity_kg - :quantity 
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    'quantity' => $item['quantity_kg'],
                    'id' => $item['product_id']
                ]);
            }

            // Add initial status to history
            $stmt = $db->prepare("
                INSERT INTO order_status_history (order_id, status, notes)
                VALUES (:order_id, 'order_placed', 'Order placed successfully')
            ");
            $stmt->execute(['order_id' => $orderId]);

            $db->commit();

            Response::success('Order placed successfully', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount
            ], 201);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Place order failed: " . $e->getMessage());
            Response::serverError('Failed to place order.');
        }
    }

    /**
     * Get consumer's orders
     */
    public static function getOrders(): void
    {
        $user = AuthMiddleware::consumer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    o.*,
                    s.organization_name as shg_name,
                    s.contact_person_name as shg_contact
                FROM orders o
                LEFT JOIN shg_profiles s ON o.shg_id = s.user_id
                WHERE o.consumer_id = :consumer_id
                ORDER BY o.created_at DESC
            ");

            $stmt->execute(['consumer_id' => $user->user_id]);
            $orders = $stmt->fetchAll();

            // Get items for each order
            foreach ($orders as &$order) {
                $stmt = $db->prepare("
                    SELECT 
                        oi.*,
                        p.millet_type,
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
            Response::serverError('Failed to retrieve orders');
        }
    }

    /**
     * Track order status
     */
    public static function trackOrder(int $orderId): void
    {
        $user = AuthMiddleware::consumer();

        try {
            $db = Database::getConnection();

            // Get order details
            $stmt = $db->prepare("
                SELECT 
                    o.*,
                    s.organization_name as shg_name,
                    s.contact_person_name as shg_contact,
                    u.mobile as shg_mobile,
                    u.email as shg_email
                FROM orders o
                INNER JOIN shg_profiles s ON o.shg_id = s.user_id
                INNER JOIN users u ON o.shg_id = u.id
                WHERE o.id = :id AND o.consumer_id = :consumer_id
            ");

            $stmt->execute([
                'id' => $orderId,
                'consumer_id' => $user->user_id
            ]);
            $order = $stmt->fetch();

            if (!$order) {
                Response::notFound('Order not found');
            }

            // Get order items
            $stmt = $db->prepare("
                SELECT 
                    oi.*,
                    p.millet_type,
                    p.quality_grade,
                    t.traceability_id,
                    t.farmer_name
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                LEFT JOIN traceability_records t ON p.supply_id = t.supply_id
                WHERE oi.order_id = :order_id
            ");
            $stmt->execute(['order_id' => $orderId]);
            $order['items'] = $stmt->fetchAll();

            // Get status history
            $stmt = $db->prepare("
                SELECT * FROM order_status_history
                WHERE order_id = :order_id
                ORDER BY created_at ASC
            ");
            $stmt->execute(['order_id' => $orderId]);
            $order['status_history'] = $stmt->fetchAll();

            Response::success('Order tracking details retrieved', [
                'order' => $order
            ]);

        } catch (\Exception $e) {
            error_log("Track order failed: " . $e->getMessage());
            Response::serverError('Failed to track order.');
        }
    }

    /**
     * Generate unique order number
     */
    private static function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return "ORD-{$date}-{$random}";
    }
}
