<?php

namespace App\Controllers\Auth;

use App\Config\Database;
use App\Helpers\Validator;
use App\Helpers\Response;
use App\Helpers\OTPHelper;
use App\Helpers\JWTHelper;

class FarmerAuthController
{
    /**
     * Register a new farmer
     */
    public static function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate required fields
        $requiredFields = ['name', 'email', 'mobile', 'password', 'street', 'city', 'pincode'];
        $errors = Validator::required($data, $requiredFields);

        // Validate email
        if (isset($data['email']) && !Validator::email($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }

        // Validate mobile
        if (isset($data['mobile']) && !Validator::mobile($data['mobile'])) {
            $errors['mobile'] = 'Mobile number must be exactly 10 digits';
        }

        // Validate pincode
        if (isset($data['pincode']) && !Validator::pincode($data['pincode'])) {
            $errors['pincode'] = 'Pincode must be exactly 6 digits';
        }

        // Validate password
        if (isset($data['password'])) {
            $passwordValidation = Validator::password($data['password']);
            if (!$passwordValidation['valid']) {
                $errors['password'] = $passwordValidation['errors'];
            }
        }

        // Validate IFSC if provided
        if (!empty($data['bank_ifsc_code']) && !Validator::ifsc($data['bank_ifsc_code'])) {
            $errors['bank_ifsc_code'] = 'Invalid IFSC code format';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            if ($stmt->fetch()) {
                Response::error('Email already registered', null, 409);
            }

            // Begin transaction
            $db->beginTransaction();

            // Insert user
            $stmt = $db->prepare("
                INSERT INTO users (email, mobile, password, role, is_verified)
                VALUES (:email, :mobile, :password, 'farmer', FALSE)
            ");

            $stmt->execute([
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT)
            ]);

            $userId = $db->lastInsertId();

            // Insert farmer profile
            $stmt = $db->prepare("
                INSERT INTO farmer_profiles (
                    user_id, name, street, city, pincode, 
                    farm_location, bank_account_number, bank_ifsc_code
                ) VALUES (
                    :user_id, :name, :street, :city, :pincode,
                    :farm_location, :bank_account_number, :bank_ifsc_code
                )
            ");

            $stmt->execute([
                'user_id' => $userId,
                'name' => Validator::sanitize($data['name']),
                'street' => Validator::sanitize($data['street']),
                'city' => Validator::sanitize($data['city']),
                'pincode' => $data['pincode'],
                'farm_location' => Validator::sanitize($data['farm_location'] ?? ''),
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_ifsc_code' => isset($data['bank_ifsc_code']) ? strtoupper($data['bank_ifsc_code']) : null
            ]);

            // Generate and send OTP
            $otp = OTPHelper::generate();
            OTPHelper::store($userId, $data['email'], $otp, 'registration');
            OTPHelper::sendEmail($data['email'], $otp, 'registration');

            $db->commit();

            Response::success('Registration successful. Please check your email for OTP verification.', [
                'user_id' => $userId,
                'email' => $data['email']
            ], 201);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Farmer registration failed: " . $e->getMessage());
            Response::serverError('Registration failed. Please try again.');
        }
    }

    /**
     * Verify OTP
     */
    public static function verifyOTP(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = Validator::required($data, ['email', 'otp']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        if (OTPHelper::verify($data['email'], $data['otp'], 'registration')) {
            Response::success('Account verified successfully. You can now login.');
        } else {
            Response::error('Invalid or expired OTP', null, 400);
        }
    }

    /**
     * Login
     */
    public static function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = Validator::required($data, ['email', 'password']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT u.id, u.email, u.password, u.role, u.is_verified, u.is_active,u.mobile,
                       f.name, f.street, f.city, f.pincode
                FROM users u
                LEFT JOIN farmer_profiles f ON u.id = f.user_id
                WHERE u.email = :email AND u.role = 'farmer'
            ");

            $stmt->execute(['email' => $data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password'])) {
                Response::error('Invalid email or password', null, 401);
            }

            if (!$user['is_verified']) {
                Response::error('Account not verified. Please verify your email first.', null, 403);
            }

            if (!$user['is_active']) {
                Response::error('Account is inactive. Please contact support.', null, 403);
            }

            // Generate JWT
            $token = JWTHelper::encode([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'name' => $user['name'],
                'mobile' => $user['mobile']
            ]);

            Response::success('Login successful', [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'mobile' => $user['mobile'],
                    'name' => $user['name'],
                    'street' => $user['street'],
                    'city' => $user['city'],
                    'pincode' => $user['pincode']
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Farmer login failed: " . $e->getMessage());
            Response::serverError('Login failed. Please try again.');
        }
    }

    /**
     * Forgot password - Send OTP
     */
    public static function forgotPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = Validator::required($data, ['email']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT id, email FROM users WHERE email = :email AND role = 'farmer'");
            $stmt->execute(['email' => $data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                Response::error('Email not found', null, 404);
            }

            // Generate and send OTP
            $otp = OTPHelper::generate();
            OTPHelper::store($user['id'], $user['email'], $otp, 'forgot_password');
            OTPHelper::sendEmail($user['email'], $otp, 'forgot_password');

            Response::success('OTP sent to your email. Please check your inbox.');

        } catch (\Exception $e) {
            error_log("Forgot password failed: " . $e->getMessage());
            Response::serverError('Failed to send OTP. Please try again.');
        }
    }

    /**
     * Reset password with OTP
     */
    public static function resetPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = Validator::required($data, ['email', 'otp', 'new_password']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        // Validate new password
        $passwordValidation = Validator::password($data['new_password']);
        if (!$passwordValidation['valid']) {
            Response::validationError(['new_password' => $passwordValidation['errors']]);
        }

        try {
            $db = Database::getConnection();

            // Verify OTP
            $stmt = $db->prepare("
                SELECT user_id FROM otp_verifications
                WHERE email = :email 
                AND otp = :otp 
                AND purpose = 'forgot_password'
                AND is_used = FALSE
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([
                'email' => $data['email'],
                'otp' => $data['otp']
            ]);

            $record = $stmt->fetch();

            if (!$record) {
                Response::error('Invalid or expired OTP', null, 400);
            }

            // Update password
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->execute([
                'password' => password_hash($data['new_password'], PASSWORD_DEFAULT),
                'user_id' => $record['user_id']
            ]);

            // Mark OTP as used
            $stmt = $db->prepare("
                UPDATE otp_verifications 
                SET is_used = TRUE 
                WHERE email = :email AND otp = :otp AND purpose = 'forgot_password'
            ");
            $stmt->execute([
                'email' => $data['email'],
                'otp' => $data['otp']
            ]);

            Response::success('Password reset successful. You can now login with your new password.');

        } catch (\Exception $e) {
            error_log("Reset password failed: " . $e->getMessage());
            Response::serverError('Password reset failed. Please try again.');
        }
    }
}
