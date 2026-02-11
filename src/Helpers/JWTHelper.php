<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Database;

class JWTHelper
{
    /**
     * Generate JWT token (NO expiry as per requirements)
     */
    public static function encode(array $payload): string
    {
        $secret = Database::getEnv('JWT_SECRET', 'default-secret-change-this');
        
        // Add issued at timestamp
        $payload['iat'] = time();
        
        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Decode and verify JWT token
     */
    public static function decode(string $token): ?object
    {
        try {
            $secret = Database::getEnv('JWT_SECRET', 'default-secret-change-this');
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            error_log("JWT decode failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract token from Authorization header
     */
    public static function extractFromHeader(): ?string
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Verify token and return payload
     */
    public static function verify(): ?object
    {
        $token = self::extractFromHeader();
        
        if (!$token) {
            return null;
        }

        return self::decode($token);
    }
}
