<?php

namespace App\Helpers;

class Response
{
    /**
     * Send JSON success response
     */
    public static function success(string $message, $data = null, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send JSON error response
     */
    public static function error(string $message, $errors = null, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, null, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, null, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, null, 404);
    }

    /**
     * Send validation error response
     */
    public static function validationError(array $errors): void
    {
        self::error('Validation failed', $errors, 422);
    }

    /**
     * Send server error response
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, null, 500);
    }
}
