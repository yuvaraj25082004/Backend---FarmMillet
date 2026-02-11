<?php

namespace App\Helpers;

use App\Config\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Kolkata'); // change if needed


class OTPHelper
{
    /**
     * Generate a 6-digit OTP
     */
    public static function generate(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store OTP in database
     */
    public static function store(int $userId, string $email, string $otp, string $purpose = 'registration'): bool
    {
        try {
            $db = Database::getConnection();
            $expiryMinutes = (int)Database::getEnv('OTP_EXPIRY_MINUTES', 5);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

           $stmt = $db->prepare("
    INSERT INTO otp_verifications (user_id, email, otp, purpose, expires_at)
    VALUES (:user_id, :email, :otp, :purpose, DATE_ADD(NOW(), INTERVAL :expiry MINUTE))
");

return $stmt->execute([
    'user_id' => $userId,
    'email' => $email,
    'otp' => $otp,
    'purpose' => $purpose,
    'expiry' => $expiryMinutes
]);

        } catch (\Exception $e) {
            error_log("OTP storage failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP
     */
    public static function verify(string $email, string $otp, string $purpose = 'registration'): bool
    {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT id, user_id FROM otp_verifications
                WHERE email = :email 
                AND otp = :otp 
                AND purpose = :purpose
                AND is_used = FALSE
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([
                'email' => $email,
                'otp' => $otp,
                'purpose' => $purpose
            ]);

            $record = $stmt->fetch();

            if ($record) {
                // Mark OTP as used
                $updateStmt = $db->prepare("UPDATE otp_verifications SET is_used = TRUE WHERE id = :id");
                $updateStmt->execute(['id' => $record['id']]);

                // Mark user as verified
                $userStmt = $db->prepare("UPDATE users SET is_verified = TRUE WHERE id = :user_id");
                $userStmt->execute(['user_id' => $record['user_id']]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log("OTP verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP via email
     */
    public static function sendEmail(string $email, string $otp, string $purpose = 'registration'): bool
    {
        try {
            $mail = new PHPMailer(true);

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = Database::getEnv('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = Database::getEnv('SMTP_USERNAME');
            $mail->Password = Database::getEnv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)Database::getEnv('SMTP_PORT', 587);

            // Email settings
            $mail->setFrom(
                Database::getEnv('SMTP_FROM_EMAIL'),
                Database::getEnv('SMTP_FROM_NAME', 'Millet Marketplace')
            );
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            
            if ($purpose === 'registration') {
                $mail->Subject = 'Verify Your Account - Millet Marketplace';
                $mail->Body = self::getRegistrationEmailTemplate($otp);
            } else {
                $mail->Subject = 'Reset Your Password - Millet Marketplace';
                $mail->Body = self::getForgotPasswordEmailTemplate($otp);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }

    private static function getRegistrationEmailTemplate(string $otp): string
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #4CAF50;'>Welcome to Millet Marketplace!</h2>
                    <p>Thank you for registering with us. Please use the following OTP to verify your account:</p>
                    <div style='background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                        {$otp}
                    </div>
                    <p><strong>This OTP is valid for 5 minutes.</strong></p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='font-size: 12px; color: #777;'>
                        Best regards,<br>
                        Millet Marketplace Team
                    </p>
                </div>
            </body>
            </html>
        ";
    }

    private static function getForgotPasswordEmailTemplate(string $otp): string
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #FF9800;'>Password Reset Request</h2>
                    <p>We received a request to reset your password. Please use the following OTP:</p>
                    <div style='background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                        {$otp}
                    </div>
                    <p><strong>This OTP is valid for 5 minutes.</strong></p>
                    <p>If you didn't request this, please ignore this email and your password will remain unchanged.</p>
                    <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='font-size: 12px; color: #777;'>
                        Best regards,<br>
                        Millet Marketplace Team
                    </p>
                </div>
            </body>
            </html>
        ";
    }
}
