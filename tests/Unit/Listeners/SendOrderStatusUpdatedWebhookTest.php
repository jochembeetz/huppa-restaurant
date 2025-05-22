<?php

namespace Tests\Unit\Listeners;

use App\Events\OrderStatusUpdated;
use App\Listeners\SendOrderStatusUpdatedWebhook;
use App\Models\Order;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Tests\TestCase;

class SendOrderStatusUpdatedWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_webhook_with_correct_data()
    {
        Http::fake([
            'https://example.com' => Http::response(null, 201),
        ]);

        $updatedAt = now();
        $order = Order::factory()->create([
            'status' => 'processing',
            'updated_at' => $updatedAt,
            'id' => 1,
        ]);

        $listener = new SendOrderStatusUpdatedWebhook(
            'https://example.com',
            'test-secret'
        );

        $listener->handle(new OrderStatusUpdated($order));

        Http::assertSent(function (Request $request) use ($updatedAt) {
            return $request->url() === 'https://example.com'
                && Arr::first($request->header('X-Webhook-Secret')) === 'test-secret'
                && $request->data() === [
                    'order_id' => 1,
                    'status' => 'processing',
                    'updated_at' => $updatedAt->toIso8601String(),
                ];
        });
    }

    public function test_it_throws_exception_when_webhook_fails()
    {
        Http::fake([
            'https://example.com' => Http::response(null, 400),
        ]);

        $order = Order::factory()->create();

        $listener = new SendOrderStatusUpdatedWebhook(
            'https://example.com',
            'test-secret'
        );

        $this->expectException(Exception::class);

        $listener->handle(new OrderStatusUpdated($order));
    }

    public function test_it_throws_exception_when_webhook_url_is_invalid()
    {
        $listener = new SendOrderStatusUpdatedWebhook(
            'invalid-url',
            'test-secret'
        );

        $order = Order::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $listener->handle(new OrderStatusUpdated($order));
    }

    public function test_it_throws_exception_when_webhook_secret_is_invalid()
    {
        $listener = new SendOrderStatusUpdatedWebhook(
            'https://example.com',
            ''
        );

        $order = Order::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $listener->handle(new OrderStatusUpdated($order));
    }
}
