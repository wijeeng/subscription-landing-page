<?php

require_once __DIR__ . '/../../src/helpers/Validator.php';

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidMsisdn(): void
    {
        $this->assertTrue(
            Validator::isValidMsisdnNumber([
                'msisdn' => '96551234567'
            ])
        );

        $this->assertTrue(
            Validator::isValidMsisdnNumber([
                'msisdn' => '51234567'
            ])
        );

        $this->assertTrue(
            Validator::isValidMsisdnNumber([
                'msisdn' => '051234567'
            ])
        );
    }

    public function testInvalidMsisdn(): void
    {
        $this->assertFalse(
            Validator::isValidMsisdnNumber([
                'msisdn' => ''
            ])
        );

        $this->assertFalse(
            Validator::isValidMsisdnNumber([
                'msisdn' => '123'
            ])
        );

        $this->assertFalse(
            Validator::isValidMsisdnNumber([
                'msisdn' => 'abcd'
            ])
        );
    }

    public function testNormalizeMsisdn(): void
    {
        $this->assertEquals(
            '96551234567',
            Validator::normalizeMsisdn('51234567')
        );

        $this->assertEquals(
            '96551234567',
            Validator::normalizeMsisdn('051234567')
        );

        $this->assertEquals(
            '96551234567',
            Validator::normalizeMsisdn('96551234567')
        );
    }

    public function testValidPin(): void
    {
        $this->assertTrue(
            Validator::isValidPin([
                'pin' => '1234'
            ])
        );
    }

    public function testInvalidPin(): void
    {
        $this->assertFalse(
            Validator::isValidPin([
                'pin' => ''
            ])
        );

        $this->assertFalse(
            Validator::isValidPin([
                'pin' => '12ab'
            ])
        );
    }
}