# Troubleshooting Guide

Common issues and solutions when using Genlytics.

## Installation Issues

### Composer Installation Fails

**Error**: `Could not find package zfhassaan/genlytics`

**Solutions**:
1. Check package name spelling
2. Ensure you're using the correct repository
3. Clear Composer cache: `composer clear-cache`
4. Update Composer: `composer self-update`

### Service Provider Not Found

**Error**: `Class 'zfhassaan\genlytics\provider\AnalyticsServiceProvider' not found`

**Solutions**:
1. Run `composer dump-autoload`
2. Clear Laravel cache: `php artisan config:clear`
3. Verify package is installed: `composer show zfhassaan/genlytics`

## Configuration Issues

### Property ID Not Found

**Error**: `Property not found` or `Invalid property ID`

**Solutions**:
1. Verify Property ID is numeric only (not "properties/XXXXX")
2. Check `.env` file has correct value:
   ```env
   GENLYTICS_PROPERTY_ID=123456789
   ```
3. Ensure property exists in Google Analytics
4. Verify service account has access to the property

### Credentials File Not Found

**Error**: `Unable to read the credential file`

**Solutions**:
1. Check file path in `.env`:
   ```env
   GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json
   ```
2. Verify file exists at the specified path
3. Check file permissions (should be readable)
4. Use absolute path if relative path doesn't work:
   ```env
   GENLYTICS_CREDENTIALS=/full/path/to/service-account.json
   ```

### Permission Denied

**Error**: `User does not have sufficient permissions` or `403 Forbidden`

**Solutions**:
1. Verify service account email is added to GA4 property
2. Check service account has "Viewer" or "Analyst" role
3. Ensure Analytics Data API is enabled in GCP
4. Wait a few minutes after granting permissions (propagation delay)

## API Issues

### Quota Exceeded

**Error**: `Quota exceeded` or `429 Too Many Requests`

**Solutions**:
1. Enable caching: `GENLYTICS_ENABLE_CACHE=true`
2. Increase cache lifetime: `GENLYTICS_CACHE_LIFETIME=1440`
3. Use background jobs to spread requests
4. Check Google Analytics API quotas in GCP Console
5. Request quota increase if needed

### Invalid Request

**Error**: `Invalid request` or `400 Bad Request`

**Solutions**:
1. Verify date format: `'7daysAgo'`, `'today'`, or `'YYYY-MM-DD'`
2. Check dimension/metric names are valid GA4 dimensions/metrics
3. Ensure dimensions and metrics are compatible
4. Review GA4 documentation for valid combinations

### Timeout Errors

**Error**: `Request timeout` or `504 Gateway Timeout`

**Solutions**:
1. Reduce date range
2. Limit number of dimensions/metrics
3. Use background jobs for long-running queries
4. Increase PHP timeout: `set_time_limit(300)`

## Cache Issues

### Cache Not Working

**Symptoms**: Always fetching from API, cache not being used

**Solutions**:
1. Verify cache is enabled: `GENLYTICS_ENABLE_CACHE=true`
2. Check Laravel cache driver is configured
3. Clear Laravel cache: `php artisan cache:clear`
4. Verify cache store is accessible
5. Check cache permissions

### Stale Data

**Symptoms**: Data not updating, showing old results

**Solutions**:
1. Clear analytics cache: `php artisan genlytics:refresh-cache --clear`
2. Use force refresh: `$analytics->runReports(..., true)`
3. Reduce cache lifetime: `GENLYTICS_CACHE_LIFETIME=60`
4. Check cache store is working properly

### Cache Key Conflicts

**Symptoms**: Wrong data returned for different queries

**Solutions**:
1. Clear all cache: `php artisan genlytics:refresh-cache --clear`
2. Verify cache key generation is working
3. Check for cache store issues

## Queue Issues

### Jobs Not Processing

**Symptoms**: Background jobs not running, data not updating

