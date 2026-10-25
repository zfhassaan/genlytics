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
     * @param string $type Report type (report, realtime, dimension, batch)
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
     * Get cache lifetime in seconds for a specific report type or default
     *
     * @param string|null $type Report type (report, realtime, dimension, batch)
     * @return int
     */
    public function getLifetime(?string $type = null): int;

    /**
     * Set cache lifetime in seconds for a specific report type or default
     *
     * @param int $seconds
     * @param string|null $type
     * @return self
     */
    public function setLifetime(int $seconds, ?string $type = null): self;

    /**
     * Set default cache lifetime in seconds
     *
     * @param int $seconds
     * @return self
     */
    public function setDefaultLifetime(int $seconds): self;

    /**
     * Get all configured cache lifetimes in seconds
     *
     * @return array<string, int>
     */
    public function getLifetimes(): array;

    /**
     * Acquire an atomic distributed lock for stampede prevention
     *
     * @param string $key Cache key
     * @param int $seconds Lock duration in seconds
     * @return mixed An atomic lock instance or null if not supported
     */
    public function lock(string $key, int $seconds = 60);
}
