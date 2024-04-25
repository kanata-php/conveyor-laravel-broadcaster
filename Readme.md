
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
    return true; // we are authorizing any user here
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

**Step 8**: Generate a "system level" token:

This package comes with a laravel command to generate a token for a user. This token won't expire, and it will only have the permissions of the user you are using to generate it. You can use it like this:

```bash
php artisan conveyor:token {your user id}
```

**Step 9**: Specify the configurations for the WebSocket server in the `.env` file:

```dotenv
# ...
BROADCAST_DRIVER=conveyor
# ...
CONVEYOR_HOST=127.0.0.1
CONVEYOR_PORT=8080
CONVEYOR_PROTOCOL=ws
CONVEYOR_QUERY="token=123456"
```

> **Alternative:** A programmatic way is to use Conveyor's JwtToken service:
>
> ```php
> use Kanata\LaravelBroadcaster\Services\JwtToken;
>
> /** @var \Kanata\LaravelBroadcaster\Models\Token $token */
> $token = JwtToken::create(
>     name: 'some-token',
>     userId: auth()->user()->id,
>     expire: null, // an expiration date can be set here
>     useLimit: 1, // how many times this token can be used
> );
> ``` 

---

> At this point you can broadcast from your Laravel instance to the Conveyor WebSocket server to public channels. To undertand how to broadcast, see [Broadcasting](https://laravel.com/docs/11.x/broadcasting).

---

**Step 6**: Install the [Conveyor JS Client](https://www.npmjs.com/package/socket-conveyor-client):

```bash
npm install socket-conveyor-client
```

Add this to the bootstrap.js file of your Laravel app so the Conveyor client is available globally:

```js
import Conveyor from "socket-conveyor-client";

window.Conveyor = Conveyor;
```

Remember to run `npm install` and `npm run dev` or `npm run prod` to compile the assets.

**Step 7**: Install the Server Side [Conveyor Client](https://github.com/kanata-php/conveyor-server-client):

```bash
composer require kanata-php/conveyor-server-client
```

Example of usage:

```php
use Kanata\ConveyorServerClient\Client;
use WebSocket\Client as WsClient;

$options = [
    'protocol' => 'ws',
    'uri' => '127.0.0.1',
    'port' => 8000,
    'onMessageCallback' => function (WsClient $currentClient, string $message) {
        echo 'Message received: ' . $message . PHP_EOL;
        $currentClient->send('Hello World!');
    },
    'onReadyCallback' => fn() => {}, // your callback here
];

$client = new Client($options);
$client->connect();
```

> **Info:** If you want to send one-off messages to the Conveyor WebSocket server, you can do like this:
> ```php
> // this laravel helper (rescue) is a pretty alternative to try/catch
> rescue(
>     callback: function () use ($channel, $payload) {
>         $options = [
>             'protocol' => config('conveyor.protocol', 'ws'),
>             'host' => config('conveyor.uri', '127.0.0.1'),
>             'port' => config('conveyor.port', 8002),
>             'query' => '?'.config('conveyor.query', ''),
>             'channel' => $channel->name,
>             'timeout' => 1,
>             'onReadyCallback' => function(Client $currentClient) use ($payload) {
>                 $currentClient->send($payload['message']);
>             },
>         ];
>         $client = new Client($options);
>         $client->connect();
>     },
>     rescue: function (Exception $e) {
>         if ($e instanceof TimeoutException) {
>             return;
>         }
>         Log::info('Conveyor failed to broadcast: ' . $e->getMessage());
>     },
>     report: false,
> );
> ```
> Yeah, maybe I'll come up with a helper soon...

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
        'channel' => 'private-actions-channel',
    ]);
});
```

### Example

Here you can see how to Broadcast an event to a channel:

Here you have an example of the Event class:

```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('actions-channel');
    }
}
```

Then, you can dispatch the event (as you would do with any other Laravel Broadcasting driver) using the `event` helper function or the `Event` facade:

```php
event(new TestEvent('My test message'));
// or
Event::dispatch(new TestEvent('My test message'));
```

### Authorizing Channels

To authorize users to access channels, you can use the `Broadcast::channel` method. See [Authorizing Channels](https://laravel.com/docs/11.x/broadcasting#authorizing-channels) for more information:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('actions-channel', function (User $user) {
    return true; // we are authorizing any user here
});
``` 
