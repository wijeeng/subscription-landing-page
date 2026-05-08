<?php
declare(strict_types=1);

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true) ?: [];

$msisdn       = $payload['msisdn'] ?? '';
$sessionToken = $payload['session_token'] ?? '';

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

if ($sessionToken === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Invalid parameters session_token is required',
        'code'    => 'SESSION_TOKEN_REQUIRED'
    ]);
    exit;
}

echo json_encode([
    'status'  => 'Success',
    'message' => 'PIN sent successfully',
    'code'    => 'PIN_SENT',
    'cid'     => rand(10000000, 99999999)
]);