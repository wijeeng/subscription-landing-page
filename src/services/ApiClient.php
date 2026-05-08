<?php

declare(strict_types=1);

final class ApiClient
{
    public function __construct(
        protected Logger $logger
    ) {
    }

    public function post(string $endpoint, array $payload, string $type = 'json'): array
    {
        $this->logger->info(
            "ApiClient request",
            [
                'endpoint' => $endpoint,
                'type'     => $type,
                'payload'  => $payload,
            ]
        );

        $headers = [
            'Accept: application/json, text/plain, application/xml, text/xml, */*',
        ];

        if ($type === 'json') {
            $headers[] = 'Content-Type: application/json';
            $postFields = json_encode($payload);
        } elseif ($type === 'form') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $postFields = http_build_query($payload);
        } else {
            throw new InvalidArgumentException("Unsupported post type: {$type}");
        }

        $ch = curl_init($endpoint);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response    = curl_exec($ch);
        $error       = curl_error($ch);
        $headerSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';

        curl_close($ch);

        if ($response === false || $error) {
            $this->logger->error(
                "ApiClient connection failed", [
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                    'error'    => $error,
                ]
            );

            throw new RuntimeException('API connection failed: ' . $error);
        }

        $body = substr($response, $headerSize);

        $decodedBody = json_decode(trim($body), true);

        $this->logger->info(
            "ApiClient response",
            [
                'endpoint'     => $endpoint,
                'http_code'    => $httpCode,
                'content_type' => $contentType,
                'body'         => is_array($decodedBody) ? $decodedBody : trim($body),
            ]
        );

        return $this->normalizeResponse($body, $httpCode, $contentType);
    }

    private function normalizeResponse(string $body, int $httpCode, string $contentType): array
    {
        $body = trim($body);

        if ($body === '') {
            return [
                'success'       => $this->isSuccessHttpCode($httpCode),
                'code'          => 'EMPTY_RESPONSE',
                'message'       => 'Empty API response',
                '_raw_response' => '',
                '_http_code'    => $httpCode,
                '_content_type' => $contentType,
            ];
        }

        $json = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return array_merge($json, [
                'success'       => $this->isSuccessHttpCode($httpCode),
                '_raw_response' => $body,
                '_http_code'    => $httpCode,
                '_content_type' => $contentType,
            ]);
        }

        if (
            str_contains(strtolower($contentType), 'xml') ||
            str_starts_with($body, '<')
        ) {
            $xml = $this->parseXml($body);

            return [
                'success'       => $this->isSuccessHttpCode($httpCode),
                'code'          => empty($xml) ? 'INVALID_XML_RESPONSE' : 'XML_RESPONSE',
                'message'       => empty($xml) ? 'Invalid XML response' : 'XML response received',
                'xml'           => $xml,
                '_raw_response' => $body,
                '_http_code'    => $httpCode,
                '_content_type' => $contentType,
            ];
        }

        return [
            'success'       => $this->isSuccessHttpCode($httpCode),
            'code'          => 'TEXT_RESPONSE',
            'message'       => $body,
            '_raw_response' => $body,
            '_http_code'    => $httpCode,
            '_content_type' => $contentType,
        ];
    }

    private function parseXml(string $body): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            libxml_clear_errors();
            return [];
        }

        libxml_clear_errors();

        return json_decode(json_encode($xml), true) ?: [];
    }

    private function isSuccessHttpCode(int $httpCode): bool
    {
        return $httpCode >= 200 && $httpCode < 300;
    }
}