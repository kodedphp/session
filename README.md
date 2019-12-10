Koded Session
=============

[![Latest Stable Version](https://img.shields.io/packagist/v/koded/session.svg)](https://packagist.org/packages/koded/session)
[![Build Status](https://travis-ci.org/kodedphp/session.svg?branch=master)](https://travis-ci.org/kodedphp/session)
[![Code Coverage](https://scrutinizer-ci.com/g/kodedphp/session/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kodedphp/session/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kodedphp/session/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kodedphp/session/?[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1.4-8892BF.svg)](https://php.net/)
[![Software license](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](LICENSE)


The library relies on the `php.ini` settings.
Every session ini directive can be reset with the
`Koded\Session\SessionConfiguration` object.

Refer to php.ini session directives:
http://php.net/manual/en/session.security.ini.php


Usage
-----

The session is started automatically by using one of the 2 methods:

### app configuration
```php
[
    'session' => [
        // your ini "session." overwrites, without "session." prefix
    ]
]
```

### or using a `SessionMiddleware`
include this middleware class in your middleware stack

```php
// your middleware stack
$middleware = [
    SessionMiddleware::class
];
```

Session class and function
--------------------------

```php
session()->get('key');
session()->set('key', 'value');
// etc.
```

The session class can be instantiated and used, but the function `session()`
is recommended instead an instance of `Session` class.


Handlers Configuration
======================

The bare minimum is to define the handler you want to use for the session:

```php
// in your configuration file

return [
    'session' => [
        'save_handler' => 'redis | memcache'
    ]
]
```

If you do not choose one of the Redis or Memcached, it defaults to `files`
handler which is the PHP's default session mechanism.

However, the `files` handler might not be desirable if your application
runs in Docker, Kubernetes, distributed environment, etc.

> The best choice for PHP sessions is Redis in almost all situations.

WARNING: Memcached may drop the session data, because it's nature. Use it with caution!

Redis handler
-------------

```php
[
    'session' => [
        'save_handler' => 'redis'
        
        // OPTIONAL, these are the defaults
        'host' => 'localhost',
        'port' => 6379,
        'timeout' => 0.0,
        'retry' => 0,
        'db' => 0,
        
        'prefix' => 'sess:',
        'serializer' => 'php', // or "json"
        'binary' => false,     // TRUE for igbinary
    ]
]
```

A typical Redis settings:
  - 1 server
  - application + redis on the same machine
  - Redis (127.0.0.1:6379)
  - no auth (Redis is not available from outside)

```php
[
    'session' => [
        'save_handler' => 'redis',
        'name'         => 'session-name',
        'prefix'       => 'sess:',

        // isolate the session data in other db
        'db'           => 1
    ]
]
```

To support huge volumes you need a good sysadmin skills and wast knowledge
to set the Redis server(s).


Memcached handler
-----------------

```php
[
    'session' => [
        'save_handler' => 'memcached',
        
        // OPTIONAL: defaults to ['127.0.0.1', 11211]
        // If you have multiple memcached servers
        'servers' => [
            ['127.0.0.1', 11211],
            ['127.0.0.1', 11212],
            ['127.0.0.2']
            ...
        ],
        
        // OPTIONAL: the options are not mandatory
        'options' => [
            ...
        ]
    ]
]
```

A typical Memcached settings:
  - 1 server
  - application + memcached on the same machine
  - Memcached (127.0.0.1:11211)

```php
[
    'session' => [
        'save_handler' => 'memcached',
        'name'         => 'session-name',
        'prefix'       => 'sess.'
    ]
]
```

To support huge amount of users you need a decent amounts of RAM
on your servers. But Memcached is a master technology for this, so you should be fine.


Files handler
-------------

This one is not recommended for any serious business.
It's fine only for small projects.

All session directives are copied from `php.ini`.

```php
[
    'session' => [
        // OPTIONAL: defaults to "session_save_path()"
        // the path where to store the session data
        'save_path' => '/var/www/sessions',
        'serialize_handler' => 'php'
    ]
]
```

A typical native PHP session settings:
```php
[
    'session' => [
        // really nothing,
        // skip this section in your configuration
    ]
]

```

You cannot use this handler if you've scaled your application,
because the session data will most likely be handled randomly 
on a different instance for every HTTP request.

