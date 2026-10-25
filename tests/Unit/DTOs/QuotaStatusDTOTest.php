<?php

namespace zfhassaan\genlytics\Tests\Unit\DTOs;

use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Http\Resources\QuotaStatusResource;
use zfhassaan\genlytics\Tests\TestCase;

class QuotaStatusDTOTest extends TestCase
{
    public function test_can_instantiate_quota_status_dto_and_resource()
    {
        $dto = new QuotaStatusDTO([
            'property_type' => 'standard',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 50000,
            'hourly_tokens_limit' => 40000,
            'hourly_tokens_consumed' => 8000,
            'concurrent_requests_active' => 3,
            'concurrent_requests_limit' => 10,
            'daily_project_requests_consumed' => 1200,
            'daily_project_requests_limit' => 50000,
            'seconds_until_daily_reset' => 14400,
            'daily_reset_at_pst' => '00:00:00 PST',
            'is_throttled' => false,
            'pacing_status' => 'optimal',
        ]);

        $this->assertEquals('standard', $dto->propertyType);
        $this->assertEquals(200000, $dto->dailyTokensLimit);
        $this->assertEquals(50000, $dto->dailyTokensConsumed);
        $this->assertEquals(150000, $dto->dailyTokensRemaining);
        $this->assertEquals(25.0, $dto->dailyUtilizationPercent);

        $this->assertEquals(40000, $dto->hourlyTokensLimit);
        $this->assertEquals(8000, $dto->hourlyTokensConsumed);
        $this->assertEquals(32000, $dto->hourlyTokensRemaining);
        $this->assertEquals(20.0, $dto->hourlyUtilizationPercent);

        $this->assertEquals(3, $dto->concurrentRequestsActive);
        $this->assertEquals(10, $dto->concurrentRequestsLimit);

        $array = $dto->toArray();
        $this->assertArrayHasKey('daily_tokens', $array);
        $this->assertArrayHasKey('hourly_tokens', $array);
        $this->assertArrayHasKey('concurrency', $array);
        $this->assertArrayHasKey('reset_schedule', $array);
        $this->assertEquals(200000, $array['daily_tokens']['limit']);

        $resource = new QuotaStatusResource($dto);
        $response = $resource->response()->getData(true);
        $this->assertEquals(200000, $response['data']['daily_tokens']['limit']);
        $this->assertEquals(150000, $response['data']['daily_tokens']['remaining']);
    }
}