**Solutions**:
1. Verify queue worker is running: `php artisan queue:work`
2. Check queue connection in `.env`: `QUEUE_CONNECTION=redis`
3. Verify queue driver is configured correctly
4. Check queue logs for errors
5. Restart queue worker

### Jobs Failing

**Symptoms**: Jobs in failed state, errors in logs

**Solutions**:
1. Check job logs: `php artisan queue:failed`
2. Review exception messages
3. Verify credentials are correct
4. Check API quotas
5. Retry failed jobs: `php artisan queue:retry all`

### Queue Connection Error

**Error**: `Connection refused` or `Could not connect to queue`

**Solutions**:
1. Verify Redis/queue service is running
2. Check connection configuration
3. Test connection: `php artisan tinker` then `Queue::size()`
4. Use database queue as fallback

## Real-Time Issues

### Real-Time Data Not Updating

**Symptoms**: Real-time data showing same values

**Solutions**:
1. Verify real-time updates enabled: `GENLYTICS_ENABLE_REALTIME_UPDATES=true`
2. Check queue worker is running
3. Reduce real-time cache lifetime: `GENLYTICS_REALTIME_CACHE_LIFETIME=15`
4. Check event listeners are registered

### Real-Time API Errors

**Error**: Real-time API specific errors

**Solutions**:
1. Real-time API has different quotas, check usage
2. Some dimensions/metrics not available in real-time
3. Verify property has real-time data available
4. Check GA4 real-time API documentation

## Data Issues

### No Data Returned

**Symptoms**: Empty results, no rows

**Solutions**:
1. Verify date range has data
2. Check property has analytics data
3. Verify dimensions/metrics are valid
4. Try different date ranges
5. Check if filters are too restrictive

### Incorrect Data

**Symptoms**: Data doesn't match Google Analytics dashboard

**Solutions**:
1. Verify date ranges match (timezone differences)
2. Check for filters applied in dashboard
3. Ensure using same property ID
4. Compare dimension/metric combinations
5. Account for data processing delays (24-48 hours)

### Data Transformation Errors

**Error**: `Undefined index` or transformation errors

**Solutions**:
1. Update to latest package version
2. Check data structure matches expectations
3. Verify dimensions/metrics are returned
4. Review error logs for details

## Performance Issues

### Slow Response Times

**Symptoms**: Requests taking too long

**Solutions**:
1. Enable caching: `GENLYTICS_ENABLE_CACHE=true`
2. Use background jobs: `GENLYTICS_USE_BACKGROUND_JOBS=true`
3. Reduce date range
4. Limit dimensions/metrics
5. Use Redis cache store
6. Optimize queries

### High Memory Usage

**Symptoms**: Memory errors, high memory consumption

**Solutions**:
1. Process data in chunks
2. Limit result sets: `'limit' => 100`
3. Use streaming for large datasets
4. Increase PHP memory limit if needed
5. Use background jobs for large queries

## Debugging

### Enable Debug Mode

Add to `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Logs

```bash
tail -f storage/logs/laravel.log
```

### Use Tinker

```bash
php artisan tinker
```

```php
$analytics = app('genlytics');
$result = $analytics->runReports(...);
dd($result);
```

### Check Configuration

```php
php artisan config:show analytics
```

### Verify Service Registration

```php
php artisan tinker
app('genlytics'); // Should return Genlytics instance
```

## Getting Help

If you're still experiencing issues:

1. Check [GitHub Issues](https://github.com/zfhassaan/genlytics/issues)
2. Review [Usage Guide](Usage-Guide.md)
3. Check [Configuration Guide](Configuration-Guide.md)
4. Create a new issue with:
   - Error message
   - Configuration (without sensitive data)
   - Steps to reproduce
   - Laravel/PHP versions

## Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 400 | Bad Request | Check request parameters |
| 401 | Unauthorized | Verify credentials |
| 403 | Forbidden | Check permissions |
| 404 | Not Found | Verify property ID |
| 429 | Too Many Requests | Enable caching, reduce requests |
| 500 | Server Error | Check Google Analytics API status |
| 503 | Service Unavailable | API temporarily unavailable |

