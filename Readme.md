
# Conveyor Laravel Broadcaster

This is a Laravel Integration for [**Socket Conveyor**](http://socketconveyor.com). It allows you to use the Conveyor WebSocket server as a broadcasting driver for Laravel. **This package needs** [**Jacked Server**](https://github.com/jacked-php/jacked-server).

This package is an alternative for those who want to use Conveyor as a broadcasting driver. For that, you need to install Jacked Server or check there how to run your WebSocket server with Conveyor

## Installation

> Start by installing [Jacked Server](https://github.com/jacked-php/jacked-server). 

**Step 1**: Install the package via composer:

```bash
composer require kanata-php/conveyor-laravel-broadcaster
```

**Step 2**: Publish the configuration:

```bash
php artisan vendor:publish --provider="Kanata\LaravelBroadcaster\ConveyorServiceProvider"
```

**Step 3**: Add Service Provider to the `config/app.php` file:

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

**Step 4**: If on Laravel 11, enable Laravel broadcasting:

```shell
php artisan install:broadcasting
```

**Step 5**: Add the following to your `config/broadcasting.php` file:

```php
<?php

return [
    // ...
    'conveyor' => [
        'driver' => 'conveyor',
    ],
];
```

**Step 6**: Protect your channel with a "channel route" (a specific laravel detail). You do this by adding the following to your `routes/channels.php`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('actions-channel', function (User $user) {
    return true; // we are authorizing any user here - update according to your needs!
});
```

**Step 7**: This package require an user to authenticate with. To quickly create a user, you can use tinker for that:

```bash
php artisan tinker
```

Within tinker, you can create a user:

```php
App\Models\User::factory()->create(['email' => 'user@jacked-server.com', 'password' => Hash::make('password')]);
```

**Step 8**: Specify the configurations for the WebSocket server in the `.env` file:

> Important: SQLite won't work well due to its lock mechanism and how concurrency happens with this service. It is recommended to use MySQL, Postgres, or a more robust database.

```dotenv
# ...
BROADCAST_CONNECTION=conveyor
# ...
# MySQL of Postgres are better alternatives that SQLite
CONVEYOR_DATABASE=pgsql
JACKED_SERVER_WEBSOCKET_ENABLED=true
# ...
```

---

> At this point you can broadcast from your Laravel instance to the Conveyor WebSocket server. To understand how to broadcast with Laravel, visit [Broadcasting](https://laravel.com/docs/11.x/broadcasting).

---

**Step 9**: Install the [Conveyor JS Client](https://www.npmjs.com/package/socket-conveyor-client):

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

    const connect = (token) => {
        let conveyor = new window.Conveyor({
            protocol: '{{ $protocol }}',
            uri: '{{ $uri }}',
            port: {{ $wsPort }},
            channel: '{{ $channel }}',
            query: '?token=' + token,
            onMessage: (e) => output.innerHTML = e,
            onReady: () => {
                btnBase.addEventListener('click', () => conveyor.send(msg.value))
                btnBroadcast.addEventListener('click', () => conveyor.send(msg.value, 'broadcast-action'))
            },
        });
    };

    const  getAuth = (callback) => {
        fetch('/broadcasting/auth?channel_name={{ $channel }}', {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => callback(data.auth))
            .catch(error => console.error(error));
    }

    document.addEventListener("DOMContentLoaded", () => getAuth(connect));
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

    $protocol = config('jacked-server.ssl-enabled') ? 'wss' : 'ws';
    $port = config('jacked-server.ssl-enabled') ? config('jacked-server.ssl-port') : config('jacked-server.port');

    return view('ws-client', [
        'protocol' => $protocol,
        'uri' => '127.0.0.1',
        'wsPort' => $port,
        'channel' => 'private-my-channel',
    ]);
});
```
