<?php
declare(strict_types=1);

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true) ?: [];

$msisdn       = $payload['msisdn'] ?? '';
$pin          = $payload['pin'] ?? '';
$sessionToken = $payload['session_token'] ?? '';

if ($sessionToken === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'Error',
        'message' => 'Invalid parameters session_token is required',
        'code' => 'SESSION_TOKEN_REQUIRED'
    ]);
    exit;
}

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

if ($pin === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Invalid parameters pin is required',
        'code'    => 'PIN_REQUIRED'
    ]);
    exit;
}

if (!ctype_digit($pin)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Invalid parameters pin must be numeric',
        'code'    => 'PIN_INVALID'
    ]);
    exit;
}

if ($pin !== '1111') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'Error',
        'message' => 'Error request ConfirmPin',
        'code'    => 'INVALID_PIN'
    ]);
    exit;
}

echo json_encode([
    'status'       => 'Success',
    'message'      => 'Confirm pin successfully',
    'code'         => 'PIN_CONFIRMED',
    'success_mode' => 'tq_only',
]);