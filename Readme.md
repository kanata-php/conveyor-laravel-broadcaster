
# Conveyor Laravel Broadcaster

This is a Laravel Integration for [**Socket Conveyor**](http://socketconveyor.com). It allows you to use the Conveyor WebSocket server as a broadcasting driver for Laravel. This package doesn't need [**Jacked Server**](https://github.com/jacked-php/jacked-server), but just know that that web server is great!

This package allows the usage of Conveyor as a broadcasting driver in Laravel.

> To understand how to broadcast with Laravel, visit [Broadcasting](https://laravel.com/docs/11.x/broadcasting).

## Quick Start

**Table of Contents**

- [Step 1: Install the package via composer](#step-1-install-the-package-via-composer)
- [Step 2: Publish the configuration](#step-2-publish-the-configuration)
- [Step 3: Add Service Provider](#step-3-add-service-provider)
- [Step 4: Enable Laravel broadcasting](#step-4-enable-laravel-broadcasting)
- [Step 5: Add broadcasting config](#step-5-add-broadcasting-config)
- [Step 6: Migrate the database](#step-6-migrate-the-database)
- [Step 7: Install the Conveyor JS Client](#step-7-install-the-conveyor-js-client)
- [Extra: Simple Conveyor Server for this example](#extra-simple-conveyor-server-for-this-example)

#### Step 1: Install the package via composer

```bash
composer require kanata-php/conveyor-laravel-broadcaster
```

#### Step 2: Publish the configuration

```bash
php artisan vendor:publish --provider="Kanata\LaravelBroadcaster\ConveyorServiceProvider"
```

#### Step 3: Add Service Provider

Laravel 10 backwards:

```php
<?php
return [
    // ...
    'providers' => [
        // ...
        Kanata\LaravelBroadcaster\ConveyorServiceProvider::class,
    ],
    // ...
];
```

Laravel 11 onwards:

```php
<?php

return [
    // ...
    Kanata\LaravelBroadcaster\ConveyorServiceProvider::class,
];
```

#### Step 4: Enable Laravel broadcasting

> This is for Laravel 11 and forward, if in any other version just skip this step!

```shell
php artisan install:broadcasting
```

#### Step 5: Add broadcasting config

Add the following to your `config/broadcasting.php` file:

```php
<?php

return [
    // ...
    'conveyor' => [
        'driver' => 'conveyor',
        'protocol' => env('CONVEYOR_PROTOCOL', 'ws'),
        'host' => env('CONVEYOR_URI', 'localhost'),
        'port' => env('CONVEYOR_PORT', 8181),
    ],
];
```

#### Step 6: Migrate the database

Set the configurations for the WebSocket server in the `.env` file:

```dotenv
# ...
BROADCAST_CONNECTION=conveyor
# ...
CONVEYOR_DATABASE=pgsql
JACKED_SERVER_WEBSOCKET_ENABLED=true
# ...
```

> `CONVEYOR_DATABASE` is optional and defaults to mysql.

Then run migrations:

```bash
php artisan migrate
```

#### Step 7: Install the [Conveyor JS Client](https://www.npmjs.com/package/socket-conveyor-client):

```bash
npm install socket-conveyor-client
```

> Important: Don't forget to run `npm run build`!

Add this to the bootstrap.js file of your Laravel app so the Conveyor client is available globally:

```js
import Conveyor from "socket-conveyor-client";

window.Conveyor = Conveyor;
```

Remember to run `npm install` and `npm run dev` or `npm run prod` to compile the assets.

> **Info:** If you want to send one-off messages to the Conveyor WebSocket server, you can just dispatch an event like follows:
> ```php
> <?php
> 
> namespace App\Events;
> 
> use Illuminate\Broadcasting\InteractsWithBroadcasting;
> use Illuminate\Broadcasting\PrivateChannel;
> use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
> 
> class TestEvent implements ShouldBroadcastNow
> {
>     use InteractsWithBroadcasting;
> 
>     public function __construct(
>         public string $message,
>         public string $channel,
>     ) {
>         $this->broadcastVia('conveyor');
>     }
> 
>     public function broadcastOn(): array
>     {
>         return [
>             new PrivateChannel($this->channel),
>         ];
>     }
> }
> ```
>
> ```php
> event(new App\Events\TestEvent( message: 'my message', channel: 'my-channel'));
> ```

> **Important**: notice that we are using `ShouldBroadcastNow` instead of `ShouldBroadcast`. Conveyor doesn't need queueing and is much faster this way. If you want, you can still use queues.


Example of usage in a view with authorization at this point:

```html
<html>
<head>
    <title>WS Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<textarea id="msg"></textarea>
<button id="btn-base">Base</button>
<button id="btn-broadcast">Broadcast</button>
<ul id="output"></ul>

<script type="text/javascript">
    // page elements
    const msg = document.getElementById('msg')
    const btnBase = document.getElementById('btn-base')
    const btnBroadcast = document.getElementById('btn-broadcast')
    const output = document.getElementById('output')

    const connect = () => {
        window.conveyor = new window.Conveyor({
            protocol: '{{ $protocol }}',
            uri: '{{ $uri }}',
            port: {{ $wsPort }},
            channel: '{{ $channel }}',
            token: '{{ \Kanata\LaravelBroadcaster\Conveyor::getToken($channel) }}',
            onMessage: (e) => output.innerHTML = e,
            onReady: () => {
                btnBase.addEventListener('click', () => window.conveyor.send(msg.value))
                btnBroadcast.addEventListener('click', () => window.conveyor.send(msg.value, 'broadcast-action'))
            },
        });
    };

    document.addEventListener("DOMContentLoaded", () => connect());
</script>
</body>
</html>
```

Then, add the route for this view at your `routes/web.php` file:

```php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/ws-client', function () {
    Auth::loginUsingId(1); // here we authorize for the sake of the example.

    return view('ws-client', [
        'protocol' => config('broadcasting.connections.conveyor.protocol'),
        'uri' => config('broadcasting.connections.conveyor.host'),
        'wsPort' => config('broadcasting.connections.conveyor.port'),
        'channel' => 'private-my-channel',
    ]);
});
```

#### Extra: Simple Conveyor Server for this example

You can use this simple server to test your broadcasting (and in production...):

```php
<?php
// file: server.php

include __DIR__ . '/vendor/autoload.php';

use Conveyor\ConveyorServer;
use Conveyor\Events\MessageReceivedEvent;
use Conveyor\Events\PreServerStartEvent;

(new ConveyorServer())
    // if you want to see messages in the console 
    ->eventListeners([
        Conveyor\Constants::EVENT_MESSAGE_RECEIVED => function (MessageReceivedEvent $event) {
            var_dump($event->data);
        },
    ])
    ->port(8181)
    ->start();
```

Remember to install conveyor with `composer require kanata-php/conveyor` and run the server with `php server.php`.
