<?php

class Validator
{
    public static  function isValidMsisdnNumber($payload): bool
    {
        if (empty($payload['msisdn'])) {
            return false;
        }

        $msisdn = self::normalizeMsisdn($payload['msisdn']);

        return preg_match('/^965\d{8}$/', $msisdn) === 1;
    }

    public static function normalizeMsisdn(string $msisdn): string
    {
        $msisdn = preg_replace('/\D/', '', $msisdn);

        if (str_starts_with($msisdn, '965')) {
            $msisdn = substr($msisdn, 3);
        }

        if (str_starts_with($msisdn, '0')) {
            $msisdn = substr($msisdn, 1);
        }

        return '965' . $msisdn;
    }

    public static function isValidPin(array $payload): bool
    {
        if (empty($payload['pin'])) {
            return false;
        }

        $pin = (string) $payload['pin'];

        return ctype_digit($pin);
    }
}