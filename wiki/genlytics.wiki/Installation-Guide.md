# Installation Guide

This guide will walk you through installing and setting up Genlytics in your Laravel application.

## Prerequisites

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Composer
- Google Analytics 4 (GA4) property
- Google Cloud Platform (GCP) project with Analytics Data API enabled
- Service account with Analytics access

## Step 1: Install via Composer

```bash
composer require zfhassaan/genlytics
```

## Step 2: Configure Google Analytics

### 2.1 Create GA4 Property

1. Go to [Google Analytics](https://analytics.google.com/)
2. Create a new GA4 property (if you don't have one)
3. Note your Property ID (numeric ID, not the full "properties/XXXXX" format)

### 2.2 Enable Analytics Data API

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select or create a project
3. Enable the **Analytics Data API**:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Analytics Data API"
   - Click "Enable"

### 2.3 Create Service Account

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "Service Account"
3. Fill in the details:
   - **Name**: `genlytics-service-account` (or your preferred name)
   - **Description**: Service account for Genlytics package
4. Click "Create and Continue"
5. Skip role assignment (optional)
6. Click "Done"

### 2.4 Generate Service Account Key

1. Click on the created service account
2. Go to "Keys" tab
3. Click "Add Key" > "Create new key"
4. Select "JSON" format
5. Click "Create"
6. Save the downloaded JSON file securely

### 2.5 Grant Analytics Access

1. Copy the service account email (e.g., `genlytics@project-id.iam.gserviceaccount.com`)
2. Go to [Google Analytics](https://analytics.google.com/)
3. Navigate to your GA4 property
4. Go to "Admin" > "Property Access Management"
5. Click "+" to add a user
6. Paste the service account email
7. Grant **Viewer** or **Analyst** role (Read & Analyze permissions)
8. Click "Add"

## Step 3: Configure Laravel

### 3.1 Publish Configuration

```bash
php artisan vendor:publish --provider="zfhassaan\genlytics\provider\AnalyticsServiceProvider" --tag="config"
```

This will create `config/analytics.php` in your Laravel project.

### 3.2 Add Environment Variables

Add to your `.env` file:

```env
# Google Analytics Configuration
GENLYTICS_PROPERTY_ID=your-property-id-here
GENLYTICS_CREDENTIALS=/path/to/service-account.json

# Optional: Cache Configuration
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=1440

# Optional: Background Jobs
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_QUEUE_CONNECTION=redis
GENLYTICS_QUEUE_NAME=default

# Optional: Real-Time Updates
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=30
```

### 3.3 Store Service Account File

**Option 1: Project Root (Recommended for Development)**

```bash
# Place the JSON file in your project root
cp ~/Downloads/service-account.json ./service-account.json

# Add to .gitignore
echo "service-account.json" >> .gitignore
```

Update `.env`:
```env
GENLYTICS_CREDENTIALS=service-account.json
```

**Option 2: Storage Directory (Recommended for Production)**

```bash
# Create directory
mkdir -p storage/app/analytics

# Move file
cp ~/Downloads/service-account.json storage/app/analytics/service-account.json
```

Update `.env`:
```env
GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json
```

**Option 3: Environment Variable (Most Secure)**

```env
GENLYTICS_CREDENTIALS={"type":"service_account","project_id":"..."}
```

Store the entire JSON content as an environment variable (use single quotes in .env).

## Step 4: Configure Queue (Optional but Recommended)

For background job processing, configure your queue:

### 4.1 Update .env

```env
QUEUE_CONNECTION=redis
# or
QUEUE_CONNECTION=database
```

### 4.2 For Database Queue

```bash
php artisan queue:table
php artisan migrate
```

### 4.3 Start Queue Worker

```bash
php artisan queue:work
```

Or use a process manager like Supervisor for production.

## Step 5: Verify Installation

### 5.1 Test Configuration

Create a test route or use Tinker:

```php
php artisan tinker
```

```php
$analytics = app('genlytics');
$result = $analytics->runReports(
    ['start_date' => '7daysAgo', 'end_date' => 'today'],
    [['name' => 'country']],
    [['name' => 'activeUsers']]
);
dd($result);
```

### 5.2 Check Service Provider

Verify the service provider is registered in `config/app.php`:

```php
'providers' => [
    // ...
    zfhassaan\genlytics\provider\AnalyticsServiceProvider::class,
],
```

Or ensure auto-discovery is enabled (default in Laravel 5.5+).

## Troubleshooting

### Service Account Not Found

**Error**: `Unable to read the credential file`

**Solution**:
- Verify the path in `.env` is correct
- Check file permissions
- Ensure the file exists at the specified path

### Permission Denied

**Error**: `User does not have sufficient permissions`

**Solution**:
- Verify service account email is added to GA4 property
- Check that the service account has "Viewer" or "Analyst" role
- Ensure Analytics Data API is enabled

### Property ID Invalid

**Error**: `Property not found`

**Solution**:
- Verify Property ID is numeric only (not "properties/XXXXX")
- Check that the property exists in your Google Analytics account
- Ensure the service account has access to the property

### Queue Not Working

**Error**: Jobs not processing

**Solution**:
- Ensure queue worker is running: `php artisan queue:work`
- Check queue connection in `.env`
- Verify queue driver is properly configured

## Next Steps

- [[Configuration-Guide|Configure Genlytics]]
- [[Usage-Guide|Learn how to use Genlytics]]
- [[Troubleshooting|Troubleshooting Guide]]

