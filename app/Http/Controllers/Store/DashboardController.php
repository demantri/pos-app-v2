<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\StoreData;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Store $store): Response
    {
        return Inertia::render('stores/Dashboard', [
            'stats' => StoreData::dashboard($store),
        ]);
    }
}
