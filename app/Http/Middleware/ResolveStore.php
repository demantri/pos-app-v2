<?php

namespace App\Http\Middleware;

use App\Support\DemoData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = DemoData::store((int) $request->route('store'));

        abort_if($store === null, 404);

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
