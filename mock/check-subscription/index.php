<?php
declare(strict_types=1);

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$msisdn  = $payload['msisdn'] ?? '';

if ($msisdn === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Invalid parameters msisdn is required',
        'code'    => 'MSISDN_REQUIRED'
    ]);
    exit;
}

if (!ctype_digit($msisdn)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Invalid parameters msisdn must be numeric',
        'code'    => 'MSISDN_INVALID'
    ]);
    exit;
}

if ($msisdn === '96550000000') {
    echo json_encode([
        'status'        => 'Success',
        'message'       => 'Check status successfully',
        'description'   => 'SUBSCRIBED',
        'code'          => 'ALREADY_SUBSCRIBED',
        'session_token' => 'mock_session_' . time(),
        'redirect_url'  => 'https://www.netflix.com',
        'redirect_mode' => 'redirect_only',
    ]);
    exit;
}

echo json_encode([
    'status'        => 'Success',
    'message'       => 'Check status successfully',
    'description'   => 'NOT_FOUND',
    'code'          => 'NOT_SUBSCRIBED',
    'session_token' => 'mock_session_' . time(),
]);
