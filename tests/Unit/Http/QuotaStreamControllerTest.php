<?php

namespace zfhassaan\genlytics\Tests\Unit\Http;

use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Http\Controllers\QuotaStreamController;
use zfhassaan\genlytics\Tests\TestCase;

class QuotaStreamControllerTest extends TestCase
{
    protected $mockQuota;
    protected QuotaStreamController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $this->controller = new QuotaStreamController($this->mockQuota);
    }

    public function test_status_endpoint_returns_json_resource()
    {
        $dto = new QuotaStatusDTO([
            'property_type' => 'standard',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 45000,
            'hourly_tokens_limit' => 40000,
            'hourly_tokens_consumed' => 8000,
            'concurrent_requests_active' => 3,
            'concurrent_requests_limit' => 10,
            'seconds_until_daily_reset' => 36000,
            'daily_reset_at_pst' => '00:00:00 PST',
        ]);

        $this->mockQuota->shouldReceive('getStatus')->once()->andReturn($dto);

        $response = $this->controller->status(new Request());
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('standard', $data['property_type']);
        $this->assertEquals(45000, $data['daily_tokens']['consumed']);
        $this->assertEquals(200000, $data['daily_tokens']['limit']);
        $this->assertEquals('consume_all', $data['pacing']['mode']);
        $this->assertTrue($data['pacing']['consume_all_enabled']);
    }

    public function test_stream_endpoint_returns_event_stream_headers()
    {
        $response = $this->controller->stream(new Request());

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertIsCallable($response->getCallback());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
