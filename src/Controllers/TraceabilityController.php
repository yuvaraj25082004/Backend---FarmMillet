<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;

class TraceabilityController
{
    /**
     * Get traceability details by ID
     */
    public static function getById(int $id): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    t.*,
                    fs.location,
                    fs.collection_by,
                    fs.collection_date,
                    fp.farm_location,
                    u.email as farmer_email,
                    u.mobile as farmer_mobile
                FROM traceability_records t
                INNER JOIN farmer_supplies fs ON t.supply_id = fs.id
                INNER JOIN farmer_profiles fp ON t.farmer_id = fp.user_id
                INNER JOIN users u ON t.farmer_id = u.id
                WHERE t.id = :id
            ");

            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch();

            if (!$record) {
                Response::notFound('Traceability record not found');
            }

            Response::success('Traceability details retrieved', [
                'traceability' => $record,
                'journey' => []
            ]);

        } catch (\Exception $e) {
            error_log("Get traceability failed: " . $e->getMessage());
            Response::serverError('Failed to retrieve traceability details.');
        }
    }

    /**
     * Search traceability by traceability ID
     */
    public static function search(): void
    {
        $traceabilityId = $_GET['id'] ?? null;

        if (!$traceabilityId) {
            Response::validationError(['id' => 'Traceability ID is required']);
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    t.*,
                    fs.location,
                    fs.collection_by,
                    fs.collection_date,
                    fs.status as supply_status,
                    fp.farm_location,
                    fp.city as farmer_city,
                    u.email as farmer_email,
                    u.mobile as farmer_mobile
                FROM traceability_records t
                INNER JOIN farmer_supplies fs ON t.supply_id = fs.id
                INNER JOIN farmer_profiles fp ON t.farmer_id = fp.user_id
                INNER JOIN users u ON t.farmer_id = u.id
                WHERE t.traceability_id = :traceability_id
            ");

            $stmt->execute(['traceability_id' => $traceabilityId]);
            $record = $stmt->fetch();

            if (!$record) {
                Response::notFound('Traceability record not found');
            }

            // Get product info if linked
            $stmt = $db->prepare("
                SELECT 
                    p.id as product_id,
                    p.millet_type,
                    p.price_per_kg,
                    s.organization_name as shg_name,
                    s.city as shg_city
                FROM products p
                INNER JOIN shg_profiles s ON p.shg_id = s.user_id
                WHERE p.supply_id = :supply_id
                LIMIT 1
            ");
            $stmt->execute(['supply_id' => $record['supply_id']]);
            $product = $stmt->fetch();

            if ($product) {
                $record['product_info'] = $product;
            }

            Response::success('Traceability details retrieved', [
                'traceability' => $record,
                'journey' => []
            ]);

        } catch (\Exception $e) {
            error_log("Search traceability failed: " . $e->getMessage());
            Response::serverError('Failed to search traceability.');
        }
    }

    /**
     * List all traceability records
     */
    public static function listAll(): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT 
                    t.id,
                    t.traceability_id,
                    t.millet_type,
                    t.farmer_name,
                    t.quality_grade,
                    t.harvest_date,
                    t.packaging_date,
                    t.created_at,
                    fs.status as supply_status
                FROM traceability_records t
                INNER JOIN farmer_supplies fs ON t.supply_id = fs.id
                ORDER BY t.created_at DESC
            ");

            $stmt->execute();
            $records = $stmt->fetchAll();

            Response::success('Traceability records retrieved', [
                'records' => $records,
                'total' => count($records)
            ]);

        } catch (\Exception $e) {
            error_log("List traceability failed: " . $e->getMessage());
            Response::serverError('Failed to list traceability records.');
        }
    }
}
