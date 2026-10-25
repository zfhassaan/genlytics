<?php

return [

    /*
     * Genlytics package version
     */
    'version' => env('GENLYTICS_VERSION', '1.0.5'),

    /*
     * The property ID of your Google Analytics 4 property.
     * This should be just the numeric ID, not the full "properties/XXXXX" format.
     */
    'property_id' => env('GENLYTICS_PROPERTY_ID'),

    /*
     * Path to the client secret JSON file or JSON array.
     */
    'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),

    /*
     * The transport used for executing network requests ('rest' or 'grpc').
     * If gRPC extension is installed, 'grpc' can yield up to 5x-10x performance improvements.
     * Defaults to 'rest' for universal compatibility.
     */
    'transport' => env('GENLYTICS_TRANSPORT', 'rest'),

    /*
     * Cache Configuration
     */
    'cache' => [
        'enabled' => env('GENLYTICS_ENABLE_CACHE', true),

        /*
         * Cache store to use (e.g. 'redis', 'memcached', 'file', or null for default store).
         */
        'store' => env('GENLYTICS_CACHE_STORE', null),

        /*
         * Cache key prefix to prevent collision.
         */
        'prefix' => env('GENLYTICS_CACHE_PREFIX', 'genlytics'),

        /*
         * Enable cache tagging if the cache store supports tags (e.g., Redis, Memcached).
         */
        'use_tags' => env('GENLYTICS_CACHE_USE_TAGS', true),

        /*
         * Global default cache expiry in seconds (default: 86400 = 24 hours).
         * Used whenever a specific report type TTL is not defined.
         */
        'default_ttl' => (int) env('GENLYTICS_CACHE_TTL', 86400),

        /*
         * Granular cache TTLs in seconds per report type.
         */
        'ttl' => [
            'report'    => (int) env('GENLYTICS_CACHE_TTL_REPORT', 86400),       // 24 hours
            'realtime'  => (int) env('GENLYTICS_CACHE_TTL_REALTIME', 60),        // 1 minute
            'dimension' => (int) env('GENLYTICS_CACHE_TTL_DIMENSION', 86400),    // 24 hours
            'batch'     => (int) env('GENLYTICS_CACHE_TTL_BATCH', 86400),        // 24 hours
            'funnel'    => (int) env('GENLYTICS_CACHE_TTL_FUNNEL', 43200),       // 12 hours
            'ecommerce' => (int) env('GENLYTICS_CACHE_TTL_ECOMMERCE', 43200),    // 12 hours
        ],

        /*
         * Atomic distributed lock duration in seconds to prevent queue stampedes.
         */
        'lock_duration' => env('GENLYTICS_CACHE_LOCK_DURATION', 60),
    ],

    /*
     * Queue & Background Job Configuration
     */
    'queue' => [
        'enabled' => env('GENLYTICS_USE_BACKGROUND_JOBS', false),
        'connection' => env('GENLYTICS_QUEUE_CONNECTION', null),
        'name' => env('GENLYTICS_QUEUE_NAME', 'analytics'),
        'unique_for' => env('GENLYTICS_JOB_UNIQUE_FOR', 120),
    ],

    /*
     * Google Analytics 4 (GA4) Data API Limits & Maximum Resource Utilization
     */
    'quota' => [
        /*
         * Operational Mode:
         * - 'maximum_utilization': Consumes up to 100% of tokens without premature throttling.
         * - 'balanced' (default): Sustains steady-state pacing, protects tokens at 85% warning threshold.
         * - 'conservative': Preserves tokens aggressively (70% warning threshold).
         */
        'mode' => env('GENLYTICS_QUOTA_MODE', 'balanced'),

        /*
         * Property type: 'standard' (200,000 tokens/day) or '360' (2,000,000 tokens/day)
         */
        'property_type' => env('GENLYTICS_PROPERTY_TYPE', 'standard'),

        /*
         * Core Tokens Per Property Per Day:
         * Standard Property: 200,000 tokens | Analytics 360 Property: 2,000,000 tokens
         */
        'daily_token_limit' => (int) env('GENLYTICS_DAILY_TOKEN_LIMIT', 200000),

        /*
         * Core Tokens Per Property Per Hour: 40,000 tokens
         */
        'hourly_token_limit' => (int) env('GENLYTICS_HOURLY_TOKEN_LIMIT', 40000),

        /*
         * Core Concurrent Requests Per Property: 10 requests maximum
         */
        'concurrent_requests_limit' => (int) env('GENLYTICS_CONCURRENT_LIMIT', 10),

        /*
         * Project-Level Quota: 50,000 requests per project per day
         */
        'project_daily_request_limit' => (int) env('GENLYTICS_PROJECT_DAILY_LIMIT', 50000),

        /*
         * IP-Level Quota: 10 queries per second (QPS) per IP address
         */
        'ip_qps_limit' => (int) env('GENLYTICS_IP_QPS_LIMIT', 10),

        /*
         * User-Level Quota: 100 requests per 100 seconds per user (adjustable up to 1,000)
         */
        'user_requests_per_100s' => (int) env('GENLYTICS_USER_100S_LIMIT', 100),

        /*
         * Reset Schedule Timezone:
         * All GA4 daily quotas refresh at midnight Pacific Standard Time (PST).
         */
        'timezone' => env('GENLYTICS_QUOTA_TIMEZONE', 'America/Los_Angeles'),

        /*
         * Adaptive Pacing & Warning Threshold:
         * Triggers automatic SWR cache fallback when remaining daily quota reaches this threshold.
         */
        'warning_threshold' => (float) env('GENLYTICS_QUOTA_WARNING_THRESHOLD', 0.85),

        /*
         * Strategy when quota is exhausted:
         * 'cache_fallback' - Return stale cache if available, or throw QuotaExceededException.
         * 'strict'         - Immediately throw QuotaExceededException.
         */
        'exhaustion_strategy' => env('GENLYTICS_QUOTA_STRATEGY', 'cache_fallback'),

        /*
         * Option to Consume All Daily Quota (100% Extraction):
         * When enabled, allows consuming all 200,000 (standard) or 2,000,000 (360) daily tokens
         * without premature stale-cache throttling.
         */
        'consume_all_daily_quota' => env('GENLYTICS_CONSUME_ALL_QUOTA', false),

        /*
         * Real-time Quota Telemetry & WebSocket Broadcasting:
         * Automatically dispatches QuotaUsageUpdated event on each query execution.
         */
        'realtime_broadcasting' => env('GENLYTICS_REALTIME_QUOTA_BROADCAST', true),
        'broadcast_channel'     => env('GENLYTICS_QUOTA_CHANNEL', 'genlytics-quota'),
    ],

    /*
     * Backward-compatibility mappings
     */
    'cache_lifetime_in_minutes' => env('GENLYTICS_CACHE_LIFETIME', 60 * 24),
    'enable_cache'              => env('GENLYTICS_ENABLE_CACHE', true),
    'use_background_jobs'       => env('GENLYTICS_USE_BACKGROUND_JOBS', false),
    'enable_realtime_updates'   => env('GENLYTICS_ENABLE_REALTIME_UPDATES', false),
    'realtime_cache_lifetime'   => env('GENLYTICS_REALTIME_CACHE_LIFETIME', 30),
    'queue_connection'          => env('GENLYTICS_QUEUE_CONNECTION', null),
    'queue_name'                => env('GENLYTICS_QUEUE_NAME', 'analytics'),

];
