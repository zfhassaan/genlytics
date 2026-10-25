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

    public function test_can_set_and_get_configurable_lifetime_per_type()
    {
        $this->cacheManager->setLifetime(3600, 'report');
        $this->cacheManager->setLifetime(120, 'realtime');
        $this->cacheManager->setLifetime(7200, 'funnel');

        $this->assertEquals(3600, $this->cacheManager->getLifetime('report'));
        $this->assertEquals(120, $this->cacheManager->getLifetime('realtime'));
        $this->assertEquals(7200, $this->cacheManager->getLifetime('funnel'));
        $this->assertEquals(60, $this->cacheManager->getLifetime('unknown_type'));
    }

    public function test_can_set_and_get_default_lifetime()
    {
        $this->cacheManager->setDefaultLifetime(1800);
        $this->assertEquals(1800, $this->cacheManager->getLifetime());
        $this->assertEquals(1800, $this->cacheManager->getLifetime('unregistered_type'));
    }

    public function test_can_get_all_lifetimes()
    {
        $this->cacheManager->setLifetime(3600, 'report');
        $lifetimes = $this->cacheManager->getLifetimes();

        $this->assertIsArray($lifetimes);
        $this->assertEquals(3600, $lifetimes['report']);
        $this->assertEquals(60, $lifetimes['default']);
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

    public function test_clear_cache_with_dedicated_store_without_tags()
    {
        $dedicatedManager = new CacheManager('genlytics_test', 60, 'array', false);
        $dedicatedManager->put('key1', 'value1');

        $result = $dedicatedManager->clear();
        $this->assertTrue($result);
    }

    public function test_cache_manager_lock()
    {
        $lock = $this->cacheManager->lock('test-lock', 10);
        // On ArrayStore or null
        $this->assertTrue($lock !== false);
    }

    public function test_cache_manager_handles_already_prefixed_keys()
    {
        $key = 'genlytics_test:already_prefixed';
        $this->cacheManager->put($key, 'val');
        $this->assertEquals('val', $this->cacheManager->get($key));
    }

    public function test_cache_manager_constructor_with_ttl_array()
    {
        $manager = new CacheManager('genlytics_test', ['default' => 500, 'pages' => 900]);
        $this->assertEquals(500, $manager->getLifetime());
        $this->assertEquals(900, $manager->getLifetime('pages'));
    }
}
