<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\GetSingleOrderController;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetSingleOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_order_data()
    {
        $order = Order::factory()->create([
            'id' => 1,
            'status' => 'processing',
            'updated_at' => now(),
        ]);

        $controller = new GetSingleOrderController();
        $response = $controller->__invoke($order);

        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'id' => 1,
            'status' => 'processing',
            'updated_at' => $order->updated_at->toJSON(),
        ], json_decode($response->getContent(), true));
    }
}
