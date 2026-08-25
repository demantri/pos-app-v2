<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/Dashboard', [
            'stats' => DemoData::dashboard($store['id']),
        ]);
    }
}
