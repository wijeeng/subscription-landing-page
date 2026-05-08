<?php

declare(strict_types=1);

final class ApiResponse
{
    public static function success(array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);

        echo json_encode([
            'success'       => true,
            'code'          => $data['code'] ?? 'OK',
            'message'       => $data['message'] ?? 'Success',
            'session_token' => $data['session_token'] ?? null,
            'data'          => $data['data'] ?? [],
        ]);

        exit;
    }

    public static function error(string $code, string $message, int $statusCode = 400, array $data = []): void
    {
        http_response_code($statusCode);

        echo json_encode([
            'success'       => false,
            'code'          => $code,
            'message'       => $message,
            'session_token' => null,
            'data'          => $data,
        ]);

        exit;
    }
}