<?php

namespace zfhassaan\genlytics\Tests\Unit\Exceptions;

use zfhassaan\genlytics\Exceptions\QuotaExceededException;
use zfhassaan\genlytics\Exceptions\QuotaValidationException;
use zfhassaan\genlytics\Tests\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_quota_exceeded_exception_properties_and_defaults()
    {
        $ex = new QuotaExceededException(
            quotaType: 'tokens_per_day',
            limit: 200000,
            consumed: 200005,
            resetInSeconds: 3600
        );

        $this->assertEquals('tokens_per_day', $ex->getQuotaType());
        $this->assertEquals(200000, $ex->getLimit());
        $this->assertEquals(200005, $ex->getConsumed());
        $this->assertEquals(3600, $ex->getResetInSeconds());
        $this->assertEquals(429, $ex->getCode());
        $this->assertStringContainsString('tokens_per_day', $ex->getMessage());
        $this->assertStringContainsString('200005', $ex->getMessage());

        $custom = new QuotaExceededException('hourly', 40000, 40001, 60, 'Custom message');
        $this->assertEquals('Custom message', $custom->getMessage());
    }

    public function test_quota_validation_exception_properties_and_defaults()
    {
        $ex = new QuotaValidationException(
            field: 'daily_limit',
            invalidValue: -50,
            expected: 'positive integer > 0'
        );

        $this->assertEquals('daily_limit', $ex->getField());
        $this->assertEquals(-50, $ex->getInvalidValue());
        $this->assertEquals('positive integer > 0', $ex->getExpected());
        $this->assertEquals(422, $ex->getCode());
        $this->assertStringContainsString('daily_limit', $ex->getMessage());
        $this->assertStringContainsString('-50', $ex->getMessage());

        $custom = new QuotaValidationException('mode', 'foo', ['balanced', 'consume_all'], 'Custom validation error');
        $this->assertEquals('Custom validation error', $custom->getMessage());
    }
}

