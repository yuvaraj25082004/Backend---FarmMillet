<?php

namespace App\Helpers;

class Validator
{
    /**
     * Validate mobile number (exactly 10 digits)
     */
    public static function mobile(string $mobile): bool
    {
        return preg_match('/^[0-9]{10}$/', $mobile) === 1;
    }

    /**
     * Validate email
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate pincode (6 digits)
     */
    public static function pincode(string $pincode): bool
    {
        return preg_match('/^[0-9]{6}$/', $pincode) === 1;
    }

    /**
     * Validate IFSC code (11 characters: 4 letters + 7 alphanumeric)
     */
    public static function ifsc(string $ifsc): bool
    {
        return preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($ifsc)) === 1;
    }

    /**
     * Validate password strength
     */
    public static function password(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate required fields
     */
    public static function required(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                continue;
            }

            $value = $data[$field];
            if (is_string($value)) {
                if (trim($value) === '') {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                }
            } elseif (is_array($value)) {
                if (empty($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                }
            } else {
                if ($value === null) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitize input
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate quality grade
     */
    public static function qualityGrade(string $grade): bool
    {
        return in_array(strtoupper($grade), ['A', 'B', 'C']);
    }

    /**
     * Validate order status
     */
    public static function orderStatus(string $status): bool
    {
        return in_array($status, [
            'order_placed',
            'confirmed',
            'picked_up',
            'in_transit',
            'delivered',
            'cancelled'
        ]);
    }

    /**
     * Validate supply status
     */
    public static function supplyStatus(string $status): bool
    {
        return in_array($status, ['pending', 'accepted', 'collected', 'completed', 'listed']);
    }
}
