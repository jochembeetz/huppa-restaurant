# Huppa Restaurant POS

## Requirements

- PHP 8.2 or higher
- Node.js 18 or higher
- Composer 2.x
- npm 9.x

## Installation

1. Clone the repository

2. Install PHP dependencies

```bash
composer install
```

3. Install Node.js dependencies

```bash
npm install
```

4. Set up environment

```bash
cp .env.example .env
php artisan key:generate
```

5. Set up database

```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed
```

## Development

Start the development server:

```bash
composer run dev
```

This will concurrently run:

- Laravel development server
- Queue worker
- Log watcher
- Vite development server

Then:

- Create an account
- Edit some orders' status and check terminal for logs
  - Expect an error, and see it retry
- Manually do a curl to get a single order (status) `curl -H "X-API-Key: secret-key" http://localhost:8000/api/orders/1`
  - Run following to see unauthorized error: `curl -H "X-API-Key: wrong-secret-key" http://localhost:8000/api/orders/1`

## Testing

Run the test suite:

```bash
composer run test
```

## Implementing on website

### Receiving Webhooks

Create a controller to handle incoming webhooks:

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-Webhook-Secret');
        if ($signature !== config('services.restaurant.webhook_secret')) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->validate([
            'order_id' => 'required|integer',
            'status' => 'required|string',
            'updated_at' => 'required|date'
        ]);

        // Process webhook data
        Log::info('Received order status update', $data);
        
        return response()->json(['message' => 'Webhook received'], 201);
    }
}
```

### Polling Fallback

If webhooks fail, implement a polling mechanism:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PollOrderStatus extends Command
{
    protected $signature = 'orders:poll {order_id}';
    
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $response = Http::withHeaders([
            'X-API-Key' => config('services.restaurant.api_key')
        ])->get("http://localhost:8000/api/orders/{$orderId}");
        
        if ($response->successful()) {
            $data = $response->json();
            // Process order status update
            $this->info("Order {$orderId} status: {$data['status']}");
        }
    }
}
```

Schedule the polling command to run periodically:

```php
// In App\Console\Kernel
protected function schedule(Schedule $schedule)
{
    $schedule->command('orders:poll {order_id}')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

## Broadcast with Websockets

### Server-side Broadcasting

Create an event for order status updates:

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("orders.{$this->order->id}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'status' => $this->order->status,
            'updated_at' => $this->order->updated_at->toIso8601String()
        ];
    }
}
```

Broadcast the event when receiving webhook or polling updates:

```php
// In WebhookController or PollOrderStatus
event(new OrderStatusUpdated($order));
```

### Client-side Listening

Using Laravel Echo and Pusher.js:

```javascript
// Install dependencies
// npm install laravel-echo pusher-js

// In your JavaScript setup
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Listen for specific order updates
Echo.private(`orders.${orderId}`)
    .listen('OrderStatusUpdated', (e) => {
        console.log('Specific order updated:', e);
    });
```

### Configuration

Add to your `.env`:
```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_app_cluster
```

## What would I do differently if I had more time and/or it would go in production

- Create DTO's for all API responses (potentially use spatie data library)
- Implement OAuth2 flow instead of api key auth
- Place both applications in one secure network and/or implement IP whitelisting
- Containerize the application and add a docker compose (including postgres, redis and website) using sail
- Add Laravel Horizon for easier queue management (includes logs for debugging)
- Add appropriate retry mechanism for webhook failures
- Webhooks based on order status trails/logs instead of the order's status
- More secure webhook verification with timestamps and nonces to prevent replay attacks
- Notifications/alerts to developers on unexpected errors
- Ofcourse, pay more respect to the frontend. (This was now neglected due to time, and I considered the assignment to be focused on the functionality on the backend).
