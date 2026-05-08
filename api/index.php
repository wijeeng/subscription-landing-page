<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/services/ApiResponse.php';
require_once __DIR__ . '/../src/services/ApiClient.php';
require_once __DIR__ . '/../src/controllers/CheckSubscriptionController.php';
require_once __DIR__ . '/../src/controllers/SendPinController.php';
require_once __DIR__ . '/../src/controllers/ConfirmPinController.php';
require_once __DIR__ . '/../src/helpers/Logger.php';
require_once __DIR__ . '/../src/helpers/Validator.php';

$logger = new Logger(__DIR__ . '/../logs/app.log');
$config = require __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('METHOD_NOT_ALLOWED', 'Only POST requests are allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    ApiResponse::error('INVALID_JSON', 'Invalid JSON request', 400);
}

$action  = $input['action'] ?? null;
$payload = $input['payload'] ?? [];

if (!is_array($payload)) {
    ApiResponse::error('INVALID_PAYLOAD', 'Payload must be an object', 400);
}

$mode = strtolower(trim((string) ($payload['mode'] ?? 'prod')));
unset($payload['mode']);

$allowedModes = ['mock', 'prod'];
if (!in_array($mode, $allowedModes, true)) {
    ApiResponse::error(
        'INVALID_MODE',
        'Invalid mode. Allowed values are: mock, prod',
        400
    );
}

$logger->info(
    'API request received',
    [
        'method'  => $_SERVER['REQUEST_METHOD'],
        'action'  => $action,
        'mode'    => $mode,
        'payload' => $payload,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]
);

try {
    $apiConfig = $config['api'][$mode] ?? [];
    $apiClient = new ApiClient($logger);

    $controller = match ($action) {
        'check_subscription' => new CheckSubscriptionController($logger, $apiClient, $apiConfig),
        'send_pin'           => new SendPinController($logger, $apiClient, $apiConfig),
        'confirm_pin'        => new ConfirmPinController($logger, $apiClient, $apiConfig),
        default              => null,
    };

    if ($controller === null) {
        $logger->error(
            'API Invalid action',
            [
                'action' => $action,
                'input'  => $input,
            ]
        );

        ApiResponse::error('INVALID_ACTION', 'Invalid action', 400);
    }

    $result = $controller->handle($payload);

    $logger->info(
        'API Success response',
        [
            'action' => $action,
            'result' => $result,
            'input'  => $input,
        ]
    );

    ApiResponse::success($result);

} catch (Throwable $e) {
    $logger->error(
        'API exception',
        [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
            'input'   => $input,
        ]
    );

    ApiResponse::error('SERVER_ERROR', $e->getMessage(), 500);
}