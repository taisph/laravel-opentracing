# OpenTracing for Laravel

[![Total Downloads](https://img.shields.io/packagist/dt/taisph/laravel-opentracing.svg?style=flat-square)](https://packagist.org/packages/taisph/laravel-opentracing)
[![Latest Stable Version](https://img.shields.io/packagist/v/taisph/laravel-opentracing.svg?style=flat-square)](https://packagist.org/packages/taisph/laravel-opentracing)
[![StyleCI](https://github.styleci.io/repos/139591541/shield?style=flat-square&branch=develop)](https://github.styleci.io/repos/139591541)
[![Build Status](https://img.shields.io/travis/taisph/laravel-opentracing/master.svg?style=flat-square)](https://travis-ci.org/taisph/laravel-opentracing)
[![Coverage Status](https://img.shields.io/coveralls/github/taisph/laravel-opentracing/master.svg?style=flat-square)](https://coveralls.io/github/taisph/laravel-opentracing?branch=develop)

Reference implementation of the OpenTracing API for Laravel including a server-less local tracer for application
logging purposes.

See [OpenTracing](http://opentracing.io/) for more information.

## Supported Clients

Currently supported clients:

- Local: No-op tracer used mainly for adding trace ids to logs.
- Jaeger: open source, end-to-end distributed tracing. See [Jaeger](https://www.jaegertracing.io/) and
    Jonah George's [Jaeger Client PHP](https://github.com/jonahgeorge/jaeger-client-php).

Note that a patched version of Jaeger Client PHP is currently required to retain PHP 5.6 support. If you need that in
your application, add the config below to your `composer.json` file in the `repositories` section.

```json
{
    "type": "vcs",
    "url": "https://github.com/taisph/jaeger-client-php"
}
```

## Installation

Install the latest version using:

```bash
composer require taisph/laravel-opentracing
```

Copy the default configuration file to your application if you want to change it by running the command below. Note
that you have to use `--force` if the file already exists.

```bash
php artisan vendor:publish --provider="LaravelOpenTracing\TracingServiceProvider"
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

### Tracing

To trace a specific process in your application you can wrap the process in a trace closure like below. This will take
care of starting a new trace span and closing it again when the closure either returns or throws.

```php
$items = app(\LaravelOpenTracing\TracingService::class)->trace(
    'todo.get_list_items',
    function () {
        return \App\Models\TodoListItem::get();
    }
);
```

Nested traces are also possible like below. It will automatically take care of the span child/parent relations.

```php
function a() {
    // We don't care about tracing this specifically.
    doSomething();

    app(\LaravelOpenTracing\TracingService::class)->trace(
        'app.do_something_else',
        function () {
            doSomethingElse();
        }
    );
}

app(\LaravelOpenTracing\TracingService::class)->trace(
    'app.do_stuff',
    function () {
        a();
    }
);
```

If you want to add context information or tags to your spans, it is possible like below.

```php
$title = 'Make more coffee';

$item = app(\LaravelOpenTracing\TracingService::class)->trace(
    'todo.store_item',
    function () use ($title) {
        return \App\Models\TodoListItem::create(['title' => $title]);
    },
    ['tags' => ['title' => $title]]
);
```

### Tracing Jobs

Configure your dispatcher to pipe jobs through the tracing pipe. This is similar to middleware, only for jobs.

```php
<?php

app(\Illuminate\Bus\Dispatcher::class)->pipeThrough([
    \LaravelOpenTracing\TracingJobPipe::class,
]);
```

## Testing

docker run --rm -it -v $(pwd):/app php:5.6-cli-alpine /bin/sh -c 'apk add --no-cache $PHPIZE_DEPS && pecl install xdebug-2.5.5 && cd app && php -dzend_extension=xdebug.so vendor/bin/phpunit'

## About

### Requirements

- PHP 5.6 or newer.

### Contributions

Bugs and feature requests are tracked on [GitHub](https://github.com/taisph/laravel-opentracing/issues).

### License

OpenTracing for Laravel is licensed under the Apache-2.0 license. See the [LICENSE](LICENSE) file for details.
