<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use Assert\Assert;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderStatusUpdatedWebhook implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    public $tries = 12;

    public $backoff = 10;

    public $timeout = 10;

    /**
     * Create the event listener.
     */
    public function __construct(#[Config('services.website.webhook_url')] public string $webhookUrl, #[Config('services.website.webhook_secret')] public string $secret)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        Assert::that($this->webhookUrl)->notEmpty()->url();
        Assert::that($this->secret)->notEmpty();

        $headers = [
            'X-Webhook-Secret' => $this->secret,
        ];

        $response = Http::withHeaders($headers)->post($this->webhookUrl, [
            'order_id' => $order->id,
            'status' => $order->status,
            'updated_at' => $order->updated_at->toIso8601String(),
        ]);

        if ($response->status() !== 201) {
            throw new \Exception('Failed to send order status updated webhook');
        }
    }

    public function failed(OrderStatusUpdated $event, Throwable $exception): void
    {
        Log::error('Failed to send order status updated webhook', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
