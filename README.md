Koded Session
=============

The library relies on the `php.ini` settings.
Every session ini directive can be reset in the
configuration object.

Refer to session php.ini directives:


Usage
-----

The session is started automatically by using one of the 2 methods:

### app configuration
```php
[
    'session' => [
        // your ini "session." overwrites (without "session." prefix)
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

The session class can be instantiated and used, but function `session()`
is much preferred instead an instance of `Session` class.

Redis configuration
-------------------

```php
[
    'session' => [
        'save_handler' => 'redis'
    ]
]
```
