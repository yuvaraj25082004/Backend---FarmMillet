<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Validator;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

class FarmerSupplyController
{
    /**
     * Add new supply
     */
    public static function addSupply(): void
    {
        $user = AuthMiddleware::farmer();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate required fields
        $requiredFields = ['millet_type', 'quantity_kg', 'harvest_date', 'packaging_date', 'location'];
        $errors = Validator::required($data, $requiredFields);

        // Validate dates
        if (isset($data['harvest_date']) && !strtotime($data['harvest_date'])) {
            $errors['harvest_date'] = 'Invalid harvest date format';
        }

        if (isset($data['packaging_date']) && !strtotime($data['packaging_date'])) {
            $errors['packaging_date'] = 'Invalid packaging date format';
        }

        // Validate quantity
        if (isset($data['quantity_kg']) && (!is_numeric($data['quantity_kg']) || $data['quantity_kg'] <= 0)) {
            $errors['quantity_kg'] = 'Quantity must be a positive number';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                INSERT INTO farmer_supplies (
                    farmer_id, millet_type, quantity_kg, quality_grade,
                    harvest_date, packaging_date, location, collection_by, collection_date
                ) VALUES (
                    :farmer_id, :millet_type, :quantity_kg, :quality_grade,
                    :harvest_date, :packaging_date, :location, :collection_by, :collection_date
                )
            ");

            $stmt->execute([
                'farmer_id' => $user->user_id,
                'millet_type' => Validator::sanitize($data['millet_type']),
                'quantity_kg' => $data['quantity_kg'],
                'quality_grade' => 'PENDING',
                'harvest_date' => $data['harvest_date'],
                'packaging_date' => $data['packaging_date'],
                'location' => Validator::sanitize($data['location']),
                'collection_by' => isset($data['collection_by']) ? Validator::sanitize($data['collection_by']) : null,
                'collection_date' => $data['collection_date'] ?? null
            ]);

            $supplyId = $db->lastInsertId();

            Response::success('Supply added successfully', [
                'supply_id' => $supplyId
            ], 201);

        } catch (\Exception $e) {
            error_log("Add supply failed: " . $e->getMessage());
            Response::serverError('Failed to add supply. Please try again.');
        }
    }

    /**
     * Get farmer's supplies
     */
    public static function getSupplies(): void
    {
        $user = AuthMiddleware::farmer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    fs.*,
                    s.organization_name as shg_name
                FROM farmer_supplies fs
                LEFT JOIN shg_profiles s ON fs.shg_id = s.user_id
                WHERE fs.farmer_id = :farmer_id
                ORDER BY fs.created_at DESC
            ");

            $stmt->execute(['farmer_id' => $user->user_id]);
            $supplies = $stmt->fetchAll();

            Response::success('Supplies retrieved successfully', $supplies);

        } catch (\Exception $e) {
            error_log("Get supplies failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve supplies.');
        }
    }

    /**
     * Get single supply details
     */
    public static function getSupplyById(int $supplyId): void
    {
        $user = AuthMiddleware::farmer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    fs.*,
                    s.organization_name as shg_name,
                    s.city as shg_city
                FROM farmer_supplies fs
                LEFT JOIN shg_profiles s ON fs.shg_id = s.user_id
                WHERE fs.id = :id AND fs.farmer_id = :farmer_id
            ");

            $stmt->execute(['id' => $supplyId, 'farmer_id' => $user->user_id]);
            $supply = $stmt->fetch();

            if (!$supply) {
                Response::notFound('Supply not found');
            }

            Response::success('Supply retrieved successfully', $supply);

        } catch (\Exception $e) {
            error_log("Get supply failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve supply details.');
        }
    }

    /**
     * Get payment history (Receipt List)
     */
    public static function getPaymentHistory(): void
    {
        $user = AuthMiddleware::farmer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    s.organization_name as paid_by,
                    sup.millet_type
                FROM payments p
                LEFT JOIN farmer_supplies sup ON sup.id = p.supply_id
                LEFT JOIN shg_profiles s ON s.user_id = sup.shg_id
                WHERE p.farmer_id = :farmer_id 
                AND p.payment_type = 'farmer_payment' 
                AND p.status = 'success'
                ORDER BY p.created_at DESC
            ");

            $stmt->execute(['farmer_id' => $user->user_id]);
            $payments = $stmt->fetchAll();

            Response::success('Payment history retrieved successfully', $payments);

        } catch (\Exception $e) {
            error_log("Get payment history failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve payment history.');
        }
    }

    public static function ReceiptById(int $paymentId): void
    {
        $user = AuthMiddleware::farmer();

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    f.name AS farmer_name,
                    s.organization_name AS paid_by,
                    s.city AS shg_city,
                    u_shg.mobile AS shg_mobile,
                    sup.millet_type,
                    sup.quantity_kg,
                    sup.quality_grade
                FROM payments p
                LEFT JOIN farmer_profiles f ON f.user_id = p.farmer_id
                LEFT JOIN farmer_supplies sup ON sup.id = p.supply_id
                LEFT JOIN shg_profiles s ON s.user_id = sup.shg_id
                LEFT JOIN users u_shg ON u_shg.id = s.user_id
                WHERE p.id = :id
                AND p.farmer_id = :farmer_id
            ");


            $stmt->execute([
                'id' => $paymentId,
                'farmer_id' => $user->user_id
            ]);

            $payment = $stmt->fetch();

            if (!$payment) {
                Response::notFound('Payment not found');
                return;
            }

            Response::success('Payment receipt retrieved successfully', $payment);

        } catch (\Exception $e) {
            error_log("Get payment receipt failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve payment receipt: ' . $e->getMessage());
        }
    }


    /**
     * Get sales summary analytics
     */
    public static function getSalesSummary(): void
    {
        $user = AuthMiddleware::farmer();

        try {
            $db = Database::getConnection();

            // Total supplies
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_supplies,
                    SUM(quantity_kg) as total_quantity,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
                FROM farmer_supplies
                WHERE farmer_id = :farmer_id
            ");
            $stmt->execute(['farmer_id' => $user->user_id]);
            $summary = $stmt->fetch();

            // Total earnings
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(amount), 0) as total_earnings,
                    COUNT(*) as total_payments
                FROM payments
                WHERE farmer_id = :farmer_id AND payment_type = 'farmer_payment' AND status = 'success'
            ");
            $stmt->execute(['farmer_id' => $user->user_id]);
            $earnings = $stmt->fetch();

            // Millet type breakdown
            $stmt = $db->prepare("
                SELECT 
                    millet_type,
                    COUNT(*) as supply_count,
                    SUM(quantity_kg) as total_quantity
                FROM farmer_supplies
                WHERE farmer_id = :farmer_id
                GROUP BY millet_type
            ");
            $stmt->execute(['farmer_id' => $user->user_id]);
            $milletBreakdown = $stmt->fetchAll();

            Response::success('Sales summary retrieved successfully', [
                'summary' => array_merge($summary, $earnings),
                'millet_breakdown' => $milletBreakdown
            ]);

        } catch (\Exception $e) {
            error_log("Get sales summary failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve sales summary.');
        }
    }
}
