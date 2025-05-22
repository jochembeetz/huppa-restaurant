<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

class GetSingleOrderController
{
    public function __invoke(Order $order): JsonResponse
    {
        return response()->json($order->only('id', 'status', 'updated_at'));
    }
}
