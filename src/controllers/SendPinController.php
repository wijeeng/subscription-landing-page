<?php

declare(strict_types=1);

final class SendPinController
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
            'SendPinController handle request received',
            [
                'payload' => $payload
            ]
        );

        $endpoint = $this->config['send-pin'] ?? '';
        if (!$endpoint) {
            $this->logger->error(
                'SendPinController handle missing endpoint',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Missing send pin endpoint');
        }

        if (!Validator::isValidMsisdnNumber($payload)) {
            $this->logger->error(
                'SendPinController handle invalid msisdn',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Invalid msisdn');
        }

        if (empty($payload['session_token'])) {
            $this->logger->error(
                'SendPinController handle missing session token',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Missing session token');
        }

        $this->logger->info(
            'SendPinController handle calling API',
            [
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]
        );

        $apiResponse = $this->client->post($endpoint, $payload);

        $this->logger->info(
            'SendPinController handle API response',
            [
                'response' => $apiResponse,
                'endpoint' => $endpoint,
                'payload'  => $payload
            ]
        );

        if ($this->isFailed($apiResponse)) {
            $this->logger->error(
                'SendPinController handle API failed',
                [
                    'response' => $apiResponse,
                    'endpoint' => $endpoint,
                    'payload'  => $payload
                ]
            );

            ApiResponse::error(
                'SEND_PIN_FAILED',
                $apiResponse['message'] ?? 'Failed to send PIN',
                200,
                $apiResponse
            );
        }

        $result = [
            'code'          => 'PIN_SENT',
            'message'       => 'PIN sent successfully',
            'session_token' => $this->extractToken($apiResponse),
            'data'          => $apiResponse,
        ];

        $this->logger->info(
            'SendPinController handle success',
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

    private function isFailed(array $response): bool
    {
        $success = $response['success'] ?? null;
        $status  = $response['status'] ?? null;
        $http    = $response['_http_code'] ?? 200;

        return $success === false
            || $success === 'false'
            || $status === false
            || $status === 'false'
            || $http >= 400;
    }
}