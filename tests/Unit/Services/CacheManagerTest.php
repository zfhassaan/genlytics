<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use zfhassaan\genlytics\Services\CacheManager;
use zfhassaan\genlytics\Tests\TestCase;

class CacheManagerTest extends TestCase
{
    protected CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheManager = new CacheManager('genlytics_test', 60);
        Cache::flush();
    }

    public function test_can_put_and_get_cache()
    {
        $key = 'test-key';
        $value = ['data' => 'test'];

        $this->cacheManager->put($key, $value);

        $this->assertEquals($value, $this->cacheManager->get($key));
    }

    public function test_can_check_if_cache_exists()
    {
        $key = 'test-key';
        $value = ['data' => 'test'];

        $this->assertFalse($this->cacheManager->has($key));

        $this->cacheManager->put($key, $value);

        $this->assertTrue($this->cacheManager->has($key));
    }

    public function test_can_forget_cache()
    {
        $key = 'test-key';
        $value = ['data' => 'test'];

        $this->cacheManager->put($key, $value);
        $this->assertTrue($this->cacheManager->has($key));

        $this->cacheManager->forget($key);

        $this->assertFalse($this->cacheManager->has($key));
    }

    public function test_can_generate_cache_key()
    {
        $type = 'report';
        $params = [
            'dateRange' => ['start_date' => '7daysAgo', 'end_date' => 'today'],
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
        ];

        $key = $this->cacheManager->generateKey($type, $params);

        $this->assertIsString($key);
        $this->assertStringStartsWith('report:', $key);
        $this->assertNotEmpty($key);
    }

    public function test_generate_key_is_consistent()
    {
        $type = 'report';
        $params = [
            'dateRange' => ['start_date' => '7daysAgo', 'end_date' => 'today'],
            'dimensions' => [['name' => 'country']],
        ];

        $key1 = $this->cacheManager->generateKey($type, $params);
        $key2 = $this->cacheManager->generateKey($type, $params);

        $this->assertEquals($key1, $key2);
    }

    public function test_can_get_lifetime()
    {
        $this->assertEquals(60, $this->cacheManager->getLifetime());
    }

    public function test_can_put_with_custom_ttl()
    {
        $key = 'test-key';
        $value = ['data' => 'test'];
        $ttl = 120;

        $this->cacheManager->put($key, $value, $ttl);

        $this->assertEquals($value, $this->cacheManager->get($key));
    }

    public function test_clear_cache()
    {
        $this->cacheManager->put('key1', 'value1');
        $this->cacheManager->put('key2', 'value2');

        $result = $this->cacheManager->clear();

        $this->assertTrue($result);
    }
}
