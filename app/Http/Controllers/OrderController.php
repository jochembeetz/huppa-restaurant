<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::latest()->paginate(10);

        return Inertia::render('dashboard', [
            'orders' => $orders,
        ]);
    }

    public function update(Order $order)
    {
        $validated = request()->validate([
            'status' => ['required', 'string', 'in:processing,completed,cancelled,pending'],
        ]);

        $order->update($validated);

        return back();
    }
}
