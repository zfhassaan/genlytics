<?php

return [

    /*
     * The property ID of your Google Analytics 4 property.
     * This should be just the numeric ID, not the full "properties/XXXXX" format.
     */
    'property_id' => env('GENLYTICS_PROPERTY_ID'),

    /*
     * Path to the client secret JSON file.
     * Take a look at the README of this package to learn how to get this file.
     * You can also pass the credentials as an array instead of a file path.
     */
    'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),

    /*
     * The amount of minutes the Google API responses will be cached.
     * If you set this to zero, the responses won't be cached at all.
     * Default: 1440 minutes (24 hours)
     */
    'cache_lifetime_in_minutes' => env('GENLYTICS_CACHE_LIFETIME', 60 * 24),

    /*
     * Enable or disable caching of analytics data.
     * When disabled, all requests will hit the Google Analytics API directly.
     */
    'enable_cache' => env('GENLYTICS_ENABLE_CACHE', true),

    /*
     * Enable or disable background job processing.
     * When enabled, analytics queries will be processed in the background
     * and cached results will be returned immediately when available.
     * Requires Laravel queue to be configured.
     */
    'use_background_jobs' => env('GENLYTICS_USE_BACKGROUND_JOBS', true),

    /*
     * Enable or disable real-time updates.
     * When enabled, real-time analytics data will be automatically refreshed
     * in the background at regular intervals.
     */
    'enable_realtime_updates' => env('GENLYTICS_ENABLE_REALTIME_UPDATES', true),

    /*
     * Real-time cache lifetime in seconds.
     * Real-time data is cached for a shorter duration than regular reports.
     * Default: 30 seconds
     */
    'realtime_cache_lifetime' => env('GENLYTICS_REALTIME_CACHE_LIFETIME', 30),

    /*
     * Here you may configure the "store" that the underlying Google_Client will
     * use to store it's data. You may also add extra parameters that will
     * be passed on setCacheConfig (see docs for google-api-php-client).
     *
     * Optional parameters: "lifetime", "prefix"
     */
    'cache' => [
        'store' => env('GENLYTICS_CACHE_STORE', 'file'),
    ],

    /*
     * Queue connection to use for background jobs.
     * If null, uses the default queue connection.
     */
    'queue_connection' => env('GENLYTICS_QUEUE_CONNECTION', null),

    /*
     * Queue name for analytics jobs.
     */
    'queue_name' => env('GENLYTICS_QUEUE_NAME', 'default'),

];
