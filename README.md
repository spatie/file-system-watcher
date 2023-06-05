# Watch changes in the file system using PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/file-system-watcher.svg?style=flat-square)](https://packagist.org/packages/spatie/file-system-watcher)
[![Tests](https://github.com/spatie/file-system-watcher/actions/workflows/run-tests.yml/badge.svg)](https://github.com/spatie/file-system-watcher/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/spatie/file-system-watcher/Check%20&%20fix%20styling?label=code%20style)](https://github.com/spatie/file-system-watcher/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/file-system-watcher.svg?style=flat-square)](https://packagist.org/packages/spatie/file-system-watcher)

This package allows you to react to all kinds of changes in the file system. 

Here's how you can run code when a new file gets added.

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->onFileCreated(function (string $newFilePath) {
        // do something...
    })
    ->start();
```

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/file-system-watcher.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/file-system-watcher)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation  

You can install the package via composer:

```bash
composer require spatie/file-system-watcher
```

In your project, you should have the JavaScript package [`chokidar`](https://github.com/paulmillr/chokidar) installed. You can install it via npm

```bash
npm install chokidar
```

or Yarn

```bash
yarn add chokidar
```

## Usage

Here's how you can start watching a directory and get notified of any changes.

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->onAnyChange(function (string $type, string $path) {
        if ($type === Watch::EVENT_TYPE_FILE_CREATED) {
            echo "file {$path} was created";
        }
    })
    ->start();
```

You can pass as many directories as you like to `path`.

To start watching, call the `start` method. Note that the `start` method will never end. Any code after that will not be executed. 

To make sure that the watcher keeps watching in production, monitor the script or command that starts it with something like [Supervisord](http://supervisord.org).  See [Supervisord example configuration](#supervisord-example-configuration) below.

### Detected the type of change

The `$type` parameter of the closure you pass to `onAnyChange` can contain one of these values:

- `Watcher::EVENT_TYPE_FILE_CREATED`: a file was created
- `Watcher::EVENT_TYPE_FILE_UPDATED`: a file was updated
- `Watcher::EVENT_TYPE_FILE_DELETED`: a file was deleted
- `Watcher::EVENT_TYPE_DIRECTORY_CREATED`: a directory was created
- `Watcher::EVENT_TYPE_DIRECTORY_DELETED`: a directory was deleted

### Listening for specific events

To handle file systems events of a certain type, you can make use of dedicated functions. Here's how you would listen for file creations only.

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->onFileCreated(function (string $newFilePath) {
        // do something...
    });
```

These are the related available methods:

- `onFileCreated()`: accepts a closure that will get passed the new file path
- `onFileUpdated()`: accepts a closure that will get passed the updated file path
- `onFileDeleted()`: accepts a closure that will get passed the deleted file path
- `onDirectoryCreated()`: accepts a closure that will get passed the created directory path
- `onDirectoryDeleted()`: accepts a closure that will get passed the deleted directory path

### Watching multiple paths

You can pass multiple paths to the `paths` method.

```php
use Spatie\Watcher\Watch;

Watch::paths($directory, $anotherDirectory);
```

### Performing multiple tasks

You can call `onAnyChange`, 'onFileCreated', ... multiple times. All given closures will be performed

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->onFileCreated(function (string $newFilePath) {
        // do something on file creation...
    })
    ->onFileCreated(function (string $newFilePath) {
        // do something else on file creation...
    })
    ->onAnyChange(function (string $type, string $path) {
        // do something...
    })
    ->onAnyChange(function (string $type, string $path) {
        // do something else...
    })
    // ...
```

### Stopping the watcher gracefully

By default, the watcher will continue indefinitely when started. To gracefully stop the watcher, you can call `shouldContinue` and pass it a closure. If the closure returns a falsy value, the watcher will stop. The given closure will be executed every 0.5 second.

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->shouldContinue(function () {
        // return true or false
    })
    // ...
```

### Change the speed of watcher

By default, the changes are tracked every 0.5 seconds, however you could change that.

```php
use Spatie\Watcher\Watch;

Watch::path($directory)
    ->setIntervalTime(1000000) //unit is microsecond therefore -> 0.1s
    // ...rest of your methods
```

Notice : there is no file watching based on polling going on.

## Testing

```bash
composer test
```

## Supervisord example configuration

Create a new Supervisord configuration to monitor a Laravel artisan command which calls the watcher.  While using Supervisord, you must specicfy your Node.js and PHP executables in your command paramater: `env PATH="/usr/local/bin"` for Node.js, the absolute path to PHP and your project's path.


```
[program:watch]
process_name=%(program_name)s
directory=/your/project
command=env PATH="/usr/local/bin" /absolute/path/to/php /your/project/artisan watch-for-files
autostart=true
autorestart=false
user=username
redirect_stderr=true
stdout_logfile=/your/project/storage/logs/watch.log
stopwaitsecs=3600
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

Parts of this package are inspired by [Laravel Octane](https://github.com/laravel/octane)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
