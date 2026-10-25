<?php

namespace zfhassaan\genlytics\Services;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

/**
 * Cache Manager Implementation
 * Following Single Responsibility Principle
 */
class CacheManager implements CacheManagerInterface
{
    protected string $prefix;
    protected array $ttls;
    protected ?string $store;
    protected bool $useTags;

    /**
     * @param string $prefix Cache key prefix
     * @param int|array $ttl Default lifetime in seconds or array of TTLs per report type
     * @param string|null $store Cache store name (e.g., 'redis', 'memcached', null)
     * @param bool $useTags Whether to use cache tags if supported by driver
     */
    public function __construct(
        string $prefix = 'genlytics',
        int|array $ttl = 86400,
        ?string $store = null,
        bool $useTags = true
    ) {
        $this->prefix = $prefix;
        $this->store = $store;
        $this->useTags = $useTags;

        if (is_array($ttl)) {
            $this->ttls = array_merge([
                'default'   => 86400,
                'report'    => 86400,
                'realtime'  => 60,
                'dimension' => 86400,
                'batch'     => 86400,
            ], $ttl);
        } else {
            $this->ttls = [
                'default'   => $ttl,
                'report'    => $ttl,
                'realtime'  => min($ttl, 60),
                'dimension' => $ttl,
                'batch'     => $ttl,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key)
    {
        return $this->storeInstance()->get($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $lifetime = $ttl ?? $this->getLifetime();

        return (bool) $this->storeInstance()->put(
            $this->prefixedKey($key),
            $value,
            $lifetime
        );
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->storeInstance()->has($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): bool
    {
        return (bool) $this->storeInstance()->forget($this->prefixedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function generateKey(string $type, array $params): string
    {
        // Deep sort params for deterministic key generation
        $this->sortRecursive($params);

        $hash = md5(json_encode($params));

        return "{$type}:" . $hash;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $rawStore = $this->rawStore();

        // If driver supports tags, flush only the genlytics tag
        if ($this->useTags && $this->supportsTags($rawStore)) {
            return $rawStore->tags([$this->prefix])->flush();
        }

        // Otherwise, if dedicated store was configured, flush that store
        if (!empty($this->store)) {
            return $rawStore->clear();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getLifetime(?string $type = null): int
    {
        if ($type !== null && isset($this->ttls[$type])) {
            return (int) $this->ttls[$type];
        }

        return (int) ($this->ttls['default'] ?? 86400);
    }

    /**
     * {@inheritDoc}
     */
    public function setLifetime(int $seconds, ?string $type = null): self
    {
        if ($type === null) {
            $this->ttls['default'] = $seconds;
        } else {
            $this->ttls[$type] = $seconds;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultLifetime(int $seconds): self
    {
        return $this->setLifetime($seconds, null);
    }

    /**
     * {@inheritDoc}
     */
    public function getLifetimes(): array
    {
        return $this->ttls;
    }

    /**
     * {@inheritDoc}
     */
    public function lock(string $key, int $seconds = 60)
    {
        $rawStore = $this->rawStore();
        $lockKey = $this->prefixedKey("lock:{$key}");

        if (method_exists($rawStore, 'lock')) {
            return $rawStore->lock($lockKey, $seconds);
        }

        if (method_exists(Cache::getFacadeRoot(), 'lock')) {
            return Cache::lock($lockKey, $seconds);
        }

        return null;
    }

    /**
     * Get the cache store instance (tagged if supported)
     */
    protected function storeInstance(): CacheRepository
    {
        $store = $this->rawStore();

        if ($this->useTags && $this->supportsTags($store)) {
            return $store->tags([$this->prefix]);
        }

        return $store;
    }

    /**
     * Get the raw underlying cache repository
     */
    protected function rawStore(): CacheRepository
    {
        return $this->store ? Cache::store($this->store) : Cache::store();
    }

    /**
     * Check if cache store supports tags
     */
    protected function supportsTags(CacheRepository $store): bool
    {
        try {
            return method_exists($store, 'tags');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Prefix a cache key
     */
    protected function prefixedKey(string $key): string
    {
        // Avoid double prefixing if key already contains it
        if (str_starts_with($key, "{$this->prefix}:")) {
            return $key;
        }

        return "{$this->prefix}:{$key}";
    }

    /**
     * Deep sort array keys recursively for consistent hashing
     */
    protected function sortRecursive(array &$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
        ksort($array);
    }
}
