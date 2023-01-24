<!--suppress ALL -->
<p align="center">

  <!-- <h3 align="center">Payfast</h3> -->
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zfhassaan/genlytics.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/genlytics)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/zfhassaan/genlytics.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/genlytics)


### Disclaimer 
The google UA Property is going away from July 2023. Google is implementing the Google Analytics V4. 
_Universal Analytics will no longer process new data in standard properties beginning on 1 July 2023. 
Prepare now by setting up and switching over to a Google Analytics 4 property._
[LearnMore](https://support.google.com/analytics/answer/11583528?hl=en-GB&authuser=0)

The Google Analytics data will only be available through the GCP API 


### About

Genlytics is a powerful Google Analytics package for Laravel, designed to help businesses and developers easily track and analyze website traffic and user behavior. Our package integrates seamlessly with your Laravel application, allowing you to access all of the features and functionality of Google Analytics without the need for any additional code. With Genlytics, you can easily track page views, user sessions, and conversion rates, as well as gain valuable insights into your audience and website performance. Whether you're a small business looking to improve your online presence, or a developer looking to add analytics functionality to your Laravel app, Genlytics has everything you need to succeed.

### Intended Audience
This document is for developers who want to fetch Google Analytics Data into their Laravel website and
show the content on a dashboard.

### Query 
With Genlytics, you can easily access and query your Google Analytics data using the GA4 property. 
You can check your website traffic, user behavior, and conversion rates using the GA4 query explorer 
at https://ga-dev-tools.web.app/ga4/query-explorer/. This powerful tool allows you to create custom 
queries and gain valuable insights into your website performance. Whether you're looking to track 
specific metrics or analyze user behavior, the GA4 query explorer provides the flexibility and functionality
you need to make data-driven decisions for your business.

### Prerequisites
Before integrating Genlytics, a powerful Google Analytics package for Laravel, into your application, it's essential to have a property created on Google Analytics v4 and data stream set up. This will ensure that all of your website's data is being properly collected and tracked by Google Analytics. Once the property and data stream are in place, you can easily integrate Genlytics with your Laravel application, providing you with valuable insights into your website traffic, user behavior, and conversion rates. With the ability to access all of the features and functionality of Google Analytics, Genlytics makes it simple for businesses and developers to understand their audience and improve their online performance.

### Installation
You can install the package via composer
```bash
composer require zfhassaan/genlytics
```

### Enviornment Variables
Easily integrate Google Analytics tracking with your Laravel application using Genlytics and its environment variables. To set up Genlytics, simply add your Google Analytics property ID and service account credentials as environment variables in your .env file. The GENLYTICS_PROPERTY_ID variable is used to specify your specific GA property and the GENLYTICS_CREDENTIALS variable is used to specify the location of your service-account.json file. With these environment variables in place, you can start tracking website traffic, user behavior, and conversion rates using the power of Google Analytics and Genlytics package for Laravel, making it easy for businesses and developers to understand their audience and improve their online performance.

```bash
# Start: Google Analytics

GENLYTICS_PROPERTY_ID=<property_id>
GENLYTICS_CREDENTIALS=<service-account.json>

# End: Google Analytics
```

### Configurations
In your config/app.php file, add the following line to the providers array:

```php
    /*
    * Package Service Providers...
    */
    ...
    \zfhassaan\genlytics\provider\AnalyticsServiceProvider::class,
    ...
```

In the aliases array of the same file, add the following line:

```php
    'aliases' => Facade::defaultAliases()->merge([
    ...
        'Genlytics' => \zfhassaan\genlytics\facades\AnalyticsFacade::class,
    ...
    ])->toArray(),
```

Publish the package assets by running the following command:

```bash
  php artisan vendor:publish 
```

and publish the `zfhassaan\genlytics\provider\AnalyticsServiceProvider` resources to the laravel app. 

### Usage
Genlytics is a powerful Google Analytics package for Laravel that allows you to easily track and analyze website traffic and user behavior. To start using Genlytics, you can create an instance of the package by initializing it like this:

```php 
$analytics = new Genlytics();
```
You can then use the package's methods to run various reports. For example, you can run a report for active users within a specific date range by calling the runReports method like this:

```php
$period = ['start_date' => $request->period['start_date'],'end_date'=> $request->period['end_date']];
$active_users = $analytics->runReports($period,['name' => $state],['name' => 'activeUsers'] );
```

In addition to running reports, Genlytics also provides a method to fetch real-time analytics data using runRealTime function. For example:

```php
$analytics->runRealTime(['name' => 'pagePath'],['name' => 'pageviews']);
```
Genlytics also provides a method RunDimensionReport to fetch the dimension, for example:

```php 
$analytics->RunDimensionReport(['start_date' => '2022-01-01','end_date' => '2022-01-31'],'browser');
```
These methods allows to easily access the data we need to make data-driven decisions for business.
