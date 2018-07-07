# OpenTracing for Laravel

[![Total Downloads](https://img.shields.io/packagist/dt/taisph/laravel-opentracing.svg?style=flat-square)](https://packagist.org/packages/taisph/laravel-opentracing)
[![Latest Stable Version](https://img.shields.io/packagist/v/taisph/laravel-opentracing.svg?style=flat-square)](https://packagist.org/packages/taisph/laravel-opentracing)
[![StyleCI](https://github.styleci.io/repos/139591541/shield?style=flat-square&branch=develop)](https://github.styleci.io/repos/139591541)

Reference implementation of the OpenTracing API for Laravel including a server-less local tracer for application
logging purposes.

See [OpenTracing](http://opentracing.io/) for more information.
 
## Installation

Install the latest version using:

```bash
composer require taisph/laravel-opentracing
```

## Basic Usage

### Example setup

Example `bootstrap/app.php` file:

```php
<?php

// Create the application.
$app = new \Illuminate\Foundation\Application(realpath(__DIR__ . '/../'));

// Bind important interfaces.
// ...

// Register important providers.
$app->register(\LaravelOpenTracing\TracingServiceProvider::class);

// Enable tracing span context in log messages.
$app->configureMonologUsing(function (\Monolog\Logger $logger) {
    $logger->pushProcessor(new \LaravelOpenTracing\Log\Processor\LocalTracerProcessor());
});

// Return the application.
return $app;
```

### Tracing jobs

Configure your dispatcher to pipe jobs through the tracing pipe. This is similar to middleware, only for jobs.

```php
<?php

app(\Illuminate\Bus\Dispatcher::class)->pipeThrough([
    \LaravelOpenTracing\TracingJobPipe::class,
]);
```

## About

### Requirements

- PHP 5.6 or newer.

### Contributions

Bugs and feature requests are tracked on [GitHub](https://github.com/taisph/laravel-opentracing/issues).

### License

OpenTracing for Laravel is licensed under the Apache-2.0 license. See the [LICENSE](LICENSE) file for details.
