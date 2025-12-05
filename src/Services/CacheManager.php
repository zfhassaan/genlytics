<?php

namespace zfhassaan\genlytics\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

/**
 * Cache Manager Implementation
 * Following Single Responsibility Principle
 */
class CacheManager implements CacheManagerInterface
{
    protected string $prefix;
    protected int $lifetime;

    /**
     * @param string $prefix Cache key prefix
     * @param int $lifetime Default cache lifetime in seconds
     */
    public function __construct(string $prefix = 'genlytics', int $lifetime = 86400)
    {
        $this->prefix = $prefix;
        $this->lifetime = $lifetime;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key)
    {
        return Cache::get($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return Cache::put(
            $this->prefixedKey($key),
            $value,
            $ttl ?? $this->lifetime
        );
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return Cache::has($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): bool
    {
        return Cache::forget($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function generateKey(string $type, array $params): string
    {
        // Sort params for consistent key generation
        ksort($params);
        
        // Create a hash of the parameters
        $hash = md5(json_encode($params));
        
        return "{$type}:" . $hash;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        // Clear all cache entries with our prefix
        // Note: This is a simplified implementation
        // In production, you might want to use cache tags if supported
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Get prefixed cache key
     *
     * @param string $key
     * @return string
     */
    protected function prefixedKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }
}

