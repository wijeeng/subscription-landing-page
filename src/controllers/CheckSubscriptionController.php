<?php

declare(strict_types=1);

final class CheckSubscriptionController
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
            'CheckSubscriptionController handle request received',
            [
                'payload' => $payload
            ]
        );

        $endpoint = $this->config['check-subscription'] ?? '';
        if (!$endpoint) {
            $this->logger->error(
                'CheckSubscriptionController handle missing endpoint',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Missing check subscription endpoint');
        }

        if (!Validator::isValidMsisdnNumber($payload)) {
            $this->logger->error(
                'CheckSubscriptionController handle invalid msisdn',
                [
                    'payload' => $payload
                ]
            );

            throw new \Exception('Invalid msisdn');
        }

        $this->logger->info(
            'CheckSubscriptionController handle calling API',
            [
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]
        );

        $apiResponse = $this->client->post($endpoint, $payload);

        $this->logger->info(
            'CheckSubscriptionController handle API response',
            [
                'response' => $apiResponse,
                'endpoint' => $endpoint,
                'payload'  => $payload
            ]
        );

        if ($this->isAlreadySubscribed($apiResponse)) {
            $this->logger->error(
                'CheckSubscriptionController handle API failed user already subscribed',
                [
                    'response' => $apiResponse,
                    'endpoint' => $endpoint,
                    'payload'  => $payload
                ]
            );

            ApiResponse::error(
                'ALREADY_SUBSCRIBED',
                'User is already subscribed',
                200,
                $apiResponse
            );
        }

        $result = [
            'code'          => 'CHECK_SUBSCRIPTION_SUCCESS',
            'message'       => 'Subscription check completed',
            'session_token' => $this->extractToken($apiResponse),
            'data'          => $apiResponse,
        ];

        $this->logger->info(
            'CheckSubscriptionController handle success',
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

    private function isAlreadySubscribed(array $response): bool
    {
        $code = strtoupper((string) ($response['code'] ?? ''));
        $message = strtoupper((string) ($response['message'] ?? ''));

        return $code === 'ALREADY_SUBSCRIBED'
            || str_contains($message, 'ALREADY SUBSCRIBED')
            || str_contains($message, 'ALREADY_SUBSCRIBED');
    }
}