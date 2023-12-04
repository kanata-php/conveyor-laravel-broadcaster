
# Laravel Broadcaster

This is a Laravel Integration for [**Socker Conveyor**](http://socketconveyor.com). It allows you to use the Conveyor WebSocket server as a broadcasting driver for Laravel.

If you want to run a WebSocket server using Laravel, you can use [**Jacked Server**](https://github.com/jacked-php/jacked-server), so your Laravel can serve itself, and, at the same port, it will serve the WebSocket server with Socket Conveyor out-of-the-box. You'll be able to customize it in the Laravel installation where you run it from.

## Installation

**Step 1**: Install the package via composer:

```bash
composer require kanata/laravel-broadcaster
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

**Step 4**: Add the following to your `config/broadcasting.php` file:

```php
<?php

return [
    // ...
    'conveyor' => [
        'driver' => 'conveyor',
    ],
];
```

**Step 5**: Specify the configurations for the WebSocket server in the `.env` file:

```dotenv
# ...
BROADCAST_DRIVER=conveyor
# ...
CONVEYOR_HOST=127.0.0.1
CONVEYOR_PORT=8080
CONVEYOR_PROTOCOL=ws
CONVEYOR_QUERY="token=123456"
```

**(Extra) Step 6**: Install the [Conveyor JS Client](https://www.npmjs.com/package/socket-conveyor-client):

```bash
npm install socket-conveyor-client
```

> **Info:** If you want to integrate with the frontend, you can use the WebSocket from JavaScript, but if you want something more advanced, with out-of-the-box integration with the Conveyor's Sub-Protocol, you can use Conveyor JS Client. See [Socket Conveyor Client](https://www.npmjs.com/package/socket-conveyor-client) for more information.

Example of usage:

```javascript
import Conveyor from './node_modules/socket-conveyor-client/index.js';

var connection = new Conveyor({
    protocol: 'ws',
    uri: '127.0.0.1',
    port: 8000,
    channel: 'my-channel',
    onMessage: (e) => {
        // your callback here
    },
    onReady: () => {
        // your callback here
    },
});

connection.send('Hello World!');
```

**(Extra) Step 7**: Install the Server Side [Conveyor Client](https://github.com/kanata-php/conveyor-server-client):

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

## Usage

You can use the Conveyor driver as you would use any other driver in Laravel. See [Broadcasting](https://laravel.com/docs/8.x/broadcasting) for more information.

### Example

Here you can see how to Broadcast an event to a channel:

Here you have an example of the Event class:

```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class OrderShipped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('orders.'.$this->order->id);
    }
}
```

Then, you can dispatch the event (as you would do with any other Laravel Broadcasting driver) using the `event` helper function or the `Event` facade:

```php
event(new OrderShipped($order));
// or
Event::dispatch(new OrderShipped($order));
```

### Authorizing Channels

To authorize users to access channels, you can use the `Broadcast::channel` method. See [Authorizing Channels](https://laravel.com/docs/8.x/broadcasting#authorizing-channels) for more information:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Order;

Broadcast::channel('channel-x', function (User $user): bool {
    return $user->can('interact-with-channel-x', User::class);
});

Broadcast::channel('orders.{orderId}', function (User $user, int $orderId) {
    return $user->id === Order::findOrNew($orderId)->user_id;
});
``` 
