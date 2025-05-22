<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use Assert\Assert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderStatusUpdatedWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 12;

    public $backoff = 10;

    public $timeout = 10;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        $webhookUrl = config('services.website.webhook_url');
        $secret = config('services.website.webhook_secret');

        Assert::that($webhookUrl)->notEmpty()->url();
        Assert::that($secret)->notEmpty();
        Http::post($webhookUrl, [
            'order_id' => $order->id,
            'status' => $order->status,
        ]);
    }

    public function failed(OrderStatusUpdated $event, Throwable $exception): void
    {
        Log::error('Failed to send order status updated webhook', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
