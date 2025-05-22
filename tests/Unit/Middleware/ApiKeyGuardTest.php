<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ApiKeyGuard;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiKeyGuardTest extends TestCase
{
    public function test_it_allows_request_with_valid_api_key()
    {
        $request = Request::create('/api/orders/1', 'GET');
        $request->headers->set('X-API-Key', 'test-key');

        $middleware = new ApiKeyGuard('test-key');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
    }

    public function test_it_blocks_request_with_invalid_api_key()
    {
        $request = Request::create('/api/orders/1', 'GET');
        $request->headers->set('X-API-Key', 'wrong-key');

        $middleware = new ApiKeyGuard('test-key');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->status());
        $this->assertEquals(['error' => 'Unauthorized'], json_decode($response->getContent(), true));
    }

    public function test_it_blocks_request_without_api_key()
    {
        $request = Request::create('/api/orders/1', 'GET');

        $middleware = new ApiKeyGuard('test-key');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->status());
        $this->assertEquals(['error' => 'Unauthorized'], json_decode($response->getContent(), true));
    }
}
