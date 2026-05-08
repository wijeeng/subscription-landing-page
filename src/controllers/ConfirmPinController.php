<?php

declare(strict_types=1);

final class ConfirmPinController
{
    public function __construct(
        protected Logger $logger,
        private ApiClient $client,
        private array $config
    ) {
    }


    public function handle(array $payload): array
    {
        $this->logger->info(
            'ConfirmPinController handle request received',
            [
                'payload' => $payload
            ]
        );

        $endpoint = $this->config['confirm-pin'] ?? '';
        if (!$endpoint) {
            $this->logger->error(
                'ConfirmPinController handle missing endpoint',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Missing confirm pin endpoint');
        }

        if (!Validator::isValidPin($payload)) {
            $this->logger->error(
                'ConfirmPinController handle invalid PIN',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Invalid PIN');
        }

        if (empty($payload['session_token'])) {
            $this->logger->error(
                'ConfirmPinController handle missing session token',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Missing session token');
        }

        $this->logger->info(
            'ConfirmPinController handle calling API',
            [
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]
        );

        $apiResponse = $this->client->post($endpoint, $payload);

        $this->logger->info(
            'ConfirmPinController handle API response',
            [
                'response' => $apiResponse,
                'endpoint' => $endpoint,
                'payload'  => $payload
            ]
        );

        if ($this->isInvalidPin($apiResponse)) {
            $this->logger->error(
                'ConfirmPinController handle Invalid PIN',
                [
                    'response' => $apiResponse,
                    'endpoint' => $endpoint,
                    'payload'  => $payload
                ]
            );

            ApiResponse::error(
                'INVALID_PIN',
                $apiResponse['message'] ?? 'Invalid PIN',
                200,
                $apiResponse
            );
        }

        if ($this->isFailed($apiResponse)) {
            $this->logger->error(
                'ConfirmPinController handle API Failed',
                [
                    'response' => $apiResponse,
                    'endpoint' => $endpoint,
                    'payload'  => $payload
                ]
            );

            ApiResponse::error(
                'CONFIRM_PIN_FAILED',
                $apiResponse['message'] ?? 'PIN confirmation failed',
                200,
                $apiResponse
            );
        }

        $result = [
            'code'          => 'PIN_CONFIRMED',
            'message'       => 'PIN confirmed successfully',
            'session_token' => $this->extractToken($apiResponse),
            'data'          => $apiResponse,
        ];

        $this->logger->info(
            'ConfirmPinController handle success',
            [
                'result'  => $result,
                'payload' => $payload,
            ]
        );

        return $result;
    }

    private function extractToken(array $response): ?string
    {
        return $response['session_token']
            ?? $response['sessionToken']
            ?? $response['token']
            ?? null;
    }

    private function isInvalidPin(array $response): bool
    {
        $code = strtoupper((string) ($response['code'] ?? ''));
        $message = strtoupper((string) ($response['message'] ?? ''));

        return in_array($code, [
                'INVALID_PIN',
                'WRONG_PIN',
                'INCORRECT_PIN',
                'INVALID_OTP',
                'WRONG_OTP',
                'INCORRECT_OTP',
            ], true)
            || str_contains($message, 'INVALID PIN')
            || str_contains($message, 'WRONG PIN')
            || str_contains($message, 'INCORRECT PIN')
            || str_contains($message, 'INVALID OTP')
            || str_contains($message, 'WRONG OTP')
            || str_contains($message, 'INCORRECT OTP');
    }

    private function isFailed(array $response): bool
    {
        return ($response['success'] ?? true) === false
            || ($response['status'] ?? true) === false
            || (($response['_http_code'] ?? 200) >= 400);
    }
}