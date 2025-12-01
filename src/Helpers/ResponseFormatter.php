<?php

declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface;

/**
 * Response Formatter Helper
 * Standardizes JSON responses across the application
 */
class ResponseFormatter
{
    /**
     * Format success response
     *
     * @param ResponseInterface $response
     * @param mixed $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    public static function success(ResponseInterface $response, $data, int $statusCode = 200): ResponseInterface
    {
        $payload = json_encode([
            'success' => true,
            'data' => $data
        ]);

        if ($payload === false) {
            $payload = json_encode(['success' => false, 'error' => 'Failed to encode response']);
            if ($payload === false) {
                $payload = '{"success":false,"error":"Failed to encode response"}';
            }
        }

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Format error response
     *
     * @param ResponseInterface $response
     * @param string $error
     * @param int $statusCode
     * @param array<string, mixed> $details Additional error details
     * @return ResponseInterface
     */
    public static function error(
        ResponseInterface $response,
        string $error,
        int $statusCode = 400,
        array $details = []
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'error' => $error
        ];

        if (!empty($details)) {
            $payload['details'] = $details;
        }

        $json = json_encode($payload);
        if ($json === false) {
            $json = '{"success":false,"error":"Failed to encode error response"}';
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Format validation error response
     *
     * @param ResponseInterface $response
     * @param array<string, mixed> $errors
     * @return ResponseInterface
     */
    public static function validationError(ResponseInterface $response, array $errors): ResponseInterface
    {
        return self::error($response, 'Validation failed', 422, $errors);
    }

    /**
     * Format not found response
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    public static function notFound(ResponseInterface $response, string $message = 'Resource not found'): ResponseInterface
    {
        return self::error($response, $message, 404);
    }
}
