<?php

namespace App\Middleware;

use App\Helpers\JWTHelper;
use App\Helpers\Response;

class AuthMiddleware
{
    /**
     * Verify JWT token and return user data
     */
    public static function authenticate(): object
    {
        $payload = JWTHelper::verify();

        if (!$payload) {
            Response::unauthorized('Invalid or missing authentication token');
        }

        return $payload;
    }

    /**
     * Verify user has required role
     */
    public static function authorize(array $allowedRoles): object
    {
        $user = self::authenticate();

        if (!in_array($user->role, $allowedRoles)) {
            Response::forbidden('You do not have permission to access this resource');
        }

        return $user;
    }

    /**
     * Verify farmer role
     */
    public static function farmer(): object
    {
        return self::authorize(['farmer']);
    }

    /**
     * Verify SHG role
     */
    public static function shg(): object
    {
        return self::authorize(['shg']);
    }

    /**
     * Verify consumer role
     */
    public static function consumer(): object
    {
        return self::authorize(['consumer']);
    }
}
