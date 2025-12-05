<?php

namespace zfhassaan\genlytics\Contracts;

/**
 * Interface for Cache Management
 * Following Single Responsibility and Dependency Inversion Principles
 */
interface CacheManagerInterface
{
    /**
     * Get cached analytics data
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found
     */
    public function get(string $key);

    /**
     * Store analytics data in cache
     *
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int|null $ttl Time to live in seconds (null uses default)
     * @return bool Success status
     */
    public function put(string $key, $value, ?int $ttl = null): bool;

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove cached data
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function forget(string $key): bool;

    /**
     * Generate cache key from parameters
     *
     * @param string $type Report type (report, realtime, dimension)
     * @param array $params Parameters to include in key
     * @return string Generated cache key
     */
    public function generateKey(string $type, array $params): string;

    /**
     * Clear all analytics cache
     *
     * @return bool Success status
     */
    public function clear(): bool;

    /**
     * Get cache lifetime in seconds
     *
     * @return int
     */
    public function getLifetime(): int;
}

