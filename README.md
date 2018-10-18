Srouter
=====

Srouter is a simple, open source PHP router. It's super small (~150 LOC), fast, and has some great annotated source code. This class allows you to just throw it into your project and start using it immediately.

### Install

If you have Composer, just include Srouter as a project dependency in your `composer.json`. If you don't just install it by downloading the .ZIP file and extracting it to your project directory.

```
require: {
    "xiaozhuangyuan/srouter": "dev-master"
}
```

### Examples

First, `use` the Srouter namespace:

```PHP
use \Xiaozhuangyuan\Srouter\Srouter;
```

Srouter is not an object, so you can just make direct operations to the class. Here's the Hello World:

```PHP
Srouter::get('/', function() {
  echo 'Hello world!';
});

Srouter::dispatch();
```

Srouter also supports lambda URIs, such as:

```PHP
Srouter::get('/(:any)', function($slug) {
  echo 'The slug is: ' . $slug;
});

Srouter::dispatch();
```

You can also make requests for HTTP methods in Srouter, so you could also do:

```PHP
Srouter::get('/', function() {
  echo 'I'm a GET request!';
});

Srouter::post('/', function() {
  echo 'I'm a POST request!';
});

Srouter::map(['get','post'],'/', function() {
  echo 'I can be both a GET and a POST request!';
});

Srouter::any('/', function() {
  echo 'I can be any request!';
});

Srouter::dispatch();
```

Lastly, if there is no route defined for a certain location, you can make Srouter run a custom callback, like:

```PHP
Srouter::error(function() {
  echo '404 :: Not Found';
});
```

If you don't specify an error callback, Srouter will just echo `404`.

<hr>

In order to let the server know the URI does not point to a real file, you may need to use one of the example [configuration files](https://github.com/xiaozhuangyuan/srouter/tree/master/config).


## Example passing to a controller instead of a closure
<hr>
It's possible to pass the namespace path to a controller instead of the closure:

For this demo lets say I have a folder called controllers with a Test.php

index.php:

```php
require('vendor/autoload.php');

use Xiaozhuangyuan\Srouter\Srouter;

Srouter::get('/', 'controllers\Test@index');
Srouter::get('page', 'controllers\Test@page');
Srouter::get('view/(:num)', 'controllers\Test@view');

Srouter::dispatch();
```

Test.php:

```php
<?php
namespace controllers;

class Test {

    public function index()
    {
        echo 'home';
    }

    public function page()
    {
        echo 'page';
    }

    public function view($id)
    {
        echo $id;
    }

}
```

This is with Srouter installed via composer.

composer.json:

```
{
   "require": {
        "xiaozhuangyuan/srouter": "dev-master"
    },
    "autoload": {
        "psr-4": {
            "" : ""
        }
    }
}
````

.htaccess(Apache):

```
Options +FollowSymlinks -Multiviews
RewriteEngine On

# Allow any files or directories that exist to be displayed directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^.*$ index.php [L]
```

Nginx:

```
location / {
    if (!-e $request_filename) {
        rewrite  ^.*$  /index.php last;
        break;
    }
}

```
